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

    /**
     * The four status tabs form a PARTITION: every fc_registration_master row falls in
     * exactly one of Not Responded / Incomplete / Registered / Exemption.
     *
     * The previous scopes keyed Not Responded and Registered off admission_status = 0 / 1
     * literally, so the 47 rows where admission_status is NULL (plus one holding the junk
     * string 're') appeared in NO tab at all — 43 of 534 rows were invisible and the badges
     * summed to 491 instead of 534. Registration completion is now read from
     * admission_status OR is_registered, and "not responded" means the trainee never even
     * received staged credentials, which is what the label actually claims.
     */

    /** Trainee has completed registration (admission marked, or the registration flag set). */
    public function scopeWhereRegistrationComplete(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('admission_status', self::STATUS_REGISTERED);

            // fc_schema_has_column(), not Schema::hasColumn() — these scopes run on every
            // status-page load and the facade hits information_schema uncached each time.
            if (fc_schema_has_column($this->getTable(), 'is_registered')) {
                $q->orWhere('is_registered', 1);
            }
        });
    }

    /** Trainee has NOT completed registration. Mirror of the above — keep the two in step. */
    public function scopeWhereRegistrationIncomplete(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q) {
                $q->where('admission_status', '!=', self::STATUS_REGISTERED)
                    ->orWhereNull('admission_status');
            })
            ->when(fc_schema_has_column($this->getTable(), 'is_registered'), function (Builder $q) {
                $q->where(function (Builder $w) {
                    $w->where('is_registered', '!=', 1)->orWhereNull('is_registered');
                });
            });
    }

    /** Exemption applications are their own bucket and never counted as registrations. */
    public function scopeWhereNotExemption(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('application_type', '!=', self::APPLICATION_EXEMPTION)
                ->orWhereNull('application_type');
        });
    }

    /** Credentials were staged for the trainee, i.e. they were able to start the form. */
    public function scopeWhereHasStagedCredentials(Builder $query, bool $has = true): Builder
    {
        if ($has) {
            return $query
                ->whereNotNull('user_id')->where('user_id', '!=', '')
                ->whereNotNull('password')->where('password', '!=', '');
        }

        return $query->where(function (Builder $q) {
            $q->whereNull('user_id')->orWhere('user_id', '')
                ->orWhereNull('password')->orWhere('password', '');
        });
    }

    /** Never engaged: no credentials staged, not registered, no exemption applied. */
    public function scopeNotResponded(Builder $query): Builder
    {
        return $query->whereNotExemption()
            ->whereRegistrationIncomplete()
            ->whereHasStagedCredentials(false);
    }

    public function scopeRegistered(Builder $query): Builder
    {
        return $query->whereNotExemption()->whereRegistrationComplete();
    }

    public function scopeExemption(Builder $query): Builder
    {
        return $query->where('application_type', self::APPLICATION_EXEMPTION);
    }

    /**
     * Started but unfinished: credentials staged, not registered, no exemption applied.
     *
     * BEHAVIOUR CHANGE, deliberate. This scope previously fell back to
     * `where('final_submit', self::SUBMISSION_DRAFT)` on a deployment whose
     * fc_registration_master has no `is_registered` column. That fallback is gone rather
     * than ported, because it answered a different question ("is the form a draft?") and
     * ignored exemptions and staged credentials entirely — so the four tabs did not
     * partition the roster and rows fell through every one of them.
     *
     * The definition below degrades correctly instead: without `is_registered` the
     * whereRegistrationIncomplete() clause simply reads admission_status alone, and
     * Not Responded / Incomplete / Registered / Exemption still cover every row exactly once.
     * SUBMISSION_DRAFT is retained on the model for other callers.
     */
    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->whereNotExemption()
            ->whereRegistrationIncomplete()
            ->whereHasStagedCredentials(true);
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
