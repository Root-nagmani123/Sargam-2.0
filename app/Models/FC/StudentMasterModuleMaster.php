<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;
use App\Models\FC\Concerns\FcUserAware;

class StudentMasterModuleMaster extends Model {
    use FcUserAware;
    protected $table = 'student_master_module_masters';
    protected $fillable = ['user_id', 'username','chosen_module','second_module'];
}
