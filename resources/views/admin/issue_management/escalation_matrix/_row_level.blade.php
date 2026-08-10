{{-- One escalation level cell ("Name  [N days]") for the Escalation Matrix grid.

     Rendered per row by IssueEscalationMatrixController::data() and returned as a raw
     column. $level is an IssueCategoryEmployeeMap or null. --}}
@if($level)
    {{ $level->employee->name ?? 'N/A' }} <span class="badge bg-info">{{ $level->days_notify }} days</span>
@else
    <span class="text-muted">&mdash;</span>
@endif
