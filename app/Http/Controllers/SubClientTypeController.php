<?php

namespace App\Http\Controllers;

use App\Models\ClientProfileTier;
use App\Models\CustomTier;
use App\Models\CustomTierLanguage;
use App\Models\Language;
use App\Models\MinimumDuration;
use App\Models\SubClientType;
use App\Models\SubClientTypeDepartment;
use App\Models\SubClientTypeDynamicFields;
use App\Models\SubClientTypeFacility;
use App\Models\SubClientTypeFilter;
use App\Models\SubClientTypeInterpretationRate;
use App\Models\SubClientTypeSubAccount;
use App\Models\SubclientVriOnDemandLanguage;
use App\Models\SubclientVriPrescheduledLanguage;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SubClientTypeController extends Controller
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
            // $query = SubClientType::query();
            $query = SubClientType::with(['user', 'facilities', 'departments', 'interpretationRates',])->whereHas('user', function ($q) {
                $q->where('status', 1);
            });
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', $search)
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        });
                });
            }
            if ($sortBy === 'name') {
                $query->addSelect([
                    'user_name' => User::select('name')
                        ->whereColumn('users.id', 'subclient_types.user_id')
                        ->limit(1)
                ]);

                $sortBy = 'user_name';
            }
            $query->orderBy($sortBy, $sortDirection);

            $data = $query->paginate($pageLength)->through(function ($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->user?->name,
                    'email' => $row->user?->email,
                    'phone' => $row->phone,
                    'user' => $row->user,
                    'facilities' => $row->facilities,
                    'departments' => $row->departments->map(function ($items) {
                        return [
                            'id' => $items->id,
                            'name' => $items->department_name,
                        ];
                    }),
                    "normal_hour_start_time" => $row->normal_hour_start_time,
                    "normal_hour_end_time" => $row->normal_hour_end_time,
                    "after_hour_start_time" => $row->after_hour_start_time,
                    "after_hour_end_time" => $row->after_hour_end_time,
                    "grace_period" => $row->grace_period,
                    "incremental" => $row->incremental,
                    "rush_fee" => $row->rush_fee,
                    'interpretation_rates' => $row->interpretationRates->first(),
                    'status' => ($row->user?->status) ? 'active' : 'inactive',
                    'created_at' => date('M-d-Y H:i', strtotime($row->created_at)),
                ];
            });

            return $this->successResponse($data, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }

    public function page(Request $request)
    {
        try {
            // $query = SubClientType::query();
            $query = SubClientType::with(['user', 'facilities', 'departments', 'interpretationRates' , 'vriOnDemandLanguages' , 'vriPrescheduledLanguages']);
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        });
                });
            }
            if ($sortBy === 'name') {
                $query->addSelect([
                    'user_name' => User::select('name')
                        ->whereColumn('users.id', 'subclient_types.user_id')
                        ->limit(1)
                ]);

                $sortBy = 'user_name';
            }
            $query->orderBy($sortBy, $sortDirection);

            $data = $query->paginate($pageLength)->through(function ($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->user?->name,
                    'email' => $row->user?->email,
                    'phone' => $row->phone,
                    'vri_on_demand_language_ids' => $row->vriOnDemandLanguages->pluck('language_id'),
                    'vri_prescheduled_language_ids' => $row->vriPrescheduledLanguages->pluck('language_id'),
                    'user' => $row->user,
                    'facilities' => $row->facilities,
                    'departments' => $row->departments->map(function ($items) {
                        return [
                            'id' => $items->id,
                            'name' => $items->department_name,
                        ];
                    }),
                    "normal_hour_start_time" => $row->normal_hour_start_time,
                    "normal_hour_end_time" => $row->normal_hour_end_time,
                    "after_hour_start_time" => $row->after_hour_start_time,
                    "after_hour_end_time" => $row->after_hour_end_time,
                    "grace_period" => $row->grace_period,
                    "incremental" => $row->incremental,
                    "rush_fee" => $row->rush_fee,
                    'interpretation_rates' => $row->interpretationRates->first(),
                    'status' => ($row->user?->status) ? 'active' : 'inactive',
                    'created_at' => date('M-d-Y H:i', strtotime($row->created_at)),
                ];
            });

            return $this->successResponse($data, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving records: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email|max:200',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|numeric|digits_between:10,20',
            'credentials_send' => 'boolean',
            'notifications' => 'boolean',
            'status' => 'boolean',

            'normal_hour_start_time' => 'required|date_format:H:i',
            'normal_hour_end_time' => 'required|date_format:H:i',

            'after_hour_start_time' => 'required|date_format:H:i',
            'after_hour_end_time' => 'required|date_format:H:i',

            'grace_period' => 'nullable|integer|min:0',
            'rush_fee' => 'nullable|numeric|min:0',
            'incremental'  => 'string|in:minute,30_minute,1_hour',

            'opi_normal_rate' => 'numeric|min:0',
            'vri_normal_rate' => 'numeric|min:0',
            'inperson_normal_rate' => 'numeric|min:0',
            'opi_normal_rate_time_unit' => 'string|in:minute,hour',
            'vri_normal_rate_time_unit' => 'string|in:minute,hour',
            'inperson_normal_time_unit' => 'string|in:minute,hour',
            'opi_normal_mins' => 'integer|min:0',
            'vri_normal_mins' => 'integer|min:0',
            'inperson_normal_mins' => 'integer|min:0',
            'opi_normal_mins_time_unit' => 'string|in:minute,hour',
            'vri_normal_mins_time_unit' => 'string|in:minute,hour',
            'inperson_normal_mins_time_unit' => 'string|in:minute,hour',

            'opi_after_rate' => 'numeric|min:0',
            'vri_after_rate' => 'numeric|min:0',
            'inperson_after_rate' => 'numeric|min:0',
            'opi_after_rate_time_unit' => 'string|in:minute,hour',
            'vri_after_rate_time_unit' => 'string|in:minute,hour',
            'inperson_after_time_unit' => 'string|in:minute,hour',
            'opi_after_mins' => 'integer|min:0',
            'vri_after_mins' => 'integer|min:0',
            'inperson_after_min' => 'numeric|min:0',
            'opi_after_mins_time_unit' => 'string|in:minute,hour',
            'vri_after_mins_time_unit' => 'string|in:minute,hour',
            'inperson_after_mins_time_unit' => 'string|in:minute,hour',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {


            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => 'main_account',
                'credentials_send' => $request->credentialsSend ?? 0,
                'notifications' => $request->notifications ?? 1,
                'status' => $request->status ?? 1,
            ]);
            $record = SubClientType::create([
                'user_id' => $user->id,
                'phone' => $request->phone,
                'normal_hour_start_time' => $request->normal_hour_start_time,
                'normal_hour_end_time' => $request->normal_hour_end_time,
                'after_hour_start_time' => $request->after_hour_start_time,
                'after_hour_end_time' => $request->after_hour_end_time,
                'grace_period' => $request->grace_period,
                'rush_fee' => $request->rush_fee,
                'incremental' => $request->incremental ?? 'minute',
            ]);
            if ($record) {

                if (!empty($request->vri_on_demand_language_ids)) {
                    $data = [];
                    foreach ($request->vri_on_demand_language_ids as $langId) {
                        $data[] = [
                            'subclient_id' => $record->id,
                            'language_id' => $langId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    SubclientVriOnDemandLanguage::insert($data);
                }

                if (!empty($request->vri_prescheduled_language_ids)) {
                    $data = [];
                    foreach ($request->vri_prescheduled_language_ids as $langId) {
                        $data[] = [
                            'subclient_id' => $record->id,
                            'language_id' => $langId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    SubclientVriPrescheduledLanguage::insert($data);
                }
                // if (!empty($request->facilities)) {
                //     $facilitiesData = [];

                //     foreach ($request->facilities as $facility) {
                //         $facilitiesData[] = [
                //             'type_id' => $record->id,
                //             'facility_name' => $facility['name'] ?? null,
                //             'street_address' => $facility['address'] ?? null,
                //             'state_id' => $facility['state_id'] ?? null,
                //             'city_id' => $facility['city_id'] ?? null,
                //             'zipcode' => $facility['zipcode'] ?? null,
                //             'created_at' => now(),
                //             'updated_at' => now(),
                //         ];
                //     }

                //     SubClientTypeFacility::insert($facilitiesData);
                // }
                // if (!empty($request->department_names)) {
                //     foreach ($request->department_names as $index => $department) {
                //         SubClientTypeDepartment::create([
                //             'type_id' => $record->id,
                //             'department_name' => $department
                //         ]);
                //     }
                // }
                if (!empty($request->opi_normal_rate)) {
                    SubClientTypeInterpretationRate::create([
                        'subclient_id' => $record->id,
                        'opi_normal_rate' => $request->opi_normal_rate,
                        'vri_normal_rate' => $request->vri_normal_rate,
                        'inperson_normal_rate' => $request->inperson_normal_rate,
                        'opi_normal_rate_time_unit' => $request->opi_normal_rate_time_unit ?? 'hour',
                        'vri_normal_rate_time_unit' => $request->vri_normal_rate_time_unit ?? 'hour',
                        'inperson_normal_time_unit' => $request->inperson_normal_time_unit ?? 'hour',
                        'opi_normal_mins' => $request->opi_normal_mins,
                        'vri_normal_mins' => $request->vri_normal_mins,
                        'inperson_normal_mins' => $request->inperson_normal_mins,
                        'opi_normal_mins_time_unit' => $request->opi_normal_mins_time_unit ?? 'minute',
                        'vri_normal_mins_time_unit' => $request->vri_normal_mins_time_unit ?? 'minute',
                        'inperson_normal_mins_time_unit' => $request->inperson_normal_mins_time_unit ?? 'minute',
                        'opi_after_rate' => $request->opi_after_rate,
                        'vri_after_rate' => $request->vri_after_rate,
                        'inperson_after_rate' => $request->inperson_after_rate,
                        'opi_after_rate_time_unit' => $request->opi_after_rate_time_unit ?? 'hour',
                        'vri_after_rate_time_unit' => $request->vri_after_rate_time_unit ?? 'hour',
                        'inperson_after_time_unit' => $request->inperson_after_time_unit ?? 'hour',
                        'opi_after_mins' => $request->opi_after_mins,
                        'vri_after_mins' => $request->vri_after_mins,
                        'inperson_after_mins' => $request->inperson_after_mins,
                        'opi_after_mins_time_unit' => $request->opi_after_mins_time_unit  ?? 'minute',
                        'vri_after_mins_time_unit' => $request->vri_after_mins_time_unit  ?? 'minute',
                        'inperson_after_mins_time_unit' => $request->inperson_after_mins_time_unit  ?? 'minute',
                        // translation rates
                        'spanish_translation_rate'=>$request->spanish_translation_rate,
                        'other_translation_rate'=>$request->other_translation_rate,
                        'spanish_formatting_rate'=>$request->spanish_formatting_rate,
                        'other_formatting_rate'=>$request->other_formatting_rate
                    ]);
                }
            }
            if ($request->credentialsSend == 1 && $request->status == 1) {
                try {
                    $name = $request->name;
                    $email = $request->email;
                    $password = $request->password;
                    $subject = 'Your Account Credentials';
                    $content = 'Your account has been created successfully. Below are your credentials for logging in:';

                    sendMail([
                        'name' => $name,
                        'subject' => $subject,
                        'content' => $content,
                        'email' => $email,
                        'password' => $password,
                        'recipient'=>$email,
                    ]);
                    // Mail::send('emails.user_created', [
                    //     'name' => $name,
                    //     'subject' => $subject,
                    //     'content' => $content,
                    //     'email' => $email,
                    //     'password' => $password,
                    // ], function ($mail) use ($email, $subject) {
                    //     $mail->to($email)
                    //         ->subject($subject);
                    // });
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                }
            }

            return $this->successResponse($record, 'Record created successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $record = SubClientType::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email,' . $record->user_id,
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|numeric|digits_between:10,20',
            'credentials_send' => 'boolean',
            'notifications' => 'boolean',
            'status' => 'boolean',

            'normal_hour_start_time' => 'required|date_format:H:i',
            'normal_hour_end_time' => 'required|date_format:H:i',
            'after_hour_start_time' => 'required|date_format:H:i',
            'after_hour_end_time' => 'required|date_format:H:i',
            'grace_period' => 'nullable|integer|min:0',
            'rush_fee' => 'nullable|numeric|min:0',
            'incremental'  => 'string|in:minute,30_minute,1_hour',

            'opi_normal_rate' => 'numeric|min:0',
            'vri_normal_rate' => 'numeric|min:0',
            'inperson_normal_rate' => 'numeric|min:0',
            'opi_normal_rate_time_unit' => 'string|in:minute,hour',
            'vri_normal_rate_time_unit' => 'string|in:minute,hour',
            'inperson_normal_time_unit' => 'string|in:minute,hour',
            'opi_normal_mins' => 'integer|min:0',
            'vri_normal_mins' => 'integer|min:0',
            'inperson_normal_mins' => 'integer|min:0',
            'opi_normal_mins_time_unit' => 'string|in:minute,hour',
            'vri_normal_mins_time_unit' => 'string|in:minute,hour',
            'inperson_normal_mins_time_unit' => 'string|in:minute,hour',

            'opi_after_rate' => 'numeric|min:0',
            'vri_after_rate' => 'numeric|min:0',
            'inperson_after_rate' => 'numeric|min:0',
            'opi_after_rate_time_unit' => 'string|in:minute,hour',
            'vri_after_rate_time_unit' => 'string|in:minute,hour',
            'inperson_after_time_unit' => 'string|in:minute,hour',
            'opi_after_mins' => 'integer|min:0',
            'vri_after_mins' => 'integer|min:0',
            'inperson_after_min' => 'numeric|min:0',
            'opi_after_mins_time_unit' => 'string|in:minute,hour',
            'vri_after_mins_time_unit' => 'string|in:minute,hour',
            'inperson_after_mins_time_unit' => 'string|in:minute,hour',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            // Update User
            $record->user()->update([
                'name' => $request->name,
                'email' => $request->email,
                'credentials_send' => $request->credentialsSend ?? 0,
                'notifications' => $request->notifications ?? 1,
                'status' => $request->status ?? 1,
            ]);

            if (!empty($request->password)) {
                $record->user()->update([
                    'password' => bcrypt($request->password)
                ]);
            }

            // Update SubClientType
            $record->update([
                'phone' => $request->phone,
                'normal_hour_start_time' => $request->normal_hour_start_time,
                'normal_hour_end_time' => $request->normal_hour_end_time,
                'after_hour_start_time' => $request->after_hour_start_time,
                'after_hour_end_time' => $request->after_hour_end_time,
                'grace_period' => $request->grace_period,
                'rush_fee' => $request->rush_fee,
                'incremental' => $request->incremental ?? 'minute',
            ]);

            $record->interpretationRates()->update([
                'opi_normal_rate' => $request->opi_normal_rate,
                'vri_normal_rate' => $request->vri_normal_rate,
                'inperson_normal_rate' => $request->inperson_normal_rate,
                'opi_normal_rate_time_unit' => $request->opi_normal_rate_time_unit ?? 'hour',
                'vri_normal_rate_time_unit' => $request->vri_normal_rate_time_unit ?? 'hour',
                'inperson_normal_time_unit' => $request->inperson_normal_time_unit ?? 'hour',
                'opi_normal_mins' => $request->opi_normal_mins,
                'vri_normal_mins' => $request->vri_normal_mins,
                'inperson_normal_mins' => $request->inperson_normal_mins,
                'opi_normal_mins_time_unit' => $request->opi_normal_mins_time_unit ?? 'minute',
                'vri_normal_mins_time_unit' => $request->vri_normal_mins_time_unit ?? 'minute',
                'inperson_normal_mins_time_unit' => $request->inperson_normal_mins_time_unit ?? 'minute',
                'opi_after_rate' => $request->opi_after_rate,
                'vri_after_rate' => $request->vri_after_rate,
                'inperson_after_rate' => $request->inperson_after_rate,
                'opi_after_rate_time_unit' => $request->opi_after_rate_time_unit ?? 'hour',
                'vri_after_rate_time_unit' => $request->vri_after_rate_time_unit ?? 'hour',
                'inperson_after_time_unit' => $request->inperson_after_time_unit ?? 'hour',
                'opi_after_mins' => $request->opi_after_mins,
                'vri_after_mins' => $request->vri_after_mins,
                'inperson_after_mins' => $request->inperson_after_mins,
                'opi_after_mins_time_unit' => $request->opi_after_mins_time_unit  ?? 'minute',
                'vri_after_mins_time_unit' => $request->vri_after_mins_time_unit  ?? 'minute',
                'inperson_after_mins_time_unit' => $request->inperson_after_mins_time_unit  ?? 'minute',
                // translation rates
                'spanish_translation_rate'=>$request->spanish_translation_rate,
                'other_translation_rate'=>$request->other_translation_rate,
                'spanish_formatting_rate'=>$request->spanish_formatting_rate,
                'other_formatting_rate'=>$request->other_formatting_rate
            ]);

            // Delete old
            SubclientVriOnDemandLanguage::where('subclient_id', $record->id)->delete();

            // Insert new
            if (!empty($request->vri_on_demand_language_ids)) {
                $data = [];

                foreach ($request->vri_on_demand_language_ids as $langId) {
                    $data[] = [
                        'subclient_id' => $record->id,
                        'language_id' => $langId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                SubclientVriOnDemandLanguage::insert($data);
            }

            // Delete old
            SubclientVriPrescheduledLanguage::where('subclient_id', $record->id)->delete();

            // Insert new
            if (!empty($request->vri_prescheduled_language_ids)) {
                $data = [];

                foreach ($request->vri_prescheduled_language_ids as $langId) {
                    $data[] = [
                        'subclient_id' => $record->id,
                        'language_id' => $langId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                SubclientVriPrescheduledLanguage::insert($data);
            }

            // // Delete & Insert Facilities
            // SubClientTypeFacility::where('type_id', $record->id)->delete();
            // if (!empty($request->facilities)) {
            //     $facilitiesData = [];

            //     foreach ($request->facilities as $facility) {
            //         $facilitiesData[] = [
            //             'type_id' => $record->id,
            //             'facility_name' => $facility['name'] ?? null,
            //             'street_address' => $facility['address'] ?? null,
            //             'state_id' => $facility['state_id'] ?? null,
            //             'city_id' => $facility['city_id'] ?? null,
            //             'zipcode' => $facility['zipcode'] ?? null,
            //             'created_at' => now(),
            //             'updated_at' => now(),
            //         ];
            //     }

            //     SubClientTypeFacility::insert($facilitiesData);
            // }

            // // Delete & Insert Departments
            // SubClientTypeDepartment::where('type_id', $record->id)->delete();
            // if (!empty($request->department_names)) {
            //     foreach ($request->department_names as $department) {
            //         SubClientTypeDepartment::create([
            //             'type_id' => $record->id,
            //             'department_name' => $department,
            //         ]);
            //     }
            // }

            if ($request->credentialsSend == 1 && $request->status == 1) {
                try {
                    $name = $request->name;
                    $email = $request->email;
                    $password = $request->password;
                    $subject = 'Your Account Credentials';
                    $content = 'Your account has been updated successfully. Below are your credentials for logging in:';

                    
                    sendMail([
                        'name' => $name,
                        'subject' => $subject,
                        'content' => $content,
                        'email' => $email,
                        'password' => $password,
                        'recipient'=>$email,
                    ]);

                    // Mail::send('emails.user_created', [
                    //     'name' => $name,
                    //     'subject' => $subject,
                    //     'content' => $content,
                    //     'email' => $email,
                    //     'password' => $password,
                    // ], function ($mail) use ($email, $subject) {
                    //     $mail->to($email)
                    //         ->subject($subject);
                    // });
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                }
            }

            return $this->successResponse($record, 'Record updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $record = SubClientType::find($id);

        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        $record->user->delete();
        return $this->successResponse(null, 'Record deleted successfully.');
    }

    public function deleteMultipleRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:subclient_types,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = SubClientType::whereIn('id', $ids);
            // Extract the user IDs
            $userIds = $deletedCount->pluck('user_id');

            // Delete users
            User::whereIn('id', $userIds)->delete();

            // if ($deletedCount > 0) {
            return $this->successResponse([], 'Records deleted successfully.', 200);
            // } else {
            //     return $this->errorResponse('No records found to delete.', 404);
            // }
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    public function changeStatus(Request $request, $id)
    {
        $record = SubClientType::find($id);

        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $record->user()->update([
            'status' => $request->status
        ]);
        return $this->successResponse($record, 'Status Change Successfully.', 200);
    }
}
