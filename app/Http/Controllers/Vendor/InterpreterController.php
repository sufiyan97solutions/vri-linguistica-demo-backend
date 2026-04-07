<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Interpreter;
use App\Models\InterpreterLanguage;
use App\Models\InterpreterRates;
use App\Models\Language;
use App\Models\User;
use App\Models\vendorInterpreter;
use App\Models\vendorInterpreterLanguage;
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

            $query = Interpreter::with(['user','languages.language', 'city', 'state'])->where('vendor_id', $this->userId);
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%');
                });
            }

            $query->orderBy($sortBy, $sortDirection);

            $data = $query->paginate($pageLength);
            $data->getCollection()->transform(function ($interpreter) {
                if ($interpreter->user && $interpreter->user->email === 'noemail@xyz.com') {
                    $interpreter->user->email = null;
                }
                return $interpreter;
            });


            return $this->successResponse($data, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }

    public function available_interpreters(Request $request)
    {
        try {

            $query = Interpreter::with(['user','languages.language', 'city', 'state'])->where('vendor_id', $this->userId)->whereHas('user', function ($q) {
                $q->where('status', 1);
            });
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%');
                });
            }

            $query->orderBy($sortBy, $sortDirection);

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
            'last_name' => 'nullable|max:150',
            'email' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:20',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|string|max:50',
            'zip_code' => 'nullable|string|max:20',
            'status' => 'boolean',
            'language_ids' => 'array',
            'language_ids.*' => 'exists:languages,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {

            $user = User::create([
                'name' => $request->first_name . ' ' . ($request->last_name ?? ''),
                'email' => $request->email ?? 'noemail@xyz.com',
                'password' => bcrypt(uniqid()),
                'status' => $request->status ?? 1,
                'role' => 'vendor_interpreter',
            ]);

            $record = Interpreter::create([
                'user_id' => $user->id,
                'vendor_id' => $this->userId,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name ?? '',
                'phone' => $request->phone,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'zip_code' => $request->zip_code,
                'address' => $request->address,
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
        'phone' => 'string|max:20',
        'state_id' => 'required|exists:states,id',
        'city_id' => 'required|string|max:50',
        'zip_code' => 'required|string|max:20',
        'status' => 'boolean',
        'language_ids' => 'array',
        'language_ids.*' => 'exists:languages,id',
    ]);

    if ($validator->fails()) {
        return $this->errorResponse('Validation failed', 422, $validator->errors());
    }

    try {
        // update vendor interpreter record
        $record->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name ?? '',
            'phone' => $request->phone,
            'state_id' => $request->state_id,
            'city_id' => $request->city_id,
            'zip_code' => $request->zip_code,
            'address' => $request->address,
        ]);

        // update related user (name, email, status)
        if ($record->user) {
            $record->user->update([
                'name' => $request->first_name . ' ' . ($request->last_name ?? ''),
                'email' => $request->email,
                'status' => $request->status ?? $record->user->status,
            ]);
        }

        // sync languages
        if (!empty($request->language_ids)) {
            $record->languages()->delete();

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
        return $this->successResponse(null, 'Record deleted successfully.');
    }

    public function deleteMultipleRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:vendor_interpreters,id',
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
