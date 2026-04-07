<?php

namespace App\Http\Controllers;

use App\Jobs\SendTranslationInvoiceJob;
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
use App\Models\Quotation;
use App\Models\SubClient;
use App\Models\SubClientType;
use App\Models\SubClientTypeInterpretationRate;
use App\Models\TierLanguage;
use App\Models\Translation;
use App\Models\TranslationDetail;
use App\Models\TranslationFile;
use App\Models\TranslationInvite;
use App\Models\TranslationInvoice;
use App\Models\TranslationLog;
use App\Models\TranslationTargetLanguage;
use App\Models\TranslationTranslatedFile;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader\PdfReader;
use setasign\Fpdi\PdfReader\PdfReaderException;
use setasign\Fpdi\TcpdfFpdi;
use Smalot\PdfParser\Parser;

class TranslationController extends Controller
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

            $query = Translation::with(['accounts.user', 'language', 'interpreter.user', 'translationDetails','translationTargetLanguages.language','translationFiles','translationInvites']);
            if(auth('api')->user()->role == 'main_account'){
                $query = $query->where('account_id',auth('api')->user()->mainAccount->id);
            }
            if(auth('api')->user()->role == 'staff_interpreter'){
                $interpreterId = auth('api')->user()->interpreter->id ?? null;
                // dd($interpreterId);
                $query = Translation::with([
                    'accounts.user',
                    'language',
                    'interpreter.user',
                    'translationDetails',
                    'translationTargetLanguages.language',
                    'translationFiles',
                    // Only load the current interpreter's invite
                    'translationInvites' => function ($q) use ($interpreterId) {
                        $q->where('interpreter_id', $interpreterId);
                    }
                ]);
                $query = $query->where(function ($q) use ($interpreterId) {
                    $q->where('interpreter_id', $interpreterId)
                        ->orWhere(function($qe) use ($interpreterId){
                            $qe->whereNotIn('status',['Cancelled','Client Cancelled'])->whereNull('interpreter_id')->whereHas('translationInvites', function ($q2) use ($interpreterId) {
                                $q2->where('interpreter_id', $interpreterId)->where('status','!=','declined');
                            }); 
                        });
                });
            }
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

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $interpreterId = $request->get('translator');
            $accountId = $request->get('client');
            $sourceLanguageId = $request->get('source_language');
            $targetLanguageId = $request->get('target_language');
            $status = $request->get('status');

            if ($startDate && $endDate) {
                $startDateFormatted = date('Y-m-d 00:00:00', strtotime($startDate));
                $endDateFormatted = date('Y-m-d 23:59:59', strtotime($endDate));
                $query->whereBetween('created_at', [$startDateFormatted, $endDateFormatted]);
            }
            if (!empty($interpreterId)) {
                $query->where('interpreter_id', $interpreterId);
            }
            if (!empty($accountId)) {
                $query->where('account_id', $accountId);
            }
            if (!empty($sourceLanguageId)) {
                $query->where('source_language_id', $sourceLanguageId);
            }
            if (!empty($targetLanguageId)) {
                $query->whereHas('translationTargetLanguages', function ($q) use ($targetLanguageId) {
                    $q->where('language_id', $targetLanguageId);
                });
            }
            if (!empty($status) && $status != 'all') {
                $query->where('status', $status);
            }


            $query->orderBy($sortBy, $sortDirection);

            $under_review = (clone $query)->where('status', 'Under Review')->count();
            $completed = (clone $query)->where('status', 'Completed')->count();
            $cancelled = (clone $query)->whereIn('status', ['Cancelled','Client Cancelled'])->count();

            $data = $query->paginate($pageLength);
            // $data->appends([
            //     'total'=> $data->total(),
            //     'under_review' => $under_review,
            //     'completed' => $completed,
            //     'cancelled' => $cancelled,
            // ]);

$dataArr = json_decode(json_encode($data), true);
$dataArr['under_review'] = $under_review;
$dataArr['completed'] = $completed;
$dataArr['cancelled'] = $cancelled;

