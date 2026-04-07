<?php

namespace App\Jobs;

use App\Mail\QuotationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class SendQuotationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $translation;
    protected $clientName;
    protected $clientEmail;
    protected $costTable;
    protected $notes;
    protected $version;

    /**
     * Create a new job instance.
     */
    public function __construct($translation, $clientName, $clientEmail, $costTable, $notes, $version)
    {
        $this->translation = $translation;
        $this->clientName = $clientName;
        $this->clientEmail = $clientEmail;
        $this->costTable = $costTable;
        $this->notes = $notes;
        $this->version = $version;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Fetch rates from translation->accounts->interpretationRates (SubClientTypeInterpretationRate)
        $account = $this->translation->accounts;
        $rateModel = $account->interpretationRates->first();
        $spanishRate = $rateModel->spanish_translation_rate ?? 0;
        $spanishFormatting = $rateModel->spanish_formatting_rate ?? 0;
        $otherRate = $rateModel->other_translation_rate ?? 0;
        $otherFormatting = $rateModel->other_formatting_rate ?? 0;

        // Generate PDF from Blade template using costTable and notes
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('quotations.quote_template', [
            'translation' => $this->translation,
            'clientName' => $this->clientName,
            'cost_table' => $this->costTable,
            'notes' => $this->notes,
            'spanishRate' => $spanishRate,
            'spanishFormatting' => $spanishFormatting,
            'otherRate' => $otherRate,
            'otherFormatting' => $otherFormatting,
        ]);
        $pdfPath = 'storage/app/public/quotations/quotation_' . $this->translation->id . '_v' . $this->version . '.pdf';
        $pdfPathRelativePath = 'storage/quotations/quotation_' . $this->translation->id . '_v' . $this->version . '.pdf';
        $pdf->save(base_path($pdfPath));

        $this->translation->status='Quote Sent';
        $this->translation->save();

        // Update quotation with pdf_path
        $quotation = \App\Models\Quotation::where('translation_id', $this->translation->id)->where('version', $this->version)->first();
        if ($quotation) {
            $quotation->pdf_path = $pdfPathRelativePath;
            $quotation->save();
        }

        if($this->version == 1){
            
            $subject = 'Quotation - Document Translation # '.$this->translation->transid.' - '.config("app.name");
            $content = 'Your translation request has been received and a quotation has been generated. Please find the attached quotation PDF for your review.';
        }
        else{
            
            $subject = 'Quotation Revised - Document Translation # '.$this->translation->transid.' - '.config("app.name");
            $content = 'A new version of your quotation has been generated. Please find the attached quotation PDF for your review.';
        }
        
        $redirect_link = config('app.frontend_url').'/translations/view/'.$this->translation->id;
        // Send email using helper
        sendMail([
            'recipient' => $this->clientEmail,
            'name' => $this->clientName,
            'subject' => $subject,
            'content'=>$content,
            'quotation_pdf' => base_path($pdfPath),
            'clientName' => $this->clientName,
            'translation_id' => $this->translation->id,
            'redirect_link'=> $redirect_link,
            'button_text'=>'View Request'
        ]);
    }
}
