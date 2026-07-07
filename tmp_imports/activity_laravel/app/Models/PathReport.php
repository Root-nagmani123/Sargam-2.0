<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/** path_report — pathology PDF upload (from upload_report.php + view_otreport.php) */
class PathReport extends Model
{
    protected $table = 'path_report';
    protected $fillable = ['userid','path_report','doc_report','course','status','submit_dt'];
    protected $casts   = ['submit_dt' => 'datetime'];

    public function ot() { return $this->belongsTo(OtDetail::class,'userid','username'); }
}
