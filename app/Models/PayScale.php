<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayScale extends Model
{
    protected $table = 'estate_pay_scale_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = ['pay_scale_range', 'pay_scale_level', 'display_label'];

    protected $appends = ['display_label_text'];

    public function getDisplayLabelTextAttribute(): string
    {
        return $this->display_label
            ?: $this->pay_scale_range . ' (' . $this->pay_scale_level . ')';
    }
}