return $this->successResponse($dataArr, 'Data retrieved successfully.', 200);
            // $dataArr = $data->toArray();
            // $dataArr['under_review'] = $under_review;
            // $dataArr['completed'] = $completed;
            // $dataArr['cancelled'] = $cancelled;

            // $data->under_review = $under_review;
        //     return $this->successResponse([
        //     'data' => $data,
        //     'total' => $data->total(),
        //     'under_review' => $under_review,
        //     'completed' => $completed,
        //     'cancelled' => $cancelled,
        // ], 'Data retrieved successfully.', 200);
            return $this->successResponse($dataArr, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }


    public function get($id)
    {
        try {
            $record = Translation::with([
                'accounts.user', 'language', 'interpreter.user', 'translationDetails.cancelledBy',
                'translationTargetLanguages.language', 'translationFiles', 'quotations.createdBy', 'quotations.rejectedBy', 'quotations.approvedBy','translationInvoices.updatedBy','translationTranslatedFiles.uploadedBy','translationTranslatedFiles.approvedBy','translationTranslatedFiles.rejectedBy'
            ]);
            if(auth('api')->user()->role == 'main_account'){
                $record = $record->where('account_id',auth('api')->user()->mainAccount->id);
            }
            if(auth('api')->user()->role == 'staff_interpreter'){
                $interpreterId = auth('api')->user()->interpreter->id ?? null;
                $record = Translation::with([
                    
                'accounts.user', 'language', 'interpreter.user', 'translationDetails.cancelledBy',
                'translationTargetLanguages.language', 'translationFiles', 'quotations.createdBy', 'quotations.rejectedBy', 'quotations.approvedBy','translationInvoices.updatedBy','translationTranslatedFiles.uploadedBy','translationTranslatedFiles.approvedBy','translationTranslatedFiles.rejectedBy',
            
                    // Only load the current interpreter's invite
                    'translationInvites' => function ($q) use ($interpreterId) {
                        $q->where('interpreter_id', $interpreterId);
                    }
                ]);
                $record = $record->where(function ($q) use ($interpreterId) {
                    $q->where('interpreter_id', $interpreterId)
                      ->orWhereHas('translationInvites', function ($q2) use ($interpreterId) {
                          $q2->where('interpreter_id', $interpreterId)->where('status','!=','declined');
                      });
                });
            }
            $record = $record->find($id);
            if (!$record) {
                throw new \Exception('Translation Request Not Found');
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

            $record = TranslationLog::with(['user'])->where('translation_id', $id)->orderBy('id','desc');
            // if(auth('api')->user()->role == 'main_account'){
            //     $record = $record->whereHas('translation',function($q){
            //         $q->where('account_id',auth('api')->user()->mainAccount->id);
            //     });
            // }
            // if(auth('api')->user()->role == 'staff_interpreter'){
            //     $interpreterId = auth('api')->user()->interpreter->id ?? null;
            //     $record = TranslationLog::with(['user'])->whereHas('translation', function ($q) use ($interpreterId) {
            //         $q->where(function ($q2) use ($interpreterId) {
            //             $q2->where('interpreter_id', $interpreterId)
            //               ->orWhereHas('translationInvites', function ($q3) use ($interpreterId) {
            //                   $q3->where('interpreter_id', $interpreterId)->where('status','!=','declined');
            //               });
            //         });
            //     });
            //     // $record = $record->where(function ($q) use ($interpreterId) {
            //     //     $q->where('interpreter_id', $interpreterId)
            //     //       ->orWhereHas('translationInvites', function ($q2) use ($interpreterId) {
            //     //           $q2->where('interpreter_id', $interpreterId)->where('status','!=','declined');
            //     //       });
            //     // });
            // }
            $record = $record->get();
            // dd($record);
            // if(!$record){
            //     throw new \Exception('Appointment Not Found');  
            // }

            return $this->successResponse($record, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving: ' . $e->getMessage(), 500);
        }
    }


    public function getAllInterpreters(Request $request)
    {
        try {
            $records = Interpreter::where('is_translator', 1)
                ->with(['languages.language','user'])
                ->whereHas('user',function($q){
                    $q->where('status',1);
                });
            $search = $request->get('search', '');
            if ($search) {
                $records->where(function ($query) use ($search) {
                    $query->whereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        });
                });
            }
            $records = $records->orderBy('first_name', 'asc');

            $data = $records->paginate(20);

            return $this->successResponse($data, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving: ' . $e->getMessage(), 500);
        }
    }

    public function getInterpreters($id)
    {
        try {
            $record = Translation::find($id);
            if (!$record) {
                return $this->errorResponse('Record not found.', 404);
            }

            // 1. Collect required language IDs (unique)
            $requiredLanguageIds = array_unique(array_merge(
                [$record->source_language_id],
                $record->translationTargetLanguages->pluck('language_id')->toArray()
            ));

            
            // 2. Query interpreters who have all required languages (unique count)
            $records = Interpreter::where('is_translator', 1)
                ->with(['languages.language','user'])
                ->whereHas('languages', function ($q) use ($requiredLanguageIds) {
                    $q->whereIn('language_id', $requiredLanguageIds);
                }, '=', count($requiredLanguageIds))
                ->orderBy('first_name', 'asc')
                ->get();

            // $records = Interpreter::where('is_translator',1)->with(['languages.language'])
            //     ->whereHas('languages', function ($q) use ($record) {
            //         $q->where('language_id', $record->language_id);
            //     })->orderBy('first_name', 'asc')->get();
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
            $record = Translation::find($id);
            if (!$record) {
                return $this->errorResponse('Record not found.', 404);
            }

            $record->load('translationInvites.interpreter.user','translationInvites.interpreter.languages.language');

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
        $record = Translation::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }


        $validator = Validator::make($request->all(), [
            'translator_ids' => 'required|array',
            'translator_ids.*' => 'exists:interpreters,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            if (!empty($request->translator_ids)) {
                $inv_ids = [];
                $terps = [];
                foreach ($request->translator_ids as $key => $interpreter) {
                    $appInv = TranslationInvite::updateOrCreate([
                        'translation_id' => $record->id,
                        'interpreter_id' => $interpreter,
                    ], [
                        'translation_id' => $record->id,
                        'interpreter_id' => $interpreter,
                        'status'=>'pending',
                        'token' => gen_uuid(),
                        'invited_at' => date('Y-m-d H:i:s'),
                    ]);
                    $inv_ids[] = $appInv->id;
                    $terps[] = $appInv->interpreter->user->name;
                    
                    
                    $subject = 'Translation Invitation';
                    $content = 'New translation invitation has been received. Please check your portal for details.';
                    $redirect_link = config('app.frontend_url') . '/interpreter/translations/view/' . $appInv->id;
                    
                    $email = $appInv->interpreter->user->email;
                    $details = [
                        'name' => $appInv->interpreter->user->name,
                        'subject' => $subject,
                        'content' => $content,
                        'redirect_link' => $redirect_link,
                        'recipient'=>$email,
                        'email'=>$email
                    ];

                    sendMail($details);
                }

                $log_changes = 'Invited translators: ';
                $log_changes .= "\n" . implode(', ', $terps);

                TranslationLog::create([
                    'translation_id' => $record->id,
                    'date' => date('Y-m-d'),
                    'time' => date('h:i a'),
                    'user_id' => $this->userId,
                    'event' => 'Invited Translators',
                    'notes' => $log_changes,
                ]);

                $record->status = 'Translators Invited';
                $record->save();
            }

            return $this->successResponse([], 'Translators invited successfully', 200);
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
        // dd($request->all());
        if(auth('api')->user()->role == 'admin'){
            $validator = Validator::make($request->all(), [
                'account_id' => 'required|exists:subclient_types,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }
        }
        $validator = Validator::make($request->all(), [
            'source_language_id' => 'required|exists:languages,id',
            'target_languages' => 'required|array',
            'target_languages.*' => 'required|exists:languages,id',
            'requester_name' => 'required|string|max:100',
            'requester_phone' => 'nullable|string|max:20',
            'requester_email' => 'nullable|email',
            'formatting'=>'boolean',
            'rush'=>'boolean',
            'comment'=>'nullable|max:500',
            'files' => 'required|array|min:1',
            'files.*.file' => 'required|file|mimes:pdf,docx,jpg,jpeg,png|max:10240',
            'files.*.password' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            
            $translation = Translation::create([
                'account_id' => $request->account_id ?? auth('api')->user()->mainAccount->id,
                'source_language_id' => $request->source_language_id,
                'requester_name' => $request->requester_name,
                'status' => 'New Request',
            ]);

            // $prefix = 'T0000';
            $uniqueNumber = str_pad($translation->id, 4, '0', STR_PAD_LEFT);
            $appointmentNumber = 'T' . $uniqueNumber;
            $translation->transid = $appointmentNumber;
            $translation->save();

            $translation_details = TranslationDetail::create([
                'translation_id'=>$translation->id,
                'due_date'=>date('Y-m-d',strtotime($request->due_date)),
                'requester_phone'=>$request->requester_phone,
                'requester_email'=>$request->requester_email,
                'comment'=>$request->comment,
                'formatting'=>$request->formatting?1:0,
                'rush'=>$request->rush?1:0
            ]);

            foreach ($request->target_languages as $target_lang) {
                TranslationTargetLanguage::create([
                    'translation_id'=>$translation->id,
                    'language_id'=>$target_lang,
                ]);
            }
            $overallWordCount=0;


            foreach ($request->files as $fi) {
                foreach ($fi as $index => $entry) {
                    // $entry = $entry[0];
                    // $file = $entry['file'];
                    $file = $request->file("files.$index.file");
                    // dd($file);
                    $password = $request->input("files.$index.password");
                    // dd($password);
                    $extension = strtolower($file->getClientOriginalExtension());
                    // dd($extension);

                    $filePath = $file->store('uploads/'.$translation->transid,'public');
    
                    try {
                        switch ($extension) {
                            case 'pdf':
                                try {
                                    $text = $this->extractFromPDF('storage/'.$filePath,$password);
                                } catch (\Throwable $th) {
                                    throw new \Exception($th->getMessage());
                                }
                                
                                break;
    
                            case 'docx':
                                // PhpWord doesn't support password-protected DOCX files
                                // Just try parsing and catch failure
                                try {
                                    $phpWord = \PhpOffice\PhpWord\IOFactory::load(storage_path("app/public/$filePath"));
                                } catch (\Exception $e) {
                                    throw new \Exception('Password-protected DOCX cannot be processed.');
                                }
    
                                $text = '';
                                foreach ($phpWord->getSections() as $section) {
                                    foreach ($section->getElements() as $element) {
                                        if (method_exists($element, 'getText')) {
                                            $text .= $element->getText() . ' ';
                                        }
                                    }
                                }
                                break;
    
                            case 'jpg':
                            case 'jpeg':
                            case 'png':
                                if(config('app.env') == 'local'){
                                    $text = (new \thiagoalessio\TesseractOCR\TesseractOCR(storage_path("app/public/$filePath")))
                                        ->executable('C:\Program Files\Tesseract-OCR\tesseract.exe')
                                        ->run();
                                }
                                else{
                                    $text = (new \thiagoalessio\TesseractOCR\TesseractOCR(storage_path("app/public/$filePath")))
                                        ->run();
                                }
                                // dd($text);
                                break;
    
                            default:
                                throw new \Exception('Unsupported file type');
                        }
    
                        $wordCount= str_word_count(strip_tags($text));
                        $overallWordCount +=$wordCount;
                        // $results[] = [
                        //     'filename' => $file->getClientOriginalName(),
                        //     'word_count' => $wordCount,
                        //     'status' => 'processed',
                        // ];
                        
                        TranslationFile::create([
                            'translation_id'=>$translation->id,
                            'original_file'=>'storage/'.$filePath,
                            'original_file_name'=>$file->getClientOriginalName(),
                            // 'original_file'=>asset('storage/uploads/'.$translation->transid.'/'.$file->getClientOriginalName()),
                            'password'=>$password,
                            'word_count'=>$wordCount,
                            'file_status'=>'Pending',
                            'amount'=>$wordCount
                        ]);
    
                    } catch (\Exception $e) {
                        $results[] = [
                            'filename' => $file->getClientOriginalName(),
                            'error' => $e->getMessage(),
                            'status' => 'error',
                        ];
                        throw new \Exception($e->getMessage());
                    }
                }
            }
            // foreach ($request->files as $files) {
            //     TranslationFile::create([
            //         'translation_id'=>$translation->id,
            //         'original_file'=>,
            //         'translated_file',
            //         'file_status'
            //     ]);
            // }

            TranslationLog::create([
                'translation_id' => $translation->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'New Translation Request',
                'user_id' => $this->userId,
                'notes' => 'New translation request created',
            ]);

            $translation->total_words_count = $overallWordCount;
            $translation->save();

            $translation = $translation->load('translationDetails','translationTargetLanguages.language','language','translationFiles','accounts.user');

            // Dispatch job to send quotation PDF to client
            $clientUser = $translation->accounts->user ?? null;
            if ($clientUser && $clientUser->email) {
                $clientName = $clientUser->name;
                $clientEmail = $clientUser->email;
                // Prepare cost table and notes for quotation
                $costTable = generateQuotationCostTable($translation); // Restore cost table on initial creation
                $notes = 'Automated quote sent to the client from by the system';
                $version = 1; // First version for new translation
                // Store initial quotation in the database
                $quotation = \App\Models\Quotation::create([
                    'translation_id' => $translation->id,
                    'version' => $version,
                    'notes' => 'Automated quote sent to the client from by the system',
                    'status' => 'initital',
                    'created_by' => $this->userId,
                    'cost_table' => json_encode($costTable),
                ]);
                $grandTotal = collect($costTable)->sum(function($row) {
                        return isset($row['total']) ? (float)$row['total'] : 0;
                    });
                $translation->total_amount = $grandTotal;
                $translation->save();

                \App\Jobs\SendQuotationJob::dispatch($translation, $clientName, $clientEmail, $costTable, $notes, $version);

                $log_changes = 'Automated quote sent to the client';
                TranslationLog::create([
                    'translation_id' => $translation->id,
                    'date' => date('Y-m-d'),
                    'time' => date('h:i a'),
                    'user_id' => $this->userId,
                    'event' => 'Quote Sent',
                    'notes' => $log_changes,
                ]);
            }

            // if ($appointment->type == 'OPI On Demand') {
            //     $this->initiateCallInvite($appointment->appid);
            //     return $this->successResponse($appointment, 'Appointment created successfully with appid ' . $appointment->appid . ". And call initiated to invite Interpreters.", 200);
            // } else {
                // return $this->successResponse($appointment, 'Appointment created successfully with appid '.$appointment->appid, 200);
                // $subject = 'Appointment Successfully Created';
                // $content = 'Your appointment has been created successfully. We look forward to serving you. Thank you for your trust and support!';
                // $redirect_link = config('app.frontend_url').'/appointments/view/'.$appointment->id;
                // $this->notifications($request->account_id, $subject, $content,$redirect_link);

                // Notify Requester
                try {
                    // $subject = 'Quotation Received - AlgoviCRM';
                    // $content = 'Your Document Translation request has been received. Please find the attached Quote Your appointment has been created successfully for '.$request->type.'. We look forward to serving you. Thank you for your trust and support!';
                    // // $redirect_link = config('app.frontend_url').'/appointment-details?token='.$appointment->token;
                    
                    // $data = [
                    //     'name'=>$request->requester_name,
                    //     'subject'=>$subject,
                    //     'content'=>$content,
                    //     'email'=>$request->requester_email,
                    //     // 'button_text'=>'View Appointment',
                    //     // 'redirect_link'=>$redirect_link,
                    //     'recipient'=>$request->requester_email,
                    //     'quotation'=>$translation->id
                    // ];
                    // sendMail($data);
                    
                } catch (\Throwable $th) {
                }

                return $this->successResponse($translation, 'Translation created successfully with ID #' . $translation->transid, 200);
            // }
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }



    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'language_id' => 'required|exists:languages,id',
            'date' => 'required|date',
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
            'requester_name' => 'nullable|string|max:100',
            'requester_phone' => 'nullable|string|max:20',
        ]);
    
        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
    
        try {
            // $this->formatBirthDate($request);
            $userId = auth('api')->user()->id;
    
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
    
            if (!empty($request->mrn_number)) {
                $patient = Patients::where('mrn_number', $request->mrn_number)->first();
                if ($patient) {
                    $patient->update([
                        'patient_name' => $request->patient_name,
                        'birth_date' => $request->birth_date,
                        'medicaid_id' => $request->medicaid_id,
                        // 'provider_name' => $request->provider_name,
                        'medicaid_plan' => $request->medicaid_plan,
                        'updated_by' => $userId,
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
            }
    
            $appointmentDetail = $appointment->appointmentDetails;
            $appointmentDetail->update([
                'requester_name' => $request->requester_name,
                'requester_email' => $request->requester_email,
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
            ]);
    
            if ($request->vendor_id) {
                $appointment->status = 'pending';
            } else if ($request->interpreter_id) {
                $appointment->status = 'assigned';
    
                if ($request->checkin_date && $request->checkin_time) {
                    $appointment->status = 'completed';
                    $appointment->appointmentAssign()->updateOrCreate(
                        ['appointment_id' => $appointment->id],
                        [
                            'interpreter_id' => $request->interpreter_id,
                            'checkin_date' => $request->checkin_date,
                            'checkin_time' => $request->checkin_time,
                            'checkout_date' => $request->checkout_date,
                            'checkout_time' => $request->checkout_time,
                            'comments' => $request->comments,
                            'notes' => $request->notes,
                        ]
                    );
                    $this->clientInvoice($request, $appointment);
                    $this->interpreterPayment($request, $appointment);
                }
            } else {
                $appointment->status = 'open';
            }
    
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
        $incremental = $account->incremental;
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

    private function extractFromPDF($fullPath,$password=null){
        // Escape input
        $escapedPath = escapeshellarg($fullPath);
        $escapedPassword = escapeshellarg($password);

        // Try reading without password
        $command = "pdftotext -layout $escapedPath -";
        $output = shell_exec($command);

        // Check if it failed due to protection
        if (empty($output)) {
            if ($password) {
                // Try with password
                $commandWithPassword = "pdftotext -layout -opw $escapedPassword $escapedPath -";
                $output = shell_exec($commandWithPassword);

                if (empty($output)) {
                    throw new \Exception("Invalid password or unable to read protected PDF.");
                }
            } else {
                throw new \Exception("PDF is likely password protected. Please provide a password.");
            }
        }

        return $output;
    }

    public function accept_invite(Request $request, $id)
    {
        $record = Translation::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        
        try {
            $invite = TranslationInvite::where('translation_id',$record->id)->where('interpreter_id',auth('api')->user()->interpreter->id)->first();
            if (!$invite) {
                return $this->errorResponse('Record not found.', 404);
            }
            
            $invite->status = 'accepted';
            $invite->save();

            $log_changes = 'Translation invite accepted by ' . $invite->interpreter->user->name;

            TranslationLog::create([
                'translation_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Translation Invite Accepted',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);
            
            return $this->successResponse([], 'Translation invite accepted successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while accepting the invite: ' . $e->getMessage(), 500);
        }
    }

    public function decline_invite(Request $request, $id)
    {
        $record = Translation::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|max:500'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        
        try {
            $invite = TranslationInvite::where('translation_id',$record->id)->where('interpreter_id',auth('api')->user()->interpreter->id)->first();
            if (!$invite) {
                return $this->errorResponse('Record not found.', 404);
            }
            
            $invite->status = 'declined';
            $invite->notes = $request->reason;
            $invite->save();

            $log_changes = 'Translation invite declined by ' . $invite->interpreter->user->name;

            TranslationLog::create([
                'translation_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Translation Invite Declined',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);
            
            return $this->successResponse([], 'Translation invite declined successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while accepting the invite: ' . $e->getMessage(), 500);
        }
    }

    
    public function assign_translator(Request $request, $id)
    {
        $record = Translation::find($id);
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

            $record->status = 'Assigned';
            $record->interpreter_id = $request->interpreter_id;
            $record->save();
            
            $log_changes = 'Translation has been assigned to ' . $record->interpreter->user->name;

            TranslationLog::create([
                'translation_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Translation Assigned',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);
            
            return $this->successResponse([], 'Translator assigned successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while accepting the invite: ' . $e->getMessage(), 500);
        }
    }
    
    public function upload_translated_files(Request $request, $id)
    {
        $record = Translation::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:zip,zipx,pdf,doc,docx,txt|max:20480', // 20MB max
        ]);
        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        
        try {

            $file = $request->file("file");
            $extension = strtolower($file->getClientOriginalExtension());
            // dd($extension);

            $filename = $file->getClientOriginalName();
            $filePath = $file->store('translated_files/'.$record->transid,'public');

            $record->status = 'Under Review';
            $record->translated_files = $filePath;
            $record->save();
            
            $log_changes = 'Translated files have been submitted by ' . auth('api')->user()->name;

            TranslationLog::create([
                'translation_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Translated Files Submitted',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            $translated_files = TranslationTranslatedFile::create(
                [
                    'translation_id'=>$record->id,
                    'file_name'=>$filename,
                    'file_path'=>'storage/'.$filePath,
                    'uploaded_by'=> auth('api')->user()->id,
                ]
            );
            
            return $this->successResponse([], 'File submitted successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while accepting the invite: ' . $e->getMessage(), 500);
        }
    }
    
    public function approve_submission(Request $request, $id)
    {
        $record = Translation::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        
        try {

            $record->status = 'Invoice Sent';
            // $record->translated_files = $filePath;
            $record->save();
            
            $log_changes = 'Submission has been approved by '.auth('api')->user()->name;

            TranslationLog::create([
                'translation_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Submission Accepted',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            // Send invoice email to client
            $clientUser = $record->accounts->user ?? null;
            if ($clientUser && $clientUser->email) {
                $clientName = $clientUser->name;
                $clientEmail = $clientUser->email;
                $last_quotation = Quotation::where('translation_id', $record->id)->orderBy('id', 'desc')->first();
                if($last_quotation){
                    $costTable = json_decode($last_quotation->cost_table,true);
                }
                else{
                    $costTable = generateQuotationCostTable($record); 
                }
                // Use same cost table logic
                try {
                    // \Mail::to($clientEmail)->send(new \App\Mail\TranslationInvoiceMail($record, $clientName, $clientEmail, $costTable));
                    $grandTotal = collect($costTable)->sum(function($row) {
                        return isset($row['total']) ? (float)$row['total'] : 0;
                    });
                    
                    $translated_file = TranslationTranslatedFile::where('translation_id',$record->id)->orderBy('id','desc')->first();
                    if($translated_file){
                        $translated_file->update([
                            'approved_by'=> auth('api')->user()->id,
                            'approved_at'=> date('Y-m-d H:i:s'),
                            'status'=>'Approved'
                        ]);
                    } 
                    

                    $invoice = TranslationInvoice::updateOrCreate(
                        ['translation_id' => $record->id],
                        [
                            'translation_id' => $record->id,
                            'account_id' => $record->account_id,
                            'interpreter_id' => $record->interpreter_id,
                            'amount' => $grandTotal,
                            'invoice_number'=> 'INV-'.str_pad($record->id, 6, '0', STR_PAD_LEFT),
                        ]
                    );
                    dispatch(new SendTranslationInvoiceJob($record, $clientName, $clientEmail, $costTable, 0));
                } catch (\Exception $e) {
                    \Log::error('Invoice email failed: ' . $e->getMessage());
                }
            }
            
            return $this->successResponse([], 'Submission approved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while accepting the invite: ' . $e->getMessage(), 500);
        }
    }
    
    public function reject_submission(Request $request, $id)
    {
        $record = Translation::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        
        try {

            $record->status = 'Submission Rejected';
            // $record->translated_files = $filePath;
            $record->save();
            
            $log_changes = 'Submission has been rejected by '.auth('api')->user()->name;

            TranslationLog::create([
                'translation_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Submission Rejected',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            $translated_file = TranslationTranslatedFile::where('translation_id',$record->id)->orderBy('id','desc')->first();
            if($translated_file){
                $translated_file->update([
                    'rejected_by'=> auth('api')->user()->id,
                    'rejected_at'=> date('Y-m-d H:i:s'),
                    'status'=>'Rejected',
                    'rejection_reason'=> $request->reason ?? 'No reason provided'
                ]);
            } 
            
            
            return $this->successResponse([], 'Submission rejected successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while accepting the invite: ' . $e->getMessage(), 500);
        }
    }
    
    public function change_invoice_status(Request $request, $id)
    {
        $record = Translation::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        
        try {

            $record->status = 'Completed';
            // $record->translated_files = $filePath;
            $record->save();
            
            $log_changes = 'Invoice marked as paid by '.auth('api')->user()->name;

            TranslationLog::create([
                'translation_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Invoice Paid',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            
            $log_changes = 'Translation request completed by '.auth('api')->user()->name;

            TranslationLog::create([
                'translation_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Request Completed',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);

            // Invoice Sent code here
            
            // Send invoice email to client
            $clientUser = $record->accounts->user ?? null;
            if ($clientUser && $clientUser->email) {
                $clientName = $clientUser->name;
                $clientEmail = $clientUser->email;
                $costTable = generateQuotationCostTable($record); // Use same cost table logic
                try {

                    $grandTotal = collect($costTable)->sum(function($row) {
                        return isset($row['total']) ? (float)$row['total'] : 0;
                    });
                    $invoice = TranslationInvoice::updateOrCreate(
                        ['translation_id' => $record->id],
                        [
                            'translation_id' => $record->id,
                            'account_id' => $record->account_id,
                            'interpreter_id' => $record->interpreter_id,
                            'amount' => $grandTotal,
                            'invoice_number'=> 'INV-'.str_pad($record->id, 6, '0', STR_PAD_LEFT),
                            'status' => 'paid',
                            'updated_by' => auth('api')->user()->id,
                            'paid_at' => date('Y-m-d H:i'),
                        ]
                    );

                    dispatch(new SendTranslationInvoiceJob($record, $clientName, $clientEmail, $costTable, 1));
                    // \Mail::to($clientEmail)->send(new \App\Mail\TranslationInvoiceMail($record, $clientName, $clientEmail, $costTable, 1));
                } catch (\Exception $e) {
                    \Log::error('Invoice email failed: ' . $e->getMessage());
                }
            }
            
            return $this->successResponse([], 'Invoice marked as paid successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while accepting the invite: ' . $e->getMessage(), 500);
        }
    }
    
    public function cancel(Request $request, $id)
    {
        $record = Translation::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        
        try {

            if(auth('api')->user()->role == 'admin'){
                $record->status = 'Cancelled';
            }
            else{
                $record->status = 'Client Cancelled';
            }
            // $record->translated_files = $filePath;
            $record->save();

            $record->translationDetails()->update([
                'cancel_reason' => $request->reason,
                'cancelled_by' => auth('api')->user()->id,
                'cancelled_at' => date('Y-m-d H:i:s'),
            ]);
            
            $log_changes = 'Transation request cancelled by '.auth('api')->user()->name;

            TranslationLog::create([
                'translation_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Request Cancelled',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);
            
            return $this->successResponse([], 'Translation cancelled successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while accepting the invite: ' . $e->getMessage(), 500);
        }
    }
    
    public function decline_translation(Request $request, $id)
    {
        $record = Translation::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        
        try {

            $record->status = 'Translator Declined';
            // $record->translated_files = $filePath;
            $record->save();

            $record->translationDetails()->update([
                'translation_decline_reason' => $request->reason,
            ]);
            
            $log_changes = 'Transation request declined by the translator: '.auth('api')->user()->name .' with the reason: ' . $request->reason;

            TranslationLog::create([
                'translation_id' => $record->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'event' => 'Request Declined By Translator',
                'user_id' => $this->userId,
                'notes' => $log_changes,
            ]);
            
            return $this->successResponse([], 'Translation declined successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while accepting the invite: ' . $e->getMessage(), 500);
        }
    }
}
