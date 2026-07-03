<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/** pre_history — OT pre-medical history (from load_otname.php red-flag check + view_otreport.php) */
class PreHistory extends Model
{
    protected $table   = 'pre_history';
    protected $fillable = [
        'userid','allergy_illness','prolonged_medication','hospital_history',
        'altitude_illness','additional_info','doc_path','course','status',
    ];
    public function ot() { return $this->belongsTo(OtDetail::class,'userid','username'); }
}
