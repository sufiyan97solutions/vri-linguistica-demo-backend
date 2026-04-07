<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\TierLanguage;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class LanguageController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            $query = Language::query();
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
                ];
            });

            return $this->successResponse($cities, 'Languages retrieved successfully.', 200);

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }

    public function tierLanguages(Request $request)
    {
        try {
            $selected = TierLanguage::all()->pluck('language_id')->toArray();
            $query = Language::query()->whereNotIn('id', $selected);
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
                ];
            });

            return $this->successResponse($cities, 'Languages retrieved successfully.', 200);

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150|unique:languages,name',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            $language = Language::create([
                'name' => $request->name,
                'status' => $request->status ?? 1,
            ]);
            return $this->successResponse($language, 'Language created successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        $language = Language::find($id);
        if (!$language) {
            return $this->errorResponse('Language not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150|unique:languages,name,' . $id,
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $language->name = $request->name;
            $language->status = $request->status;

            $language->save();

            return $this->successResponse($language, 'Language updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $language = Language::find($id);

        if (!$language) {
            return $this->errorResponse('Language not found.', 404);
        }

        $language->delete();
        return $this->successResponse(null, 'Language deleted successfully.');
    }

    public function deleteMultipleRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:languages,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = Language::whereIn('id', $ids)->delete();

            if ($deletedCount > 0) {
                return $this->successResponse([], 'Records deleted successfully.', 200);
            } else {
                return $this->errorResponse('No records found to delete.', 404);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }

    public function changeStatus(Request $request ,$id)
    {
        $user = Language::find($id);

        if (!$user) {
            return $this->errorResponse('Language not found.', 404);
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
