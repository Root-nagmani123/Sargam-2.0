<?php
namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionSetting extends Model
{
    use HasFactory;
    protected $table = 'mess_permission_settings';
    protected $fillable = ['role', 'permission'];
}
