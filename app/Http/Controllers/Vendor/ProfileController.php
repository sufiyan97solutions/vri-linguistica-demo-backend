<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Interpreter;
use App\Models\InterpreterLanguage;
use App\Models\SubClientType;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    private $userId;

    public function __construct()
    {
        $userId = auth('api')->user()->id;
        $this->userId = $userId;
    }

    public function getProfile()
    {

        $profile = User::with(['vendorRates','vendorInterpreters'])->where('id', $this->userId)->first();

        return $this->successResponse($profile, 'Profile retrieved successfully.', 200);
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'string|max:150',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            $user = User::find($this->userId)->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);
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
                $user->save();  // Don't forget to save the updated image path
            }
            return $this->successResponse($user, 'Profile updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }
}
