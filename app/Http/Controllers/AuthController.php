<?php

namespace App\Http\Controllers;

use App\Mail\UserCreated;
use App\Models\SubUser;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role'=>$request->role
            ]);

            $token = $user->createToken('authToken')->accessToken;

            $userData = [
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'password' => $request->password,
                'role'=>$request->role
            ];

            try {
                sendMail($userData);
            } catch (\Exception $e) {
                return $this->errorResponse('Email could not be sent: ' . $e->getMessage(), 500);
            }
            return $this->successResponse(['token' => $token, 'user' => $user], 'User registered successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during signup: ' . $e->getMessage(), 500);
        }
    }


    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (auth()->attempt($credentials)) {
            $token = auth()->user()->createToken('authToken')->accessToken;
            $subUser = SubUser::with('permissions')->where('user_id', auth()->user()->id)->first();
            return $this->successResponse([
                'token' => $token,
                'user' => auth()->user(),
                'role' => auth()->user()->role,
                "permissions" => $subUser ? $subUser->permissions->map(function ($val) {
                    return $val->access;
                }) : [],
            ], 'Login successful');
        } else {
            return $this->errorResponse('Invalid Credentials', 401);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $status = Password::sendResetLink(
                $request->only('email'),
                function ($user, $token) {
                    \Log::error($token);
                    $user->notify(new ResetPasswordNotification($token));
                }
            );

            if ($status === Password::RESET_LINK_SENT) {
                return $this->successResponse(null, 'Password reset link sent to your email.');
            } else {
                return $this->errorResponse('Failed to send password reset link', 500);
            }
        } catch (\Exception $e) {
            \Log::error('Password reset link sending failed: ' . $e->getMessage());

            return $this->errorResponse('An error occurred while sending the password reset link. Please try again later.', 500);
        }
    }



    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user) use ($request) {
                    $user->password = Hash::make($request->password);
                    $user->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return $this->successResponse(null, 'Password reset successful.');
            } else {
                return $this->errorResponse('Failed to reset password. Invalid token or email.', 400);
            }
        } catch (\Exception $e) {
            \Log::error('Password reset failed: ' . $e->getMessage());
            return $this->errorResponse('An error occurred while resetting the password. Please try again later.', 500);
        }
    }

    public function changePassword(Request $request)
    {

        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('User not logged in', 404);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:8',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }


        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'password' => ['Current password is incorrect']
                ]
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();
        return $this->successResponse($user, 'Password changed successfully.', 200);
    }
}
