@php
    $reportCollapseId = $reportCollapseId ?? 'facultySessionFeedbackReportCollapse';
    $linkLabelClass = $linkLabelClass ?? 'hide-menu small small-sm-normal text-nowrap';
@endphp
<li class="sidebar-item"
    style="background: #4077ad;border-radius: 30px 0px 0px 30px;width: 100%; box-shadow: -2px 3px rgba(251, 248, 248, 0.1);min-width: 250px;">
    <a class="sidebar-link d-flex justify-content-between align-items-center"
        data-bs-toggle="collapse" href="#{{ $reportCollapseId }}" role="button"
        aria-expanded="false" aria-controls="{{ $reportCollapseId }}">
        <span class="{{ $linkLabelClass }} fw-bold">Session Feedback Report</span>
        <i class="material-icons menu-icon material-symbols-rounded"
            style="font-size: 18px; font-size: 24px-sm;">keyboard_arrow_down</i>
    </a>
</li>
<ul class="collapse list-unstyled ps-3" id="{{ $reportCollapseId }}">
    <li class="sidebar-item">
        <a class="sidebar-link" href="{{ route('faculty.session_feedback.details') }}">
            <span class="{{ $linkLabelClass }}">Faculty Feedback with OT Details</span>
        </a>
    </li>
    <li class="sidebar-item">
        <a class="sidebar-link" href="{{ route('faculty.session_feedback.comments') }}">
            <span class="{{ $linkLabelClass }}">Faculty Feedback with Comments</span>
        </a>
    </li>
    <li class="sidebar-item">
        <a class="sidebar-link" href="{{ route('faculty.session_feedback.average') }}">
            <span class="{{ $linkLabelClass }}">Faculty Feedback Average</span>
        </a>
    </li>
    <li class="sidebar-item">
        <a class="sidebar-link" href="{{ route('faculty.session_feedback.database', ['course_type' => 'current']) }}">
            <span class="{{ $linkLabelClass }}">Faculty Feedback Database</span>
        </a>
    </li>
</ul>
