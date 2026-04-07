<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class StatesController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            $query = State::with('cities'); // Load related cities data
            $search = $request->get('search', '');
            $id = $request->get('id', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if($id){
                $query->where('id', 'like', '%' . $id . '%');
            }
            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            $query->orderBy($sortBy, $sortDirection);

            $states = $query->paginate($pageLength)->through(function ($state) {
                return [
                    'id' => $state->id,
                    'name' => $state->name,
                    'status' => $state->status ? 'active' : 'inactive',
                    'created_at' => date('d-m-Y H:i', strtotime($state->created_at)),
                    'cities' => $state->cities->map(function ($city) {
                        return [
                            'id' => $city->id,
                            'name' => $city->name,
                        ];
                    }),
                ];
            });

            return $this->successResponse($states, 'States retrieved successfully.', 200);

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving states: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150|unique:states,name',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            $state = State::create([
                'name' => $request->name,
                'status' => $request->status ?? 1,
            ]);
            return $this->successResponse($state, 'State created successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        $state = State::find($id);
        if (!$state) {
            return $this->errorResponse('State not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150|unique:states,name,' . $id,
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $state->name = $request->name;
            $state->status = $request->status;
            $state->save();

            return $this->successResponse($state, 'State updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $state = State::find($id);

        if (!$state) {
            return $this->errorResponse('State not found.', 404);
        }

        $state->delete();
        return $this->successResponse(null, 'State deleted successfully.');
    }
    public function deleteMultipleRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:states,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = State::whereIn('id', $ids)->delete();

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
        $user = State::find($id);

        if (!$user) {
            return $this->errorResponse('State not found.', 404);
        }
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user->status = $request->status;
        $user->save();
        return $this->successResponse($user, 'Status Change Successfully.', 200);
    }
}
