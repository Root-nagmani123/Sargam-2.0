{{-- Row actions for the Escalation Matrix grid.

     Rendered per row by IssueEscalationMatrixController::data() and returned as a raw
     column. Faithful copy of the previous inline markup — editMatrix() takes the
     category plus all three levels' employee/day pairs. --}}
<button type="button" class="btn btn-sm btn-warning"
        onclick="editMatrix({{ $row['category']->pk }}, {{ Illuminate\Support\Js::from($row['category']->issue_category) }}, {{ $row['level1']?->employee_master_pk ?? 'null' }}, {{ $row['level1']?->days_notify ?? 0 }}, {{ $row['level2']?->employee_master_pk ?? 'null' }}, {{ $row['level2']?->days_notify ?? 0 }}, {{ $row['level3']?->employee_master_pk ?? 'null' }}, {{ $row['level3']?->days_notify ?? 0 }})">
    <iconify-icon icon="solar:pen-bold"></iconify-icon> Edit
</button>
