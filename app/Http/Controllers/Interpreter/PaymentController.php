<?php

namespace App\Http\Controllers\Interpreter;

use App\Http\Controllers\Controller;
use App\Models\GeneratedInvoice;
use App\Models\Interpreter;
use App\Models\Payment;
use App\Models\SubClientType;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    private $userId;
    private $interpreterId;

    public function __construct()
    {
        $userId = auth('api')->user()->id;
        $this->userId = $userId;
        $this->interpreterId = Interpreter::where('user_id', $this->userId)->first()->id;
    }

    public function index(Request $request)
    {
        try {

            $query = Payment::with(['paymentUser.interpreters' , 'appointment.appointmentDetails', 'appointment.appointmentAssign'])->where('payment_user_id', $this->userId);
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');


            if ($startDate) {
                $query->whereDate('date', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('date', '<=', $endDate);
            }

            $pageLength = $request->get('pageLength', 10);
            $data = $query->paginate($pageLength);

            return $this->successResponse($data, 'Invoices fetched successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }
}
