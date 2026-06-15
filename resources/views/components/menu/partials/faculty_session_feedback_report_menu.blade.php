@php
    $reportCollapseId = $reportCollapseId ?? 'facultySessionFeedbackReportCollapse';
    $linkLabelClass = $linkLabelClass ?? 'hide-menu small small-sm-normal text-nowrap';
@endphp
<li class="sidebar-item mb-1">
    <a class="sidebar-link sidebar-link-collapse d-flex align-items-center justify-content-between rounded-2 px-3 py-2"
        data-bs-toggle="collapse" href="#{{ $reportCollapseId }}" role="button"
        aria-expanded="false" aria-controls="{{ $reportCollapseId }}">
        <span class="d-flex align-items-center gap-2 min-w-0">
            <i class="material-icons material-symbols-rounded sidebar-panel-menu__icon" aria-hidden="true">analytics</i>
            <span class="{{ $linkLabelClass }}">Session Feedback Report</span>
        </span>
        <i class="material-icons material-symbols-rounded sidebar-panel-menu__chevron menu-icon"
            aria-hidden="true">chevron_right</i>
    </a>
</li>
<ul class="collapse list-unstyled mb-2" id="{{ $reportCollapseId }}">
    <li class="sidebar-panel-submenu-tree">
        <ul class="list-unstyled mb-0">
    <li class="sidebar-item mb-1">
        <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('faculty.session_feedback.details') }}">
            <span class="{{ $linkLabelClass }}">Faculty Feedback with OT Details</span>
        </a>
    </li>
    <li class="sidebar-item mb-1">
        <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('faculty.session_feedback.comments') }}">
            <span class="{{ $linkLabelClass }}">Faculty Feedback with Comments</span>
        </a>
    </li>
    <li class="sidebar-item mb-1">
        <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('faculty.session_feedback.average') }}">
            <span class="{{ $linkLabelClass }}">Faculty Feedback Average</span>
        </a>
    </li>
    <li class="sidebar-item mb-1">
        <a class="sidebar-link d-flex align-items-center rounded-2 px-3 py-2" href="{{ route('faculty.session_feedback.database', ['course_type' => 'current']) }}">
            <span class="{{ $linkLabelClass }}">Faculty Feedback Database</span>
        </a>
    </li>
        </ul>
    </li>
</ul>
