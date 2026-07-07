<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

// class FoundationCourseStatus extends Model
// {
//     use HasFactory;
// }

// app/Models/FoundationCourse.php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class FoundationCourseStatus extends Model
{
    use HasFactory;

    protected $table = 'fc_registration_master';

    protected $primaryKey = 'pk';

    protected $appends = ['full_name', 'service_label', 'exemption_reason'];

    const STATUS_NOT_RESPONDED = 0;

    const STATUS_REGISTERED = 1;

    const APPLICATION_EXEMPTION = 2;

    const SUBMISSION_DRAFT = 1;

    protected $guarded = [];

    public function service()
    {
        return $this->belongsTo(ServiceMaster::class, 'service_master_pk', 'pk');
    }

    public function exemption()
    {
        return $this->belongsTo(FcExemptionMaster::class, 'fc_exemption_master_pk', 'Pk');
    }

    public function scopeNotResponded(Builder $query): Builder
    {
        return $query->where('admission_status', self::STATUS_NOT_RESPONDED);
    }

    public function scopeRegistered(Builder $query): Builder
    {
        return $query->where('admission_status', self::STATUS_REGISTERED);
    }

    public function scopeExemption(Builder $query): Builder
    {
        return $query->where('application_type', self::APPLICATION_EXEMPTION);
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        if (Schema::hasColumn('fc_registration_master', 'is_registered')) {
            return $query
                ->where(function (Builder $q) {
                    $q->where('is_registered', 0)->orWhereNull('is_registered');
                })
                ->where(function (Builder $q) {
                    $q->where('application_type', '!=', self::APPLICATION_EXEMPTION)
                        ->orWhereNull('application_type');
                })
                ->where('admission_status', '!=', self::STATUS_REGISTERED)
                ->whereNotNull('user_id')
                ->where('user_id', '!=', '');
        }

        return $query->where('final_submit', self::SUBMISSION_DRAFT);
    }

    public function getFullNameAttribute(): string
    {
        $built = trim(
            ($this->first_name ?? '').' '.
            ($this->middle_name ?? '').' '.
            ($this->last_name ?? '')
        );

        if ($built !== '') {
            return strtoupper($built);
        }

        return strtoupper(trim((string) ($this->display_name ?? ''))) ?: '—';
    }

    public function getServiceLabelAttribute(): string
    {
        $service = $this->relationLoaded('service') ? $this->service : null;
        if (! $service) {
            return 'NOT APPLICABLE';
        }

        $name = trim((string) ($service->service_name ?? $service->service_short_name ?? ''));

        return $name !== '' ? strtoupper($name) : 'NOT APPLICABLE';
    }

    public function getExemptionReasonAttribute(): string
    {
        $exemption = $this->relationLoaded('exemption') ? $this->exemption : null;
        $name = trim((string) ($exemption->Exemption_name ?? ''));

        return $name !== '' ? $name : '—';
    }
}
