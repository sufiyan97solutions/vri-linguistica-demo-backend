<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Quotation extends Model
{
    protected $table = 'quotations';
    protected $fillable = [
        'user_id', 'status', 'total', 'notes'
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
}
