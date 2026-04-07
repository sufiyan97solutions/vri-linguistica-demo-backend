<?php

namespace App\Http\Controllers;

use App\Events\IncomingCallEvent;
use App\Mail\NotificationMail;
use App\Models\Appointment;
use App\Models\AppointmentAssign;
use App\Models\AppointmentDetail;
use App\Models\AppointmentInvites;
use App\Models\AppointmentLog;
use App\Models\ClientTemplateInterpretationRate;
use App\Models\Department;
use App\Models\Facility;
use App\Models\Interpreter;
use App\Models\Invoice;
use App\Models\Language;
use App\Models\Patients;
use App\Models\Payment;
use App\Models\SubClient;
use App\Models\SubClientType;
use App\Models\SubClientTypeInterpretationRate;
use App\Models\TierLanguage;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    use ApiResponseTrait;

    private $userId;

    public function __construct()
    {
        $userId = auth('api')?->user()?->id;
        $this->userId = $userId;
    }

    public function index(Request $request)
    {
        try {

            $query = Appointment::with(['accounts.user', 'language', 'interpreter.user', 'interpreter.interpreterRates', 'vendor', 'appointmentDetails.facility', 'appointmentDetails.department','appointmentDetails.patient', 'appointmentAssign']);
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

            $record = Appointment::with(['accounts.user', 'language', 'interpreter.user', 'interpreter.vendor', 'interpreter.interpreterRates', 'appointmentDetails.facility',  'appointmentDetails.patient', 'appointmentDetails.department', 'appointmentAssign', 'vendor'])->find($id);
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

            $record = Appointment::with(['accounts.user', 'subclient', 'language', 'interpreter.user', 'interpreter.interpreterRates', 'appointmentDetails.facility', 'appointmentDetails.department', 'appointmentAssign'])->find($record->appointment_id);
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

            $appointment = Appointment::with(['accounts.user', 'subclient', 'language', 'interpreter.user', 'interpreter.interpreterRates', 'appointmentDetails.facility', 'appointmentDetails.department', 'appointmentAssign'])->find($record->appointment_id);
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

            $appointment = Appointment::with(['accounts.user', 'subclient', 'language', 'interpreter.user', 'interpreter.interpreterRates', 'appointmentDetails.facility', 'appointmentDetails.department', 'appointmentAssign'])->find($record->appointment_id);
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

    
    public function getWithTokenForRequester($token)
    {
        try {

            $record = Appointment::with(['accounts.user', 'language', 'interpreter.user', 'interpreter.interpreterRates', 'appointmentDetails.facility', 'appointmentDetails.department', 'appointmentAssign'])->where('token',$token)->first();
            if (!$record) {
                throw new \Exception('Appointment Not Found');
            }

            return $this->successResponse($record, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }


    public function joinRoomWithToken(Request $request, $token)
    {
        $record = Appointment::with(['accounts.user', 'language', 'interpreter.user', 'interpreter.vendor', 'interpreter.interpreterRates', 'appointmentDetails.facility',  'appointmentDetails.patient', 'appointmentDetails.department', 'appointmentAssign', 'vendor'])->where('token',$token)->first();
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
            ->post('https://api.100ms.live/v2/room-codes/room/'.$room_id.'/role/requester');

        if (!$tokenResponse->ok()) {
            return response()->json(['error' => 'Token creation failed'], 500);
        }

        return $this->successResponse([
                'iframe_url' => "https://".config('app.hms_subdomain').".app.100ms.live/meeting/{$tokenResponse['code']}"
            ], 'room created successfully.', 200);
            
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

                $subject = 'Appointment Invitation';
                $content = 'New appointment invitation has been sent to you. Please check your portal for details.';
                $this->notifications($request->interpreter_ids, $subject, $content);

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
            'language_id' => 'required|exists:languages,id',
            'date' => 'required',
            'start_time' => 'required',
            'account_id' => 'required|exists:subclient_types,id',
            'facility_id' => 'required|exists:subclient_types_facilities,id',
            'department_id' => 'required|exists:subclient_types_departments,id',
            'duration' => 'required|integer',
            'priority_level' => ['required', Rule::in(['Regular', 'Low', 'High'])],
            'mrn_number' => 'nullable|min:1',
            'patient_name' => 'nullable|string|max:150',
            'birth_date' => 'nullable|date',
            'provider_name' => 'nullable|string|max:150',
            'type' => ['required', Rule::in(['In Person', 'VRI', 'OPI', 'OPI On Demand'])],
            'requester_name' => 'required|string|max:100',
            'requester_phone' => 'nullable|string|max:20',
            'requester_email' => 'required_if:type,VRI,OPI,"OPI On Demand"|array',
            'requester_email.*'=>'required|email|max:255'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        // try {
            if ($request->birth_date) {
                $request->merge([
                    'birth_date' => date('Y-m-d', strtotime($request->birth_date)),
                ]);
            }
            $userId = auth('api')->user()->id;
            if (!empty($request->mrn_number)) {
                $patient = Patients::where('mrn_number', $request->mrn_number)->first();
                if ($patient) {
                    $patient->update([
                        'patient_name' => $request->patient_name,
                        'birth_date' => $request->birth_date,
                        'medicaid_id' => $request->medicaid_id,
                        // 'provider_name' => $request->provider_name,
                        'medicaid_plan' => $request->medicaid_plan,
                        'created_by' => $userId,
                    ]);
                } else {
                    $patient = Patients::create([
                        'mrn_number' => $request->mrn_number,
                        'patient_name' => $request->patient_name,
                        'birth_date' => $request->birth_date,
                        'medicaid_id' => $request->medicaid_id,
                        // 'provider_name' => $request->provider_name,
                        'medicaid_plan' => $request->medicaid_plan,
                        'created_by' => $userId,
                    ]);
                }
            } else {
                $patient = Patients::create([
                    'mrn_number' => $request->mrn_number,
                    'patient_name' => $request->patient_name,
                    'birth_date' => $request->birth_date,
                    'medicaid_id' => $request->medicaid_id,
                    // 'provider_name' => $request->provider_name,
                    'medicaid_plan' => $request->medicaid_plan,
                    'created_by' => $userId,
                ]);
            }
            
            $appointment = Appointment::create([
                'account_id' => $request->account_id,
                'interpreter_id' => $request->interpreter_id,
                'type' => $request->type,
                'datetime' => date('Y-m-d H:i:s', strtotime($request->date . ' ' . $request->start_time)),
                'date' => $request->date,
                'start_time' => $request->start_time,
                'language_id' => $request->language_id,
                'duration' => $request->duration,
                'vendor_id' => $request->vendor_id,
                'created_by' => $this->userId,
                'token' => gen_uuid()
            ]);

            if ($request->vendor_id) {
                $appointment->status = 'pending';
            } else if ($request->interpreter_id) {
                $appointment->status = 'assigned';

                if ($request->checkin_date && $request->checkin_time) {
                    $appointment->status = 'completed';
                    $appointment->appointmentAssign()->create([
                        'interpreter_id' => $request->interpreter_id,
                        'checkin_date' => $request->checkin_date,
                        'checkin_time' => $request->checkin_time,
                        'checkout_date' => $request->checkout_date,
                        'checkout_time' => $request->checkout_time,
                        'comments' => $request->comments,
                        'notes' => $request->notes,
                    ]);
                    $this->clientInvoice($request, $appointment);
                    $this->interpreterPayment($request, $appointment);
                }
            } else {
                $appointment->status = 'open';
            }

            // $prefix = 'A0000';
            $uniqueNumber = str_pad($appointment->id, 4, '0', STR_PAD_LEFT);
            $appointmentNumber = 'A' . $uniqueNumber;
            $appointment->appid = $appointmentNumber;
            $appointment->save();

            $room_name = NULL;
            $room_id = NULL;
            if($request->type == 'VRI'){
                $room_info = createRoom(['description'=>$appointment->language->name.' - Appointment']);
                $room_name = $room_info['room_name'];
                $room_id = $room_info['room_id'];
            }

            $appointment_details = AppointmentDetail::create([
                'appointment_id' => $appointment->id,
                'requester_name' => $request->requester_name,
                'requester_email' => $request->requester_email? implode(',',$request->requester_email):null,
                'requester_phone' => $request->requester_phone,
                'requester_pincode' => $request->requester_pin_code,
                'requester_economic_service' => $request->economic_services,
                'provider_name' => $request->provider_name,
                'facility_id' => $request->facility_id,
                'address' => $request->address,
                'department_id' => $request->department_id,
                'video_link' => $request->video_link,
                'auto_assign' => $request->auto_assign ?? 0,
                'patient_phone' => $request->patient_phone,
                'special_instruction' => $request->special_instruction,
                'encounter_source' => $request->encounter_source,
                'priority_level' => $request->priority_level,
                'patient_id' => $patient->id,
                'room_name'=>$room_name,
                'room_id'=>$room_id,
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

            

            
            if($request->type == 'VRI'){
                $appointmentTime = strtotime($appointment->datetime); // assuming ISO or "Y-m-d H:i:s"
                $currentTime = time();
    
                $timeDiff = $appointmentTime - $currentTime;
                // dd($timeDiff);
                if ($timeDiff >= 0 && $timeDiff <= 300) { // 300 seconds = 5 minutes
                    // This appointment is within the next 5 minutes
                    $this->initiateVRIInvite($appointment);
                }
            }
            // if ($appointment->type == 'OPI On Demand') {
            //     return $this->successResponse($appointment, 'Appointment created successfully with appid ' . $appointment->appid . ". And call initiated to invite Interpreters.", 200);
            // } 
                // return $this->successResponse($appointment, 'Appointment created successfully with appid '.$appointment->appid, 200);
                $subject = 'Appointment Successfully Created';
                $content = 'Your appointment has been created successfully. We look forward to serving you. Thank you for your trust and support!';
                $redirect_link = config('app.frontend_url').'/appointments/view/'.$appointment->id;
                $this->notifications($request->account_id, $subject, $content,$redirect_link);

                // Notify Requester
                try {
                    if($request->requester_email > 0){
                        $subject = 'Appointment Successfully Created';
                        $content = 'Your appointment has been created successfully for '.$request->type.'. We look forward to serving you. Thank you for your trust and support!';
                        $redirect_link = config('app.frontend_url').'/appointment-details?token='.$appointment->token;
                        
                        $data = [
                            'name'=>$request->requester_name,
                            'subject'=>$subject,
                            'content'=>$content,
                            'email'=>$request->requester_email,
                            'button_text'=>'View Appointment',
                            'redirect_link'=>$redirect_link,
                            'recipient'=>$request->requester_email,
                        ];
                        sendMail($data);
                    }
                    
                } catch (\Throwable $th) {
                }

                return $this->successResponse($appointment, 'Appointment created successfully with appid ' . $appointment->appid, 200);
            // }
        // } catch (\Exception $e) {
        //     return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        // }
    }



    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'language_id' => 'required|exists:languages,id',
            'date' => 'required',
            'start_time' => 'required',
            'account_id' => 'required|exists:subclient_types,id',
            'facility_id' => 'required|exists:subclient_types_facilities,id',
            'department_id' => 'required|exists:subclient_types_departments,id',
            'duration' => 'required|integer',
            'priority_level' => ['required', Rule::in(['Regular', 'Low', 'High'])],
            'mrn_number' => 'nullable|min:1',
            'patient_name' => 'nullable|string|max:150',
            'birth_date' => 'nullable|date',
            'provider_name' => 'nullable|string|max:150',
            'type' => ['required', Rule::in(['In Person', 'VRI', 'OPI', 'OPI On Demand'])],
            'requester_name' => 'required|string|max:100',
            'requester_phone' => 'nullable|string|max:20',
            'requester_email' => 'required_if:type,VRI,OPI,"OPI On Demand"|array',
            'requester_email.*'=>'required|email|max:255'
        ]);
    
        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
    
        try {
            // $this->formatBirthDate($request);
            if ($request->birth_date) {
                $request->merge([
                    'birth_date' => date('Y-m-d', strtotime($request->birth_date)),
                ]);
            }
            $userId = auth('api')->user()->id;
            if (!empty($request->mrn_number)) {
                $patient = Patients::where('mrn_number', $request->mrn_number)->first();
                if ($patient) {
                    $patient->update([
                        'patient_name' => $request->patient_name,
                        'birth_date' => $request->birth_date,
                        'medicaid_id' => $request->medicaid_id,
                        // 'provider_name' => $request->provider_name,
                        'medicaid_plan' => $request->medicaid_plan,
                        'created_by' => $userId,
                    ]);
                } else {
                    $patient = Patients::create([
                        'mrn_number' => $request->mrn_number,
                        'patient_name' => $request->patient_name,
                        'birth_date' => $request->birth_date,
                        'medicaid_id' => $request->medicaid_id,
                        // 'provider_name' => $request->provider_name,
                        'medicaid_plan' => $request->medicaid_plan,
                        'created_by' => $userId,
                    ]);
                }
            } else {
                $patient = Patients::create([
                    'mrn_number' => $request->mrn_number,
                    'patient_name' => $request->patient_name,
                    'birth_date' => $request->birth_date,
                    'medicaid_id' => $request->medicaid_id,
                    // 'provider_name' => $request->provider_name,
                    'medicaid_plan' => $request->medicaid_plan,
                    'created_by' => $userId,
                ]);
            }

            $appointment = Appointment::findOrFail($id);
            $appointment->update([
                'account_id' => $request->account_id,
                'interpreter_id' => $request->interpreter_id,
                'type' => $request->type,
                'datetime' => date('Y-m-d H:i:s', strtotime($request->date . ' ' . $request->start_time)),
                'date' => $request->date,
                'start_time' => $request->start_time,
                'language_id' => $request->language_id,
                'duration' => $request->duration,
                'vendor_id' => $request->vendor_id,
                'updated_by' => $userId,
            ]);
    
    
            $room_name = $appointment->appointmentDetails->room_name;
            $room_id = $appointment->appointmentDetails->room_id;
            if($request->type == 'VRI'){
                if($request->type != $appointment->type){
                    $room_info = createRoom(['description'=>$appointment->language->name.' - Appointment']);
                    $room_name = $room_info['room_name'];
                    $room_id = $room_info['room_id'];
                }
            }

            $appointmentDetail = $appointment->appointmentDetails;
            $appointmentDetail->update([
                'requester_name' => $request->requester_name,
                'requester_email' => $request->requester_email? implode(',',$request->requester_email):null,
                'requester_phone' => $request->requester_phone,
                'provider_name' => $request->provider_name,
                'facility_id' => $request->facility_id,
                'address' => $request->address,
                'department_id' => $request->department_id,
                'video_link' => $request->video_link,
                'auto_assign' => $request->auto_assign ?? 0,
                'patient_phone' => $request->patient_phone,
                'special_instruction' => $request->special_instruction,
                'encounter_source' => $request->encounter_source,
                'priority_level' => $request->priority_level,
                'patient_id' => $patient->id ?? null,
                'room_name'=>$room_name,
                'room_id'=>$room_id,
            ]);
    
            // if ($request->vendor_id) {
            //     $appointment->status = 'pending';
            // } else if ($request->interpreter_id) {
            //     $appointment->status = 'assigned';
    
            //     if ($request->checkin_date && $request->checkin_time) {
            //         $appointment->status = 'completed';
            //         $appointment->appointmentAssign()->updateOrCreate(
            //             ['appointment_id' => $appointment->id],
            //             [
            //                 'interpreter_id' => $request->interpreter_id,
            //                 'checkin_date' => $request->checkin_date,
            //                 'checkin_time' => $request->checkin_time,
            //                 'checkout_date' => $request->checkout_date,
            //                 'checkout_time' => $request->checkout_time,
            //                 'comments' => $request->comments,
            //                 'notes' => $request->notes,
            //             ]
            //         );
            //         $this->clientInvoice($request, $appointment);
            //         $this->interpreterPayment($request, $appointment);
            //     }
            // } else {
            //     $appointment->status = 'open';
            // }
    
            $appointment->save();
    
            AppointmentLog::create([
                'appointment_id' => $appointment->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Appointment Updated',
                'user_id' => $userId,
                'notes' => 'Appointment updated',
            ]);
    
            $appointment = $appointment->load('appointmentDetails');
    
            $subject = 'Appointment Successfully Updated';
            $content = 'Your appointment has been updated. We look forward to serving you. Thank you for your trust and support!';
            $redirect_link = config('app.frontend_url').'/appointments/view/'.$appointment->id;
            $this->notifications($request->account_id, $subject, $content,$redirect_link);

            // Notify Requester
            try {
                if($request->requester_email > 0){
                    $subject = 'Appointment Updated';
                    $content = 'Your appointment has been updated for '.$request->type.'. We look forward to serving you. Thank you for your trust and support!';
                    $redirect_link = config('app.frontend_url').'/appointment-details?token='.$appointment->token;
                    
                    $data = [
                        'name'=>$request->requester_name,
                        'subject'=>$subject,
                        'content'=>$content,
                        'email'=>$request->requester_email,
                        'button_text'=>'View Appointment',
                        'redirect_link'=>$redirect_link,
                        'recipient'=>$request->requester_email,
                    ];
                    sendMail($data);
                }
                
            } catch (\Throwable $th) {
            }
                
            return $this->successResponse($appointment, 'Appointment updated successfully.', 200);
    
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during update: ' . $e->getMessage(), 500);
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

            $deletedCount = Appointment::whereIn('id', $ids)->delete();

            if ($deletedCount > 0) {
                return $this->successResponse([], 'Records deleted successfully.', 200);
            } else {
                return $this->errorResponse('No records found to delete.', 404);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    public function filterPatient(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'mrn_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $mrnNumber = $request->mrn_number;

            $patient = Patients::where('mrn_number', $mrnNumber)->first();

            if ($patient) {
                // $patient->provider_name=null;
                $patient->save();
                
                $patient = $patient->toArray();
                $patient['birth_date'] = date('Y-m-d',strtotime($patient['birth_date']));
                return $this->successResponse($patient, 'Patient found successfully.', 200);
            } else {
                // $createValidator = Validator::make($request->all(), [
                //     'patient_name' => 'required|string|max:255',
                //     'birth_date' => 'required|date',
                //     'provider_name' => 'required|string|max:255',
                //     'medicaid_id' => 'nullable|string|max:255',
                //     'medicaid_plan' => 'nullable|string|max:255',
                // ]);

                // if ($createValidator->fails()) {
                //     return $this->errorResponse('Validation failed', 422, $createValidator->errors());
                // }

                // if ($request->birth_date) {
                //     $request->merge([
                //         'birth_date' => date('Y-m-d', strtotime($request->birth_date)),
                //     ]);
                // }

                // $userId = auth('api')->user()->id;
                // // $newPatient = Patient::create([
                // //     'mrn_number' => $mrnNumber,
                // //     'patient_name' => $request->patient_name,
                // //     'birth_date' => $request->birth_date,
                // //     'provider_name' => $request->provider_name,
                // //     'medicaid_id' => $request->medicaid_id ,
                // //     'medicaid_plan' => $request->medicaid_plan,
                // //     'created_by' => $userId,
                // // ]);

                return $this->errorResponse('Patient not found.', 422);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
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
            'start_time' => 'required',
            'reschedule_reason' => 'required|max:250',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $log_changes = 'Appointment Re-scheduled from ' . $record->datetime;

            $record->datetime = $request->date . ' ' . $request->start_time;
            $record->date = $request->date;
            $record->start_time = $request->start_time;
            $record->appointmentDetails->reschedule_reason = $request->reschedule_reason;
            $record->appointmentDetails->save();
            $record->save();


            $log_changes .= ' to ' . $record->datetime;
            $log_changes .= "\nReason: \n";
            $log_changes .= $record->reschedule_reason;

            $account_id = $record->accounts->id;
            $subject = 'Appointment reschedule';
            $content = 'Please be informed that your appointment has been successfully rescheduled. The reason for this change is: ' . $record->reschedule_reason . '. We sincerely appreciate your understanding.';
            $this->notifications($account_id, $subject, $content);

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
            return $this->errorResponse('An error occurred while reschedule records: ' . $e->getMessage(), 500);
        }
    }

    public function actions_assign(Request $request, $id)
    {
        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'interpreter_id' => 'required_without:vendor_id|nullable|exists:interpreters,id',
            'vendor_id' => 'required_without:interpreter_id|nullable|exists:users,id',
        ]);


        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $record->appointmentDetails->extra_mileage_request = null;
            $record->appointmentDetails->extra_mileage = null;
            $record->appointmentDetails->save();

            if ($request->vendor_id) {
                $record->vendor_id = $request->vendor_id;
                $record->interpreter_id = null;
                $record->status = 'pending';
            } elseif ($request->interpreter_id) {
                $record->interpreter_id = $request->interpreter_id;
                $record->vendor_id = null;
                $record->status = 'assigned';
            } else {
                $record->interpreter_id = null;
                $record->vendor_id = null;
                $record->status = 'open';
            }
            $user = User::where('id', $this->userId)->first();
            if($user->role != 'vendor'){
                $record->assigned_by = $this->userId;
            }

            $record->save();
            if ($request->interpreter_id) {
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
                $account_id = $record->accounts->id;
                $subject = 'Your Appointment Has Been Assigned';
                $content = 'We would like to inform you that your appointment has been successfully assigned to our interpreter, ' . $record->interpreter->first_name . ' ' . $record->interpreter->last_name . '. We are here to ensure the best service experience for you. Thank you for your understanding.';
                $redirect_link = config('app.frontend_url').'/appointments/view/'.$record->id;
            
                $this->notifications($account_id, $subject, $content,$redirect_link);
            } else {
                $log_changes = 'Appointment assigned to ' . $record->vendor->name;
            }
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
            return $this->errorResponse('An error occurred : ' . $e->getMessage(), 500);
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

            $record->appointmentDetails->dnc_reason = $request->dnc_reason;
            $record->appointmentDetails->save();
            $record->status = 'dnc';
            $record->save();

            $log_changes = 'Appointment status changed to "dnc" with the reason:';
            $log_changes .= "\n" . $record->appointmentDetails->dnc_reason;

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Appointment DNCed',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);
            $account_id = $record->accounts->id;
            $subject = 'Update Regarding Your Appointment Status';
            $content = 'We would like to inform you that the status of your appointment has been updated to "Do Not Cover (DNC)". Reason: ' . $record->status_reason . '. Thank you for your understanding, and please let us know if you have any questions.';
            $this->notifications($account_id, $subject, $content);

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

            $record->appointmentDetails->cnc_reason = $request->cnc_reason;
            $record->appointmentDetails->save();
            $record->status = 'cnc';
            $record->save();

            $log_changes = 'Appointment status changed to "cnc" with the reason:';
            $log_changes .= "\n" . $record->appointmentDetails->cnc_reason;

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Appointment CNCed',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            $account_id = $record->accounts->id;
            $subject = 'Update Regarding Your Appointment Status';
            $content = 'Unfortunately, your appointment has been marked as "Could Not Cover (CNC)". Reason: ' . $request->cnc_reason . '. We sincerely apologize for the inconvenience and are available to assist you with any questions or rescheduling.';
            $this->notifications($account_id, $subject, $content);

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

            $record->appointmentDetails->cancel_reason = $request->cancel_reason;
            $record->appointmentDetails->save();
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

            $account_id = $record->accounts->id;
            $subject = 'Appointment Cancelled';
            $content = 'We would like to inform you that your appointment has been cancelled due to the reason (' . $request->cancel_reason . '). If you have any questions, please feel free to contact us.';
            $this->notifications($account_id, $subject, $content);

            return $this->successResponse([], 'Appointment status updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    public function extra_mileage(Request $request, $id)
    {
        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'extra_mileage' => 'required|max:50',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $record->appointmentDetails->extra_mileage = $request->extra_mileage;
            $record->appointmentDetails->save();

            $vendor = User::where('id', $request->vendor_id)->first();
            $log_changes = 'Vendor requested $'.$request->extra_mileage.' for extra mileage';
            // $log_changes .= "\n $" . $request->extra_mileage;

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Extra Mileage Request',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            $created_by = User::where('id', $record->created_by)->first();
            $assigned_by = User::where('id', $record->assigned_by)->first();
            $superAdmin = User::where('role', 'super_admin')->first();

            $subject = 'Appointment Extra Mileage Request';
            $content = 'The vendor has submitted a request for extra mileage ($'. $request->extra_mileage . ') for this appointment. Kindly review the request and take appropriate action.';

            try {
                $details = [
                    'name' => $assigned_by->name,
                    'subject' => $subject,
                    'content' => $content,
                    'redirect_link' => config('app.frontend_url').'/appointments/view/'.$id
                ];

                $toEmail = $assigned_by->email;
                $ccEmails = [];

                if ($created_by && $created_by->email) {
                    $ccEmails[] = $created_by->email;
                }

                if ($superAdmin && $superAdmin->email) {
                    $ccEmails[] = $superAdmin->email;
                }

                try {
                    Mail::to($toEmail)
                        ->cc($ccEmails)
                        ->send(new NotificationMail($details));
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }


            return $this->successResponse([], 'Extra mileage request has been submitted.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    public function action_approval(Request $request)
    {
        $record = Appointment::find($request->id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        try {
            $record->appointmentDetails->extra_mileage_request = $request->extra_mileage_request;
            $record->appointmentDetails->save();

            $approval = $request->extra_mileage_request == 1? 'Approved': 'Declined';

            $log_changes = 'Appointment Extra Mileage request' . $approval;

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Appointment Extra Mileage Approval',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            $created_by = User::where('id', $record->created_by)->first();
            $assigned_by = User::where('id', $record->assigned_by)->first();
            $vendor = User::where('id', $record->vendor_id)->first();
            $approved_by = User::where('id', $this->userId)->first();
            $superAdmin = User::where('role', 'super_admin')->first();
            
            $subject = 'Appointment Extra Mileage Apporoval';
            $content = 'Your Extra mileage request for appointment '.$record->appid.' has been '.$approval.' by '. $approved_by->name.'. If you have any questions, please feel free to contact us.';
            try {
                $details = [
                    'name' => $vendor->name,
                    'subject' => $subject,
                    'content' => $content,
                    'redirect_link' =>  config('app.frontend_url').'/appointments/view/'.$record->id
                ];

                $toEmail = $vendor->email;
                $ccEmails = [];

                // if ($created_by && $created_by->email) {
                //     $ccEmails[] = $created_by->email;
                // }
                // if ($assigned_by && $assigned_by->email) {
                //     $ccEmails[] = $assigned_by->email;
                // }

                // if ($superAdmin && $superAdmin->email) {
                //     $ccEmails[] = $superAdmin->email;
                // }

                try {
                    Mail::to($toEmail)
                        // ->cc($ccEmails)
                        ->send(new NotificationMail($details));
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }

            return $this->successResponse([], 'Appointment extra mileage '.$approval, 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }


    public function actions_decline(Request $request)
    {
        $record = Appointment::find($request->id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        try {

            if ($request->assignee == 'vendor') {
                $record->status = 'vendor_declined';
            } else if ($request->assignee == 'interpreter') {
                $record->status = 'declined';
            }
            $record->save();


            $log_changes = 'Appointment status changed to ' . $record->status;

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Appointment Declined',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            $account_id = $record->accounts->id;
            $subject = 'Appointment Declined';
            $content = 'We would like to inform you that your appointment has been declined due to the reason (' . $request->cancel_reason . '). If you have any questions, please feel free to contact us.';
            $this->notifications($account_id, $subject, $content);

            return $this->successResponse([], 'Appointment status updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }


    public function actions_adjustment(Request $request, $id)
    {

        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'checkin_date' => 'required',
            'checkin_time' => 'required',
            'checkout_date' => 'required',
            'checkout_time' => 'required',
            'comments' => ['required', Rule::in(['Admission/Intake', 'Discharge', 'Informed Consent', 'Service Decline', 'Other'])],
            'notes' => 'nullable|max:500'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // Get appointmentAssign record
        $appointmentAssign = $record->appointmentAssign;

        if ($appointmentAssign) {
            $appointmentAssign->update([
                'checkin_date' => $request->checkin_date,
                'checkin_time' => $request->checkin_time,
                'checkout_date' => $request->checkout_date,
                'checkout_time' => $request->checkout_time,
                'comments' => $request->comments,
                'notes' => $request->notes,
            ]);
        } else {
            $record->appointmentAssign()->create([
                'checkin_date' => $request->checkin_date,
                'checkin_time' => $request->checkin_time,
                'checkout_date' => $request->checkout_date,
                'checkout_time' => $request->checkout_time,
                'comments' => $request->comments,
                'notes' => $request->notes,
            ]);
        }

        $record->status = 'completed';
        $record->save();


        if ($record) {
            $this->clientInvoice($request, $record);
            if ($record->interpreter_id && $record->interpreter->vendor_id) {
                $this->vendorPayment($request, $record);
            }elseif($record->interpreter_id){
                $this->interpreterPayment($request, $record);
            }elseif ($record->vendor_id) {
                $this->vendorPayment($request, $record);
            }
        }


        $log_changes = 'Appointment status changed to "completed" with';
        $log_changes .= "\nComments: " . $request->comments;
        $log_changes .= "\nNotes: " . $request->notes;

        AppointmentLog::create([
            'appointment_id' => $record->id,
            'date' => date('Y-m-d'),
            'time' => date('h:i a'),
            'event' => 'Appointment completed',
            'user_id' => $this->userId,
            'notes' => $log_changes,
        ]);

        $account_id = $record->accounts->id;
        $subject = 'Appointment Successfully Completed';
        $content = 'We would like to inform you that your appointment was successfully completed with the following details:' . "\n\n" .
            'Check-In Date: ' . $request->checkin_date . "\n" .
            'Check-In Time: ' . $request->checkin_time . "\n" .
            'Check-Out Date: ' . $request->checkout_date . "\n" .
            'Check-Out Time: ' . $request->checkout_time . "\n\n" .
            'Notes: ' . $request->notes . "\n";
        if (!empty($request->comments)) {
            $content .= '**Comments:** ' . $request->comments . "\n";
        }
        $content .= "\n" . 'Thank you for your time and trust in our services. If you have any questions, feel free to contact us.';
        $this->notifications($account_id, $subject, $content);

        return $this->successResponse([], 'Appointment adjusted successfully.', 200);
    }

    public function add_patient(Request $request, $id)
    {

        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }


        $validator = Validator::make($request->all(), [
            'patient_phone' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $record->appointmentDetails()->update([
            'patient_phone' => $request->patient_phone,
        ]);

        $log_changes = 'Appointment patient number added';

        AppointmentLog::create([
            'appointment_id' => $record->id,
            'date' => date('Y-m-d'),
            'time' => date('h:i a'),
            'event' => 'Patient number Add',
            'user_id' => $this->userId,
            'notes' => $log_changes,
        ]);

        if (config('app.env') == 'production') {

            $curl = curl_init();

            // Set the URL and other options for the cURL request
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://sip.elogixit.com/ast/api/dialer.php',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query(array(
                    'action' => 'addpatient',
                    'patientphonenumber' => $request->patient_phone,
                    'appointmentid' => $record->appid
                )),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));

            $response = curl_exec($curl);

            \Log::info("Add Patient Call Response: " . $response);
        }

        return $this->successResponse([], 'Patient number updated successfully.', 200);
    }

    public function hang_up_call($id)
    {

        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $log_changes = 'Call hanged up';

        AppointmentLog::create([
            'appointment_id' => $record->id,
            'date' => date('Y-m-d'),
            'time' => date('h:i a'),
            'event' => 'Call hanged up',
            'user_id' => $this->userId,
            'notes' => $log_changes,
        ]);

        if (config('app.env') == 'production') {

            $curl = curl_init();

            // Set the URL and other options for the cURL request
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://sip.elogixit.com/ast/api/dialer.php',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query(array(
                    'action' => 'hangup',
                    'appointmentid' => $record->appid
                )),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));

            $response = curl_exec($curl);

            \Log::info("Hang Up Call Response: " . $response);
        }

        return $this->successResponse([], 'Call hanged up successfully.', 200);
    }


    public function actions_auto_invite_by_call(Request $request, $id)
    {

        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }


        return $this->successResponse($record, 'Interpreters are being Invited By The Call.', 200);
    }

    public function actions_auto_invite_by_email(Request $request, $id)
    {

        $record = Appointment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }


        $appointments = Appointment::whereNotNull('interpreter_id')->where('datetime', $record->datetime)->pluck('interpreter_id');

        $records = Interpreter::whereNotIn('id', $appointments)->with(['languages.language'])
            ->whereHas('languages', function ($q) use ($record) {
                $q->where('language_id', $record->language_id);
            })->orderBy('first_name', 'asc')->get();

        $inv_ids = [];
        $terps = [];
        foreach ($records as $key => $terp) {
            $appInv = AppointmentInvites::updateOrCreate([
                'appointment_id' => $record->id,
                'interpreter_id' => $terp->id,
            ], [
                'appointment_id' => $record->id,
                'interpreter_id' => $terp->id,
                'token' => gen_uuid()
            ]);
            $inv_ids[] = $appInv->id;
            $terps[] = $appInv->interpreter->first_name . ' ' . $appInv->interpreter->last_name;
        }


        sendApptInvites($inv_ids);

        $log_changes = 'Auto Invited interpreters: ';
        $log_changes .= "\n" . implode(', ', $terps);

        AppointmentLog::create([
            'appointment_id' => $record->id,
            'date' => date('Y-m-d'),
            'time' => date('h:i a'),
            'user_id' => $this->userId,
            'event' => 'Invited Interpreters',
            'notes' => $log_changes,
        ]);


        return $this->successResponse($record, 'Interpreters invited successfully', 200);

        // return $this->successResponse($record, 'Interpreters are being Invited By The Call.', 200);
    }

    public function asterisk_webhook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required',
            'phonenumber' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $record = Appointment::where('appid', $request->appointment_id)->first();
            if (!$record) {
                return $this->errorResponse('Record not found.', 404);
            }

            $interpreter  = Interpreter::where('phone', "1" . $request->phonenumber)->first();
            if (!$interpreter) {
                return $this->errorResponse('Interpreter not found.', 404);
            }


            $record->status = 'assigned';
            $record->interpreter_id = $interpreter->id;
            $record->save();

            AppointmentAssign::updateOrCreate([
                'appointment_id' => $record->id
            ], [
                'appointment_id' => $record->id,
                'interpreter_id' => $interpreter->id
            ]);



            $log_changes = 'Appointment assigned to ' . $record->interpreter->first_name . ' ' . $record->interpreter->last_name;

            AppointmentLog::create([
                'appointment_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Appointment Assigned',
                // 'user_id'=>$this->userId,
                'notes' => $log_changes,
            ]);


            return $this->successResponse([], 'Appointment assigned successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    private function initiateVRIInvite($appointment)
    {

        if (config('app.env') != 'production') {
            return;
        }

        $languageId = $appointment->language_id;

        // Get all interpreters who speak the required language
        $interpreters = Interpreter::whereHas('user',function($query) {
            $query->where('status',1);
        })->whereHas('languages', function ($query) use ($languageId) {
            $query->where('language_id', $languageId);
        })->get();

        foreach ($interpreters as $interpreter) {
            broadcast(new IncomingCallEvent([
                'id' => $appointment->id,
                'language' => $appointment->language->name,
            ], $interpreter->user_id));
        }

        // \Log::info("Initiate VRI : " . $response);
        return true;
    }

    private function initiateCallInvite($app_id)
    {

        if (config('app.env') != 'production') {
            return;
        }


        $phoneNumbers = Interpreter::pluck('phone')->toArray();
        // dd($phoneNumbers);
        $numbers = [];
        foreach ($phoneNumbers as $phone) {
            $numbers[] = substr($phone, 1);
        }

        // $phoneNumbers = array(
        //     '2512399192',
        //     '5614860789',
        //     '8016088863'
        // );

        // Convert the array to a comma-separated list
        $phoneNumbersList = implode(',', $numbers);

        // Initialize cURL session
        $curl = curl_init();

        // Set the URL and other options for the cURL request
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://sip.elogixit.com/ast/api/dialer.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query(array(
                'action' => 'originate',
                'phonenumbers' => $phoneNumbersList,
                'ext' => '2000',
                'apptid' => $app_id
            )),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        // Execute the cURL request and get the response
        $response = curl_exec($curl);

        // $curl = curl_init();

        // curl_setopt_array($curl, array(
        // CURLOPT_URL => 'https://sip.elogixit.com/ast/api/dialer.php',
        // CURLOPT_RETURNTRANSFER => true,
        // CURLOPT_ENCODING => '',
        // CURLOPT_MAXREDIRS => 10,
        // CURLOPT_TIMEOUT => 0,
        // CURLOPT_FOLLOWLOCATION => true,
        // CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        // CURLOPT_CUSTOMREQUEST => 'POST',
        // CURLOPT_POSTFIELDS => 'action=originate&phonenumbers=2512399192%2C5614860789&ext=2000&appointment_id='.$app_id,
        // CURLOPT_HTTPHEADER => array(
        //     'Content-Type: application/x-www-form-urlencoded'
        // ),
        // ));

        // $response = curl_exec($curl);

        // curl_close($curl);


        \Log::info("Initiate Call Response: " . $response);
        return true;
    }

    private function notifications($user_id, $subject, $content, $redirect_link=null)
    {
        $data = SubClientType::with('user')->where('id', $user_id)->first();
        if (!$data) {
            $data = Interpreter::with('user')->where('id', $user_id)->first();
        }
        if ($data && $data->user && $data->user->notifications == 1) {
            try {
                $email = $data->user->email;
                $details = [
                    'name' => $data->user->name,
                    'subject' => $subject,
                    'content' => $content,
                    'redirect_link' => $redirect_link,
                    'recipient'=>$email
                ];

                try {
                    sendMail($details);
                    // Mail::to()->send(new NotificationMail($details));
                } catch (\Throwable $th) {
                    return response()->json(['error' => 'Error sending email', 'message' => $th->getMessage()], 500);
                }

                return $this->successResponse($data, 'Data retrieved successfully.', 200);
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                return response()->json(['error' => 'Something went wrong'], 500);
            }
        } else {
            return response()->json(['error' => 'User not found or notifications disabled'], 404);
        }
    }

    private function clientInvoice($request, $record)
    {
        // ✅ Check-in & Check-out DateTime Calculation
        $checkinDateTime = new \DateTime("{$request->checkin_date} {$request->checkin_time}");
        $checkoutDateTime = new \DateTime("{$request->checkout_date} {$request->checkout_time}");

        // ✅ Calculate Total Duration (Including Days)
        $interval = $checkinDateTime->diff($checkoutDateTime);
        $apptDuration = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
        $apptDuration = max(0, $apptDuration);

        // ✅ Estimated Minimum Duration
        $apptEstDuration = $record->duration;
        $apptTime = $checkinDateTime->format("H:i:s");

        // ✅ Filter Custom Interpreter & Interpretation Rates
        $account = $record->accounts;
        $filteredRates = $account->interpretationRates->first();


        // ✅ Time Category Selection (Normal or After Hours)
        $timeCategory = ($apptTime >= $account->normal_hour_start_time && $apptTime <= $account->normal_hour_end_time)
            ? "normal" : "after";

        // ✅ Appointment Type Selection
        $types = [
            "In Person" => "inperson",
            "VRI" => "vri",
            "OPI" => "opi",
            "OPI On Demand" => "opi"
        ];
        $type = $types[$record->type] ?? null;

        if (!$filteredRates || !$type) return;


        $rateKey = "{$type}_{$timeCategory}_rate";
        $minDurationKey = "{$type}_{$timeCategory}_mins";
        $minDurationUnitKey = "{$type}_{$timeCategory}_mins_time_unit";
        $timeUnitKey = ($type === 'inperson') ? "{$type}_{$timeCategory}_time_unit" : "{$type}_{$timeCategory}_rate_time_unit";

        $rate = $filteredRates[$rateKey] ?? 0;
        $rateUnit = $filteredRates[$timeUnitKey] ?? 'minute';

        $minDuration = $filteredRates[$minDurationKey] ?? 0;
        $minDurationUnit = $filteredRates[$minDurationUnitKey] ?? 'minute';

        $minDurationInMinutes = ($minDurationUnit == 'hour') ? $minDuration * 60 : $minDuration;
        $apptDuration = ($minDurationInMinutes > $apptDuration) ? $minDurationInMinutes : $apptDuration;

        // ✅ Extra Duration Calculation
        $extraDuration = max(0, $apptDuration - $apptEstDuration);
        $incremental = Null;
        if ($apptDuration < $apptEstDuration) {
            $incremental = $account->incremental;
        } else {
            $incremental = Null;
        }
        $rushFee = $account->rush_fee;
        $incrementalDurations = ['minute' => 1, '30_minute' => 30, '1_hour' => 60];

        $finalDuration = 0;
        if ($extraDuration > 0) {
            if ($incremental != Null) {
                $inc = $incrementalDurations[$incremental];
            } else {
                $inc = 0;
            }

            if ($inc == 1) {
                $finalDuration = $apptEstDuration + $extraDuration;
            } else if ($inc == 30) {
                $finalExtra = ceil($extraDuration / 30) * 30;
                $finalDuration = $apptEstDuration + $finalExtra;
            } else if ($inc == 60) {
                $hours = ($extraDuration / 60);
                $finalHours = ceil($hours) * 60;
                $finalDuration = $apptEstDuration + $finalHours;
            }
        } else {
            $finalDuration = $apptEstDuration + $extraDuration;
        }


        $totalAmount = ($rateUnit == 'hour') ? ($finalDuration * ($rate / 60)) : ($finalDuration * $rate);
        if ($finalDuration == 0) $totalAmount = $rate;

        // ✅ Total Hours Calculation (Formatted HH:MM)
        $totalHours = sprintf('%02d:%02d', ($interval->days * 24) + $interval->h, $interval->i);
        $addRushFee = 0.00;
        // ✅ Apply Rush Fee if Appointment is <= 24 Hours
        if (($interval->days * 24 + $interval->h + ($interval->i / 60)) <= 24) {
            $totalAmount = round($totalAmount + $rushFee, 2);
            $addRushFee = $rushFee;
        }
        $extraMileage = $record->appointmentDetails->extra_mileage ?? null;
        $extraMileageRequest = $record->appointmentDetails->extra_mileage_request ?? null;
        if($extraMileageRequest == 1){
            $totalAmount = $totalAmount + $extraMileage;
        }

        // ✅ Final Payload for Invoice
        $payload = [
            'appointment_id' => $record->id,
            'rate' => $rate,
            'rate_unit' => $rateUnit,
            'min_duration' => $minDuration,
            'duration_unit' => $minDurationUnit,
            'incremental' => $incremental,
            'notes' => $request->notes,
            'rush_fee' => $addRushFee,
            'extra_duration' => $finalDuration,
            'total_amount' => $totalAmount,
            'total_hours' => $totalHours
        ];

        // ✅ Save or Update Invoice
        Invoice::updateOrCreate(['appointment_id' => $record->id], $payload);
    }

    private function interpreterPayment($request, $record)
    {
        // ✅ Check-in & Check-out DateTime Calculation
        $checkinDateTime = new \DateTime("{$request->checkin_date} {$request->checkin_time}");
        $checkoutDateTime = new \DateTime("{$request->checkout_date} {$request->checkout_time}");

        // ✅ Calculate Total Duration (Including Days)
        $interval = $checkinDateTime->diff($checkoutDateTime);
        $apptDuration = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
        $apptDuration = max(0, $apptDuration);

        // ✅ Estimated Minimum Duration
        $apptEstDuration = $record->duration;
        $apptTime = $checkinDateTime->format("H:i:s");

        // ✅ Filter Custom Interpreter & Interpretation Rates
        $interpreter = $record->interpreter;
        $filteredRates = $interpreter->interpreterRates;



        // ✅ Time Category Selection (Normal or After Hours)
        $timeCategory = ($apptTime >= $filteredRates->normal_hour_start_time && $apptTime <= $filteredRates->normal_hour_end_time)
            ? "normal" : "after";

        // ✅ Appointment Type Selection
        $types = [
            "In Person" => "inperson",
            "VRI" => "vri",
            "OPI" => "opi",
            "OPI On Demand" => "opi"
        ];
        $type = $types[$record->type] ?? null;

        if (!$filteredRates || !$type) return;


        $rateKey = "{$type}_{$timeCategory}_rate";
        $timeUnitKey = "{$type}_{$timeCategory}_rate_time_unit";
        $minDurationKey = "{$type}_{$timeCategory}_mins";
        $minDurationUnitKey = "{$type}_{$timeCategory}_min_time_unit";

        $rate = $filteredRates[$rateKey] ?? 0;
        $rateUnit = $filteredRates[$timeUnitKey] ?? 'minute';

        $minDuration = $filteredRates[$minDurationKey] ?? 0;
        $minDurationUnit = $filteredRates[$minDurationUnitKey] ?? 'minute';

        $minDurationInMinutes = ($minDurationUnit == 'hour') ? $minDuration * 60 : $minDuration;
        $apptDuration = ($minDurationInMinutes > $apptDuration) ? $minDurationInMinutes : $apptDuration;

        // ✅ Extra Duration Calculation (without incremental and rush fee)
        $extraDuration = max(0, $apptDuration - $apptEstDuration);
        // $finalDuration = $apptEstDuration + $extraDuration;
        $finalDuration = $apptDuration;

        $totalAmount = ($rateUnit == 'hour') ? ($finalDuration * ($rate / 60)) : ($finalDuration * $rate);
        if ($finalDuration == 0) $totalAmount = $rate;



        // ✅ Final Payload for Invoice
        $payload = [
            'payment_user_id' => $record->interpreter->user_id,
            'appt_id' => $record->id,
            'payment' => $totalAmount,
            'status' => 'pending',
            'date' => date('Y-m-d'),
            'user_type' => 'interpreter',
            'rate' => $rate,
            'rate_unit' => $rateUnit,
            'min_duration' => $minDuration,
            'duration_unit' => $minDurationUnit,
            'extra_duration' => $extraDuration,
            'total_hours' => $finalDuration,
            'extra_mileage' => 0
        ];

        // ✅ Save or Update Invoice
        Payment::updateOrCreate(['appt_id' => $record->id], $payload);
    }

    private function vendorPayment($request, $record)
    {
        // ✅ Check-in & Check-out DateTime Calculation
        $checkinDateTime = new \DateTime("{$request->checkin_date} {$request->checkin_time}");
        $checkoutDateTime = new \DateTime("{$request->checkout_date} {$request->checkout_time}");

        // ✅ Calculate Total Duration (Including Days)
        $interval = $checkinDateTime->diff($checkoutDateTime);
        $apptDuration = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
        $apptDuration = max(0, $apptDuration);

        // ✅ Estimated Minimum Duration
        $apptEstDuration = $record->duration;
        $apptTime = $checkinDateTime->format("H:i:s");
        // ✅ Filter Custom Interpreter & Interpretation Rates
        if($record->interpreter){
            $vendor_id = $record->interpreter->vendor_id;
        }else{
            $vendor_id  = $record->vendor_id;
        }
        $vendor = User::where('id', $vendor_id )->first();

        $filteredRates = $vendor->vendorRates;

        // ✅ Time Category Selection (Normal or After Hours)
        $timeCategory = ($apptTime >= $filteredRates->normal_hour_start_time && $apptTime <= $filteredRates->normal_hour_end_time)
            ? "normal" : "after";

        // ✅ Appointment Type Selection
        $types = [
            "In Person" => "inperson",
            "VRI" => "vri",
            "OPI" => "opi",
            "OPI On Demand" => "opi"
        ];
        $type = $types[$record->type] ?? null;

        if (!$filteredRates || !$type) return;


        $rateKey = "{$type}_{$timeCategory}_rate";
        $minDurationKey = "{$type}_{$timeCategory}_mins";
        $minDurationUnitKey = "{$type}_{$timeCategory}_min_time_unit";
        $timeUnitKey = "{$type}_{$timeCategory}_rate_time_unit";

        $rate = $filteredRates[$rateKey] ?? 0;
        $rateUnit = $filteredRates[$timeUnitKey] ?? 'minute';

        $minDuration = $filteredRates[$minDurationKey] ?? 0;
        $minDurationUnit = $filteredRates[$minDurationUnitKey] ?? 'minute';

        $minDurationInMinutes = ($minDurationUnit == 'hour') ? $minDuration * 60 : $minDuration;
        $apptDuration = ($minDurationInMinutes > $apptDuration) ? $minDurationInMinutes : $apptDuration;

        // ✅ Extra Duration Calculation (without incremental and rush fee)
        $extraDuration = max(0, $apptDuration - $apptEstDuration);
        $finalDuration = $apptDuration;

        $totalAmount = ($rateUnit == 'hour') ? ($finalDuration * ($rate / 60)) : ($finalDuration * $rate);
        if ($finalDuration == 0) $totalAmount = $rate;
        
        $extraMileage = $record->appointmentDetails->extra_mileage ?? null;
        $extraMileageRequest = $record->appointmentDetails->extra_mileage_request ?? null;
        if($extraMileageRequest == 1){
            $totalAmount = $totalAmount + $extraMileage;
        }

        // ✅ Final Payload for Invoice
        $payload = [
            'payment_user_id' => $vendor_id,
            'appt_id' => $record->id,
            'payment' => $totalAmount,
            'status' => 'pending',
            'date' => date('Y-m-d'),
            'user_type' => 'vendor',
            'rate' => $rate,
            'rate_unit' => $rateUnit,
            'min_duration' => $minDuration,
            'duration_unit' => $minDurationUnit,
            'extra_duration' => $extraDuration,
            'total_hours' => $finalDuration,
            'extra_mileage' => $extraMileage ?? 0
        ];

        // ✅ Save or Update Invoice
        Payment::updateOrCreate(['appt_id' => $record->id], $payload);
    }

    public function inviteGuests(Request $request, $id)
    {
        $record = Appointment::with(['accounts.user', 'language', 'interpreter.user', 'interpreter.vendor', 'interpreter.interpreterRates', 'appointmentDetails.facility',  'appointmentDetails.patient', 'appointmentDetails.department', 'appointmentAssign', 'vendor'])->find($id);
        if (!$record) {
            throw new \Exception('Appointment Not Found');
        }

        $validator = Validator::make($request->all(), [
            'requester_email' => 'required|array',
            'requester_email.*'=>'required|email|max:255'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $record->appointmentDetails()->update([
            'requester_email' => implode(',',$request->requester_email),
        ]);
        
        // Notify Requester
        try {
            $subject = 'Appointment Invite';
            $content = 'You have been invited for the VRI appointment. We look forward to serving you. Thank you for your trust and support!';
            $redirect_link = config('app.frontend_url').'/appointment-details?token='.$record->token;
            
            $data = [
                'name'=>'Guest',
                'subject'=>$subject,
                'content'=>$content,
                'email'=>$request->requester_email,
                'button_text'=>'View Appointment',
                'redirect_link'=>$redirect_link,
                'recipient'=>$request->requester_email,
            ];
            sendMail($data);
            
        } catch (\Throwable $th) {
        }

        return $this->successResponse([], 'Guests invited successfully.', 200);
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
            ->post('https://api.100ms.live/v2/room-codes/room/'.$room_id.'/role/admin');

        if (!$tokenResponse->ok()) {
            return response()->json(['error' => 'Token creation failed'], 500);
        }

        return $this->successResponse([
                'iframe_url' => "https://".config('app.hms_subdomain').".app.100ms.live/meeting/{$tokenResponse['code']}"
            ], 'room created successfully.', 200);
            
    }
}
