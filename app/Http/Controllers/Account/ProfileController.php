<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Interpreter;
use App\Models\InterpreterFilter;
use App\Models\InterpreterLanguage;
use App\Models\MinimumDuration;
use App\Models\SubClientType;
use App\Models\SubClientTypeDepartment;
use App\Models\SubClientTypeDynamicFields;
use App\Models\SubClientTypeFacility;
use App\Models\SubClientTypeFilter;
use App\Models\SubClientTypeSubAccount;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    private $userId;
    private $accountId;

    public function __construct()
    {
        $userId = auth('api')->user()->id;
        $this->userId = $userId;
        $this->accountId = SubClientType::where('user_id',$this->userId)->first()->id;
    }

    public function getProfile(){
        
        $profile = SubClientType::with(['user', 'facilities', 'departments', 'interpretationRates',])->where('user_id',$this->userId)->first();
        
        return $this->successResponse($profile, 'Profile retrieved successfully.', 200);
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'phone' => 'nullable|numeric|digits_between:10,20',
            'credentials_send' => 'boolean',
            'notifications' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            User::find($this->userId)->update([
                'name'=>$request->name,
                'credentials_send' => $request->credentialsSend ?? 0,
                'notifications' => $request->notifications ?? 1,
            ]);
            $record = SubClientType::where('user_id',$this->userId)->first();
            
            if($record){
                $record->update([
                    'phone' => $request->phone,
                ]);
            }
            return $this->successResponse($record, 'Profile updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }

}
