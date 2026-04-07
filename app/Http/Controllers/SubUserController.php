<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Staff;
use App\Models\StaffLanguage;
use App\Models\SubClient;
use App\Models\SubClientDynamicFields;
use App\Models\SubClientFilter;
use App\Models\SubClientType;
use App\Models\SubUser;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class SubUserController extends Controller
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

            $query = SubUser::with(['user', "group"]);
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('type', 'like', $search . '%');
                    $query->whereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
                });
            }

            $query->orderBy($sortBy, $sortDirection);

            $data = $query->paginate($pageLength);

            return $this->successResponse($data, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving Sub Users: ' . $e->getMessage(), 500);
        }
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'required|string|max:150|unique:users,email',
            'password' => 'required_if:type,agent,remote_operator',
            'gender' => 'required|in:male,female,nonbinary',
            'phone' => 'max:12',
            'group_id' => 'exists:permission_groups,id',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            $record = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password ? bcrypt($request->password) : bcrypt('password'),
                'status' => $request->status,
            ]);
            $staff = SubUser::create([
                'user_id' => $record->id,
                'group_id' => $request->group_id,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'status' => $request->status,
            ]);

            return $this->successResponse($staff, 'Record created successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        $record = SubUser::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'required|string|max:150|unique:users,email,' . $record->user_id,
            'password' => 'required_if:type,agent,remote_operator',
            'gender' => 'required|in:male,female,nonbinary',
            'phone' => 'max:12',
            'group_id' => 'exists:permission_groups,id',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $user = User::find($record->user_id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = $request->password ? bcrypt($request->password) : bcrypt('password');
            $user->status = $request->status;
            $user->save();

            $record->update([
                'group_id' => $request->group_id,
                'gender' => $request->gender,
                'phone' => $request->phone,
                'status' => $request->status,
            ]);

            return $this->successResponse($record, 'Record updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $record = SubUser::find($id);

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
        'ids.*' => 'exists:sub_users,id',
    ]);

    if ($validator->fails()) {
        return $this->errorResponse('Validation failed', 422, $validator->errors());
    }

    try {
        $ids = $validator->validated()['ids'];
        $user_ids = SubUser::whereIn('id', $ids)->pluck('user_id')->toArray();
        $deletedCount = SubUser::whereIn('id', $ids)->delete();
        if ($deletedCount > 0) {
            User::whereIn('id', $user_ids)->delete();
            return $this->successResponse([], 'Records deleted successfully.', 200);
        } else {
            return $this->errorResponse('No records found to delete.', 404);
        }
    } catch (\Exception $e) {
        return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
    }
}


    public function changeStatus(Request $request, $id)
    {
        $record = SubUser::find($id);

        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $record->status = $request->status;
        $record->save();

        $record->user()->update([
            'status' => $request->status
        ]);
        return $this->successResponse($record, 'Status Change Successfully.', 200);
    }
}
