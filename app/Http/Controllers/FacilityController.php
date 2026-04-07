<?php

namespace App\Http\Controllers;

use App\Models\SubClientTypeFacility;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class FacilityController extends Controller
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
            $query = SubClientTypeFacility::with(['city', 'state'])
                ->when($request->user_id, function ($q) use ($request) {
                    $q->where('type_id', $request->user_id);
                });
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('abbreviation', 'like', '%' . $search . '%')
                        ->orWhere('address', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
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
            'abbreviation' => 'required|string|max:100|unique:subclient_types_facilities,abbreviation',
            'address' => 'required|string|max:200',
            'phone' => 'required|numeric|digits_between:10,20',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|string|max:50',
            'zipcode' => 'nullable|numeric',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            $record = SubClientTypeFacility::create([
                'type_id' => $request->user_id,
                'abbreviation' => $request->abbreviation,
                'address' => $request->address,
                'phone' => $request->phone,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'zipcode' => $request->zip_code,
                'status' => $request->status ?? 1,
            ]);
            return $this->successResponse($record, 'Record created successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        $record = SubClientTypeFacility::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'abbreviation' => 'required|string|max:100|unique:subclient_types_facilities,abbreviation,' . $id,
            'address' => 'required|string|max:200',
            'phone' => 'required|numeric|digits_between:10,20',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|string|max:50',
            'zipcode' => 'nullable|numeric',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $record->abbreviation = $request->abbreviation;
            $record->address = $request->address;
            $record->city_id = $request->city_id;
            $record->state_id = $request->state_id;
            $record->zipcode = $request->zip_code;
            $record->phone = $request->phone;
            $record->status = $request->status;
            $record->save();

            return $this->successResponse($record, 'Record updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $record = SubClientTypeFacility::find($id);

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
            'ids.*' => 'exists:subclient_types_facilities,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = SubClientTypeFacility::whereIn('id', $ids)->delete();

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
        $record = SubClientTypeFacility::find($id);

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
