@extends('admin.layouts.master')

@section('title', 'Timetable Repository - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Timetable Repository" />

    <x-session_message />

    {{-- Active / Archived: same course-lifecycle split the other Time Table screens use.
         Plain links (not JS pills) so the chosen tab survives a save/delete redirect. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0" role="group"
            aria-label="Filter documents by course status">
            <li class="nav-item" role="presentation">
                <a href="{{ route('timetable-repository.index', ['status' => 'active']) }}"
                   class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $status === 'active' ? 'active' : '' }}"
                   @if($status === 'active') aria-current="page" @endif>
                    Active <span class="badge bg-light text-dark ms-1">{{ $counts['active'] }}</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="{{ route('timetable-repository.index', ['status' => 'archive']) }}"
                   class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $status === 'archive' ? 'active' : '' }}"
                   @if($status === 'archive') aria-current="page" @endif>
                    Archived <span class="badge bg-light text-dark ms-1">{{ $counts['archive'] }}</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="card shadow-sm border-0" style="border-left: 4px solid #0d6efd;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Timetable Repository</h1>
                    <p class="text-muted small mb-0">
                        {{ $status === 'archive'
                            ? 'Documents uploaded against courses that have already ended.'
                            : 'Course and week wise PDF documents for running courses.' }}
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('timetable-repository.create') }}"
                       class="btn btn-primary rounded-1 px-3 d-inline-flex align-items-center gap-2 ttr-add-btn">
                        <i class="material-icons material-symbols-rounded">upload_file</i> Upload Document
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table text-nowrap w-100" id="timetableRepositoryTable">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Name</th>
                            <th>Course Name</th>
                            <th>Week</th>
                            <th>PDF</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    {{-- Koi manual "no records" row nahi: DataTables colspan ko expand nahi karta,
                         to 1-cell wali empty row 6 columns ke against "Incorrect column count"
                         (tn/18) throw kar deti hai. Khaali listing ka message emptyTable se aata hai. --}}
                    <tbody>
                        @foreach($items as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row->document_name }}</td>
                            <td>{{ $row->course->course_name ?? '--' }}</td>
                            <td data-order="{{ $row->week_number ?? 0 }}">
                                @if($row->week_number)
                                    Week {{ $row->week_number }}
                                    <span class="text-muted small d-block">
                                        {{ $row->week_start->format('d M') }} – {{ $row->week_start->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->format('d M Y') }}
                                    </span>
                                @else
                                    --
                                @endif
                            </td>
                            <td>
                                @if($row->fileExists())
                                    <a href="{{ route('timetable-repository.download', $row->pk) }}"
                                       class="text-danger d-inline-flex align-items-center gap-1"
                                       title="Download {{ $row->file_name }}">
                                        <i class="material-icons material-symbols-rounded">picture_as_pdf</i>
                                        <span class="text-body small">{{ \Illuminate\Support\Str::limit($row->file_name, 30) }}</span>
                                    </a>
                                    @if($row->file_size_for_humans)
                                        <span class="text-muted small">({{ $row->file_size_for_humans }})</span>
                                    @endif
                                @else
                                    <span class="text-muted small">File missing</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('timetable-repository.edit', $row->pk) }}" class="text-primary" title="Edit">
                                        <i class="material-icons material-symbols-rounded">edit</i>
                                    </a>
                                    <form action="{{ route('timetable-repository.destroy', $row->pk) }}" method="POST"
                                          onsubmit="return confirm('Delete this document? The uploaded PDF will also be removed.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                            <i class="material-icons material-symbols-rounded">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#timetableRepositoryTable').DataTable({
        // Khaali rakho: controller pehle hi pk desc bhejta hai (naya record sabse upar).
        order: [],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                searchable: false,
                render: function(data, type, row, meta) {
                    if (type === 'display') {
                        var start = meta.settings._iDisplayStart || 0;
                        return start + meta.row + 1;
                    }
                    return data;
                }
            },
            { targets: 5, orderable: false, searchable: false }
        ],
        language: {
            emptyTable: "No documents found.",
            zeroRecords: "No matching documents found.",
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        responsive: true,
        autoWidth: false,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Upload button ko search row ke bagal me le jao (baaki listings jaisa).
    if (window.SargamDataTableUI) {
        window.SargamDataTableUI.appendToSearchRow('timetableRepositoryTable', $('.ttr-add-btn').addClass('ms-2'));
    }
});
</script>
@endpush
