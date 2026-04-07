<?php

namespace App\Http\Controllers;

use App\Mail\UserCreated;
use App\Models\User;
use App\Models\VendorRates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class VenderController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = User::with(['vendorRates'])->where('role', 'vendor')->where('status', 1);
        $search = $request->get('search', '');
        $sortBy = $request->get('sortBy', 'created_at');
        $pageLength = $request->get('pageLength', 10);
        $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $query->orderBy($sortBy, $sortDirection);

        $users = $query->paginate($pageLength)->through(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'image' => $user->image,
                'status' => $user->status ? 'Active' : 'Inactive',
                'created_at' => $user->created_at,
            ];
        });

        return $this->successResponse($users, 'Users retrieved successfully.', 200);
    }

    public function page(Request $request)
    {
        $query = User::with(['vendorRates'])->where('role', 'vendor');
        $search = $request->get('search', '');
        $sortBy = $request->get('sortBy', 'created_at');
        $pageLength = $request->get('pageLength', 10);
        $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $query->orderBy($sortBy, $sortDirection);

        $users = $query->paginate($pageLength)->through(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'image' => $user->image,
                'vendor_rates' => $user->vendorRates,
                'status' => $user->status ? 'Active' : 'Inactive',
                'created_at' => $user->created_at,
            ];
        });

        return $this->successResponse($users, 'Users retrieved successfully.', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email|max:200',
            'password' => 'required|string|min:8|confirmed',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'boolean',
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

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'status' => $request->status ?? 1,
                'role' => 'vendor',
            ]);

            if ($imagePath) {
                $user->image = $imagePath;
                $user->save();
            }

            if ($user) {
                VendorRates::create([
                    'vendor_id' => $user->id,

                    // Normal Hours
                    'normal_hour_start_time' => $request->normal_hour_start_time,
                    'normal_hour_end_time' => $request->normal_hour_end_time,
                    'after_hour_start_time' => $request->after_hour_start_time,
                    'after_hour_end_time' => $request->after_hour_end_time,
                    'grace_period' => $request->grace_period,

                    // Normal Rates
                    'opi_normal_rate' => $request->opi_normal_rate,
                    'vri_normal_rate' => $request->vri_normal_rate,
                    'inperson_normal_rate' => $request->inperson_normal_rate,
                    'opi_normal_rate_time_unit' => $request->opi_normal_rate_time_unit,
                    'vri_normal_rate_time_unit' => $request->vri_normal_rate_time_unit,
                    'inperson_normal_rate_time_unit' => $request->inperson_normal_time_unit,
                    'opi_normal_mins' => $request->opi_normal_mins,
                    'vri_normal_mins' => $request->vri_normal_mins,
                    'inperson_normal_mins' => $request->inperson_normal_mins,
                    'opi_normal_min_time_unit' => $request->opi_normal_mins_time_unit,
                    'vri_normal_min_time_unit' => $request->vri_normal_mins_time_unit,
                    'inperson_normal_min_time_unit' => $request->inperson_normal_mins_time_unit,

                    // After Hours Rates
                    'opi_after_rate' => $request->opi_after_rate,
                    'vri_after_rate' => $request->vri_after_rate,
                    'inperson_after_rate' => $request->inperson_after_rate,
                    'opi_after_rate_time_unit' => $request->opi_after_rate_time_unit,
                    'vri_after_rate_time_unit' => $request->vri_after_rate_time_unit,
                    'inperson_after_rate_time_unit' => $request->inperson_after_time_unit,
                    'opi_after_mins' => $request->opi_after_mins,
                    'vri_after_mins' => $request->vri_after_mins,
                    'inperson_after_mins' => $request->inperson_after_mins,
                    'opi_after_min_time_unit' => $request->opi_after_mins_time_unit,
                    'vri_after_min_time_unit' => $request->vri_after_mins_time_unit,
                    'inperson_after_min_time_unit' => $request->inperson_after_mins_time_unit,
                ]);
            }

            $userData = [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $request->password,
            ];

            try {
                sendMail($userData);
            } catch (\Exception $e) {
                return $this->errorResponse('Email could not be sent: ' . $e->getMessage(), 500);
            }

            return $this->successResponse($user, 'User created successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email,' . $id . '|max:200',
            'password' => 'nullable|string|min:8|confirmed',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $user->name = $request->name;
            $user->email = $request->email;
            $user->status = $request->status;

            if ($request->filled('password')) {
                $user->password = bcrypt($request->password);
            }

            $user->status = $request->status;
            $user->role = 'vendor';

            if ($request->hasFile('image')) {
                if ($user->image) {
                    $existingImagePath = public_path($user->image);
                    if (file_exists($existingImagePath)) {
                        unlink($existingImagePath);
                    }
                }

                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images'), $imageName);
                $user->image = 'images/' . $imageName;
            }

            $user->save();

            $user->vendorRates()->updateOrCreate([
                'vendor_id' => $user->id,
            ], [
                // Normal Hours
                'normal_hour_start_time' => $request->normal_hour_start_time,
                'normal_hour_end_time' => $request->normal_hour_end_time,
                'after_hour_start_time' => $request->after_hour_start_time,
                'after_hour_end_time' => $request->after_hour_end_time,
                'grace_period' => $request->grace_period,

                // Normal Rates
                'opi_normal_rate' => $request->opi_normal_rate,
                'vri_normal_rate' => $request->vri_normal_rate,
                'inperson_normal_rate' => $request->inperson_normal_rate,
                'opi_normal_rate_time_unit' => $request->opi_normal_rate_time_unit,
                'vri_normal_rate_time_unit' => $request->vri_normal_rate_time_unit,
                'inperson_normal_rate_time_unit' => $request->inperson_normal_time_unit,
                'opi_normal_mins' => $request->opi_normal_mins,
                'vri_normal_mins' => $request->vri_normal_mins,
                'inperson_normal_mins' => $request->inperson_normal_mins,
                'opi_normal_min_time_unit' => $request->opi_normal_mins_time_unit,
                'vri_normal_min_time_unit' => $request->vri_normal_mins_time_unit,
                'inperson_normal_min_time_unit' => $request->inperson_normal_mins_time_unit,

                // After Hours Rates
                'opi_after_rate' => $request->opi_after_rate,
                'vri_after_rate' => $request->vri_after_rate,
                'inperson_after_rate' => $request->inperson_after_rate,
                'opi_after_rate_time_unit' => $request->opi_after_rate_time_unit,
                'vri_after_rate_time_unit' => $request->vri_after_rate_time_unit,
                'inperson_after_rate_time_unit' => $request->inperson_after_time_unit,
                'opi_after_mins' => $request->opi_after_mins,
                'vri_after_mins' => $request->vri_after_mins,
                'inperson_after_mins' => $request->inperson_after_mins,
                'opi_after_min_time_unit' => $request->opi_after_mins_time_unit,
                'vri_after_min_time_unit' => $request->vri_after_mins_time_unit,
                'inperson_after_min_time_unit' => $request->inperson_after_mins_time_unit,
            ]);

            return $this->successResponse($user, 'User updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }

        if ($user->image) {
            $existingImagePath = public_path($user->image);
            if (file_exists($existingImagePath)) {
                unlink($existingImagePath);
            }
        }

        $user->delete();

        return $this->successResponse(null, 'User deleted successfully.');
    }

    public function changePassword(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse('User not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:8',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Current password is incorrect', 400);
        }
        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->successResponse($user, 'Password changed successfully.', 200);
    }

    public function deleteMultipleRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = User::whereIn('id', $ids)->delete();

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
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found.', 404);
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
