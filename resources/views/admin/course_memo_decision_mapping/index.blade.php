@extends('admin.layouts.master')

@section('title', 'Course Memo Decision Mapping')

@section('setup_content')
<style>
/* ─── Hide DataTable default controls ─── */
#memoDecisionTable_wrapper .dataTables_length,
#memoDecisionTable_wrapper .dataTables_filter,
#memoDecisionTable_wrapper .dataTables_info { display: none !important; }

/* ─── Paginate styling ─── */
#memoDecisionTable_wrapper .dataTables_paginate { margin-top: 0 !important; display: flex; align-items: center; gap: 2px; }
#memoDecisionTable_wrapper .paginate_button { border: none !important; background: transparent !important; padding: 5px 10px; border-radius: 4px; font-size: 0.8125rem; cursor: pointer; color: #495057 !important; }
#memoDecisionTable_wrapper .paginate_button:hover:not(.disabled) { background: #f1f3f5 !important; }
#memoDecisionTable_wrapper .paginate_button.current,
#memoDecisionTable_wrapper .paginate_button.current:hover { background: #1b3a5c !important; color: #fff !important; border-radius: 4px; }
#memoDecisionTable_wrapper .paginate_button.disabled { opacity: 0.35; cursor: default; }
#memoDecisionTable_wrapper .ellipsis { padding: 5px 4px; color: #adb5bd; }

