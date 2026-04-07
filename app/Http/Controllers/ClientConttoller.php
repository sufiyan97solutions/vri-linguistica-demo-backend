<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientBillingInfo;
use App\Models\ClientServiceRate;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {

        $query = Hospital::query();
        $search = $request->get('search', '');
        // $statusSearch = $request->input('status', '');
        $sortBy = $request->get('sortBy', 'created_at');
        $pageLength = $request->get('pageLength', 10);
        // $sortDirection = $request->get('sortDirection', 'asc');
        $sortDirection = $request->get('sortDirection', $sortBy === 'created_at' ? 'desc' : 'asc');

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
        }

        // if ($statusSearch !== '') {
        //     if ($statusSearch === 'active') {
        //         $query->where('status', 1);
        //     } elseif ($statusSearch === 'inactive') {
        //         $query->where('status', 0);
        //     }
        // }

        $query->orderBy($sortBy, $sortDirection);

        $hospitals = $query->paginate($pageLength)->through(function ($hospital) {
            return [
                'id' => $hospital->id,
                'name' => $hospital->name,
                'phone' => $hospital->phone,
                // 'email' => $hospital->email,
                'address_line1' => $hospital->address_line1,
                // 'address_line2' => $hospital->address_line2,
                'status' => $hospital->status,
                'state_id' => $hospital->state_id,
                'state' => [
                    'id' => $hospital->state?->id ?? null,
                    'name' => $hospital->state?->name ?? null,
                    'status' => isset($hospital->state?->status) ? ($hospital->state->status ? 'active' : 'inactive') : null,
                    'created_at' => $hospital->state ? date('d-m-Y H:i', strtotime($hospital->state->created_at)) : null,
                ],
                'city_id' => $hospital->city_id,
                'city' => [
                    'id' => $hospital->city?->id ?? null,
                    'name' => $hospital->city?->name ?? null,
                    'status' => isset($hospital->city?->status) ? ($hospital->city->status ? 'active' : 'inactive') : null,
                    'created_at' => $hospital->city ? date('d-m-Y H:i', strtotime($hospital->city->created_at)) : null,
                ],
                'zip_code' => $hospital->zip_code,
                'created_at' => date('d-m-Y H:i',strtotime($hospital->created_at)),
                // 'updated_at' => $hospital->updated_at,
            ];
        });

        // Return the response
        return $this->successResponse($hospitals, 'Hospitals retrieved successfully.', 200);
    }


    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'boolean',
            'email' => 'required|email|unique:users,email|max:200',
            'purchase_contact' => 'string|max:150',
            'phone' => 'numeric|digits_between:10,20',
            'start_date' => 'date',
            'end_date' => 'date',
            'notes' => 'string|max:1000',
            'billing_contact' => 'required|string|max:200',
            'billing_phone' => 'required|numeric|digits_between:10,20',
            'billing_fax' => 'numeric|digits_between:10,20',
            'billing_address' => 'string|max:250',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'zip_code' => 'required|numeric|digits:10',
            'otp_spanish'=>'numeric',
            'otp_other'=>'numeric',
            'appointments_spanish'=>'numeric',
            'appointments_other'=>'numeric',
            'translations_spanish'=>'numeric',
            'translations_other'=>'numeric',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        try {
            
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'status' => $request->status ?? 1,
                'role' => 'client',
            ]);

            $client = Client::create([
                'user_id'=>$user->id,
                'purchase_contact'=>$request->purchase_contact,
                'phone'=>$request->phone,
                'start_date'=>$request->start_date,
                'end_date'=>$request->end_date,
                'notes'=>$request->notes
            ]);
            
            $client_billing = ClientBillingInfo::create([
                'client_id'=>$client->id,
                'contact'=>$request->billing_contact,
                'phone'=>$request->billing_phone,
                'fax'=>$request->billing_fax,
                'address'=>$request->billing_address,
                'state_id'=>$request->state_id,
                'city_id'=>$request->city_id,
                'zip_code'=>$request->zip_code,
            ]);

            $client_service_rates = ClientServiceRate::create([
                'client_id'=>$client->id,
                'otp_spanish'=>$request->otp_spanish,
                'otp_other'=>$request->otp_other,
                'appointments_spanish'=>$request->appointments_spanish,
                'appointments_other'=>$request->appointments_other,
                'translations_spanish'=>$request->translations_spanish,
                'translations_other'=>$request->translations_other,
            ]);
            $client->load(['billingInfo', 'serviceRates']);

            return $this->successResponse($client, 'Client created successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during creation: ' . $e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        $hospital = Hospital::find($id);
        if (!$hospital) {
            return $this->errorResponse('Hospital not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'phone' => 'required|numeric|digits_between:10,20',
            // 'email' => 'required|email|max:200',
            'address_line1' => 'required|string|max:200',
            // 'address_line2' => 'nullable|string|max:200',
            'status' => 'boolean',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'zip_code' => 'required|numeric|digits:10',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $hospital->name = $request->name;
            // $hospital->email = $request->email;
            $hospital->phone = $request->phone;
            $hospital->address_line1 = $request->address_line1;
            // $hospital->address_line2 = $request->address_line2;
            $hospital->status = $request->status;
            $hospital->state_id = $request->state_id;
            $hospital->city_id = $request->city_id;
            $hospital->zip_code = $request->zip_code;
            $hospital->save();

            $hospital->load(['state', 'city']);

            return $this->successResponse($hospital, 'Hospital updated successfully.', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred during updating: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $hospital = Hospital::find($id);

        if (!$hospital) {
            return $this->errorResponse('Hospital not found.', 404);
        }

        $hospital->delete();
        return $this->successResponse(null, 'Hospital deleted successfully.');
    }

    public function deleteMultipleRecords(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:hospitals,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $ids = $validator->validated()['ids'];

            $deletedCount = Hospital::whereIn('id', $ids)->delete();

            if ($deletedCount > 0) {
                return $this->successResponse([], 'Records deleted successfully.', 200);
            } else {
                return $this->errorResponse('No records found to delete.', 404);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while deleting records: ' . $e->getMessage(), 500);
        }
    }
    public function changeStatus(Request $request ,$id)
    {
        $user = Hospital::find($id);

        if (!$user) {
            return $this->errorResponse('Hospital not found.', 404);
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
