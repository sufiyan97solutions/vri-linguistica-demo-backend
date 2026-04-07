<?php

namespace App\Http\Controllers\Interpreter;

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
    private $interpreterId;

    public function __construct()
    {
        $userId = auth('api')->user()->id;
        $this->userId = $userId;
        $this->interpreterId = Interpreter::where('user_id', $this->userId)->first()->id;
    }

    public function analytics(Request $request)
    {
        $interpreterLanguages = InterpreterLanguage::where('interpreter_id', $this->interpreterId)->get();
        $languageIds = $interpreterLanguages->pluck('language_id')->toArray();



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
            ->whereIn('status', ['open', 'assigned', 'active', 'completed', 'declined', 'cancelled'])
            ->where(function ($q) use ($languageIds) {
                $q->where('status', 'open')
                    ->whereIn('language_id', $languageIds)
                    ->orWhere('interpreter_id', $this->interpreterId);
            })
            ->select('type', \DB::raw('COUNT(*) as count'))
            ->whereDate('datetime', now()->toDateString())
            ->groupBy('type')
            ->get();

        // --- 2. Next 14 Days ---
        $fourteen_days_raw = \DB::table('appointments')
            ->whereIn('status', ['open', 'assigned', 'active', 'completed', 'declined', 'cancelled'])
            ->where(function ($q) use ($languageIds) {
                $q->where('status', 'open')
                    ->whereIn('language_id', $languageIds)
                    ->orWhere('interpreter_id', $this->interpreterId);
            })
            ->select('type', \DB::raw('COUNT(*) as count'))
            ->whereBetween('datetime', [
                now()->addDay()->startOfDay(),
                now()->addDays(15)->endOfDay()
            ])
            ->groupBy('type')
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

            $query = Appointment::with(['accounts.user', 'language', 'interpreter.user', 'vendor', 'appointmentDetails.facility', 'appointmentDetails.department', 'appointmentDetails.patient', 'appointmentAssign']);
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
                $query->whereHas('appointmentDetails', function ($q) use ($facilityID) {
                    $q->where('facility_id', $facilityID);
                });
            }
            
            if (!empty($departmentID)) {
                $query->whereHas('appointmentDetails', function ($q) use ($departmentID) {
                    $q->where('department_id', $departmentID);
                });
            }
            
            if (!empty($service)) {
                $query->where('type', $service);
            }
            
            if (!empty($client)) {
                $query->whereHas('accounts.user', function ($q) use ($client) {
                    $q->where('name', 'like', '%' . $client . '%');
                });
            }
            
            // Interpreter language filtering
            $interpreterLanguages = InterpreterLanguage::where('interpreter_id', $this->interpreterId)->get();
            $languageIds = $interpreterLanguages->pluck('language_id')->toArray();
            
            // Status filtering
            if (!empty($status) && $status != 'all') {
                $query->where('status', $status);
            } else {
                $query->whereIn('status', ['open', 'assigned', 'active', 'completed', 'declined', 'cancelled', 'cnc']);
            }
            
            // Interpreter filter
            $query->where('interpreter_id', $this->interpreterId);
            
            // Sorting and pagination
            $query->orderBy($sortBy, $sortDirection);
            $data = $query->paginate($pageLength);
            
            return $this->successResponse($data, 'Data retrieved successfully.', 200);
            
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving cities: ' . $e->getMessage(), 500);
        }
    }
}
