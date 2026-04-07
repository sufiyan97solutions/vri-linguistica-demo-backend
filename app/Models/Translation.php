<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $table = 'translations';

    // status
    // 'New Request','Quote Sent','Quote Rejected','Quote Revised','Quote Approved','Translators Invited','Assigned','Under Review','Client Request Editing','Client Cancelled','Submission Rejected','Invoice Sent','Translator Declined','Cancelled','Completed'
    protected $fillable = [
        'transid',
        'account_id',
        'source_language_id',
        'requester_name',
        'status',
        'interpreter_id',
        'total_words_count',
        'est_words_count',
        'total_amount',
        'est_amount',
        'translated_files',
        'invoice'
    ];

    public function translationDetails(){
        return $this->hasOne(TranslationDetail::class);
    }
    
    public function translationTargetLanguages(){
        return $this->hasMany(TranslationTargetLanguage::class);
    }

    public function language(){
        return $this->belongsTo(Language::class,'source_language_id');
    }
    
    public function translationFiles(){
        return $this->hasMany(TranslationFile::class);
    }

    public function translationTranslatedFiles()
    {
        return $this->hasMany(TranslationTranslatedFile::class)->orderBy('id', 'desc');
    }
    
    public function accounts()
    {
        return $this->belongsTo(SubClientType::class, 'account_id', 'id');
    }
    
    public function interpreter()
    {
        return $this->belongsTo(Interpreter::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class)->orderBy('version', 'desc');
    }

    public function latestQuotation()
    {
        // This method returns the latest quotation for the translation
        // It uses the latestOfMany method to get the most recent quotation based on the version
        return $this->hasOne(Quotation::class)->latestOfMany('version');
        // return $this->hasMany
        // (Quotation::class)->latestOfMany();
    }

    public function translationLogs()
    {
        return $this->hasMany(TranslationLog::class)->orderBy('id', 'desc');
    }

    public function translationInvites()
    {
        return $this->hasMany(TranslationInvite::class);
    }

    public function translationInvoices()
    {
        return $this->hasOne(TranslationInvoice::class);
    }


}
