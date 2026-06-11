<?php

namespace App\Console\Commands;

use App\Models\FC\FcFormGroupField;
use App\Models\FC\FcFormStep;
use Illuminate\Console\Command;

class FcSyncStepGroupFieldsCommand extends Command
{
    protected $signature = 'fc:sync-step-group-fields
                            {step? : Target step ID (e.g. 99 on production)}
                            {--from= : Source step ID with existing group fields (default: fc-registration step3)}
                            {--dry-run : Show what would be copied without writing}
                            {--list : List step-3 style steps and field counts}';

    protected $description = 'Copy missing fc_form_group_fields from a source Other Details step into a target step (fixes empty group tabs in form builder)';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listSteps();
        }

        $stepId = $this->argument('step');
        if (! $stepId) {
            $this->error('Provide a target step ID, or use --list to see available steps.');

            return self::FAILURE;
        }

        $target = FcFormStep::with(['form', 'fieldGroups.groupFields'])->find($stepId);
        if (! $target) {
            $this->error("Step {$stepId} not found.");

            return self::FAILURE;
        }

        $source = $this->resolveSourceStep($target);
        if (! $source) {
            $this->error('Source step not found. Pass --from=<step_id> (a step that already has group fields).');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info("Target: [{$target->id}] {$target->step_name} ({$target->step_slug}) — form: ".($target->form->form_slug ?? 'n/a'));
        $this->info("Source: [{$source->id}] {$source->step_name} ({$source->step_slug}) — form: ".($source->form->form_slug ?? 'n/a'));
        if ($dryRun) {
            $this->warn('DRY RUN — no database changes.');
        }

        $sourceGroups = $source->fieldGroups->keyBy('group_name');
        $copied = 0;
        $skipped = 0;
        $unmatched = [];

        foreach ($target->fieldGroups as $targetGroup) {
            $sourceGroup = $sourceGroups->get($targetGroup->group_name);
            if (! $sourceGroup) {
                $unmatched[] = $targetGroup->group_name;

                continue;
            }

            $existingNames = $targetGroup->groupFields->pluck('field_name')->flip();

            foreach ($sourceGroup->groupFields as $srcField) {
                if ($existingNames->has($srcField->field_name)) {
                    $skipped++;

                    continue;
                }

                if (! $dryRun) {
                    $newField = $srcField->replicate();
                    $newField->group_id = $targetGroup->id;
                    $newField->save();
                }

                $copied++;
                $this->line("  + {$targetGroup->group_label} → {$srcField->field_name}");
            }
        }

        if ($unmatched !== []) {
            $this->warn('No matching source group for: '.implode(', ', $unmatched));
        }

        $target->load('fieldGroups.groupFields');
        $total = $target->fieldGroups->sum(fn ($g) => $g->groupFields->count());

        $this->newLine();
        $this->info("Copied: {$copied} | Skipped (already exist): {$skipped} | Total fields on target now: {$total}");

        return self::SUCCESS;
    }

    private function listSteps(): int
    {
        $steps = FcFormStep::with('form')
            ->withCount('fieldGroups')
            ->orderBy('form_id')
            ->orderBy('step_number')
            ->get();

        $rows = [];
        foreach ($steps as $step) {
            if (! $step->usesFieldGroups() && $step->field_groups_count === 0) {
                continue;
            }

            $fieldCount = FcFormGroupField::whereIn(
                'group_id',
                $step->fieldGroups()->pluck('id')
            )->count();

            $rows[] = [
                $step->id,
                $step->form->form_slug ?? '—',
                $step->step_name,
                $step->step_slug,
                $step->field_groups_count,
                $fieldCount,
            ];
        }

        $this->table(
            ['ID', 'Form', 'Step', 'Slug', 'Groups', 'Group fields'],
            $rows
        );

        return self::SUCCESS;
    }

    private function resolveSourceStep(FcFormStep $target): ?FcFormStep
    {
        $fromId = $this->option('from');
        if ($fromId) {
            return FcFormStep::with(['form', 'fieldGroups.groupFields'])->find($fromId);
        }

        $candidates = FcFormStep::with(['form', 'fieldGroups.groupFields'])
            ->whereHas('fieldGroups')
            ->get()
            ->filter(fn (FcFormStep $s) => $s->fieldGroups->sum(fn ($g) => $g->groupFields->count()) > 0);

        return $candidates->first(fn (FcFormStep $s) => ($s->form->form_slug ?? '') === 'fc-registration' && $s->isStep3Type())
            ?? $candidates->first(fn (FcFormStep $s) => $s->isStep3Type())
            ?? $candidates->sortByDesc(fn (FcFormStep $s) => $s->fieldGroups->sum(fn ($g) => $g->groupFields->count()))->first();
    }
}
