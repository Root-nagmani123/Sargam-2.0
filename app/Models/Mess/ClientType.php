<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class ClientType extends Model
{
    public const TYPE_EMPLOYEE = 'employee';
    public const TYPE_OT = 'ot';
    public const TYPE_COURSE = 'course';
    public const TYPE_OTHER = 'other';
    public const TYPE_SECTION = 'section';

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'mess_client_types';
    
    protected $fillable = [
        'client_type',
        'client_name',
        'status',
    ];

    /**
     * @return array<string,string>
     */
    public static function clientTypes(): array
    {
        return [
            self::TYPE_EMPLOYEE => 'Employee',
            self::TYPE_OT      => 'OT',
            self::TYPE_COURSE  => 'Course',
            self::TYPE_OTHER   => 'Other',
            self::TYPE_SECTION => 'Section',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status ?: self::STATUS_ACTIVE;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status === self::STATUS_ACTIVE ? 'success' : 'danger';
    }
}
