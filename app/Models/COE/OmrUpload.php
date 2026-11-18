<?php

namespace App\Models\COE;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One OMR scanner CSV uploaded on COE › Result Management › OMR Upload.
 * Its answer sheets are OmrResponse rows, deleted with it.
 */
class OmrUpload extends Model
{
    protected $table = 'coe_omr_uploads';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'original_file_name',
        'file_hash',
        'remarks',
        'question_count',
        'has_score',
        'total_rows',
        'error_rows',
        'created_by',
        'created_date',
    ];

    protected $casts = [
        'question_count' => 'integer',
        'has_score' => 'boolean',
        'total_rows' => 'integer',
        'error_rows' => 'integer',
        'created_by' => 'integer',
        'created_date' => 'datetime',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(OmrResponse::class, 'coe_omr_upload_pk', 'pk');
    }
}
