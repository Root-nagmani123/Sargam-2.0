<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExemptionCategory extends Model
{
    use HasFactory;

    protected $table = 'fc_exemption_master'; // your table name
    protected $primaryKey = 'pk';

    protected $fillable = [
        'cse_heading',
        'cse_subheading',
        'attended_heading',
        'attended_subheading',
        'medical_heading',
        'medical_subheading',
        'optout_heading',
        'optout_subheading',
        'important_notice',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
