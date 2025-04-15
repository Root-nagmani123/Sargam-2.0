<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeMaster extends Model
{
    protected $table = 'employee_master';
    public $timestamps = false;

    protected $guarded = [];

    public const title = [
        1 => 'Mr',
        2 => 'Mrs'
    ];

    public const gender = [
        1 => 'Male',
        2 => 'Female',
        3 => 'Other'
    ];

    public const maritalStatus = [
        1 => 'Single',
        2 => 'Married',
        3 => 'Other'
    ];

    public const casteCategory = [
        1 => 'General',
        2 => 'OBC',
        3 => 'SC',
        4 => 'ST',
        5 => 'Other'
    ];
    
}
