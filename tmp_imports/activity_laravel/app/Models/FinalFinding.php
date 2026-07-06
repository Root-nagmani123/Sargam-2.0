<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/** final_findings — doctor's clinical notes per OT (from upload_report.php + view_otreport.php) */
class FinalFinding extends Model
{
    protected $table = 'final_findings';
    protected $fillable = ['userid','findings','course','submited_by','status','submit_dt'];
    protected $casts   = ['submit_dt' => 'datetime'];

    public function ot() { return $this->belongsTo(OtDetail::class,'userid','username'); }
}
