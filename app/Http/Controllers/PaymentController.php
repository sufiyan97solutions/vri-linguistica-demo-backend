<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\GeneratedInvoice;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SubClientType;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use DateTime;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    private $userId;


    public function __construct()
    {
        $userId = auth('api')->user()->id;
        $this->userId = $userId;
    }



    public function index(Request $request)
    {
        try {

            $query = Payment::with(['paymentUser.interpreters', 'appointment'])
                ->whereHas('paymentUser', function ($q) {
                    $q->where('role', 'staff_interpreter');
                });
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



    public function changeStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:payments,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $records = Payment::whereIn('id', $ids);

            if ($records->exists()) {
                $records->update([
                    'status' => 'paid'
                ]);
                return $this->successResponse([], 'Status changed to Paid successfully.', 200);
            } else {
                return $this->errorResponse('No matching invoices found.', 404);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while updating records: ' . $e->getMessage(), 500);
        }
    }

    public function indexVendor(Request $request)
    {
        try {

            $query = Payment::with(['paymentUser.interpreters', 'appointment'])
                ->whereHas('paymentUser', function ($q) {
                    $q->where('role', 'vendor');
                });
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
