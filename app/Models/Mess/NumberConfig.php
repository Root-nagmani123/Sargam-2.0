<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumberConfig extends Model
{
    use HasFactory;
    
    protected $table = 'mess_number_configs';
    
    protected $fillable = [
        'config_type',
        'prefix',
        'current_number',
        'padding',
        'sample_format'
    ];
    
    public static function generateNumber($type)
    {
        $config = self::firstOrCreate(
            ['config_type' => $type],
            ['prefix' => strtoupper(substr($type, 0, 2)), 'current_number' => 0, 'padding' => 4]
        );
        
        $config->increment('current_number');
        
        return $config->prefix . str_pad($config->current_number, $config->padding, '0', STR_PAD_LEFT);
    }
}
