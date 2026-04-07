<?php

namespace App\Http\Controllers;

use App\Models\Interpreter;
use App\Models\InterpreterFilter;
use App\Models\InterpreterLanguage;
use App\Models\InterpreterRates;
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
use Illuminate\Support\Facades\Validator;

class InterpreterController extends Controller
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

            $query = Interpreter::with(['user', 'languages.language', 'city', 'state', 'interpreterRates']);
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            }
            $query->whereHas('user', function ($q) {
                $q->whereNot('role', 'vendor_interpreter');
            })
            ->orderBy($sortBy, $sortDirection);
            $data = $query->paginate($pageLength);

            return $this->successResponse($data, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:150',
            'last_name' => 'max:150',
            'email' => 'string|max:150|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|string|max:50',
            'zip_code' => 'required|string|max:20',
            'status' => 'boolean',
            'language_ids' => 'array',
            'language_ids.*' => 'exists:languages,id',
            'is_translator'=>'boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            $imagePath = null;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images'), $imageName);
                $imagePath = 'images/' . $imageName;
            }
            $user = User::create([
                'name' => $request->first_name . ' ' . ($request->last_name ?? ''),
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'status' => $request->status ?? 1,
                'notification' => 1,
                'credentials_send' => 1,
                'role' => 'staff_interpreter',
            ]);

            if ($imagePath) {
                $user->image = $imagePath;
                $user->save();
            }

            $record = Interpreter::create([
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name ?? '',
                'phone' => $request->phone,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'zip_code' => $request->zip_code,
                'address' => $request->address,
                'is_translator'=>$request->is_translator ? 1 :0,
            ]);

            if ($record) {
                if (!empty($request->language_ids)) {
                    foreach ($request->language_ids as $index => $language) {
                        InterpreterLanguage::create([
                            'interpreter_id' => $record->id,
                            'language_id' => $language
                        ]);
                    }
                }

                InterpreterRates::create([
                    'interpreter_id' => $record->id,

                    // Normal Hours
                    'normal_hour_start_time' => $request->normal_hour_start_time,
                    'normal_hour_end_time' => $request->normal_hour_end_time,
                    'after_hour_start_time' => $request->after_hour_start_time,
                    'after_hour_end_time' => $request->after_hour_end_time,
                    // 'grace_period' => $request->grace_period,

                    // Normal Rates
                    'opi_normal_rate' => $request->opi_normal_rate,
                    'vri_normal_rate' => $request->vri_normal_rate,
                    'inperson_normal_rate' => $request->inperson_normal_rate,
                    'opi_normal_rate_time_unit' => $request->opi_normal_rate_time_unit,
                    'vri_normal_rate_time_unit' => $request->vri_normal_rate_time_unit,
                    'inperson_normal_rate_time_unit' => $request->inperson_normal_time_unit,
                    'opi_normal_mins' => $request->opi_normal_mins,
                    'vri_normal_mins' => $request->vri_normal_mins,
                    'inperson_normal_mins' => $request->inperson_normal_mins,
                    'opi_normal_min_time_unit' => $request->opi_normal_mins_time_unit,
                    'vri_normal_min_time_unit' => $request->vri_normal_mins_time_unit,
                    'inperson_normal_min_time_unit' => $request->inperson_normal_mins_time_unit,

                    // After Hours Rates
                    'opi_after_rate' => $request->opi_after_rate,
                    'vri_after_rate' => $request->vri_after_rate,
                    'inperson_after_rate' => $request->inperson_after_rate,
                    'opi_after_rate_time_unit' => $request->opi_after_rate_time_unit,
                    'vri_after_rate_time_unit' => $request->vri_after_rate_time_unit,
                    'inperson_after_rate_time_unit' => $request->inperson_after_time_unit,
                    'opi_after_mins' => $request->opi_after_mins,
                    'vri_after_mins' => $request->vri_after_mins,
                    'inperson_after_mins' => $request->inperson_after_mins,
                    'opi_after_min_time_unit' => $request->opi_after_mins_time_unit,
                    'vri_after_min_time_unit' => $request->vri_after_mins_time_unit,
                    'inperson_after_min_time_unit' => $request->inperson_after_mins_time_unit,

                    // translation rates
                    'spanish_translation_rate'=>$request->spanish_translation_rate,
                    'other_translation_rate'=>$request->other_translation_rate,
                    'spanish_formatting_rate'=>$request->spanish_formatting_rate,
                    'other_formatting_rate'=>$request->other_formatting_rate
                ]);
            }
            $userData = [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $request->password,
            ];

            try {
                sendMail($userData);
            } catch (\Exception $e) {
                return $this->errorResponse('Email could not be sent: ' . $e->getMessage(), 500);
            }
            return $this->successResponse($record, 'Record created successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        $record = Interpreter::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:150',
            'last_name' => 'max:150',
            'email' => 'string|max:150',
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'string|max:20',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|string|max:50',
            'zip_code' => 'required|string|max:20',
            'status' => 'boolean',
            'language_ids' => 'array',
            'language_ids.*' => 'exists:languages,id',
            'is_translator'=>'boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $user = User::find($record->user_id);
            if (!$user) {
                return $this->errorResponse('User not found.', 404);
            }

            // Update user details
            $record->user()->update([
                'name' => $request->first_name . ' ' . ($request->last_name ?? ''),
                'email' => $request->email,
                'status' => $request->status ?? 1,
            ]);

            // Update password if provided
            if (!empty($request->password)) {
                $request->validate([
                    'password' => 'confirmed|min:8', // Password confirmation validation
                ]);
                $record->user()->update([
                    'password' => bcrypt($request->password)
                ]);
            }

            // Handle image upload and deletion
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($user->image) {
                    $existingImagePath = public_path($user->image);
                    if (file_exists($existingImagePath)) {
                        unlink($existingImagePath);
                    }
                }

                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images'), $imageName);
                $user->image = 'images/' . $imageName;
                $user->save();  // Don't forget to save the updated image path
            }

            // Update interpreter details
            $record->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name ?? '',
                'phone' => $request->phone,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'zip_code' => $request->zip_code,
                'address' => $request->address,
                'is_translator'=>$request->is_translator
            ]);

            $record->interpreterRates()->updateOrCreate([
                'interpreter_id' => $record->id,
            ], [
                // Normal Hours
                'normal_hour_start_time' => $request->normal_hour_start_time,
                'normal_hour_end_time' => $request->normal_hour_end_time,
                'after_hour_start_time' => $request->after_hour_start_time,
                'after_hour_end_time' => $request->after_hour_end_time,
                // 'grace_period' => $request->grace_period,

                // Normal Rates
                'opi_normal_rate' => $request->opi_normal_rate,
                'vri_normal_rate' => $request->vri_normal_rate,
                'inperson_normal_rate' => $request->inperson_normal_rate,
                'opi_normal_rate_time_unit' => $request->opi_normal_rate_time_unit,
                'vri_normal_rate_time_unit' => $request->vri_normal_rate_time_unit,
                'inperson_normal_rate_time_unit' => $request->inperson_normal_time_unit,
                'opi_normal_mins' => $request->opi_normal_mins,
                'vri_normal_mins' => $request->vri_normal_mins,
                'inperson_normal_mins' => $request->inperson_normal_mins,
                'opi_normal_min_time_unit' => $request->opi_normal_mins_time_unit,
                'vri_normal_min_time_unit' => $request->vri_normal_mins_time_unit,
                'inperson_normal_min_time_unit' => $request->inperson_normal_mins_time_unit,

                // After Hours Rates
                'opi_after_rate' => $request->opi_after_rate,
                'vri_after_rate' => $request->vri_after_rate,
                'inperson_after_rate' => $request->inperson_after_rate,
                'opi_after_rate_time_unit' => $request->opi_after_rate_time_unit,
                'vri_after_rate_time_unit' => $request->vri_after_rate_time_unit,
                'inperson_after_rate_time_unit' => $request->inperson_after_time_unit,
                'opi_after_mins' => $request->opi_after_mins,
                'vri_after_mins' => $request->vri_after_mins,
                'inperson_after_mins' => $request->inperson_after_mins,
                'opi_after_min_time_unit' => $request->opi_after_mins_time_unit,
                'vri_after_min_time_unit' => $request->vri_after_mins_time_unit,
                'inperson_after_min_time_unit' => $request->inperson_after_mins_time_unit,

                // translation rates
                'spanish_translation_rate'=>$request->spanish_translation_rate,
                'other_translation_rate'=>$request->other_translation_rate,
                'spanish_formatting_rate'=>$request->spanish_formatting_rate,
                'other_formatting_rate'=>$request->other_formatting_rate
            ]);

            // Update languages
            if (!empty($request->language_ids)) {
                // First, delete old languages
                $record->languages()->delete();

                // Add new languages
                foreach ($request->language_ids as $language) {
                    InterpreterLanguage::create([
                        'interpreter_id' => $record->id,
                        'language_id' => $language
                    ]);
                }
            }

            return $this->successResponse($record, 'Record updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }


    public function destroy($id)
    {
        $record = Interpreter::find($id);

        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        $record->user->delete();
        // $record->delete();
        return $this->successResponse(null, 'Record deleted successfully.');
    }

    public function deleteMultipleRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:interpreters,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = Interpreter::whereIn('id', $ids);
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
        $record = Interpreter::find($id);

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
