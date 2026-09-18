{{-- There is no detail route for a mapping, so View opens a read-only modal
     built from the row's own data. Rendered per row by
     IssueEscalationMatrixController::data(); the click handler is delegated. --}}
<div class="ic-act-group" role="group" aria-label="Row actions">
    <button type="button" class="ic-act ic-act--view em-view-btn"
            aria-label="View escalation mapping for {{ $row['category']->issue_category }}"
            data-category="{{ $row['category']->issue_category }}"
            data-category-id="{{ $row['category']->pk }}"
            data-l1-name="{{ $row['level1']->employee->name ?? '' }}"
            data-l1-days="{{ $row['level1']->days_notify ?? '' }}"
            data-l1-emp="{{ $row['level1']->employee_master_pk ?? '' }}"
            data-l2-name="{{ $row['level2']->employee->name ?? '' }}"
            data-l2-days="{{ $row['level2']->days_notify ?? '' }}"
            data-l2-emp="{{ $row['level2']->employee_master_pk ?? '' }}"
            data-l3-name="{{ $row['level3']->employee->name ?? '' }}"
            data-l3-days="{{ $row['level3']->days_notify ?? '' }}"
            data-l3-emp="{{ $row['level3']->employee_master_pk ?? '' }}">
        <span class="ic-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
        <span class="ic-act__label">View</span>
    </button>
</div>
