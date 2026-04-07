<?php

namespace App\Http\Controllers;

use App\Models\ClientTemplate;
use App\Models\ClientTemplateInterpretationRate;
use App\Models\Language;
use App\Models\MinimumDuration;
use App\Models\SubClientType;
use App\Models\SubClientTypeDepartment;
use App\Models\SubClientTypeDynamicFields;
use App\Models\SubClientTypeFacility;
use App\Models\SubClientTypeFilter;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubClientTemplateController extends Controller
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
            // $query = SubClientType::query();
            $query = ClientTemplate::with(['interpretationRates', 'interpretationRates.tiers'])->where('status', 1);
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                });
            }

            $query->orderBy($sortBy, $sortDirection);

            $data = $query->paginate($pageLength);
            // ->through(function ($row) {
            //     return [
            //         'id' => $row->id,
            //         'name' => $row->name,
            //         'normal_hour_start_time' => $row->normal_hour_start_time,
            //         'normal_hour_end_time'=>$row->normal_hour_end_time,
            //         'after_hour_start_time'=>$row->after_hour_start_time,
            //         'after_hour_end_time' => $row->after_hour_end_time,
            //         'grace_period' => $row->grace_period,

            //         'interpretation_rates' => $row->interpretation_rates->map(function ($val) {
            //             return [
            //                 'id' => $val->id,
            //                 "tier_id" => $val->tier_id,
            //                 // "tier"=> $val->tier->name, 
            //                 "opi_normal_rate" => $val->opi_normal_rate,
            //                 "vri_normal_rate" => $val->vri_normal_rate,
            //                 "inperson_normal_rate" => $val->inperson_normal_rate,
            //                 "opi_normal_rate_time_unit" => $val->opi_normal_rate_time_unit,
            //                 "vri_normal_rate_time_unit" => $val->vri_normal_rate_time_unit,
            //                 "inperson_normal_time_unit" => $val->inperson_normal_time_unit,
            //                 "opi_after_rate" => $val->opi_after_rate,
            //                 "vri_after_rate" => $val->vri_after_rate,
            //                 "inperson_after_rate" => $val->inperson_after_rate,
            //                 "opi_after_rate_time_unit" => $val->opi_after_rate_time_unit,
            //                 "vri_after_rate_time_unit" => $val->vri_after_rate_time_unit,
            //                 "inperson_after_time_unit" => $val->inperson_after_time_unit,
            //             ];
            //         }),

            //         'status' => ($row->status) ? 'active' : 'inactive',
            //         'created_at' => date('M-d-Y H:i', strtotime($row->created_at)),
            //     ];
            // });

            return $this->successResponse($data, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving templates: ' . $e->getMessage(), 500);
        }
    }

    public function page(Request $request)
    {
        try {
            // $query = SubClientType::query();
            $query = ClientTemplate::with(['interpretationRates', 'interpretationRates.tiers']);
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
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
            'name' => 'required|string|max:150',

            'normal_hour_start_time' => 'required|date_format:H:i',
            'normal_hour_end_time' => 'required|date_format:H:i',

            'after_hour_start_time' => 'required|date_format:H:i',
            'after_hour_end_time' => 'required|date_format:H:i',

            'grace_period' => 'nullable|integer|min:0',
            'rush_fee' => 'nullable|numeric|min:0',
            'incremental'  => 'string|in:minute,30_minute,1_hour',
            'status' => 'required|boolean',

            'tier_ids.*' => 'nullable|exists:tiers,id',

            'opi_normal_rate.*' => 'numeric|min:0',
            'vri_normal_rate.*' => 'numeric|min:0',
            'inperson_normal_rate.*' => 'numeric|min:0',
            'opi_normal_rate_time_unit.*' => 'string|in:minute,hour',
            'vri_normal_rate_time_unit.*' => 'string|in:minute,hour',
            'inperson_normal_time_unit.*' => 'string|in:minute,hour',
            'opi_normal_mins.*' => 'integer|min:0',
            'vri_normal_mins.*' => 'integer|min:0',
            'inperson_normal_mins.*' => 'integer|min:0',
            'opi_normal_mins_time_unit.*'  => 'string|in:minute,hour',
            'vri_normal_mins_time_unit.*'  => 'string|in:minute,hour',
            'inperson_normal_mins_time_unit.*'  => 'string|in:minute,hour',

            'opi_after_rate.*' => 'numeric|min:0',
            'vri_after_rate.*' => 'numeric|min:0',
            'inperson_after_rate.*' => 'numeric|min:0',
            'opi_after_rate_time_unit.*' => 'string|in:minute,hour',
            'vri_after_rate_time_unit.*' => 'string|in:minute,hour',
            'inperson_after_time_unit.*' => 'string|in:minute,hour',
            'opi_after_mins.*' => 'integer|min:0',
            'vri_after_mins.*' => 'integer|min:0',
            'inperson_after_min.*' => 'numeric|min:0',
            'opi_after_mins_time_unit.*'  => 'string|in:minute,hour',
            'vri_after_mins_time_unit.*'  => 'string|in:minute,hour',
            'inperson_after_mins_time_unit.*'  => 'string|in:minute,hour',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            $record = ClientTemplate::create([
                'name' => $request->name,
                'normal_hour_start_time' => $request->normal_hour_start_time,
                'normal_hour_end_time' => $request->normal_hour_end_time,
                'after_hour_start_time' => $request->after_hour_start_time,
                'after_hour_end_time' => $request->after_hour_end_time,
                'grace_period' => $request->grace_period,
                'rush_fee' => $request->rush_fee,
                'incremental' => $request->incremental ?? 'minute',
                'status' => $request->status ?? 1,
            ]);
            $tierIds = [];
            if ($record) {
                if (!empty($request->tier_ids)) {
                    $i = 0;
                    foreach ($request->tier_ids as $index => $tier_id) {
                        ClientTemplateInterpretationRate::create([
                            'client_template_id' => $record->id,
                            'tier_id' => $tier_id,
                            'opi_normal_rate' => $request->opi_normal_rate[$i],
                            'vri_normal_rate' => $request->vri_normal_rate[$i],
                            'inperson_normal_rate' => $request->inperson_normal_rate[$i],
                            'opi_normal_rate_time_unit' => $request->opi_normal_rate_time_unit[$i] ?? 'hour',
                            'vri_normal_rate_time_unit' => $request->vri_normal_rate_time_unit[$i] ?? 'hour',
                            'inperson_normal_time_unit' => $request->inperson_normal_time_unit[$i] ?? 'hour',
                            'opi_normal_mins' => $request->opi_normal_mins[$i],
                            'vri_normal_mins' => $request->vri_normal_mins[$i],
                            'inperson_normal_mins' => $request->inperson_normal_mins[$i],
                            'opi_normal_mins_time_unit' => $request->opi_normal_mins_time_unit[$i] ?? 'minute',
                            'vri_normal_mins_time_unit' => $request->vri_normal_mins_time_unit[$i] ?? 'minute',
                            'inperson_normal_mins_time_unit' => $request->inperson_normal_mins_time_unit[$i] ?? 'minute',
                            'opi_after_rate' => $request->opi_after_rate[$i],
                            'vri_after_rate' => $request->vri_after_rate[$i],
                            'inperson_after_rate' => $request->inperson_after_rate[$i],
                            'opi_after_rate_time_unit' => $request->opi_after_rate_time_unit[$i] ?? 'hour',
                            'vri_after_rate_time_unit' => $request->vri_after_rate_time_unit[$i] ?? 'hour',
                            'inperson_after_time_unit' => $request->inperson_after_time_unit[$i] ?? 'hour',
                            'opi_after_mins' => $request->opi_after_mins[$i],
                            'vri_after_mins' => $request->vri_after_mins[$i],
                            'inperson_after_mins' => $request->inperson_after_mins[$i],
                            'opi_after_mins_time_unit' => $request->opi_after_mins_time_unit[$i]  ?? 'minute',
                            'vri_after_mins_time_unit' => $request->vri_after_mins_time_unit[$i]  ?? 'minute',
                            'inperson_after_mins_time_unit' => $request->inperson_after_mins_time_unit[$i]  ?? 'minute',
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
        $record = ClientTemplate::find($id);
        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',

            'normal_hour_start_time' => 'required|date_format:H:i',
            'normal_hour_end_time' => 'required|date_format:H:i',

            'after_hour_start_time' => 'required|date_format:H:i',
            'after_hour_end_time' => 'required|date_format:H:i',

            'grace_period' => 'nullable|integer|min:0',
            'rush_fee' => 'nullable|numeric|min:0',
            'incremental'  => 'string|in:minute,30_minute,1_hour',
            'status' => 'required|boolean',

            'tier_ids.*' => 'nullable|exists:tiers,id',

            'opi_normal_rate.*' => 'numeric|min:0',
            'vri_normal_rate.*' => 'numeric|min:0',
            'inperson_normal_rate.*' => 'numeric|min:0',
            'opi_normal_rate_time_unit.*' => 'string|in:minute,hour',
            'vri_normal_rate_time_unit.*' => 'string|in:minute,hour',
            'inperson_normal_time_unit.*' => 'string|in:minute,hour',
            'opi_normal_mins.*' => 'integer|min:0',
            'vri_normal_mins.*' => 'integer|min:0',
            'inperson_normal_mins.*' => 'integer|min:0',
            'opi_normal_mins_time_unit.*'  => 'string|in:minute,hour',
            'vri_normal_mins_time_unit.*'  => 'string|in:minute,hour',
            'inperson_normal_mins_time_unit.*'  => 'string|in:minute,hour',

            'opi_after_rate.*' => 'numeric|min:0',
            'vri_after_rate.*' => 'numeric|min:0',
            'inperson_after_rate.*' => 'numeric|min:0',
            'opi_after_rate_time_unit.*' => 'string|in:minute,hour',
            'vri_after_rate_time_unit.*' => 'string|in:minute,hour',
            'inperson_after_time_unit.*' => 'string|in:minute,hour',
            'opi_after_mins.*' => 'integer|min:0',
            'vri_after_mins.*' => 'integer|min:0',
            'inperson_after_min.*' => 'numeric|min:0',
            'opi_after_mins_time_unit.*'  => 'string|in:minute,hour',
            'vri_after_mins_time_unit.*'  => 'string|in:minute,hour',
            'inperson_after_mins_time_unit.*'  => 'string|in:minute,hour',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $record->update([
                'name' => $request->name,
                'normal_hour_start_time' => $request->normal_hour_start_time,
                'normal_hour_end_time' => $request->normal_hour_end_time,
                'after_hour_start_time' => $request->after_hour_start_time,
                'after_hour_end_time' => $request->after_hour_end_time,
                'grace_period' => $request->grace_period,
                'rush_fee' => $request->rush_fee,
                'incremental' => $request->incremental ?? 'minute',
                'status' => $request->status ?? 1,
            ]);

            $record->save();

            $record->interpretationRates()->delete();

            if (!empty($request->tier_ids)) {
                $i = 0;
                foreach ($request->tier_ids as $index => $tier_id) {
                    ClientTemplateInterpretationRate::create([
                        'client_template_id' => $record->id,
                        'tier_id' => $tier_id,
                        'opi_normal_rate' => $request->opi_normal_rate[$i],
                        'vri_normal_rate' => $request->vri_normal_rate[$i],
                        'inperson_normal_rate' => $request->inperson_normal_rate[$i],
                        'opi_normal_rate_time_unit' => $request->opi_normal_rate_time_unit[$i] ?? 'hour',
                        'vri_normal_rate_time_unit' => $request->vri_normal_rate_time_unit[$i] ?? 'hour',
                        'inperson_normal_time_unit' => $request->inperson_normal_time_unit[$i] ?? 'hour',
                        'opi_normal_mins' => $request->opi_normal_mins[$i],
                        'vri_normal_mins' => $request->vri_normal_mins[$i],
                        'inperson_normal_mins' => $request->inperson_normal_mins[$i],
                        'opi_normal_mins_time_unit' => $request->opi_normal_mins_time_unit[$i] ?? 'minute',
                        'vri_normal_mins_time_unit' => $request->vri_normal_mins_time_unit[$i] ?? 'minute',
                        'inperson_normal_mins_time_unit' => $request->inperson_normal_mins_time_unit[$i] ?? 'minute',
                        'opi_after_rate' => $request->opi_after_rate[$i],
                        'vri_after_rate' => $request->vri_after_rate[$i],
                        'inperson_after_rate' => $request->inperson_after_rate[$i],
                        'opi_after_rate_time_unit' => $request->opi_after_rate_time_unit[$i] ?? 'hour',
                        'vri_after_rate_time_unit' => $request->vri_after_rate_time_unit[$i] ?? 'hour',
                        'inperson_after_time_unit' => $request->inperson_after_time_unit[$i] ?? 'hour',
                        'opi_after_mins' => $request->opi_after_mins[$i],
                        'vri_after_mins' => $request->vri_after_mins[$i],
                        'inperson_after_mins' => $request->inperson_after_mins[$i],
                        'opi_after_mins_time_unit' => $request->opi_after_mins_time_unit[$i],
                        'vri_after_mins_time_unit' => $request->vri_after_mins_time_unit[$i],
                        'inperson_after_mins_time_unit' => $request->inperson_after_mins_time_unit[$i],
                    ]);
                    $i++;
                }
            }

            return $this->successResponse($record, 'Record updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $record = ClientTemplate::find($id);

        if (!$record) {
            return $this->errorResponse('Record not found.', 404);
        }
        $record->delete();
        // $record->delete();
        return $this->successResponse(null, 'Record deleted successfully.');
    }

    public function deleteMultipleRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:client_templates,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = ClientTemplate::whereIn('id', $ids)->delete();

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
        $record = ClientTemplate::find($id);

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
