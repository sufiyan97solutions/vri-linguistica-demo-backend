<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomeBasedHospital;
use App\Models\Interpreter;
use App\Models\InterpreterLanguage;
use App\Models\User;
use App\Models\UserTopHospital;
use App\Models\VendorInterpreterUser;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class InterpretersController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $sortBy = $request->get('sortBy', 'created_at');
            $pageLength = $request->get('pageLength', 10);
            $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');
            $userId = auth('api')->user()->id;

            $query = Interpreter::where('vender_auth_id', $userId)->with(['city', 'state', 'languages', 'homeBasedHospitals', 'userTopHospitals', 'interpreter']);

            // Apply search filters
            if ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('interpreter', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('address', 'like', '%' . $search . '%')
                        ->orWhereHas('city', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('state', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        });
                });
            }

            // Check for sorting by related user attributes (name or email)
            if (in_array($sortBy, ['name', 'email'])) {
                $query->join('vendor_interpreter_users', 'interpreters.vender_interpreter_id', '=', 'vendor_interpreter_users.id')
                    ->orderBy("vendor_interpreter_users.$sortBy", $sortDirection)
                    ->select('interpreters.*'); // Ensure we select the main table's fields
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            $interpreters = $query->paginate($pageLength)->through(function ($interpreter) {
                return [
                    'id' => $interpreter->id,
                    'phone' => $interpreter->phone,
                    'address' => $interpreter->address,
                    'interpreter' => [
                        'id' => $interpreter->interpreter?->id ?? null,
                        'name' => $interpreter->interpreter?->name ?? null,
                        'last_name' => $interpreter->interpreter?->last_name ?? null,
                        'email' => $interpreter->interpreter?->email ?? null,
                        'image' => $interpreter->interpreter?->image ?? null,
                        'status' => $interpreter->interpreter?->status ? 'Active' : 'Inactive',
                        'created_at' => $interpreter->interpreter ? date('d-m-Y H:i', strtotime($interpreter->interpreter->created_at)) : null,
                    ],
                    'city_id' => $interpreter->city_id,
                    'city' => [
                        'id' => $interpreter->city?->id ?? null,
                        'name' => $interpreter->city?->name ?? null,
                        'status' => $interpreter->city?->status ? 'Active' : 'Inactive',
                        'created_at' => $interpreter->city ? date('d-m-Y H:i', strtotime($interpreter->city->created_at)) : null,
                    ],
                    'state_id' => $interpreter->state_id,
                    'state' => [
                        'id' => $interpreter->state?->id ?? null,
                        'name' => $interpreter->state?->name ?? null,
                        'status' => $interpreter->state?->status ? 'Active' : 'Inactive',
                        'created_at' => $interpreter->state ? date('d-m-Y H:i', strtotime($interpreter->state->created_at)) : null,
                    ],
                    'interpreter_language' => $interpreter->languages->map(function ($language) {
                        return [
                            'id' => $language->id ?? null,
                            'name' => $language->name ?? null,
                            'status' => $language->status ? 'Active' : 'Inactive' ?? null,
                        ];
                    }),
                    'home_based_hospitals' => $interpreter->homeBasedHospitals->map(function ($hospital) {
                        return [
                            'id' => $hospital->id ?? null,
                            'name' => $hospital->name ?? null,
                            'phone' => $hospital->phone ?? null,
                            'email' => $hospital->email ?? null,
                            'address_line1' => $hospital->address_line1 ?? null,
                            'address_line2' => $hospital->address_line2 ?? null,
                            'status' => $hospital->status ? 'Active' : 'Inactive' ?? null,
                        ];
                    }),
                    'user_top_hospitals' => $interpreter->userTopHospitals->map(function ($hospital) {
                        return [
                            'id' => $hospital->id ?? null,
                            'name' => $hospital->name ?? null,
                            'phone' => $hospital->phone ?? null,
                            'email' => $hospital->email ?? null,
                            'address_line1' => $hospital->address_line1 ?? null,
                            'address_line2' => $hospital->address_line2 ?? null,
                            'status' => $hospital->status ? 'Active' : 'Inactive' ?? null,
                        ];
                    }),
                    'created_at' => date('d-m-Y H:i', strtotime($interpreter->created_at)),
                ];
            });

            return $this->successResponse($interpreters, 'Interpreters retrieved successfully.', 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while retrieving interpreters', 'message' => $e->getMessage()], 500);
        }
    }




    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'last_name' => 'nullable|string|max:150',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'email' => 'required|email|unique:vendor_interpreter_users,email|max:200',

            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:150',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'language_ids' => 'required|array',
            'language_ids.*' => 'exists:languages,id',
            'home_based_hospital_ids' => 'nullable|array',
            'home_based_hospital_ids.*' => 'exists:hospitals,id',
            'user_top_hospital_ids' => 'nullable|array',
            'user_top_hospital_ids.*' => 'exists:hospitals,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $imagePath = null;

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images'), $imageName);
                $imagePath = 'images/' . $imageName;
            }
            $userId = auth('api')->user()->id;

            $user = VendorInterpreterUser::create([
                'name' => $request->name,
                'email' => $request->email,
                'last_name' => $request->last_name,
                'password' => bcrypt($request->password),
                'status' => $request->status ?? 1,
            ]);

            if ($imagePath) {
                $user->image = $imagePath;
                $user->save();
            }


            $interpreter = Interpreter::create([
                'user_id' => null,
                'phone' => $request->phone,
                'address' => $request->address,
                'city_id' => $request->city_id,
                'state_id' => $request->state_id,
                'vender_interpreter_id' => $user->id,
                'vender_auth_id' => $userId
            ]);

            foreach ($request->language_ids as $language_id) {
                InterpreterLanguage::create([
                    'interpreter_id' => $interpreter->id,
                    'language_id' => $language_id,
                ]);
            }

            if ($request->filled('home_based_hospital_ids')) {
                foreach ($request->home_based_hospital_ids as $hospital_id) {
                    HomeBasedHospital::create([
                        'interpreter_id' => $interpreter->id,
                        'hospital_id' => $hospital_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($request->filled('user_top_hospital_ids')) {
                foreach ($request->user_top_hospital_ids as $hospital_id) {
                    UserTopHospital::create([
                        'interpreter_id' => $interpreter->id,
                        'hospital_id' => $hospital_id,
                        'user_id' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $interpreter->load(['languages', 'interpreter']);


            return $this->successResponse($interpreter, 'Interpreter created successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        $interpreter = Interpreter::find($id);
        if (!$interpreter) {
            return $this->errorResponse('Interpreter not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'email' => 'required|email|unique:vendor_interpreter_users,email,' . $interpreter->vender_interpreter_id . '|max:200',

            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:150',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'language_ids' => 'required|array',
            'language_ids.*' => 'exists:languages,id',
            'home_based_hospital_ids' => 'nullable|array',
            'home_based_hospital_ids.*' => 'exists:hospitals,id',
            'user_top_hospital_ids' => 'nullable|array',
            'user_top_hospital_ids.*' => 'exists:hospitals,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $admin = VendorInterpreterUser::find($interpreter->vender_interpreter_id);
            $admin->name = $request->name;
            $admin->last_name = $request->last_name;
            $admin->email = $request->email;
            $admin->status = $request->status;


            if ($request->filled('password')) {
                $admin->password = bcrypt($request->password);
            }

            $admin->status = $request->status;

            if ($request->hasFile('image')) {
                if ($admin->image) {
                    $existingImagePath = public_path($admin->image);
                    if (file_exists($existingImagePath)) {
                        unlink($existingImagePath);
                    }
                }

                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images'), $imageName);
                $admin->image = 'images/' . $imageName;
            }

            $admin->save();

            $userId = auth('api')->user()->id;

            $interpreter->update([
                'user_id' => null,
                'phone' => $request->phone,
                'address' => $request->address,
                'city_id' => $request->city_id,
                'state_id' => $request->state_id,
                'vender_interpreter_id' => $admin->id,
                'vender_auth_id' => $userId
            ]);

            $languageData = [];
            foreach ($request->language_ids as $language_id) {
                $languageData[$language_id] = ['created_at' => now(), 'updated_at' => now()];
            }
            $interpreter->languages()->sync($languageData);

            if ($request->filled('home_based_hospital_ids')) {
                $homeBasedHospitalData = [];
                foreach ($request->home_based_hospital_ids as $hospital_id) {
                    $homeBasedHospitalData[$hospital_id] = ['created_at' => now(), 'updated_at' => now()];
                }
                $interpreter->homeBasedHospitals()->sync($homeBasedHospitalData);
            }
            if ($request->filled('user_top_hospital_ids')) {
                $userTopHospitalData = [];
                foreach ($request->user_top_hospital_ids as $hospital_id) {
                    $userTopHospitalData[$hospital_id] = [
                        'created_at' => now(),
                        'updated_at' => now(),
                        'user_id' => $admin->id, // Ensure user_id is provided here
                    ];
                }
                $interpreter->userTopHospitals()->sync($userTopHospitalData);
            }

            $interpreter->load('languages', 'homeBasedHospitals', 'userTopHospitals', 'interpreter');

            return $this->successResponse($interpreter, 'Interpreter updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during update: ' . $e->getMessage(), 500);
        }
    }



    public function destroy($id)
    {
        $Interpreter = Interpreter::find($id);

        if (!$Interpreter) {
            return $this->errorResponse('Interpreter not found.', 404);
        }
        $admin = VendorInterpreterUser::find($Interpreter->vender_interpreter_id);
        if ($admin) {
            $admin->delete();
        }

        $Interpreter->delete();
        return $this->successResponse(null, 'Interpreter deleted successfully.', 200);
    }

    public function deleteMultipleRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:interpreters,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $interpreters = Interpreter::whereIn('id', $ids)->get();

            if ($interpreters->isEmpty()) {
                return $this->errorResponse('No records found to delete.', 404);
            }

            $vendorInterpreterIds = $interpreters->pluck('vender_interpreter_id')->unique();
            VendorInterpreterUser::whereIn('id', $vendorInterpreterIds)->delete();

            $deletedCount = Interpreter::whereIn('id', $ids)->delete();


            return $this->successResponse([], 'Records deleted successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }


    public function changeStatus(Request $request, $id)
    {
        $user = Interpreter::find($id);

        if (!$user) {
            return $this->errorResponse('Interpreter not found.', 404);
        }
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        $interpreter = VendorInterpreterUser::find($user->vender_interpreter_id);
        $interpreter->status = $request->status;
        $interpreter->save();
        $user->load(['user', 'languages', 'city', 'state']);

        return $this->successResponse($user, 'Status Change Successfully.', 200);
    }
}
