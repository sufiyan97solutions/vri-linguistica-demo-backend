<?php

namespace App\Http\Controllers;

use App\Models\SubClientTypeDepartment;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
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
            $query = SubClientTypeDepartment::with(['facility'])
            ->when($request->facility_id, function ($q) use ($request) {
                $q->where('facility_id', $request->facility_id);
            })->when($request->user_id, function ($q) use ($request) {
                $q->where('type_id', $request->user_id);
            });
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('department_name', 'like', '%' . $search . '%')
                        ->orWhere('facility_billing_code', 'like', '%' . $search . '%')
                        ->orWhere('department_billing_code', 'like', '%' . $search . '%');
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
            'facility_id' => 'required|exists:subclient_types_facilities,id',
            'department_name' => 'nullable|string|max:150',
            'facility_billing_code' => 'nullable|string|max:50',
            'department_billing_code' => 'nullable|string|max:50',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            $record = SubClientTypeDepartment::create([
                'type_id' => $request->user_id,
                'facility_id' => $request->facility_id,
                'facility_billing_code' => $request->facility_billing_code,
                'department_billing_code' => $request->department_billing_code,
                'department_name' => $request->department_name,
                'status' => $request->status ?? 1,
            ]);
            return $this->successResponse($record, 'Record created successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        $record = SubClientTypeDepartment::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'facility_id' => 'required|exists:subclient_types_facilities,id',
            'department_name' => 'nullable|string|max:150',
            'facility_billing_code' => 'nullable|string|max:50',
            'department_billing_code' => 'nullable|string|max:50',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $record->facility_id = $request->facility_id;
            $record->department_name = $request->department_name;
            $record->facility_billing_code = $request->facility_billing_code;
            $record->department_billing_code = $request->department_billing_code;
            $record->status = $request->status;
            $record->save();

            return $this->successResponse($record, 'Record updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $record = SubClientTypeDepartment::find($id);

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
            'ids.*' => 'exists:subclient_types_departments,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = SubClientTypeDepartment::whereIn('id', $ids)->delete();

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
        $record = SubClientTypeDepartment::find($id);

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
