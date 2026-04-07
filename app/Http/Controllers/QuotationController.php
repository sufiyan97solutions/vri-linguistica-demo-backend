<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Quotation;
use App\Models\Translation;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Jobs\SendQuotationJob;
use App\Models\TranslationLog;

class QuotationController extends Controller
{
    use ApiResponseTrait;
    private $userId;
    public function __construct()
    {
        $this->userId = auth('api')?->user()?->id;
    }
    public function getLatest($translation_id)
    {
        $quotation = Quotation::where('translation_id', $translation_id)
            ->orderByDesc('version')
            ->first();
        if (!$quotation) {
            return $this->errorResponse('Quotation not found.', 404);
        }
        return $this->successResponse($quotation, 'Latest quotation retrieved successfully.', 200);
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'cost_table' => 'required|array|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        $translation = Translation::find($id);
        if (!$translation) {
            return $this->errorResponse('Translation not found.', 404);
        }

        // Create new version
        $lastVersion = Quotation::where('translation_id', $translation->id)->max('version');
        $newVersion = $lastVersion ? $lastVersion + 1 : 1;
        $newQuotation = Quotation::create([
            'translation_id' => $translation->id,
            'version' => $newVersion,
            'notes' => $request->notes ?? null,
            'status' => 'revised',
            'updated_by' => $this->userId,
            'cost_table' => json_encode($request->cost_table) ?? null,
        ]);
        // Generate PDF and send to client
        $clientUser = $translation->accounts->user ?? null;
        if ($clientUser && $clientUser->email) {
            $clientName = $clientUser->name;
            $clientEmail = $clientUser->email;
        }

        if ($clientEmail) {
            // Dispatch job to generate PDF and send email asynchronously
            SendQuotationJob::dispatch($translation, $clientName, $clientEmail, $newQuotation->cost_table, $newQuotation->notes, $newQuotation->version);
        }
        
        $log_changes = 'Quote # '.$newQuotation->version.' revised';
        TranslationLog::create([
            'translation_id' => $translation->id,
            'date' => date('Y-m-d'),
            'time' => date('h:i a'),
            'user_id' => $this->userId,
            'event' => 'Quote Revised',
            'notes' => $log_changes,
        ]);
        $translation->status = 'Quote Revised';
        $translation->save();
        return $this->successResponse($newQuotation, 'Quotation revised and will be sent to client.', 200);
    }

    public function approve($id)
    {
        $translation = Translation::with('accounts.user')->find($id);
        if (!$translation) {
            return $this->errorResponse('Translation not found.', 404);
        }
        
        $latestQuotation = $translation->quotations()->orderByDesc('version')->first();
        if ($latestQuotation) {
            $latestQuotation->update([
                'approved' => 1,
                'approved_by' => $this->userId,
                'approved_at' => now(),
            ]);

            if(auth('api')?->user()->role=='main_account'){
                // $subject = 'Quote Approved';
                // $content = 'Thank you for approving the quotation for transation request #'.$translation->transid.'. We will proceed with the next steps.';
                // $redirect_link = config('app.frontend_url').'/user/translation/view/'.$translation->id;
                // $data = [
                //     'name'=>$request->requester_name,
                //     'subject'=>$subject,
                //     'content'=>$content,
                //     'email'=>$request->requester_email,
                //     'button_text'=>'View Appointment',
                //     'redirect_link'=>$redirect_link,
                //     'recipient'=>$request->requester_email,
                // ];
            }
            else{
                $subject = 'Quote Approved - Document Translation # '.$translation->transid.' - '.config("app.name");
                // $subject = 'Quote Approved - ';
                $content = 'The quotation for translation request #'.$translation->transid.' has been approved. We will proceed with the next steps.';
                $redirect_link = config('app.frontend_url').'/user/translations/view/'.$translation->id;
                $email = $translation->accounts->user->email;
                $data = [
                    'name'=>$translation->accounts->user->name,
                    'subject'=>$subject,
                    'content'=>$content,
                    'email'=>$email,
                    'button_text'=>'View Request',
                    'redirect_link'=>$redirect_link,
                    'recipient'=>$email,
                ];
                sendMail($data);
            }
            
            $log_changes = 'Quote # '.$translation->latestQuotation->version.'  approved';
            TranslationLog::create([
                'translation_id' => $translation->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'user_id' => $this->userId,
                'event' => 'Quote Approved',
                'notes' => $log_changes,
            ]);
            
            $translation->status = 'Quote Approved';
            $translation->save();
        }
        return $this->successResponse([], 'Quotation approved successfully.', 200);
    }

    public function reject(Request $request,$id)
    {
        $translation = Translation::find($id);
        if (!$translation) {
            return $this->errorResponse('Translation not found.', 404);
        }
        
        // $translation->quotations()->getLatest()->update(['status' => 'approved', 'approved_by' => $this->userId, 'approved_at' => now()]);
        // $translation->quotations()->getLatest()->update(['rejected' => true, 'rejected_by' => $this->userId, 'rejected_at' => now(), 'rejection_reason' => $request->rejection_reason ?? null]);

        $latestQuotation = $translation->quotations()->orderByDesc('version')->first();
        if ($latestQuotation) {
            $latestQuotation->update([
                'rejected' => 1,
                'rejected_by' => $this->userId,
                'rejected_at' => now(),
                'rejection_reason' => $request->rejection_reason ?? null,
            ]);

            if(auth('api')?->user()->role=='main_account'){
                // $subject = 'Quote Approved';
                // $content = 'Thank you for approving the quotation for transation request #'.$translation->transid.'. We will proceed with the next steps.';
                // $redirect_link = config('app.frontend_url').'/user/translation/view/'.$translation->id;
                // $data = [
                //     'name'=>$request->requester_name,
                //     'subject'=>$subject,
                //     'content'=>$content,
                //     'email'=>$request->requester_email,
                //     'button_text'=>'View Appointment',
                //     'redirect_link'=>$redirect_link,
                //     'recipient'=>$request->requester_email,
                // ];
            }
            else{
                $subject = 'Quote Rejected - Document Translation # '.$translation->transid.' - '.config("app.name");
                // $subject = 'Quote Approved - ';
                $content = 'The quotation for translation request #'.$translation->transid.' has been rejected with the reason:<br>';
                $content .= '<i>"'.$translation->latestQuotation->rejection_reason.'"</i>';
                // $content .= '<br>We will proceed with the next steps.';
                $redirect_link = config('app.frontend_url').'/user/translations/view/'.$translation->id;
                $email = $translation->accounts->user->email;
                $data = [
                    'name'=>$translation->accounts->user->name,
                    'subject'=>$subject,
                    'content'=>$content,
                    'email'=>$email,
                    'button_text'=>'View Request',
                    'redirect_link'=>$redirect_link,
                    'recipient'=>$email,
                ];
                sendMail($data);
            }

            $log_changes = 'Quote # '.$translation->latestQuotation->version.'  rejected with the reason: '.$translation->latestQuotation->rejection_reason;

            TranslationLog::create([
                'translation_id' => $translation->id,
                'date' => date('Y-m-d'),
                'time' => date('h:i a'),
                'user_id' => $this->userId,
                'event' => 'Quote Rejected',
                'notes' => $log_changes,
            ]);

            $translation->status = 'Quote Rejected';
            $translation->save();
        }

        return $this->successResponse([], 'Quotation rejected successfully.', 200);
    }
}
