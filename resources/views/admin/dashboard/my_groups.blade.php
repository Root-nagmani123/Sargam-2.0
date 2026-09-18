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

.mg-view-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    padding: 0;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    background: var(--ds-surface-2);
    color: var(--ds-primary);
    line-height: 1;
    cursor: pointer;
}

.mg-view-btn:hover {
    background: var(--ds-primary);
    border-color: var(--ds-primary);
    color: #fff;
}

.mg-view-btn .material-icons {
    font-size: 18px;
}

/* Roster rows inside the modal. */
.mg-modal-meta {
    font-size: 0.8125rem;
    color: var(--ds-ink-muted);
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
                                <th scope="col" class="mg-col-members">Members</th>
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
                                    {{-- View, not a bare number: the count alone said
                                         how many but not who. The tally rides along
                                         in the tooltip so nothing is lost. --}}
                                    <td class="mg-col-members">
                                        <button type="button" class="mg-view-btn"
                                            data-map-pk="{{ $group->pk }}"
                                            data-group-name="{{ $group->group_name }}"
                                            title="View {{ $group->total_members ?? 0 }} member{{ ($group->total_members ?? 0) == 1 ? '' : 's' }}"
                                            aria-label="View members of {{ $group->group_name }}">
                                            <i class="material-icons material-symbols-rounded" aria-hidden="true">visibility</i>
                                        </button>
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

{{-- Group roster --}}
<div class="modal fade" id="mgMembersModal" tabindex="-1" aria-labelledby="mgMembersLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-semibold mb-0" id="mgMembersLabel">Group Members</h5>
                    <div class="mg-modal-meta mt-1" id="mgMembersMeta"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="mgMembersBody">
                    <p class="text-center text-muted my-4 mb-0">Loading…</p>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    // {mapPk} is substituted client-side; the server re-checks that the viewer
    // is actually a member of the group before returning its roster.
    const studentsUrlTemplate = @json(route('admin.dashboard.my-groups.students', ['mapPk' => '__PK__']));
    const modalEl = document.getElementById('mgMembersModal');

    function showModal() {
        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else if (window.jQuery) {
            $(modalEl).modal('show');
        }
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function renderStudents(list) {
        if (!list || !list.length) {
            return '<p class="text-center text-muted my-4 mb-0">No officer trainees are mapped to this group.</p>';
        }

        let html = '<div class="table-responsive"><table class="table align-middle mb-0 mg-table">'
            + '<thead><tr>'
            + '<th scope="col" class="mg-col-no">S. No.</th>'
            + '<th scope="col">OT Code</th>'
            + '<th scope="col">Student Name</th>'
            + '</tr></thead><tbody>';

        list.forEach(function (s, i) {
            html += '<tr>'
                + '<td class="mg-col-no">' + (i + 1) + '</td>'
                + '<td>' + escapeHtml(s.ot_code) + '</td>'
                + '<td class="fw-semibold">' + escapeHtml(s.name) + '</td>'
                + '</tr>';
        });

        return html + '</tbody></table></div>';
    }

    $(document).on('click', '.mg-view-btn', function () {
        const mapPk = $(this).data('mapPk');
        const groupName = $(this).data('groupName');

        $('#mgMembersLabel').text(groupName || 'Group Members');
        $('#mgMembersMeta').text('');
        $('#mgMembersBody').html('<p class="text-center text-muted my-4 mb-0">Loading…</p>');
        showModal();

        $.get(studentsUrlTemplate.replace('__PK__', mapPk))
            .done(function (res) {
                const g = res.group || {};
                $('#mgMembersMeta').text(
                    [g.course, g.type].filter(Boolean).join(' · ')
                    + '  —  ' + (res.students ? res.students.length : 0) + ' member'
                    + ((res.students && res.students.length === 1) ? '' : 's')
                );
                $('#mgMembersBody').html(renderStudents(res.students));
            })
            .fail(function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Could not load the group members.';
                $('#mgMembersBody').html('<p class="text-center text-danger my-4 mb-0">' + escapeHtml(msg) + '</p>');
            });
    });
});
</script>
@endpush
