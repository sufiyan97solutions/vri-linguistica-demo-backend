<?php

namespace App\Http\Controllers\Interpreter;

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
    private $interpreterId;

    public function __construct()
    {
        $userId = auth('api')->user()->id;
        $this->userId = $userId;
        $this->interpreterId = Interpreter::where('user_id', $this->userId)->first()->id;
    }

    public function getProfile()
    {

        $profile = Interpreter::with(['user', 'languages.language', 'city', 'state', 'interpreterRates',])->where('user_id', $this->userId)->first();

        return $this->successResponse($profile, 'Profile retrieved successfully.', 200);
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:150',
            'last_name' => 'max:150',
            'email' => 'string|max:150',
            'phone' => 'string|max:20',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|string|max:50',
            'zip_code' => 'required|string|max:20',
            'language_ids' => 'array',
            'language_ids.*' => 'exists:languages,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            $user = User::find($this->userId);
            $record = Interpreter::where('user_id', $this->userId)->first();
            $record->user()->update([
                'name' => $request->first_name . ' ' . ($request->last_name ?? ''),
                'email' => $request->email,
            ]);
            
            if ($record) {
                // Handle image upload and deletion
                if ($request->hasFile('image')) {
                    // Delete old image if exists
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
                $record->update([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name ?? '',
                    'phone' => $request->phone,
                    'state_id' => $request->state_id,
                    'city_id' => $request->city_id,
                    'zip_code' => $request->zip_code,
                    'address' => $request->address,
                ]);

                if (!empty($request->language_ids)) {
                    // First, delete old languages
                    $record->languages()->delete();
    
                    // Add new languages
                    foreach ($request->language_ids as $language) {
                        InterpreterLanguage::create([
                            'interpreter_id' => $record->id,
                            'language_id' => $language
                        ]);
                    }
                }
            }
            return $this->successResponse($record, 'Profile updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }
}
