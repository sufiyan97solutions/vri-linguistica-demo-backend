<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\GeneratedInvoice;
use App\Models\SubClientType;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    use ApiResponseTrait;

    private $userId;
    private $accountId;

    public function __construct()
    {
        $userId = auth('api')->user()->id;
        $this->userId = $userId;
        $this->accountId = SubClientType::where('user_id', $this->userId)->first()->id;
    }

    public function getProfile(Request $request)
    {
        try {
            $pageLength = $request->get('pageLength', 10);

            $client = SubClientType::where('user_id', $this->userId)->first();
            if (!$client) {
                return $this->errorResponse('Client not found.', 404);
            }
            $ClientId = $client->id;

            $query = GeneratedInvoice::with([
                'client.user',
                'client.appointments' => function ($q) {
                    $q->where('status', 'completed');
                },
                'client.appointments.language',
                'client.appointments.appointmentAssign',
                'client.appointments.appointmentDetails.facility',
                'client.appointments.appointmentDetails.department',
                'client.appointments.interpreter.user',
                'client.appointments.vendor',
                'client.appointments.invoice'
            ])
                ->whereHas('client.appointments', function ($q) {
                    $q->where('status', 'completed');
                })
                ->where('client_id', $ClientId);

            $data = $query->paginate($pageLength);

            return $this->successResponse($data, 'Invoices fetched successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving invoices: ' . $e->getMessage(), 500);
        }
    }
}
