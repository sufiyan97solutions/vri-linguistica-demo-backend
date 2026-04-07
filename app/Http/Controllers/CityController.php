<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            $query = City::with('state');  // Load state relationship
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

            $cities = $query->paginate($pageLength)->through(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'status' => $city->status ? 'active' : 'inactive',
                    'created_at' => date('d-m-Y H:i', strtotime($city->created_at)),
                    'state' => $city->state ? [
                        'id' => $city->state->id,
                        'name' => $city->state->name,
                        'status' => $city->state->status ? 'active' : 'inactive',
                        'created_at' => date('d-m-Y H:i', strtotime($city->state->created_at)),
                    ] : null,  // Handle null state
                ];
            });

            return $this->successResponse($cities, 'Cities retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150|unique:cities,name',
            'status' => 'boolean',
            'state_id' => 'required|exists:states,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $city = City::create([
                'name' => $request->name,
                'status' => $request->status ?? 1,
                'state_id' => $request->state_id,
            ]);

            $city->load('state');

            $cityData = [
                'id' => $city->id,
                'name' => $city->name,
                'status' => $city->status ? 'Active' : 'Inactive',
                'created_at' => date('d-m-Y H:i', strtotime($city->created_at)),
                'state' => [
                    'id' => $city->state->id,
                    'name' => $city->state->name,
                    'status' => $city->state->status ? 'Active' : 'Inactive',
                    'created_at' => date('d-m-Y H:i', strtotime($city->state->created_at)),
                ],
            ];

            return $this->successResponse($cityData, 'City created successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }



    public function update(Request $request, $id)
    {
        $city = City::find($id);
        if (!$city) {
            return $this->errorResponse('City not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150|unique:cities,name,' . $id,
            'status' => 'boolean',
            'state_id' => 'required|exists:states,id'

        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $city->name = $request->name;
            $city->status = $request->status;
            $city->state_id = $request->state_id;
            $city->save();

            $city->load('state');

            $cityData = [
                'id' => $city->id,
                'name' => $city->name,
                'status' => $city->status ? 'Active' : 'Inactive',
                'created_at' => date('d-m-Y H:i', strtotime($city->created_at)),
                'state' => [
                    'id' => $city->state->id,
                    'name' => $city->state->name,
                    'status' => $city->state->status ? 'Active' : 'Inactive',
                    'created_at' => date('d-m-Y H:i', strtotime($city->state->created_at)),
                ],
            ];
            return $this->successResponse($cityData, 'city updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $city = City::find($id);

        if (!$city) {
            return $this->errorResponse('City not found.', 404);
        }

        $city->delete();
        return $this->successResponse(null, 'City deleted successfully.');
    }

    public function deleteMultipleRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:cities,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = City::whereIn('id', $ids)->delete();

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
        $user = City::find($id);

        if (!$user) {
            return $this->errorResponse('City not found.', 404);
        }
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user->status = $request->status;
        $user->save();
        return $this->successResponse($user, 'Status Change Successfully.');
    }
}
