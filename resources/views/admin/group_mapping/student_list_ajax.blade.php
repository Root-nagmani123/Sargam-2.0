{{--
    Student Details — the body of #studentDetailsModal, re-rendered by
    custom.js on open, on search and on every pagination click.

    New design (docs/new-design-index-page.md): §3 table panel, §3b action
    stacks, §4 variant B footer. Nothing here carries its own <style> — the old
    version shipped one, and because it was injected into the page on every AJAX
    load its unscoped `.btn-outline-primary` / `.table-hover tr:hover` rules
    restyled the whole screen behind the modal. The styling lives in
    public/css/programme-admin.css under :is(.prog-page, .prog-modal).

    JS hooks this partial must keep (custom.js 1050-1560):
      #groupMappingEncryptedId  reload/pagination re-read the mapping id here
      #selectAllOts             header select-all
      .student-select           per-row checkbox + data-email/phone/name
      .student-table-wrapper    updateSearchResultsCount() counts tbody rows
      "No students found."      that same helper matches this exact text
      .student-list-pagination  delegated pagination click
      .edit-student             data-student-id/name/email/contact
      .delete-student           data-mapping-id/name
      .student-action-btn       initStudentActionTooltips() — the old markup
                                never carried this class, so the tooltips it
                                builds had nothing to attach to
--}}

{{-- The reload path reads the mapping id from here and falls back to
     window.currentGroupMappingId; studentList() decrypts it, so it must be
     encrypted exactly as the row's View button sends it. --}}
<input type="hidden" id="groupMappingEncryptedId" value="{{ encrypt($groupMappingPk) }}">

@if (!empty($groupName) || !empty($facilityName) || !empty($courseName))
    <div class="prog-field-card mb-3">
        <div class="prog-facts">
            <div class="prog-fact">
                <span class="prog-fact__label">Course Name</span>
                <div class="prog-fact__value {{ filled($courseName) ? '' : 'is-empty' }}">
                    {{ filled($courseName) ? $courseName : '—' }}
                </div>
            </div>
            <div class="prog-fact">
                <span class="prog-fact__label">Group Name</span>
                <div class="prog-fact__value {{ filled($groupName) ? '' : 'is-empty' }}">
                    {{ filled($groupName) ? $groupName : '—' }}
                </div>
            </div>
            <div class="prog-fact">
                <span class="prog-fact__label">Faculty</span>
                <div class="prog-fact__value {{ filled($facilityName) ? '' : 'is-empty' }}">
                    {{ filled($facilityName) ? $facilityName : '—' }}
                </div>
            </div>
        </div>
    </div>
@endif

<div class="programme-dt-panel prog-student-panel">
    <div class="table-responsive student-table-wrapper">
        <table class="table table-hover align-middle mb-0 w-100 programme-dt-table prog-student-table">
            <thead>
                <tr>
                    <th class="prog-student-check" scope="col">
                        <input type="checkbox" class="form-check-input" id="selectAllOts"
                            aria-label="Select all students on this page">
                    </th>
                    <th scope="col">S. No.</th>
                    <th scope="col">Name</th>
                    <th scope="col">OT Code</th>
                    <th scope="col">Email</th>
                    <th scope="col">Contact No</th>
                    <th scope="col" class="text-center">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($students as $studentMap)
                    @php $student = $studentMap->studentsMaster; @endphp
                    <tr>
                        <td class="prog-student-check">
                            @if ($student && $student->pk)
                                <input type="checkbox" class="form-check-input student-select"
                                    value="{{ encrypt($student->pk) }}"
                                    data-email="{{ $student->email }}"
                                    data-phone="{{ $student->contact_no }}"
                                    data-name="{{ $student->display_name }}"
                                    aria-label="Select {{ $student->display_name }}">
                            @endif
                        </td>

                        <td>{{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}</td>
                        <td class="fw-semibold">{{ $student->display_name ?? 'N/A' }}</td>
                        <td>{{ $student->generated_OT_code ?? 'N/A' }}</td>
                        <td>{{ $student->email ?? 'N/A' }}</td>
                        <td>{{ $student->contact_no ?? 'N/A' }}</td>

                        <td>
                            @if ($student && $student->pk)
                                {{-- §3b: icon over caption, every stack the same width. --}}
                                <div class="prog-act-group" role="group" aria-label="Student actions">
                                    <button type="button"
                                        class="prog-act prog-act--edit edit-student student-action-btn"
                                        data-student-id="{{ encrypt($student->pk) }}"
                                        data-name="{{ e($student->display_name) }}"
                                        data-email="{{ e($student->email) }}"
                                        data-contact="{{ e($student->contact_no) }}"
                                        title="Edit student details" aria-label="Edit student details">
                                        <span class="prog-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                        <span class="prog-act__label">Edit</span>
                                    </button>

                                    <button type="button"
                                        class="prog-act prog-act--del delete-student student-action-btn"
                                        data-mapping-id="{{ encrypt($studentMap->pk) }}"
                                        data-name="{{ e($student->display_name ?? 'this student') }}"
                                        title="Remove from group" aria-label="Remove from group">
                                        <span class="prog-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                        <span class="prog-act__label">Remove</span>
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No students found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- §4 variant B: a Laravel paginator dressed in the DataTables chrome, so
         it matches the footer on the index page behind this modal. It sits
         INSIDE the panel, so its border-top reads as the table's own divider
         rather than a second floating strip. --}}
    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="programme-dt-pagination student-list-pagination">
            {{ $students->links('vendor.pagination.custom') }}
        </div>
        <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
            <div class="dataTables_info">
                @if ($students->total() > 0)
                    Showing {{ number_format($students->firstItem()) }} to {{ number_format($students->lastItem()) }}
                    of {{ number_format($students->total()) }} students
                @else
                    Showing 0 students
                @endif
            </div>
        </div>
    </div>
</div>
