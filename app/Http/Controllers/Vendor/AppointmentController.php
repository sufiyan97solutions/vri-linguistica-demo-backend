<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentAssign;
use App\Models\AppointmentDetail;
use App\Models\AppointmentInvites;
use App\Models\AppointmentLog;
use App\Models\Vendor;
use App\Models\Language;
use App\Models\SubClient;
use App\Models\SubClientTypeDepartment;
use App\Models\SubClientTypeFacility;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
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

            $query = Appointment::with([
                'accounts.user',
                'language',
                'interpreter.user',
                'interpreter.vendor',
                'vendor',
                'appointmentDetails.facility',
                'appointmentDetails.department',
                'appointmentDetails.patient',
                'appointmentAssign'
            ]);
            
            $query->whereIn('status', ['pending','assigned','active', 'completed', 'declined', 'cancelled', 'cnc'])->where(function ($q) {
                $q->whereHas('interpreter', function ($q) {
                    $q->where('vendor_id', $this->userId);
                })->orWhere('appointments.vendor_id', $this->userId);
            });
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'like', '%' . $search . '%')
                        ->orWhere('type', 'like', '%' . $search . '%')
                        ->orWhere('datetime', 'like', '%' . $search . '%')
                        ->orWhereHas('appointmentDetails', function ($q) use ($search) {
                            $q->where('requester_name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('language', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('accounts', function ($q) use ($search) {
                            $q->whereHas('user', function ($f) use ($search) {
                                $f->where('name', 'like', '%' . $search . '%');
                            });
                        })
                        ->orWhereHas('appointmentDetails', function ($q) use ($search) {
                            $q->whereHas('facility', function ($f) use ($search) {
                                $f->where('abbreviation', 'like', '%' . $search . '%');
                            });
                        })
                        ->orWhereHas('appointmentDetails', function ($q) use ($search) {
                            $q->whereHas('department', function ($d) use ($search) {
                                $d->where('department_name', 'like', '%' . $search . '%');
                            });
                        })
                        ->orWhereHas('vendor', function ($q) use ($search) {
                            $q->where('first_name', 'like', '%' . $search . '%');
                        });
                });
            }

            $query->orderBy($sortBy, $sortDirection);
            $data = $query->paginate($pageLength);
            return $this->successResponse($data, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }

    public function actions_assign(Request $request, $id)
    {
        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'interpreter_id' => 'exists:interpreters,id'
        ]);


        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            // $record->appointmentDetails->extra_mileage_request = null;
            // $record->appointmentDetails->extra_mileage = null;
            // $record->appointmentDetails->save();

            // if ($request->vendor_id) {
            //     $record->vendor_id = $request->vendor_id;
            //     $record->interpreter_id = null;
            //     $record->status = 'pending';
            // } elseif ($request->interpreter_id) {
            // } else {
            //     $record->interpreter_id = null;
            //     $record->vendor_id = null;
            //     $record->status = 'open';
            // }
            $record->interpreter_id = $request->interpreter_id;
            // $record->vendor_id = null;
            $record->status = 'assigned';
            // $user = User::where('id', $this->userId)->first();
            // if($user->role != 'vendor' || $user->role != 'staff_interpreter'){
            //     $record->assigned_by = $this->userId;
            // }

            $record->save();
            // if ($request->interpreter_id) {
                $log_changes = 'Appointment assigned to ' . $record->interpreter->first_name . ' ' . $record->interpreter->last_name;
                AppointmentAssign::updateOrCreate([
                    'appointment_id' => $id
                ], [
                    'appointment_id' => $id,
                    'interpreter_id' => $request->interpreter_id,
                    'checkin_date' => $request->checkin_date,
                    'checkin_time' => $request->checkin_time,
                    'checkout_date' => $request->checkout_date,
                    'checkout_time' => $request->checkout_time,
                ]);
                // $account_id = $record->accounts->id;
                // $subject = 'Your Appointment Has Been Assigned';
                // $content = 'We would like to inform you that your appointment has been successfully assigned to our interpreter, ' . $record->interpreter->first_name . ' ' . $record->interpreter->last_name . '. We are here to ensure the best service experience for you. Thank you for your understanding.';
                // $redirect_link = config('app.frontend_url').'/appointments/view/'.$record->id;
            
                // $this->notifications($account_id, $subject, $content,$redirect_link);
            // }

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Vendor assigned to interpreter',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);
            return $this->successResponse([], 'Appointment assigned successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred : ' . $e->getMessage(), 500);
        }
    }

    public function get($id)
    {
        try {
            $record = Appointment::with(['accounts.user', 'language', 'interpreter.user', 'vendor', 'appointmentDetails.facility', 'appointmentDetails.patient', 'appointmentDetails.department', 'appointmentAssign'])->find($id);
            if (!$record) {
                throw new \Exception('Appointment Not Found');
            }
            return $this->successResponse($record, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving: ' . $e->getMessage(), 500);
        }
    }

    public function getLogs($id)
    {
        try {

            $record = AppointmentLog::with(['user'])->where('appointment_id', $id)->get();
            return $this->successResponse($record, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving: ' . $e->getMessage(), 500);
        }
    }
}
