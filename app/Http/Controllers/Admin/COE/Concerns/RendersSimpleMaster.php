<?php

namespace App\Http\Controllers\Admin\COE\Concerns;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * Renders a flat COE "fields + status" master with the shared
 * resources/views/admin/coe/simple_master view (keys documented at its top).
 *
 * Each row is a list of values in the same order as the fields, then the
 * active flag last:  ['Adhar Shila', 'AS-01', 4, 12, true]
 */
trait RendersSimpleMaster
{
    /**
     * @param  array{title:string, entity:string, prefix:string, colvisKey:string, labels?:array,
     *     sno?:array{show?:bool, label?:string}, filters?:list<array{key:string, label:string}>}  $config
     * @param  list<array<string, mixed>>  $fields
     * @param  iterable<int, list<mixed>>  $rows
     */
    protected function renderSimpleMaster(array $config, array $fields, iterable $rows): View
    {
        $entity = $config['entity'];
        $config['labels'] = ($config['labels'] ?? []) + [
            'createButton' => "Create {$entity}",
            'createTitle' => "Create {$entity}",
            'editTitle' => "Edit {$entity}",
            'createSubmit' => 'Create',
            'editSubmit' => 'Update',
        ];
        $config['sno'] = ($config['sno'] ?? []) + ['show' => true, 'label' => 'S. No.'];

        $keys = array_column($fields, 'key');
        // The dialog may ask for fields in a different order than the grid shows them
        // (a parent select first). Every key must appear exactly once.
        $config['formOrder'] = $config['formOrder'] ?? $keys;
        $rows = Collection::make($rows)->values()->map(fn (array $r, int $i) => [
            'pk' => $i + 1,
            'values' => array_combine($keys, array_slice($r, 0, count($keys))),
            'active' => (bool) end($r),
        ]);

        // Filter options come from the rows themselves, so a filter can't offer
        // a value the grid doesn't have (numbers sort numerically).
        $config['filters'] = array_map(function (array $flt) use ($rows) {
            $flt['options'] = $flt['key'] === 'status'
                ? ['Active', 'Inactive']
                : $rows->pluck('values.'.$flt['key'])->unique()
                    ->sort(fn ($a, $b) => is_numeric($a) && is_numeric($b) ? $a <=> $b : strnatcasecmp((string) $a, (string) $b))
                    ->values()->all();

            return $flt;
        }, $config['filters'] ?? []);

        return view('admin.coe.simple_master.index', [
            'master' => $config + ['fields' => $fields, 'rows' => $rows],
        ]);
    }

    /**
     * The single required, unique name field most masters have.
     *
     * @return array<string, mixed>
     */
    protected function nameField(string $name, string $label, string $placeholder, int $max = 100): array
    {
        return [
            'key' => 'name', 'name' => $name, 'label' => $label, 'column' => $label,
            'type' => 'text', 'placeholder' => $placeholder,
            'required' => true, 'max' => $max, 'unique' => true,
        ];
    }
}
