<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facades\Pdf;

class TranslationInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $translation;
    public $clientName;
    public $clientEmail;
    public $costTable;
    public $paid;

    public function __construct($translation, $clientName, $clientEmail, $costTable,$paid=0)
    {
        $this->translation = $translation;
        $this->clientName = $clientName;
        $this->clientEmail = $clientEmail;
        $this->costTable = $costTable;
        $this->paid = $paid;
    }

    public function build()
    {
        // Prepare details for notification_mail
        if($this->paid){
            $subject = 'Invoice Paid - Document Translation # '.$this->translation->transid.' - '.config("app.name");
            $content = 'The invoice has been marked as paid for the translation request #'.$this->translation->transid.'. If you have any questions, feel free to contact us.';
            
        }
        else{
            $subject = 'Pending Invoice - Document Translation # '.$this->translation->transid.' - '.config("app.name");
            $content = 'The translation request #'.$this->translation->transid.' has been completed. Please find the attached invoice. If you have any questions, feel free to contact us.';
        }
    
        $details = [
            'name' => $this->clientName,
            'subject' => $subject,
            'content' => $content,
            'recipient' => $this->clientEmail,
            'email'=>$this->clientEmail,
            'paid' => $this->paid,
        ];

        // Generate PDF from translation_invoice.blade.php
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('mails.translation_invoice', [
            'translation' => $this->translation,
            'clientName' => $this->clientName,
            'clientEmail' => $this->clientEmail,
            'cost_table' => $this->costTable,
            'paid' => $this->paid
        ]);
        
        $pdfPath = 'storage/app/public/invoices/invoice_' . $this->translation->id .'.pdf';
        $pdfPathRelativePath = 'storage/invoices/invoice_' . $this->translation->id .'.pdf';
        $pdf->save(base_path($pdfPath));

        $this->translation->translationInvoices()->update(
            [
                'invoice_path' => $pdfPathRelativePath,
            ]
        );

        return $this->subject($details['subject'])
            ->view('mails.notification_mail')
            ->with(['details' => $details])
            ->attach(base_path($pdfPath), [
                'as' => 'Invoice - Translation Request #'.$this->translation->transid.'.pdf',
                'mime' => 'application/pdf',
            ]);
            // ->attachData($pdf->output(), 'Invoice - Translation Request #'.$this->translation->transid.'.pdf');
    }
}
