<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $translation;
    public $clientName;
    public $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct($translation, $clientName, $pdfPath)
    {
        $this->translation = $translation;
        $this->clientName = $clientName;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Quotation from AlgoviCRM')
            ->view('quotations.quote_template')
            ->attach($this->pdfPath, [
                'as' => 'Quotation.pdf',
                'mime' => 'application/pdf',
            ])
            ->with([
                'translation' => $this->translation,
                'clientName' => $this->clientName,
            ]);
    }
}
