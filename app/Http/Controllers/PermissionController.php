<?php

namespace App\Http\Controllers;

use App\Models\Access;
use App\Models\Department;
use App\Models\Facility;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\SubUser;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
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
            $query = PermissionGroup::with(['permissions.access', 'createdBy']);
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                });
            }

            $query->orderBy($sortBy, $sortDirection);

            $data = $query->paginate($pageLength);

            return $this->successResponse($data, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving Permission groups: ' . $e->getMessage(), 500);
        }
    }
    public function accesses()
    {
        try {
            $data = Access::get();

            // Group data by page_name
            $groupedData = $data->groupBy('page_name')->map(function ($items, $key) {
                return [
                    'page_name' => $key,
                    'accesses' => $items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'url' => $item->url,
                            'created_at' => $item->created_at,
                            'updated_at' => $item->updated_at
                        ];
                    })->values()
                ];
            })->values();

            return $this->successResponse($groupedData, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving Permission groups: ' . $e->getMessage(), 500);
        }
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'select_all' => 'boolean',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $record = PermissionGroup::create([
                'name' => $request->name,
                'select_all' => $request->select_all ?? 0,
                'created_by_id' => Auth::user()->id,
                'status' => $request->status,
            ]);

            if ($record) {
                if (isset($request->ids)) {
                    foreach ($request->ids as $key => $id) {
                        $data = new Permission();
                        $data->group_id = $record->id;
                        $data->access_id = $id;
                        $data->status = 1;
                        $data->save();
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'select_all' => 'boolean',
            'status' => 'boolean',
            'ids' => 'array'
        ]);
    
        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
    
        try {
            $record = PermissionGroup::findOrFail($id);
    
            $oldPermissions = Permission::where('group_id', $id)->pluck('access_id')->toArray();
            $newPermissions = $request->ids ?? [];
            $removedPermissions = array_diff($oldPermissions, $newPermissions);
            $addedPermissions = array_diff($newPermissions, $oldPermissions);
    
            if (!empty($removedPermissions) || !empty($addedPermissions)) {
                $subUsers = SubUser::where('group_id', $id)->get();
                foreach ($subUsers as $subUser) {
                    $user = User::find($subUser->user_id);
                    if ($user) {
                        $user->tokens()->delete();
                    }
                }
            }
            $record->update([
                'name' => $request->name,
                'select_all' => $request->select_all ?? 0,
                'status' => $request->status,
            ]);
    
            Permission::where('group_id', $id)->delete();
            foreach ($newPermissions as $accessId) {
                Permission::create([
                    'group_id' => $record->id,
                    'access_id' => $accessId,
                    'status' => 1
                ]);
            }
    
            return $this->successResponse($record, 'Record updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during update: ' . $e->getMessage(), 500);
        }
    }
    

    public function destroy($id)
    {
        try {
            $record = PermissionGroup::find($id);
            if (!$record) {
                return $this->errorResponse('Record not found.', 404);
            }
            Permission::where('group_id', $id)->delete();
            $record->delete();
            return $this->successResponse(null, 'Record deleted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting: ' . $e->getMessage(), 500);
        }
    }

    public function deleteMultipleRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:permission_groups,id', // Fix: Correct table name
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            // First, delete related permissions
            Permission::whereIn('group_id', $ids)->delete();

            // Then, delete the permission groups
            $deletedCount = PermissionGroup::whereIn('id', $ids)->delete();

            if ($deletedCount > 0) {
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
        $record = PermissionGroup::find($id);

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

        return $this->successResponse($record, 'Status Change Successfully.', 200);
    }
}
