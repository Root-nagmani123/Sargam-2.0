@extends('admin.layouts.master')

@section('title', 'My Groups')

@push('styles')
<style>
/* =====================================================================
   My Groups — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   Only what Bootstrap utilities + .ds-* can't express lives here.
   ===================================================================== */

/* Group-type pill. Bootstrap's .badge is solid-filled and shouts on a row
   where the group NAME is the thing being scanned, so this is the quiet
   outlined variant the design system has no component for. */
.mg-type {
    display: inline-block;
    padding: var(--ds-space-1) var(--ds-space-2);
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    background: var(--ds-surface-2);
    color: var(--ds-ink-muted);
    font-size: 0.8125rem;
    line-height: 1.25;
}

.mg-course-icon {
    font-size: 20px;
    color: var(--ds-primary);
}

/* Pushes the per-course tally to the far end of .ds-card-header, which is
   a flex row with no spacer element of its own. */
.mg-course-count {
    margin-left: auto;
    font-size: 0.8125rem;
    font-weight: 400;
    color: var(--ds-ink-muted);
}

.mg-table th.mg-col-no,
.mg-table td.mg-col-no {
    width: 5rem;
    text-align: center;
    /* Without this the 5rem column breaks the header across two lines. */
    white-space: nowrap;
    color: var(--ds-ink-muted);
}

/* Total Members — centred and narrow, the way the Course Group Mapping grid
   sets its own student-count column. */
.mg-table th.mg-col-members,
.mg-table td.mg-col-members {
    width: 9rem;
    text-align: center;
    white-space: nowrap;
}

.mg-members {
    display: inline-block;
    min-width: 2.25rem;
    padding: var(--ds-space-1) var(--ds-space-2);
    border-radius: var(--ds-radius-1);
    background: var(--ds-surface-2);
    color: var(--ds-ink);
    font-variant-numeric: tabular-nums;
    font-weight: 600;
    font-size: 0.8125rem;
    line-height: 1.25;
}

/* Keeps the empty-state sentence to a readable measure instead of one long
   line spanning the full page width. */
.mg-empty-text {
    max-width: 34rem;
    margin-inline: auto;
}

.mg-muted {
    color: var(--ds-ink-muted);
}
</style>
@endpush

@section('content')
@php
    $groups = $groups ?? collect();
    $total = $groups->count();

    // Group by course so an OT who has been through more than one programme sees
    // each programme's groups together rather than one flat run.
    $byCourse = $groups->groupBy(fn ($g) => $g->course_name ?: 'Unassigned course');
@endphp

{{-- Page padding is applied globally by sargam-app.css (.page-wrapper
     .container-fluid); per the spacing contract this element takes no p-*. --}}
<div class="container-fluid">

    <x-breadcrum title="My Groups" />

    @if($total === 0)
        <div class="ds-empty-state">
            <i class="material-icons material-symbols-rounded d-block mb-2 mg-muted"
               style="font-size: 40px;" aria-hidden="true">groups</i>
            <p class="fw-semibold mb-1" style="color: var(--ds-ink);">You are not mapped to any group yet</p>
            <p class="mb-0 mg-empty-text">
                Groups are assigned by the course team. Once you are added to a language,
                counsellor, seminar or lecture group it will appear here.
            </p>
        </div>
    @else
        <div class="row g-3 ds-section">
            <div class="col-6 col-lg-3">
                <div class="ds-stat-card">
                    <div>
                        <p class="ds-stat-label">Groups</p>
                        <div class="ds-stat-value">{{ $total }}</div>
                    </div>
                    <span class="ds-stat-icon">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">groups</i>
                    </span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="ds-stat-card">
                    <div>
                        <p class="ds-stat-label">{{ Str::plural('Course', $byCourse->count()) }}</p>
                        <div class="ds-stat-value">{{ $byCourse->count() }}</div>
                    </div>
                    <span class="ds-stat-icon">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">school</i>
                    </span>
                </div>
            </div>
        </div>

        @foreach($byCourse as $courseName => $courseGroups)
            <div class="ds-card ds-section">
                <div class="ds-card-header">
                    <i class="material-icons material-symbols-rounded mg-course-icon" aria-hidden="true">school</i>
                    <span>{{ $courseName }}</span>
                    <span class="mg-course-count">
                        {{ $courseGroups->count() }} {{ Str::plural('group', $courseGroups->count()) }}
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0 mg-table">
                        <thead>
                            {{-- Column set and order mirror the admin Course Group
                                 Mapping grid, minus Status / Action — an OT may look
                                 at their groups but not edit or delete them. Course
                                 Name is the card heading above rather than a column,
                                 since these tables are already grouped by course. --}}
                            <tr>
                                <th scope="col" class="mg-col-no">S. No.</th>
                                <th scope="col">Group Type</th>
                                <th scope="col">Group Name</th>
                                <th scope="col">Faculty</th>
                                <th scope="col" class="mg-col-members">Total Members</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courseGroups as $group)
                                <tr>
                                    <td class="mg-col-no">{{ $loop->iteration }}</td>
                                    <td>
                                        @if($group->group_type)
                                            <span class="mg-type">{{ $group->group_type }}</span>
                                        @else
                                            <span class="mg-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $group->group_name ?: '—' }}</td>
                                    <td>
                                        @if($group->faculty_name)
                                            {{ $group->faculty_name }}
                                        @else
                                            <span class="mg-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="mg-col-members">
                                        <span class="mg-members">{{ $group->total_members ?? 0 }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
