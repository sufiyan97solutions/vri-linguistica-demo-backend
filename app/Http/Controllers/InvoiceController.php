<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\GeneratedInvoice;
use App\Models\Invoice;
use App\Models\Language;
use App\Models\Staff;
use App\Models\StaffLanguage;
use App\Models\SubClient;
use App\Models\SubClientDynamicFields;
use App\Models\SubClientFilter;
use App\Models\SubClientType;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\PersonalAccessTokenResult;
use Laravel\Passport\Token;

class InvoiceController extends Controller
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

            $query = Appointment::with(['accounts.user', 'subclient', 'language', 'facility', 'interpreter', 'invoice', 'appointmentAssign'])->whereNotNull('interpreter_id')->whereIn('status', ['assigned', 'completed']);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $clientID = $request->get('client_ids');
            $interpreterID = $request->get('interpreter_id');
            $verified = $request->get('verified', 0);


            if ($startDate) {
                $query->whereDate('datetime', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('datetime', '<=', $endDate);
            }

            if (!empty($clientID)) {
                $query->where('account_id', $clientID);
            }

            if (!empty($interpreterID)) {
                $query->where('interpreter_id', $interpreterID);
            }

            if ($verified == 0) {
                $query->whereDoesntHave('invoice', function ($query) {
                    $query->where('verified', 1);
                });
            } else {
                $query->whereHas('invoice', function ($q) use ($verified) {
                    $q->where('verified', 1);
                });
            }


            $pageLength = $request->get('pageLength', 10);


            $data = $query->paginate($pageLength);

            return $this->successResponse($data, 'Invoices fetched successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }

    public function AppointClients(Request $request)
    {
        try {

            $query = SubClientType::with([
                'user',
                'appointments' => function ($q) {
                    $q->whereIn('status', ['completed', 'cancelled']);
                },
                'appointments.language',
                'appointments.appointmentAssign',
                'appointments.appointmentDetails.facility',
                'appointments.appointmentDetails.department',
                'appointments.interpreter',
                'appointments.vendor',
                'appointments.invoice'
            ])
                ->whereHas('appointments', function ($q) {
                    $q->whereIn('status', ['completed', 'cancelled']);
                });
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $clientID = $request->get('client_ids');
            $InvoicingPeriod = $request->get('invoicing_period');

            if ($startDate) {
                $query->whereHas('appointments', function ($q) use ($startDate) {
                    $q->whereDate('datetime', '>=', $startDate);
                });
            }

            if ($endDate) {
                $query->whereHas('appointments', function ($q) use ($endDate) {
                    $q->whereDate('datetime', '<=', $endDate);
                });
            }

            if (!empty($clientID)) {
                $query->whereIn('id', (array)$clientID);
            }

            $pageLength = $request->get('pageLength', 10);
            $data = $query->paginate($pageLength);
            return $this->successResponse($data, 'Billed users fetched successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }

    public function AppointClientsListPage(Request $request)
    {
        try {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $clientID = $request->get('client_ids');
            $InvoicingPeriod = $request->get('invoicing_period');

            // If all filters are null, return an empty response
            if (is_null($startDate) && is_null($endDate) && empty($clientID) && is_null($InvoicingPeriod)) {
                return $this->successResponse([], 'No data available.', 200);
            }

            $query = SubClientType::with([
                'user',
                'appointments' => function ($q) {
                    $q->whereIn('status', ['completed', 'cancelled']);
                },
                'appointments.language',
                'appointments.appointmentAssign',
                'appointments.appointmentDetails.facility',
                'appointments.appointmentDetails.department',
                'appointments.interpreter',
                'appointments.vendor',
                'appointments.invoice'
            ])
                ->whereHas('appointments', function ($q) {
                    $q->whereIn('status', ['completed', 'cancelled']);
                });


            if ($startDate) {
                $query->whereHas('appointments', function ($q) use ($startDate) {
                    $q->whereDate('datetime', '>=', $startDate);
                });
            }

            if ($endDate) {
                $query->whereHas('appointments', function ($q) use ($endDate) {
                    $q->whereDate('datetime', '<=', $endDate);
                });
            }

            if (!empty($clientID)) {
                $query->whereIn('id', (array)$clientID);
            }

            $pageLength = $request->get('pageLength', 10);
            $data = $query->paginate($pageLength);
            return $this->successResponse($data, 'Billed users fetched successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }

    public function generatedInvoices(Request $request)
    {
        try {

            $query = GeneratedInvoice::with([
                'client.user',
                'client.appointments' => function ($q) {
                    $q->whereIn('status', ['completed', 'cancelled']);
                },
                'client.appointments.language',
                'client.appointments.appointmentAssign',
                'client.appointments.appointmentDetails.facility',
                'client.appointments.appointmentDetails.department',
                'client.appointments.interpreter',
                'client.appointments.vendor',
                'client.appointments.invoice'
            ])->whereHas('client.appointments', function ($q) {
                $q->whereIn('status', ['completed', 'cancelled']);
            });
            $pageLength = $request->get('pageLength', 10);
            $data = $query->paginate($pageLength);
            return $this->successResponse($data, 'Invoices fetched successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving generated invoices: ' . $e->getMessage(), 500);
        }
    }

    public function destroyInvoices($id)
    {
        $record = GeneratedInvoice::find($id);

        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $record->delete();
        return $this->successResponse(null, 'Record deleted successfully.');
    }

    public function deleteMultipleInvoices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:generated_invoices,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = GeneratedInvoice::whereIn('id', $ids)->delete();

            if ($deletedCount > 0) {
                return $this->successResponse([], 'Records deleted successfully.', 200);
            } else {
                return $this->errorResponse('No records found to delete.', 404);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        // $validator = Validator::make($request->all(), [
        //     'appid' => 'required|string|max:150',
        //     'datetime' => 'string|max:150',
        //     'email' => 'required|string|max:150|unique:users,email,'. $record->user_id,
        //     'type' => 'required|in:agent,interpreter,remote_operator',
        //     'password' => 'required_if:type,agent,remote_operator',
        //     'gender' => 'required|in:male,female,nonbinary',
        //     'phone' => 'max:12',
        //     'status' => 'boolean',
        //     'language_ids' => 'array',
        //     'language_ids.*' => 'exists:languages,id', 
        // ]);

        // if ($validator->fails()) {
        //     return $this->errorResponse('Validation failed', 422, $validator->errors());
        // }

        try {

            $invoice = Invoice::where('appointment_id', $record->id)->first();
            if ($invoice) {
                $invoice = $invoice->toArray();
            } else {
                $invoice = [];
            }
            if (!empty($request->start_time)) {
                $record->appointmentAssign()->update([
                    'checkin_time' => $request->start_time
                ]);
            }
            if (!empty($request->end_time)) {
                $record->appointmentAssign()->update([
                    'checkout_time' => $request->end_time
                ]);
            }

            if (!empty($request->start_time) && !empty($request->end_time)) {
                $start_time = new \DateTime($request->start_time);
                $end_time = new \DateTime($request->end_time);
                $invoice['total_hours'] = $start_time->diff($end_time)->h . ':' . $start_time->diff($end_time)->i;
                $invoice['total_hours'] = date('H:i', strtotime($invoice['total_hours']));
            }
            if (!empty($request->rate)) {
                $invoice['rate'] = $request->rate;
            }

            if (!empty($invoice['total_hours']) && !empty($invoice['rate']) & !empty($request->end_time) & !empty($request->start_time)) {
                $start_time = new \DateTime($request->start_time);
                $end_time = new \DateTime($request->end_time);

                $hours = $start_time->diff($end_time)->h;
                $mins = $start_time->diff($end_time)->i;

                $invoice['total_amount'] = ($hours + ($mins / 60)) * $invoice['rate'];
            }

            if (!empty($request->patient_first_name)) {
                $invoice['patient_first_name'] = $request->patient_first_name;
            }

            if (!empty($request->patient_last_name)) {
                $invoice['patient_last_name'] = $request->patient_last_name;
            }

            if (!empty($request->notes)) {
                $invoice['notes'] = $request->notes;
            }

            if (!empty($request->requester)) {
                $record->update([
                    'requester_name' => $request->requester,
                    'rate_unit' => $request->rate_unit
                ]);
            }

            // Invoice::updateOrCreate([
            //     'appointment_id' => $record->id
            // ], $invoice);
            Invoice::where('appointment_id', $record->id)->update($invoice);

            return $this->successResponse($invoice, 'Invoice updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }

    public function makeInvoices(Request $request)
    {

        $data = GeneratedInvoice::create([
            'client_id'          => $request->client_id,
            'invoice_number'     => $request->invoice_number,
            'invoice_type'       => $request->invoice_type,
            'total_appointments' => $request->total_appointments,
            'billing_date'       => now()->format('Y-m-d'),
            'total_due_bill'     => $request->total_due_bill,
            'status'     => 'Pending',
        ]);

        return $this->successResponse($data, 'Invoice Save Successfully.', 200);
    }

    public function changeStatus($id)
    {
        $record = GeneratedInvoice::find($id);

        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $record->update([
            'status' => 'Paid'
        ]);

        return $this->successResponse([], 'Invoice Paid Successfully.', 200);
    }
}
