<?php

namespace App\Http\Controllers\Interpreter;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentAssign;
use App\Models\AppointmentDetail;
use App\Models\AppointmentInvites;
use App\Models\AppointmentLog;
use App\Models\Interpreter;
use App\Models\InterpreterLanguage;
use App\Models\Language;
use App\Models\SubClient;
use App\Models\SubClientTypeDepartment;
use App\Models\SubClientTypeFacility;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
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
            $interpreterLanguages = InterpreterLanguage::where('interpreter_id', $this->interpreterId)->get();
            $languageIds = $interpreterLanguages->pluck('language_id')->toArray();

            $query = Appointment::with(['accounts.user', 'language', 'interpreter.user', 'vendor', 'appointmentDetails.facility', 'appointmentDetails.department', 'appointmentAssign']);
            $query->whereIn('status', ['open', 'assigned', 'active', 'completed', 'declined', 'cancelled'])
                ->where(function ($q) use ($languageIds) {
                    $q->where('status', 'open')
                        ->whereIn('language_id', $languageIds)
                        ->orWhere('interpreter_id', $this->interpreterId);
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
                        ->orWhereHas('interpreter', function ($q) use ($search) {
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


    public function get($id)
    {
        try {

            $record = Appointment::with(['accounts.user', 'language', 'interpreter.user', 'vendor', 'appointmentDetails.facility', 'appointmentDetails.department', 'appointmentAssign'])->find($id);
            if (!$record) {
                throw new \Exception('Appointment Not Found');
            }

            return $this->successResponse($record, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving: ' . $e->getMessage(), 500);
        }
    }

    public function getWithToken($token)
    {
        try {

            $record = AppointmentInvites::where('token', $token)->whereIn('status', ['pending', 'accepted'])->first();
            if (!$record) {
                throw new \Exception('Page Expired');
            }

            $record = Appointment::with(['subclient', 'language', 'facility', 'department', 'interpreter'])->find($record->appointment_id);
            if (!$record) {
                throw new \Exception('Appointment Not Found');
            }

            return $this->successResponse($record, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }


    public function acceptWithToken(Request $request, $token)
    {
        try {

            $record = AppointmentInvites::where('token', $token)->where('status', 'pending')->first();
            if (!$record) {
                throw new \Exception('Page Expired');
            }

            $appointment = Appointment::with(['subclient', 'language', 'facility', 'department', 'interpreter'])->find($record->appointment_id);
            if (!$appointment) {
                throw new \Exception('Appointment Not Found');
            }

            $appointment->status = 'assigned';
            $appointment->interpreter_id = $record->interpreter_id;
            $appointment->save();

            AppointmentAssign::updateOrCreate([
                'appointment_id' => $appointment->id
            ], [
                'appointment_id' => $appointment->id,
                'interpreter_id' => $record->interpreter_id,
            ]);

            AppointmentInvites::where('appointment_id', $appointment->id)->update([
                'status' => 'expired'
            ]);

            $record->update([
                'status' => 'accepted'
            ]);


            $log_changes = 'Interpreter: ' . $record->interpreter->first_name . ' ' . $record->interpreter->last_name . ' accepted the invite';

            AppointmentLog::create([
                'appointment_id' => $appointment->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Invite Accepted',
                'notes' => $log_changes,
            ]);

            return $this->successResponse([], 'Invite accepted successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function rejectWithToken(Request $request, $token)
    {
        try {

            $record = AppointmentInvites::where('token', $token)->where('status', 'pending')->first();
            if (!$record) {
                throw new \Exception('Page Expired');
            }

            $appointment = Appointment::with(['subclient', 'language', 'facility', 'department', 'interpreter'])->find($record->appointment_id);
            if (!$appointment) {
                throw new \Exception('Appointment Not Found');
            }

            $record->update([
                'status' => 'rejected'
            ]);

            $log_changes = 'Interpreter: ' . $record->interpreter->first_name . ' ' . $record->interpreter->last_name . ' rejected the invite';

            AppointmentLog::create([
                'appointment_id' => $appointment->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Invite Rejected',
                'notes' => $log_changes,
            ]);

            return $this->successResponse([], 'Invite rejected successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }



    public function getLogs($id)
    {
        try {

            $record = AppointmentLog::with(['user'])->where('appointment_id', $id)->get();
            // if(!$record){
            //     throw new \Exception('Appointment Not Found');  
            // }

            return $this->successResponse($record, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving: ' . $e->getMessage(), 500);
        }
    }


    public function getInterpreters($id)
    {
        try {
            $record = Appointment::find($id);
            if (!$record) {
                return $this->errorResponse('Record not found.', 404);
            }

            $appointments = Appointment::whereNotNull('interpreter_id')->where('datetime', $record->datetime)->pluck('interpreter_id');

            $records = Interpreter::whereNotIn('id', $appointments)->with(['languages.language'])
                ->whereHas('languages', function ($q) use ($record) {
                    $q->where('language_id', $record->language_id);
                })->orderBy('first_name', 'asc')->get();
            // if(!$record){
            //     throw new \Exception('Appointment Not Found');  
            // }

            return $this->successResponse($records, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving: ' . $e->getMessage(), 500);
        }
    }

    public function getInvitedInterpreters($id)
    {
        try {
            $record = Appointment::find($id);
            if (!$record) {
                return $this->errorResponse('Record not found.', 404);
            }

            $record->load('invites.interpreter');

            return $this->successResponse($record, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving: ' . $e->getMessage(), 500);
        }
    }

    public function getInterpretersWithAppointments($id)
    {
        try {
            $record = Appointment::find($id);
            if (!$record) {
                return $this->errorResponse('Record not found.', 404);
            }

            $appointments = Appointment::whereNotNull('interpreter_id')->where('datetime', $record->datetime)->pluck('interpreter_id');

            $records = Interpreter::whereIn('id', $appointments)->with(['languages.language'])
                ->whereHas('languages', function ($q) use ($record) {
                    $q->where('language_id', $record->language_id);
                })->orderBy('first_name', 'asc')->get();
            // if(!$record){
            //     throw new \Exception('Appointment Not Found');  
            // }

            return $this->successResponse($records, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving: ' . $e->getMessage(), 500);
        }
    }

    public function inviteInterpreters(Request $request, $id)
    {
        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }


        $validator = Validator::make($request->all(), [
            'interpreter_ids' => 'required|array',
            'interpreter_ids.*' => 'exists:interpreters,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            if (!empty($request->interpreter_ids)) {
                $inv_ids = [];
                $terps = [];
                foreach ($request->interpreter_ids as $key => $interpreter) {
                    $appInv = AppointmentInvites::updateOrCreate([
                        'appointment_id' => $record->id,
                        'interpreter_id' => $interpreter,
                    ], [
                        'appointment_id' => $record->id,
                        'interpreter_id' => $interpreter,
                        'token' => gen_uuid()
                    ]);
                    $inv_ids[] = $appInv->id;
                    $terps[] = $appInv->interpreter->first_name . ' ' . $appInv->interpreter->last_name;
                }


                sendApptInvites($inv_ids);

                $log_changes = 'Invited interpreters: ';
                $log_changes .= "\n" . implode(', ', $terps);

                AppointmentLog::create([
                    'appointment_id' => $record->id,
                    'date' => date('Y-m-d'),
                    'time' => date('h:i a'),
                    'user_id' => $this->userId,
                    'event' => 'Invited Interpreters',
                    'notes' => $log_changes,
                ]);
            }

            return $this->successResponse([], 'Interpreters invited successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }

    public function clearInvites(Request $request, $id)
    {
        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }


        try {
            AppointmentInvites::where('appointment_id', $record->id)->delete();

            $log_changes = 'Invites Cleared';

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Invites Cleared',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            return $this->successResponse([], 'Invites cleared successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => "required|in:In Person,Video Remote Interpretation,OTP Pre Schedule,OPI On Demand",
            'datetime' => 'required',
            'language_id' => 'required|exists:languages,id',
            'account_id' => 'required|exists:subclient_types,id',
            'subclient_id' => 'nullable|exists:subclients,id',
            // 'facility_id'=>'required|exists:facilities,id',
            // 'department_id'=>'required|exists:departments,id',
            'requester_name' => 'required|max:100',
            'requester_email' => 'required|max:100',
            'caller_phone' => 'required|max:20',
            'gender' => 'nullable|in:male,female,nonbinary',
            'estimated_duration' => 'nullable|numeric',
            'dynamic_fields' => 'array',
            'us_based' => 'boolean',
            'non_us_based' => 'boolean',
            'english_to_target' => 'boolean',
            'spanish_to_target' => 'boolean',
            'court_certified' => 'boolean',
            'medical_certified' => 'boolean',
            'notes' => 'nullable',
            'department_text' => 'nullable',
            'facility_text' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            $appointment = Appointment::create([
                'account_id' => $request->account_id,
                'subclient_id' => $request->sub_account_id,
                'type' => $request->type,
                'datetime' => $request->datetime,
                'language_id' => $request->language_id,
                'estimated_duration' => $request->estimated_duration,
                'status' => 'available'
            ]);

            // $prefix = 'A0000';
            $uniqueNumber = str_pad($appointment->id, 4, '0', STR_PAD_LEFT);
            $appointmentNumber = 'A' . $uniqueNumber;
            $appointment->appid = $appointmentNumber;
            $appointment->save();

            $appointment_details = AppointmentDetail::create([
                'appointment_id' => $appointment->id,
                'facility_id' => $request->facility_id,
                'address' => $request->address,
                'department_id' => $request->department_id,
                'department_text' => $request->department_text,
                'requester_name' => $request->requester_name,
                'requester_email' => $request->requester_email,
                'requester_phone' => $request->caller_phone,
                'interpreter_gender' => $request->gender,
                'dynamic_fields' => json_encode($request->dynamic_fields),
                'notes' => $request->notes,
                'video_link' => $request->video_link,
                'us_based' => $request->us_based,
                'non_us_based' => $request->non_us_based,
                'english_to_target' => $request->english_to_target,
                'spanish_to_target' => $request->spanish_to_target,
                'court_certified' => $request->court_certified,
                'medical_certified' => $request->medical_certified,
                'auto_assign' => $request->auto_assign,
            ]);

            AppointmentLog::create([
                'appointment_id' => $appointment->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'New Appointment',
                'user_id' => $this->userId,
                'notes' => 'New Appointment created',
            ]);

            $appointment = $appointment->load('appointmentDetails');
            return $this->successResponse($appointment, 'Appointment created successfully with appid ' . $appointment->appid, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => "required|in:In Person,Video Remote Interpretation,OTP Pre Schedule,OPI On Demand",
            'datetime' => 'required',
            'language_id' => 'required|exists:languages,id',
            // 'facility_id'=>'required|exists:facilities,id',
            // 'department_id'=>'required|exists:departments,id',
            'requester_name' => 'max:100',
            'caller_phone' => 'max:20',
            'gender' => 'nullable|in:male,female,nonbinary',
            'estimated_duration' => 'numeric',
            'dynamic_fields' => 'array',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            // Backup original data
            $originalData = $record->getOriginal();

            $record->update([
                'account_id' => $request->account_id,
                'subclient_id' => $request->sub_account_id,
                'type' => $request->type,
                'datetime' => $request->datetime,
                'language_id' => $request->language_id,
                'facility_id' => $request->facility_id,
                'department_id' => $request->department_id,
                'requester_name' => $request->requester_name,
                'caller_phone' => $request->caller_phone,
                'gender' => $request->gender,
                'estimated_duration' => $request->estimated_duration,
                'dynamic_fields' => json_encode($request->dynamic_fields),
                'video_link' => $request->video_link,
            ]);

            $changes = $record->getChanges();

            $log_changes = '';
            // Log the changes
            foreach ($changes as $field => $newValue) {
                $oldValue = $originalData[$field] ?? null; // Get the old value
                if ($field == 'subclient_id') {
                    $log_changes .= "\nField 'client' changed from '" . SubClient::find($oldValue)?->name . "' to '" . SubClient::find($newValue)?->name . "'";
                } else if ($field == 'language_id') {
                    $log_changes .= "\nField 'language' changed from '" . Language::find($oldValue)?->name . "' to '" . Language::find($newValue)?->name . "'";
                } else if ($field == 'facility_id') {
                    $log_changes .= "\nField 'facility' changed from '" . SubClientTypeFacility::find($oldValue)?->abbreviation . "' to '" . SubClientTypeFacility::find($newValue)?->abbreviation . "'";
                } else if ($field == 'department_id') {
                    $log_changes .= "\nField 'department' changed from '" . SubClientTypeDepartment::find($oldValue)?->name . "' to '" . SubClientTypeDepartment::find($newValue)?->name . "'";
                } else if ($field == 'datetime' || $field == 'updated_at') {
                } else {
                    $log_changes .= "\nField '{$field}' changed from '{$oldValue}' to '{$newValue}'";
                }
            }

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'EDIT',
                'user_id' => $this->userId,
                // 'notes'=>'New Appointment created',
            ]);

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Information Modified',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);


            return $this->successResponse($record, 'Record updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $record = Appointment::find($id);

        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $record->delete();
        return $this->successResponse(null, 'Record deleted successfully.');
    }

    public function deleteMultipleRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:appointments,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = Interpreter::whereIn('id', $ids)->delete();

            if ($deletedCount > 0) {
                return $this->successResponse([], 'Records deleted successfully.', 200);
            } else {
                return $this->errorResponse('No records found to delete.', 404);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    public function actions_reschedule(Request $request, $id)
    {
        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required',
            'time' => 'required',
            'reschedule_reason' => 'required|max:250',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $log_changes = 'Appointment Re-scheduled from ' . $record->datetime;

            $record->datetime = $request->date . ' ' . $request->time;
            // $record->time = $request->time;
            $record->reschedule_reason = $request->reschedule_reason;
            $record->save();


            $log_changes .= ' to ' . $record->datetime;
            $log_changes .= "\nReason: \n";
            $log_changes .= $record->reschedule_reason;

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Appointment Re-scheduled',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            return $this->successResponse([], 'Appointment rescheduled successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    public function actions_assign(Request $request, $id)
    {
        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'interpreter_id' => 'required|exists:interpreters,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $record->status = 'assigned';
            $record->interpreter_id = $request->interpreter_id;
            $record->save();

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



            $log_changes = 'Appointment assigned to ' . $record->interpreter->first_name . ' ' . $record->interpreter->last_name;

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Appointment Assigned',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);


            return $this->successResponse([], 'Appointment assigned successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    public function actions_dnc(Request $request, $id)
    {
        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'dnc_reason' => 'required|max:250',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $record->dnc_reason = $request->dnc_reason;
            $record->status = 'dnc';
            $record->save();

            $log_changes = 'Appointment status changed to "dnc" with the reason:';
            $log_changes .= "\n" . $record->dnc_reason;

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Appointment DNCed',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            return $this->successResponse([], 'Appointment status updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    public function actions_cnc(Request $request, $id)
    {
        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'cnc_reason' => 'required|max:250',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $record->cnc_reason = $request->cnc_reason;
            $record->status = 'cnc';
            $record->save();

            $log_changes = 'Appointment status changed to "cnc" with the reason:';
            $log_changes .= "\n" . $record->cnc_reason;

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Appointment CNCed',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            return $this->successResponse([], 'Appointment status updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    public function actions_cancel(Request $request, $id)
    {
        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'cancel_reason' => 'required|max:250',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $record->cancel_reason = $request->cancel_reason;
            $record->status = 'cancelled';
            $record->save();


            $log_changes = 'Appointment status changed to "cancelled" with the reason:';
            $log_changes .= "\n" . $record->cancel_reason;

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Appointment Cancelled',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            return $this->successResponse([], 'Appointment status updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    public function joinRoom(Request $request, $id)
    {
        $record = Appointment::with(['accounts.user', 'language', 'interpreter.user', 'interpreter.vendor', 'interpreter.interpreterRates', 'appointmentDetails.facility',  'appointmentDetails.patient', 'appointmentDetails.department', 'appointmentAssign', 'vendor'])->find($id);
        if (!$record) {
            throw new \Exception('Appointment Not Found');
        }

        // 1. Ensure meeting is within 5 minutes
        $scheduledTime = new \DateTime($record->datetime); // Create DateTime object
        $scheduledTime->sub(new \DateInterval('PT5M'));    // Subtract 5 minutes

        if (new \DateTime() < $scheduledTime) {
            return response()->json(['error' => 'Too early to join'], 403);
        }


        // 3. Generate token
        $room_id = $record->appointmentDetails->room_id;
        $tokenResponse = \Http::withToken(generateManagementToken())
            ->post('https://api.100ms.live/v2/room-codes/room/'.$room_id.'/role/interpreter');

        if (!$tokenResponse->ok()) {
            return response()->json(['error' => 'Token creation failed'], 500);
        }

        // return response()->json([
        //     'iframe_url' => "https://".config('app.hms_subdomain').".app.100ms.live/meeting/{$tokenResponse['code']}"
        // ]);
        return $this->successResponse([
            'iframe_url' => "https://".config('app.hms_subdomain').".app.100ms.live/meeting/{$tokenResponse['code']}"
        ], 'Room created successfully.', 200);
        
    }

    public function answerVriCall(Request $request, $id)
    {
        $record = Appointment::with(['accounts.user', 'language', 'interpreter.user', 'interpreter.vendor', 'interpreter.interpreterRates', 'appointmentDetails.facility',  'appointmentDetails.patient', 'appointmentDetails.department', 'appointmentAssign'])->whereNull('interpreter_id')->find($id);
        if (!$record) {
            throw new \Exception('Appointment Not Found');
        }

        $record->status = 'assigned';
            $record->interpreter_id = $this->interpreterId;
            $record->save();

            AppointmentAssign::updateOrCreate([
                'appointment_id' => $id
            ], [
                'appointment_id' => $id,
                'interpreter_id' => $this->interpreterId
            ]);

            $log_changes = 'VRI on demand asnwered & assigned to ' . auth('api')->user()->name;

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Appointment Assigned',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);


        // 3. Generate token
        $room_id = $record->appointmentDetails->room_id;
        $tokenResponse = \Http::withToken(generateManagementToken())
            ->post('https://api.100ms.live/v2/room-codes/room/'.$room_id.'/role/interpreter');

        if (!$tokenResponse->ok()) {
            return response()->json(['error' => 'Token creation failed'], 500);
        }

        return response()->json([
            'appointment'=>$record,
            'iframe_url' => "https://".config('app.hms_subdomain').".app.100ms.live/meeting/{$tokenResponse['code']}"
        ]);
    }

    // public function changeStatus(Request $request ,$id)
    // {
    //     $record = Appointment::find($id);

    //     if (!$record) {
    //         return $this->errorResponse('Record not found.', 404);
    //     }
    //     $validator = Validator::make($request->all(), [
    //         'status' => 'required|boolean',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->errorResponse('Validation failed', 422, $validator->errors());
    //     }

    //     $record->status = $request->status;
    //     $record->save();

    //     $record->user()->update([
    //         'status'=>$request->status
    //     ]);
    //     return $this->successResponse($record, 'Status Change Successfully.', 200);
    // }
}
