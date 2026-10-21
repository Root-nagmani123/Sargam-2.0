<div class="card shadow-sm border-0 mb-4">
    <div class="card-body pb-2">

        {{-- Search with AJAX --}}
        <!-- <div class="row g-3 align-items-center mb-3">
            <div class="col-md-6">
                <label for="studentSearch" class="form-label fw-semibold">Search Students</label>
                <div class="input-group">
                    <span class="input-group-text bg-light">
                        <i class="material-icons material-symbols-rounded">search</i>
                    </span>
                    <input type="text" id="studentSearch" class="form-control"
                        placeholder="Search by name, email or contact">
                </div>
                <small class="text-muted">Search updates dynamically (AJAX enabled).</small>
            </div>
        </div> -->

        {{-- Group Info --}}
        @if(!empty($groupName) || !empty($facilityName) || !empty($courseName))
        <div class="bg-light p-3 rounded-3 border mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <strong>Course Name:</strong>
                    <span class="text-muted">{{ $courseName ?? 'N/A' }}</span>
                </div>
                <div class="col-md-4">
                    <strong>Group Name:</strong>
                    <span class="text-muted">{{ $groupName ?? 'N/A' }}</span>
                </div>
                <div class="col-md-4">
                    <strong>Faculty:</strong>
                    <span class="text-muted">{{ $facilityName ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        @endif
        <div class="table-responsive student-table-wrapper">
            <table class="table table-hover align-middle text-nowrap modern-table">
                <thead>
                    <tr>
                        <th style="width: 45px;">
                            <div class="form-check m-0">
                                <input type="checkbox" class="form-check-input" id="selectAllOts"
                                    aria-label="Select all students">
                            </div>
                        </th>
                        <th>#</th>
                        <th>Name</th>
                        <th>OT Code</th>
                        <th>Email</th>
                        <th>Contact No</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($students as $index => $studentMap)
                    @php $student = $studentMap->studentsMaster; @endphp

                    <tr>
                        <td>
                            @if($student && $student->pk)
                            <div class="form-check m-0">
                                <input type="checkbox" class="form-check-input student-select"
                                    value="{{ encrypt($student->pk) }}" data-email="{{ $student->email }}"
                                    data-phone="{{ $student->contact_no }}" data-name="{{ $student->display_name }}"
                                    aria-label="Select {{ $student->display_name }}">
                            </div>
                            @endif
                        </td>

                        <td>{{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}</td>
                        <td class="fw-semibold">{{ $student->display_name ?? 'N/A' }}</td>
                        <td>{{ $student->generated_OT_code ?? 'N/A' }}</td>
                        <td>{{ $student->email ?? 'N/A' }}</td>
                        <td>{{ $student->contact_no ?? 'N/A' }}</td>

                        <td class="text-center">
                            @if($student && $student->pk)
                            <div class="btn-group gap-2" role="group" aria-label="Student Actions">

                                <button type="button"
                                    class="btn btn-outline-primary btn-sm rounded-pill px-3 edit-student"
                                    data-student-id="{{ encrypt($student->pk) }}"
                                    data-name="{{ e($student->display_name) }}" data-email="{{ e($student->email) }}"
                                    data-contact="{{ e($student->contact_no) }}" title="Edit student details">
                                    <i class="material-icons material-symbols-rounded me-1">edit</i> Edit
                                </button>

                                <button type="button"
                                    class="btn btn-outline-danger btn-sm rounded-pill px-3 delete-student"
                                    data-mapping-id="{{ encrypt($studentMap->pk) }}"
                                    data-name="{{ e($student->display_name ?? 'this student') }}"
                                    title="Remove from group">
                                    <i class="material-icons material-symbols-rounded me-1">delete</i> Remove
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
        <div class="d-flex justify-content-between align-items-center flex-wrap mt-3">
            <div class="text-muted small mb-2">
                <strong>Total Students:</strong> {{ $students->total() }} |
                <strong>Page:</strong> {{ $students->currentPage() }} of {{ $students->lastPage() }} |
                <strong>Per Page:</strong> {{ $students->perPage() }}
            </div>

            <div class="student-list-pagination">
                {!! $students->links('pagination::bootstrap-5') !!}
            </div>
        </div>
        <style>
        .modern-table th,
        .modern-table td {
            padding: 0.75rem 1rem;
        }

        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .btn-outline-primary,
        .btn-outline-danger {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .student-table-wrapper {
            border-radius: 8px;
            overflow-x: auto;
            overflow-y: visible;
            border: 1px solid #e6e6e6;
            max-width: 100%;
        }

        .table-hover tbody tr:hover {
            background-color: #f7faff;
        }

        .input-group-text {
            border-right: none;
        }

        #studentSearch {
            border-left: none;
        }

        /* Responsive table styles */
        @media (max-width: 768px) {
            .modern-table {
                font-size: 0.875rem;
            }

            .modern-table th,
            .modern-table td {
                padding: 0.5rem;
                white-space: nowrap;
            }

            .btn-group {
                flex-direction: column;
                gap: 0.25rem;
            }

            .btn-sm {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }

            .material-icons.material-symbols-rounded {
                font-size: 16px;
            }

            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width: 576px) {
            .modern-table th:first-child,
            .modern-table td:first-child {
                position: sticky;
                left: 0;
                background: white;
                z-index: 1;
            }

            .modern-table thead th {
                position: sticky;
                top: 0;
                background: #f8f9fa;
                z-index: 2;
            }

            .modern-table thead th:first-child {
                z-index: 3;
            }
        }
        </style>