/* ─── Custom Search ─── */
.cmd-search-wrap { position: relative; width: 260px; }
.cmd-search-wrap .form-control { border-radius: 8px; border: 1px solid #dee2e6; padding-left: 38px; font-size: 0.875rem; }
.cmd-search-wrap .cmd-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #adb5bd; font-size: 18px; pointer-events: none; }

/* ─── Status Badges ─── */
.cmd-badge-active { display: inline-block; background-color: #d1fae5; color: #065f46; border-radius: 10px !important; padding: 4px 14px; font-size: 0.8rem; font-weight: 600; }
.cmd-badge-inactive { display: inline-block; background-color: #fee2e2; color: #991b1b; border-radius: 10px !important; padding: 4px 14px; font-size: 0.8rem; font-weight: 600; }

/* ─── Table ─── */
#memoDecisionTable thead th { background-color: #f8f9fa; font-size: 0.8125rem; font-weight: 600; color: #6c757d; border-bottom: 2px solid #dee2e6; padding: 12px 16px; white-space: nowrap; }
#memoDecisionTable tbody td { font-size: 0.875rem; padding: 12px 16px; vertical-align: middle; border-bottom: 1px solid #f1f3f5; color: #212529; }
#memoDecisionTable tbody tr:hover td { background-color: #fafbfc; }

/* ─── Action Buttons ─── */
.cmd-action-btn { background: none; border: none; padding: 3px 5px; cursor: pointer; line-height: 1; display: inline-flex; align-items: center; text-decoration: none; }
.cmd-action-btn .material-symbols-rounded { font-size: 20px; }
.cmd-action-btn:hover { opacity: 0.7; }

/* ─── Modal ─── */
.cmd-modal .modal-content { border: 1.5px dashed #6ea8fe; border-radius: 12px; }
.cmd-modal .modal-header { border-bottom: none; padding: 20px 24px 6px; }
.cmd-modal .modal-body   { padding: 8px 24px 16px; }
.cmd-modal .modal-footer { border-top: none; padding: 0 24px 20px; }
.cmd-modal .modal-title  { font-size: 1rem; font-weight: 700; color: #1b3a5c; }
.cmd-modal .form-label   { font-size: 0.875rem; font-weight: 500; margin-bottom: 4px; color: #495057; }
.cmd-modal .form-control,
.cmd-modal .form-select  { border-radius: 6px; font-size: 0.875rem; border: 1px solid #dee2e6; padding: 8px 12px; }
.cmd-modal .form-control:focus,
.cmd-modal .form-select:focus { border-color: #6ea8fe; box-shadow: 0 0 0 3px rgba(110,168,254,0.15); }
</style>

<div class="container-fluid">
    <x-breadcrum title="Course Memo Decision Mapping" subtitle="List of course memo decision mappings">
        <button type="button" data-bs-toggle="modal" data-bs-target="#addCmdModal"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add New Mapping</span>
        </button>
    </x-breadcrum>

    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4">

                {{-- Custom Search --}}
                <div class="d-flex justify-content-end mb-3">
                    <div class="cmd-search-wrap">
                        <i class="material-icons material-symbols-rounded cmd-search-icon">search</i>
                        <input type="text" id="cmdSearch" class="form-control" placeholder="Search">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table mb-0 w-100" id="memoDecisionTable">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Course Name</th>
                                <th>Memo Decision</th>
                                <th>Memo Conclusion</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div id="cmdPaginationCell"></div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="text-muted small">Showing</span>
                        <select id="cmdPerPage" class="form-select form-select-sm" style="width:78px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                        <span id="cmdTotalInfo" class="text-muted small">of 0 items</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ ADD MODAL ═══════════════════ --}}
<div class="modal fade cmd-modal" id="addCmdModal" tabindex="-1"
    aria-labelledby="addCmdModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCmdModalLabel">Add Memo Conclusion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="conclusionForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="mb-3">
                        <label for="course_master_pk" class="form-label">Select Course <span class="text-danger">*</span></label>
                        <select name="course_master_pk" id="course_master_pk" class="form-select">
                            <option value="">Select Course</option>
                            @foreach($CourseMaster as $course)
                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Course is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="memo_type_master_pk" class="form-label">Select Memo <span class="text-danger">*</span></label>
                        <select name="memo_type_master_pk" id="memo_type_master_pk" class="form-select">
                            <option value="">Select Memo</option>
                            @foreach($MemoTypeMaster as $memo)
                            <option value="{{ $memo->pk }}">{{ $memo->memo_type_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Memo is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="memo_conclusion_master_pk" class="form-label">Select Memo Conclusion <span class="text-danger">*</span></label>
                        <select name="memo_conclusion_master_pk" id="memo_conclusion_master_pk" class="form-select">
                            <option value="">Select Memo Conclusion</option>
                            @foreach($MemoConclusionMaster as $memo)
                            <option value="{{ $memo->pk }}">{{ $memo->discussion_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Memo Conclusion is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="active_inactive" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="active_inactive" id="active_inactive" class="form-select">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                        <div class="invalid-feedback">Status is required.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn fw-semibold px-4" id="submitConclusionForm"
                    style="background:#1b3a5c;color:#fff;border-color:#1b3a5c;">Create Memo Mapping</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════ EDIT MODAL ═══════════════════ --}}
<div class="modal fade cmd-modal" id="editconclusionModal" tabindex="-1"
    aria-labelledby="editConclusionLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editConclusionLabel">Edit Memo Conclusion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit_conclusionForm" novalidate>
                    <input type="hidden" id="edit_id" name="edit_id">
                    <div class="mb-3">
                        <label for="edit_course_master_pk" class="form-label">Select Course <span class="text-danger">*</span></label>
                        <select id="edit_course_master_pk" class="form-select">
                            <option value="">Select Course</option>
                            @foreach($CourseMaster as $course)
                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Course is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_memo_type_master_pk" class="form-label">Select Memo <span class="text-danger">*</span></label>
                        <select id="edit_memo_type_master_pk" class="form-select">
                            <option value="">Select Memo</option>
                            @foreach($MemoTypeMaster as $memo)
                            <option value="{{ $memo->pk }}">{{ $memo->memo_type_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Memo is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_memo_conclusion_master_pk" class="form-label">Select Memo Conclusion <span class="text-danger">*</span></label>
                        <select id="edit_memo_conclusion_master_pk" class="form-select">
                            <option value="">Select Memo Conclusion</option>
                            @foreach($MemoConclusionMaster as $memo)
                            <option value="{{ $memo->pk }}">{{ $memo->discussion_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Memo Conclusion is required.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_active_inactive" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="edit_active_inactive" class="form-select">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                        <div class="invalid-feedback">Status is required.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn fw-semibold px-4" id="edit_submitConclusionForm"
                    style="background:#1b3a5c;color:#fff;border-color:#1b3a5c;">Update</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(function() {
        const tableSelector = '#memoDecisionTable';
        if (!$.fn.DataTable.isDataTable(tableSelector)) {
            $(tableSelector).DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('course.memo.decision.index') }}",
                order: [[0, 'desc']],
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'course_name',
                        name: 'course.course_name'
                    },
                    {
                        data: 'memo_decision',
                        name: 'memo.memo_type_name'
                    },
                    {
                        data: 'memo_conclusion',
                        name: 'memoConclusion.discussion_name'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [1, 'asc']
                ]
            });
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Show modal on button click
        document.getElementById('showConclusionAlert').addEventListener('click', function() {
            var conclusionModal = new bootstrap.Modal(document.getElementById('conclusionModal'));
            conclusionModal.show();
        });

        // Handle form submission
        document.getElementById('submitConclusionForm').addEventListener('click', function(e) {
            e.preventDefault(); // ✅ prevent form double submit

            const course = document.getElementById('course_master_pk').value;
            const memo = document.getElementById('memo_type_master_pk').value;
            const conclusion = document.getElementById('memo_conclusion_master_pk').value;
            const status = document.getElementById('active_inactive').value;

            // ✅ Client-side validation
            if (!course || !memo || !conclusion || !status) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Required',
                    text: 'Please fill all required fields!'
                });
                return;
            }

            const data = {
                course_master_pk: course,
                memo_type_master_pk: memo,
                memo_conclusion_master_pk: conclusion,
                active_inactive: status
            };

            console.log('Submitting:', data);

            fetch("{{ route('course.memo.decision.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(res => {
                    if (res.status === true) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: res.message || 'Course Memo Decision Mapping saved successfully!',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // ✅ Reload DataTable
                            $('#memoDecisionTable').DataTable().ajax.reload(null, false);

                            // ✅ Reset form
                            document.getElementById('conclusionForm').reset();
                            $('#conclusionModal').modal('hide');
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Something went wrong!'
                        });
                    }
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Please try again later.'
                    });
                });
        });


    });

    //edit form//

    document.addEventListener('DOMContentLoaded', function() {

        // EDIT button click
        $(document).on('click', '.editConclusion', function() {

            let id = $(this).data('id');
            let course = $(this).data('course');
            let memo = $(this).data('memo');
            let conclusion = $(this).data('conclusion');
            let status = $(this).data('status');

            console.log(id, course, memo, conclusion, status); // 🔍 debug
            $('#edit_id').val(id);
            $('#edit_course_master_pk').val(course).trigger('change');
            $('#edit_memo_type_master_pk').val(memo).trigger('change');
            $('#edit_memo_conclusion_master_pk').val(conclusion).trigger('change');
            $('#edit_active_inactive').val(status).trigger('change');

            // Show modal
            new bootstrap.Modal(document.getElementById('editconclusionModal')).show();
        });
    });

    $('#edit_submitConclusionForm').on('click', function (e) {
    e.preventDefault();

    const data = {
        id: $('#edit_id').val(),
        course_master_pk: $('#edit_course_master_pk').val(),
        memo_type_master_pk: $('#edit_memo_type_master_pk').val(),
        memo_conclusion_master_pk: $('#edit_memo_conclusion_master_pk').val(),
        active_inactive: $('#edit_active_inactive').val()
    };
    alert(data.course_master_pk);


    fetch("{{ route('course.memo.decision.update') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.status) {
            Swal.fire({
                icon: 'success',
                title: 'Updated',
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                $('#memoDecisionTable').DataTable().ajax.reload(null, false);
                $('#editconclusionModal').modal('hide');
            });
        }
    });
});

</script>

@if(session('success'))
<script>
    Swal.fire({ icon: 'success', title: 'Success', text: "{{ session('success') }}" });
</script>
@endif
@if(session('error'))
<script>
    Swal.fire({ icon: 'error', title: 'Error', text: "{{ session('error') }}" });
</script>
@endif
@endpush