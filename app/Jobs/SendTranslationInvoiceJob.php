<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use App\Mail\TranslationInvoiceMail;

class SendTranslationInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $record;
    protected $clientName;
    protected $clientEmail;
    protected $costTable;
    protected $paid;

    public function __construct($record, $clientName, $clientEmail, $costTable, $paid = 0)
    {
        $this->record = $record;
        $this->clientName = $clientName;
        $this->clientEmail = $clientEmail;
        $this->costTable = $costTable;
        $this->paid = $paid;
    }

    public function handle()
    {
        Mail::to($this->clientEmail)
            ->send(new TranslationInvoiceMail($this->record, $this->clientName, $this->clientEmail, $this->costTable, $this->paid));
    }
}
