<?php

namespace App\DataTables\Concerns;

/**
 * Status and Action cells of the COE server-side masters, the same markup as
 * coe-grid.js's paintState() so toggling matches an un-reloaded row.
 */
trait CoeMasterCells
{
    private function statusCell(bool $active): string
    {
        return '<span class="coe-state coe-state--' . ($active ? 'active' : 'inactive') . '">'
            . ($active ? 'Active' : 'Inactive') . '</span>';
    }

    /** $blocked: why Delete is disabled ('' = enabled). */
    private function actionsCell(bool $active, string $blocked = ''): string
    {
        if ($active) {
            $blocked = 'Deactivate it before deleting it';
        }

        return '<div class="coe-act-group" role="group" aria-label="Row actions">'
            . '<button type="button" class="coe-act coe-act--edit">'
            . '<span class="coe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>'
            . '<span class="coe-act__label">Edit</span></button>'
            . '<label class="coe-act coe-act--toggle ' . ($active ? 'is-on' : 'is-off') . '">'
            . '<span class="coe-act__icon"><input type="checkbox" role="switch" '
            . 'class="form-check-input plain-status-toggle coe-switch"' . ($active ? ' checked' : '')
            . ' aria-label="' . ($active ? 'Deactivate' : 'Activate') . '"></span>'
            . '<span class="coe-act__label">' . ($active ? 'Deactivate' : 'Activate') . '</span></label>'
            . '<button type="button" class="coe-act coe-act--del"'
            . ($blocked !== '' ? ' disabled title="' . e($blocked) . '"' : '') . '>'
            . '<span class="coe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>'
            . '<span class="coe-act__label">Delete</span></button>'
            . '</div>';
    }

    /** Status filter: 1 = active; anything else is inactive (these tables store 2, older rows 0). */
    private function applyStatusFilter($query, string $column = 'active_inactive')
    {
        $status = request()->query('status_filter');
        if ($status === '1') {
            $query->where($column, 1);
        } elseif ($status === '0') {
            $query->where($column, '!=', 1);
        }

        return $query;
    }
}
