<?php

namespace App\Http\Controllers;

use App\Mail\UserCreated;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = User::where('role', 'admin');
        $search = $request->get('search', '');
        $sortBy = $request->get('sortBy', 'created_at');
        $pageLength = $request->get('pageLength', 10);
        $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');


        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                     ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $query->orderBy($sortBy, $sortDirection);

        $admins = $query->paginate($pageLength)->through(function ($admin) {
            return [
                'id' => $admin->id,
                'name' => $admin->name,
                'last_name' => $admin->last_name,
                'email' => $admin->email,
                'role' => $admin->role,
                'image' => $admin->image,
                'status' => $admin->status ? 'Active' : 'Inactive',
                'created_at' => date('d-m-Y H:i', strtotime($admin->created_at)),
            ];
        });

        return $this->successResponse($admins, 'Users retrieved successfully.', 200);
    }




    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'last_name' => 'nullable|string|max:150',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'email' => 'required|email|unique:users,email|max:200',
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
                'last_name' => $request->last_name,
                'password' => bcrypt($request->password),
                'status' => $request->status ?? 1,
                'role' => 'admin',
            ]);

            if ($imagePath) {
                $user->image = $imagePath;
                $user->save();
            }

            $userData = [
                'name' => $user->name,
                'last_name' => $user->last_name,
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
        $admin = User::find($id);

        if (!$admin) {
            return $this->errorResponse('User not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'last_name' => 'nullable|string|max:150',
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'email' => 'required|email|unique:users,email,' . $id . '|max:200',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {

            $admin->name = $request->name;
            $admin->last_name = $request->last_name;
            $admin->email = $request->email;
            $admin->status = $request->status;


            if ($request->filled('password')) {
                $admin->password = bcrypt($request->password);
            }

            $admin->status = $request->status;
            $admin->role = 'admin';

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

            return $this->successResponse($admin, 'User updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }


    public function destroy($id)
    {
        $admin = User::find($id);

        if (!$admin) {
            return $this->errorResponse('User not found.', 404);
        }

        if ($admin->image) {
            $existingImagePath = public_path($admin->image);
            if (file_exists($existingImagePath)) {
                unlink($existingImagePath);
            }
        }

        $admin->delete();
        return $this->successResponse([], 'User deleted successfully.', 200);
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
