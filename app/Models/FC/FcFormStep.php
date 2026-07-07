<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FcFormStep extends Model
{
    protected $fillable = [
        'form_id', 'step_name', 'step_slug', 'step_number', 'target_table',
        'completion_column', 'tracker_column', 'is_active', 'description', 'icon',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(FcForm::class, 'form_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FcFormField::class, 'step_id')->orderBy('display_order');
    }

    public function activeFields(): HasMany
    {
        return $this->hasMany(FcFormField::class, 'step_id')->where('is_active', 1)->orderBy('display_order');
    }

    public function fieldGroups(): HasMany
    {
        return $this->hasMany(FcFormFieldGroup::class, 'step_id')->orderBy('display_order');
    }

    public function activeFieldGroups(): HasMany
    {
        return $this->hasMany(FcFormFieldGroup::class, 'step_id')->where('is_active', 1)->orderBy('display_order');
    }

    /**
     * Whether this step uses tabbed field groups (e.g. Other Details / step 3).
     * Copied forms prefix the slug: {form_slug}-step3 — not only the literal "step3".
     */
    public function usesFieldGroups(): bool
    {
        if ($this->relationLoaded('fieldGroups')) {
            if ($this->fieldGroups->isNotEmpty()) {
                return true;
            }
        } elseif ($this->fieldGroups()->exists()) {
            return true;
        }

        return $this->isStep3Type();
    }

    public function isStep3Type(): bool
    {
        $slug = (string) ($this->step_slug ?? '');

        return $slug === 'step3'
            || $slug === '99th-step3'
            || str_ends_with($slug, '-step3');
    }

    /**
     * Whether this step uses the shared document checklist editor.
     */
    public function isDocumentsStep(): bool
    {
        $slug = (string) ($this->step_slug ?? '');

        return $slug === 'documents' || str_ends_with($slug, '-documents');
    }

    /**
     * Canonical step key for progress/sequencing (step1, step3, bank, …).
     * Copied forms store slugs like fc-registration-copy-step3.
     */
    public function logicalStepKey(): string
    {
        $slug = (string) ($this->step_slug ?? '');

        if ($this->relationLoaded('form') && $this->form) {
            $prefix = $this->form->form_slug . '-';
            if (str_starts_with($slug, $prefix)) {
                return substr($slug, strlen($prefix));
            }
        }

        return $slug;
    }
}
