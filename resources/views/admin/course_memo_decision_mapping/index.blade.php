@extends('admin.layouts.master')

@section('title', 'Course Memo Decision Mapping - Sargam | Lal Bahadur')

@section('setup_content')
<style>
/* Course Memo Decision Mapping - responsive (mobile/tablet only, desktop unchanged) */
@media (max-width: 991.98px) {
    .course-memo-decision-mapping-index .datatables .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    .course-memo-decision-mapping-index .datatables #memoDecisionTable {
        min-width: 500px;
    }
    .course-memo-decision-mapping-index .datatables #memoDecisionTable th,
    .course-memo-decision-mapping-index .datatables #memoDecisionTable td {
        padding: 8px 10px;
        font-size: 0.9rem;
    }
}
@media (max-width: 767.98px) {
    .course-memo-decision-mapping-index .datatables .card-body {
        padding: 1rem !important;
    }
    .course-memo-decision-mapping-index .datatables #memoDecisionTable th,
    .course-memo-decision-mapping-index .datatables #memoDecisionTable td {
        padding: 6px 8px;
        font-size: 0.85rem;
    }
    body:has(.course-memo-decision-mapping-index) .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
    body:has(.course-memo-decision-mapping-index) .modal-content {
        border-radius: 0.5rem;
    }
}
@media (max-width: 575.98px) {
    .course-memo-decision-mapping-index.container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    .course-memo-decision-mapping-index .course-memo-header-row {
        row-gap: 0.5rem;
    }
    .course-memo-decision-mapping-index .course-memo-header-row .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .course-memo-decision-mapping-index .course-memo-header-actions {
        display: flex;
        justify-content: stretch;
    }
    .course-memo-decision-mapping-index .course-memo-header-actions .btn {
        width: 100%;
        justify-content: center;
    }
    .course-memo-decision-mapping-index .datatables .card-body {
        padding: 0.75rem !important;
    }
    .course-memo-decision-mapping-index .datatables .table-responsive {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .course-memo-decision-mapping-index .datatables #memoDecisionTable th,
    .course-memo-decision-mapping-index .datatables #memoDecisionTable td {
        padding: 6px 8px;
        font-size: 0.8125rem;
    }
    body:has(.course-memo-decision-mapping-index) .modal-dialog {
        margin: 0.5rem auto;
        max-width: calc(100% - 1rem);
    }
    body:has(.course-memo-decision-mapping-index) .modal-content {
        max-height: calc(100vh - 1rem);
        overflow-y: auto;
    }
    body:has(.course-memo-decision-mapping-index) .modal-body {
        padding: 1rem !important;
    }
    body:has(.course-memo-decision-mapping-index) .modal-header,
    body:has(.course-memo-decision-mapping-index) .modal-footer {
        padding: 0.75rem 1rem !important;
    }
}
@media (max-width: 375px) {
    .course-memo-decision-mapping-index.container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .course-memo-decision-mapping-index .datatables .card-body {
        padding: 0.5rem !important;
    }
    .course-memo-decision-mapping-index .course-memo-header-row h4 {
        font-size: 1.1rem;
    }
}
</style>
<div class="container-fluid course-memo-decision-mapping-index">
    <x-breadcrum title="Course Memo Decision Mapping" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row course-memo-header-row align-items-center">
                        <div class="col-6">
                            <h4>Course Memo Decision Mapping</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2 course-memo-header-actions">
                                <!-- <a href="{{ route('course.memo.decision.create') }}" class="btn btn-primary">+Add New
                                    Mapping</a> -->
                                <button type="button" id="showConclusionAlert" class="btn btn-primary">
                                    +Add New Mapping
                                </button>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <table class="table w-100" id="memoDecisionTable">
                            <thead style="background-color: #004a93;">
                                <tr>
                                    <th>S.No.</th>
                                    <th>Course Name</th>
                                    <th>Memo type</th>
                                    <th>Memo Conclusion</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Bootstrap Modal -->
<div class="modal fade" id="conclusionModal" tabindex="-1" aria-labelledby="conclusionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="conclusionModalLabel">Add Memo Conclusion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="conclusionForm">
                    <div class="row">
                        <!-- Course Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Course <span style="color:red;">*</span></label>
                            <select name="course_master_pk" id="course_master_pk" class="form-select" required>
                                <option value="">-- Select Course --</option>
                                @foreach($CourseMaster as $course)
                                <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Memo Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Memo <span style="color:red;">*</span></label>
                            <select name="memo_type_master_pk" id="memo_type_master_pk" class="form-select" required>
                                <option value="">-- Select Memo --</option>
                                @foreach($MemoTypeMaster as $memo)
                                <option value="{{ $memo->pk }}">{{ $memo->memo_type_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Memo Conclusion Dropdown -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Select Memo Conclusion <span style="color:red;">*</span></label>
                            <select name="memo_conclusion_master_pk" id="memo_conclusion_master_pk" class="form-select" required>
                                <option value="">-- Select Memo Conclusion --</option>
                                @foreach($MemoConclusionMaster as $memo)
                                <option value="{{ $memo->pk }}">{{ $memo->discussion_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status <span style="color:red;">*</span></label>
                            <select name="active_inactive" id="active_inactive" class="form-select" required>
                                <option value="1">Active</option>
                                <option value="2">Inactive</option>
                            </select>
                        </div>

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="submitConclusionForm" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="editconclusionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="editConclusionLabel">Edit Memo Conclusion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="edit_conclusionForm">

                    <!-- ✅ Hidden ID -->
                    <input type="hidden" id="edit_id" name="edit_id">

                    <div class="row">

                        <!-- Course -->
                        <div class="col-md-6 mb-3">
                            <label>Select Course *</label>
                            <select id="edit_course_master_pk" class="form-select">
                                <option value="">-- Select Course --</option>
                                @foreach($CourseMaster as $course)
                                <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Memo -->
                        <div class="col-md-6 mb-3">
                            <label>Select Memo *</label>
                            <select id="edit_memo_type_master_pk" class="form-select">
                                <option value="">-- Select Memo --</option>
                                @foreach($MemoTypeMaster as $memo)
                                <option value="{{ $memo->pk }}">{{ $memo->memo_type_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Memo Conclusion -->
                        <div class="col-md-6 mb-3">
                            <label>Select Memo Conclusion *</label>
                            <select id="edit_memo_conclusion_master_pk" class="form-select">
                                <option value="">-- Select Memo Conclusion --</option>
                                @foreach($MemoConclusionMaster as $memo)
                                <option value="{{ $memo->pk }}">{{ $memo->discussion_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label>Status *</label>
                            <select id="edit_active_inactive" class="form-select">
                                <option value="1">Active</option>
                                <option value="2">Inactive</option>
                            </select>
                        </div>

                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" id="edit_submitConclusionForm">Update</button>
            </div>

        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
    $(function() {
        $('#memoDecisionTable').DataTable({
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

@endsection
