<?php

namespace App\Models\COE;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One scriber on COE → Master Data → Scriber's Master.
 *
 * Inactive scribers are the screen's Archived tab and are not offered for new
 * assignments; see the migration for why there is no separate archive flag.
 */
class ScriberMaster extends Model
{
    public const ROLE_PRIMARY = 1;
    public const ROLE_SECONDARY = 2;

    protected $table = 'coe_scriber_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'scriber_name',
        'phone_number',
        'email',
        'scriber_role',
        'remarks',
        'active_inactive',
    ];

    protected $casts = [
        'scriber_role' => 'integer',
        'active_inactive' => 'boolean',
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    /**
     * Role key => label. One list for the form, its validation, the filter and the grid.
     *
     * @return array<int, string>
     */
    public static function roles(): array
    {
        return [
            self::ROLE_PRIMARY => 'Primary',
            self::ROLE_SECONDARY => 'Secondary',
        ];
    }

    public static function roleLabel(?int $role): string
    {
        return self::roles()[$role] ?? '—';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active_inactive', 1);
    }
}
