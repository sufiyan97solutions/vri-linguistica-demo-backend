<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\MinimumDuration;
use App\Models\SubClient;
use App\Models\SubClientDepartment;
use App\Models\SubClientDynamicFields;
use App\Models\SubClientFacility;
use App\Models\SubClientFilter;
use App\Models\SubClientType;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class SubAccountController extends Controller
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

            $query = SubClient::with(['type', 'facilities.facility', 'departments.department', 'filters', 'dynamicFields','minDurations'])->where('status',1)->where('type_id',$this->userId);
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                          ->orWhere('id', 'like', '%' . $search . '%') // Add search on id
                          ->orWhereHas('type', function ($q) use ($search) {
                              $q->where('name', 'like', '%' . $search . '%');
                          });
                });
            }

            $query->orderBy($sortBy, $sortDirection);

            $data = $query->paginate($pageLength)->through(function ($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'type' => $row->type,
                    'status' => $row->status ? 'active' : 'inactive',
                    'created_at' => date('M-d-Y H:i', strtotime($row->created_at)),
                    'filters' => $row->filters,
                    'dynamic_fields' => $row->dynamicFields,
                    'facilities' => $row->facilities->map(function ($items) {
                        return [
                            'id' => $items->facility->id,
                            'abbreviation' => $items->facility->abbreviation,
                        ];
                    }),
                    'departments' => $row->departments->map(function ($items) {
                        return [
                            'id' => $items->department->id,
                            'name' => $items->department->name,
                        ];
                    }),
                    'est_duration' => $row->est_duration,
                    'min_durations' => $row->minDurations,

                ];
            });

            return $this->successResponse($data, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving subAccounts: ' . $e->getMessage(), 500);
        }
    }

    public function page(Request $request)
    {
        try {

            $query = SubClient::with(['type', 'facilities.facility', 'departments.department', 'filters', 'dynamicFields','minDurations'])->where('type_id',$this->userId);
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                          ->orWhere('id', 'like', '%' . $search . '%') // Add search on id
                          ->orWhereHas('type', function ($q) use ($search) {
                              $q->where('name', 'like', '%' . $search . '%');
                          });
                });
            }

            $query->orderBy($sortBy, $sortDirection);

            $data = $query->paginate($pageLength)->through(function ($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'type' => $row->type,
                    'status' => $row->status ? 'active' : 'inactive',
                    'created_at' => date('M-d-Y H:i', strtotime($row->created_at)),
                    'filters' => $row->filters,
                    'dynamic_fields' => $row->dynamicFields,
                    'facilities' => $row->facilities->map(function ($items) {
                        return [
                            'id' => $items->facility->id,
                            'abbreviation' => $items->facility->abbreviation,
                        ];
                    }),
                    'departments' => $row->departments->map(function ($items) {
                        return [
                            'id' => $items->department->id,
                            'name' => $items->department->name,
                        ];
                    }),
                    'est_duration' => $row->est_duration,
                    'min_durations' => $row->minDurations,

                ];
            });

            return $this->successResponse($data, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving subAccounts: ' . $e->getMessage(), 500);
        }
    }


    public function getDynamicFields($id)
    {
        $record = SubClient::find($id);

        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $record = $record->dynamicFields;

        return $this->successResponse($record, 'Record fetched successfully.', 200);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'us_based' => 'boolean',
            'non_us_based' => 'boolean',
            'english_to_target' => 'boolean',
            'spanish_to_target' => 'boolean',
            'dynamic_fields.*' => 'max:100',
            'dynamic_fields_required.*' => 'boolean',
            'court_certified' => 'boolean',
            'medical_certified' => 'boolean',
            
            'us_based_locked' => 'boolean',
            'non_us_based_locked' => 'boolean',
            'english_to_target_locked' => 'boolean',
            'spanish_to_target_locked' => 'boolean',
            'court_certified_locked' => 'boolean',
            'medical_certified_locked' => 'boolean',
            'status' => 'boolean',
            'facility_ids.*' => 'nullable|exists:facilities,id', 
            'department_ids.*' => 'nullable|exists:departments,id', 
            'est_duration'=>'boolean',
            'facility_ids.*' => 'nullable|exists:facilities,id', 
            'department_ids.*' => 'nullable|exists:departments,id', 
            'min_duration.*' => 'nullable', 
            'language_ids.*' => 'nullable|exists:languages,id', 
            'start_time.*' => 'nullable', 
            'end_time.*' => 'nullable', 
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            $record = SubClient::create([
                'name' => $request->name,
                'type_id' => $this->userId,
                'status' => $request->status ?? 1,
                'est_duration'=>$request->est_duration ?? 1,
            ]);

            if ($record) {
                SubClientFilter::create([
                    'subclient_id' => $record->id,
                    'us_based' => $request->us_based,
                    'non_us_based' => $request->non_us_based,
                    'english_to_target' => $request->english_to_target,
                    'spanish_to_target' => $request->spanish_to_target,
                    'court_certified' =>$request->court_certified,
                    'medical_certified' =>$request->medical_certified,
                    'us_based_locked'=>$request->us_based_locked ?? 0,
                    'non_us_based_locked'=>$request->non_us_based_locked ?? 0,
                    'english_to_target_locked'=>$request->english_to_target_locked ?? 0,
                    'spanish_to_target_locked'=>$request->spanish_to_target_locked ?? 0,
                    'court_certified_locked' =>$request->court_certified_locked ?? 0,
                    'medical_certified_locked' =>$request->medical_certified_locked ?? 0,
                ]);

                if (!empty($request->dynamic_fields)) {
                    $i=0;
                    foreach ($request->dynamic_fields as $index => $field) {
                        SubClientDynamicFields::create([
                            'subclient_id' => $record->id,
                            'name' => $field,
                            'required'=>$request->dynamic_fields_required[$i] ?? 0
                        ]);
                        $i++;
                    }
                }

                if (!empty($request->facility_ids)) {
                    foreach ($request->facility_ids as $index => $facility) {
                        SubClientFacility::create([
                            'subclient_id' => $record->id,
                            'facility_id' => $facility
                        ]);
                    }
                }

                if (!empty($request->department_ids)) {
                    foreach ($request->department_ids as $index => $department) {
                        SubClientDepartment::create([
                            'subclient_id' => $record->id,
                            'departments_id' => $department
                        ]);
                    }
                }
                if(!empty($request->language_ids)){
                    $i=0;
                    foreach ($request->language_ids as $language) {
                        MinimumDuration::create([
                            'subclient_id'=>$record->id,
                            'min_duration'=>$request->min_duration[$i],
                            'language_id'=>$language,
                            'start_time'=>$request->start_time[$i],
                            'end_time'=>$request->end_time[$i],
                        ]);
                        $i++;
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
        $record = SubClient::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'status' => 'boolean',
            'us_based' => 'boolean',
            'non_us_based' => 'boolean',
            'english_to_target' => 'boolean',
            'spanish_to_target' => 'boolean',
            'court_certified' => 'boolean',
            'medical_certified' => 'boolean',
            'dynamic_fields.*' => 'max:100',
            'facility_ids.*' => 'nullable|exists:facilities,id', 
            'department_ids.*' => 'nullable|exists:departments,id', 
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $record->name = $request->name;
            $record->status = $request->status;
            $record->type_id = $this->userId;
            $record->save();

            SubClientFilter::updateOrCreate([
                'subclient_id' => $record->id
            ], [
                'subclient_id' => $record->id,
                'us_based' => $request->us_based,
                'non_us_based' => $request->non_us_based,
                'english_to_target' => $request->english_to_target,
                'spanish_to_target' => $request->spanish_to_target,
                'court_certified' =>$request->court_certified,
                'medical_certified' =>$request->medical_certified,
            ]);

            $record->dynamicFields()->delete();
            $record->facilities()->delete();
            $record->departments()->delete();

            if (!empty($request->dynamic_fields)) {
                foreach ($request->dynamic_fields as $index => $field) {
                    SubClientDynamicFields::create([
                        'subclient_id' => $record->id,
                        'name' => $field,
                    ]);
                }
            }

            if (!empty($request->facility_ids)) {
                SubClientFacility::where('subclient_id', $record->id)->delete();
                foreach ($request->facility_ids as $facility) {
                    SubClientFacility::create([
                        'subclient_id' => $record->id,
                        'facility_id' => $facility,
                    ]);
                }
            }

            if (!empty($request->department_ids)) {
                SubClientDepartment::where('subclient_id', $record->id)->delete();
                foreach ($request->department_ids as $department) {
                    SubClientDepartment::create([
                        'subclient_id' => $record->id,
                        'departments_id' => $department,
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
        $record = SubClient::find($id);

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
            'ids.*' => 'exists:subclients,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = SubClient::whereIn('id', $ids)->delete();

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
        $record = SubClient::find($id);

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
