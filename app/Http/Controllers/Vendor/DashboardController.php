<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Interpreter;
use App\Models\InterpreterFilter;
use App\Models\InterpreterLanguage;
use App\Models\SubClientType;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    private $userId;

    public function __construct()
    {
        $userId = auth('api')->user()->id;
        $this->userId = $userId;
    }

    public function analytics(Request $request)
    {

        // Actual DB values
        $types = ['In Person', 'VRI', 'OPI', 'OPI On Demand'];

        // Human-readable labels
        $labels = [
            'In Person' => 'In Person',
            'VRI' => 'Video Remote Interpretation',
            'OPI' => 'OTP Pre Schedule',
            'OPI On Demand' => 'OPI On Demand'
        ];

        // --- 1. Today's Data ---
        $todays_data_raw = \DB::table('appointments')
        ->leftJoin('interpreters', 'appointments.interpreter_id', '=', 'interpreters.id') // left join in case interpreter is null
        ->whereIn('appointments.status', ['pending', 'assigned', 'active', 'completed', 'declined', 'cancelled', 'cnc'])
        ->where(function ($q) {
            $q->where('interpreters.vendor_id', $this->userId)
              ->orWhere('appointments.vendor_id', $this->userId);
        })
        ->whereDate('appointments.datetime', now()->toDateString())
        ->select('appointments.type', \DB::raw('COUNT(*) as count'))
        ->groupBy('appointments.type')
        ->get();
    

        // --- 2. Next 14 Days ---
        $fourteen_days_raw = \DB::table('appointments')
        ->leftJoin('interpreters', 'appointments.interpreter_id', '=', 'interpreters.id')
        ->whereIn('appointments.status', ['pending', 'assigned', 'active', 'completed', 'declined', 'cancelled', 'cnc'])
        ->where(function ($q) {
            $q->where('interpreters.vendor_id', $this->userId)
              ->orWhere('appointments.vendor_id', $this->userId);
        })
        ->whereBetween('appointments.datetime', [
            now()->addDay()->startOfDay(),
            now()->addDays(15)->endOfDay()
        ])
        ->select('appointments.type', \DB::raw('COUNT(*) as count'))
        ->groupBy('appointments.type')
        ->get();
    

        // --- Mapping raw data into desired structure with readable labels ---
        $todays_data = collect($types)->map(function ($type) use ($todays_data_raw, $labels) {
            $count = $todays_data_raw->firstWhere('type', $type)->count ?? 0;
            return [
                'type' => $labels[$type] ?? $type,
                'value' => $type,
                'count' => $count
            ];
        });

        $fourteen_days = collect($types)->map(function ($type) use ($fourteen_days_raw, $labels) {
            $count = $fourteen_days_raw->firstWhere('type', $type)->count ?? 0;
            return [
                'type' => $labels[$type] ?? $type,
                'value' => $type,
                'count' => $count
            ];
        });

        $result = [
            'todays_data' => $todays_data,
            'fourteen_days' => $fourteen_days
        ];

        return $this->successResponse($result, 'Reports retrieved successfully.', 200);
    }

    public function appointments(Request $request)
    {
        try {

            $query = Appointment::with(['accounts.user', 'language', 'interpreter.user', 'interpreter.vendor', 'vendor', 'appointmentDetails.facility', 'appointmentDetails.patient', 'appointmentDetails.department', 'appointmentAssign']);
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'like', '%' . $search . '%')
                        ->orWhere('type', 'like', '%' . $search . '%')
                        ->orWhere('datetime', 'like', '%' . $search . '%')
                        ->orWhereHas('appointmentDetails', function ($q) use ($search) {
                            $q->where('requester_name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('language', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('accounts', function ($q) use ($search) {
                            $q->whereHas('user', function ($f) use ($search) {
                                $f->where('name', 'like', '%' . $search . '%');
                            });
                        })
                        ->orWhereHas('appointmentDetails', function ($q) use ($search) {
                            $q->whereHas('facility', function ($f) use ($search) {
                                $f->where('abbreviation', 'like', '%' . $search . '%');
                            });
                        })
                        ->orWhereHas('appointmentDetails', function ($q) use ($search) {
                            $q->whereHas('department', function ($d) use ($search) {
                                $d->where('department_name', 'like', '%' . $search . '%');
                            });
                        })
                        ->orWhereHas('interpreter', function ($q) use ($search) {
                            $q->where('first_name', 'like', '%' . $search . '%');
                        });
                });
            }


            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $facilityID = $request->get('facility_id');
            $languageID = $request->get('language_id');
            $departmentID = $request->get('department_id');
            $service = $request->get('service');
            $client = $request->get('client');
            $status = $request->get('status');

            if ($startDate && $endDate) {
                $startDateFormatted = date('Y-m-d H:i:s', strtotime($startDate . ' 00:00:00'));
                $endDateFormatted = date('Y-m-d H:i:s', strtotime($endDate . ' 23:59:59'));
                $query->whereBetween('datetime', [$startDateFormatted, $endDateFormatted]);
            }

            if (!empty($languageID) && $languageID !== ['All']) {
                $query->where('language_id', $languageID);
            }

            if (!empty($facilityID)) {
                $query->WhereHas('appointmentDetails', function ($q) use ($facilityID) {
                    $q->where('facility_id', $facilityID);
                });
            }
            if (!empty($departmentID)) {
                $query->WhereHas('appointmentDetails', function ($q) use ($departmentID) {
                    $q->where('department_id', $departmentID);
                });
            }

            if (!empty($service)) {
                $query->where('type', $service);
            }

            if (!empty($client)) {
                $query->whereHas('accounts.user', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
            }
            
            $query->whereIn('status', ['pending', 'assigned', 'active', 'completed', 'declined', 'cancelled', 'cnc'])
            ->where(function ($q) use ($search) {
                $q->whereHas('interpreter', function ($q) {
                    $q->where('vendor_id', $this->userId);
                })
            ->orWhere('vendor_id', $this->userId);
            });

            if(!empty($status) && $status != 'all'){
                if($status == 'extra_mileage'){
                    $query->WhereHas('appointmentDetails', function ($q) use ($departmentID) {
                        $q->where('extra_mileage_request', 1);
                    });
                }else{
                    $query->where('status', $status);
                }
            }




            $query->orderBy($sortBy, $sortDirection);

            $data = $query->paginate($pageLength);

            return $this->successResponse($data, 'Data retrieved successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }
}
