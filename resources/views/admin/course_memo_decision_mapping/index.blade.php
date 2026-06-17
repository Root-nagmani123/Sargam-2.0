@extends('admin.layouts.master')

@section('title', 'Course Memo Decision Mapping')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Course Memo Decision Mapping" />
    <div class="datatables">
        <div class="card" >
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Course Memo Decision Mapping</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
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
                            <thead style="background-color: #af2910;">
                                <tr>
                                    <th>S.No.</th>
                                    <th>Course Name</th>
                                    <th>Memo Decision</th>
                                    <th>Memo Conclusion</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>

    <div class="card cmdm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="cmdmDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="memoDecisionTable"></div>
            </div>

            <div class="programme-dt-panel cmdm-dt-scroll">
                <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="memoDecisionTable">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">S. No.</th>
                            <th scope="col">Course Name</th>
                            <th scope="col">Memo Decision</th>
                            <th scope="col">Memo Conclusion</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                </table>
                <div id="cmdmDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="memoDecisionTable"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add mapping modal -->
<div class="modal fade cmdm-form-modal" id="conclusionModal" tabindex="-1" aria-labelledby="conclusionModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered cmdm-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title mb-0 fw-bold" id="conclusionModalLabel">Add Memo Conclusion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-4 pb-2">
                <form id="conclusionForm" novalidate>
                    <div class="mb-4">
                        <label for="course_master_pk" class="form-label cgt-field-label mb-2">
                            Select Course <span class="text-danger">*</span>
                        </label>
                        <select name="course_master_pk" id="course_master_pk" class="form-select rounded-3" required>
                            <option value="">Select Course</option>
                            @foreach($CourseMaster as $course)
                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="memo_type_master_pk" class="form-label cgt-field-label mb-2">
                            Select Memo <span class="text-danger">*</span>
                        </label>
                        <select name="memo_type_master_pk" id="memo_type_master_pk" class="form-select rounded-3" required>
                            <option value="">Select Memo</option>
                            @foreach($MemoTypeMaster as $memo)
                            <option value="{{ $memo->pk }}">{{ $memo->memo_type_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="memo_conclusion_master_pk" class="form-label cgt-field-label mb-2">
                            Select Memo Conclusion <span class="text-danger">*</span>
                        </label>
                        <select name="memo_conclusion_master_pk" id="memo_conclusion_master_pk" class="form-select rounded-3" required>
                            <option value="">Select Memo Conclusion</option>
                            @foreach($MemoConclusionMaster as $memo)
                            <option value="{{ $memo->pk }}">{{ $memo->discussion_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-0">
                        <label for="active_inactive" class="form-label cgt-field-label mb-2">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select name="active_inactive" id="active_inactive" class="form-select rounded-3" required>
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top border-0 gap-2 justify-content-end pt-3 pb-4 px-4">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitConclusionForm" class="btn btn-primary rounded-3 px-4 py-2">Create Memo Mapping</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit mapping modal -->
<div class="modal fade cmdm-form-modal" id="editconclusionModal" tabindex="-1" aria-labelledby="editConclusionLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered cmdm-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title mb-0 fw-bold" id="editConclusionLabel">Edit Memo Conclusion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-4 pb-2">
                <form id="edit_conclusionForm" novalidate>
                    <input type="hidden" id="edit_id" name="edit_id">

                    <div class="mb-4">
                        <label for="edit_course_master_pk" class="form-label cgt-field-label mb-2">
                            Select Course <span class="text-danger">*</span>
                        </label>
                        <select id="edit_course_master_pk" class="form-select rounded-3">
                            <option value="">Select Course</option>
                            @foreach($CourseMaster as $course)
                            <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="edit_memo_type_master_pk" class="form-label cgt-field-label mb-2">
                            Select Memo <span class="text-danger">*</span>
                        </label>
                        <select id="edit_memo_type_master_pk" class="form-select rounded-3">
                            <option value="">Select Memo</option>
                            @foreach($MemoTypeMaster as $memo)
                            <option value="{{ $memo->pk }}">{{ $memo->memo_type_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="edit_memo_conclusion_master_pk" class="form-label cgt-field-label mb-2">
                            Select Memo Conclusion <span class="text-danger">*</span>
                        </label>
                        <select id="edit_memo_conclusion_master_pk" class="form-select rounded-3">
                            <option value="">Select Memo Conclusion</option>
                            @foreach($MemoConclusionMaster as $memo)
                            <option value="{{ $memo->pk }}">{{ $memo->discussion_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-0">
                        <label for="edit_active_inactive" class="form-label cgt-field-label mb-2">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select id="edit_active_inactive" class="form-select rounded-3">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top border-0 gap-2 justify-content-end pt-3 pb-4 px-4">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4 py-2" id="edit_submitConclusionForm">Update</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function() {
        const tableSelector = '#memoDecisionTable';

        const addModalEl = document.getElementById('conclusionModal');
        const editModalEl = document.getElementById('editconclusionModal');

        [addModalEl, editModalEl].forEach(function(modalEl) {
            if (modalEl && modalEl.parentElement && modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
        });

        if (!$.fn.DataTable.isDataTable(tableSelector)) {
            $(tableSelector).DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('course.memo.decision.index') }}",
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

        function iconOnlyBtn($btn, iconClass, extraClass) {
            $btn.removeClass('btn btn-sm btn-outline-warning btn-outline-danger btn-outline-secondary d-flex align-items-center gap-1');
            $btn.addClass('programme-action-btn ' + (extraClass || ''));
            $btn.find('.material-icons').remove();
            $btn.find('span').remove();
            if (!$btn.find('.bi').length) {
                $btn.append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
            }
        }

        function buildToggleControl($toggle) {
            const $label = $('<label>', {
                class: 'programme-action-toggle-icon cmdm-action-toggle mb-0',
                'aria-label': 'Toggle mapping status'
            });

            $toggle.detach().addClass('cmdm-status-toggle-input').appendTo($label);
            $label.append('<i class="bi bi-toggle-off cmdm-toggle-icon cmdm-toggle-icon--off" aria-hidden="true"></i>');
            $label.append('<i class="bi bi-toggle-on cmdm-toggle-icon cmdm-toggle-icon--on" aria-hidden="true"></i>');

            return $label;
        }

        function decorateCmdmRows() {
            $(tableSelector + ' tbody tr').each(function() {
                const $row = $(this);
                if ($row.hasClass('cmdm-row-decorated')) {
                    return;
                }

                const $cells = $row.find('td');
                if ($cells.length < 6) {
                    return;
                }

                const $courseCell = $cells.eq(1);
                const $memoCell = $cells.eq(2);
                const $conclusionCell = $cells.eq(3);
                const $statusCell = $cells.eq(4);
                const $actionCell = $cells.eq(5);

                $courseCell.addClass('cmdm-col-course');
                $memoCell.addClass('cmdm-col-memo-decision');
                $conclusionCell.addClass('cmdm-col-memo-conclusion');

                const $toggle = $statusCell.find('.status-toggle').first();
                const isActive = $toggle.length ? $toggle.is(':checked') : false;

                $statusCell.empty().append(
                    $('<span>', {
                        class: 'badge rounded-pill programme-status-badge cmdm-status-badge ' +
                            (isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive'),
                        text: isActive ? 'Active' : 'Inactive'
                    })
                );

                const $editBtn = $actionCell.find('.editConclusion').first().detach();
                const $deleteForm = $actionCell.find('form').first().detach();
                const $disabledDelete = $actionCell.find('button[disabled]').not('.editConclusion').first().detach();

                const $group = $('<div>', {
                    class: 'd-inline-flex align-items-center programme-action-group',
                    role: 'group',
                    'aria-label': 'Course memo mapping actions'
                });

                if ($editBtn.length) {
                    iconOnlyBtn($editBtn, 'bi-pencil');
                    $group.append($editBtn);
                }

                if ($toggle.length) {
                    $group.append(buildToggleControl($toggle));
                }

                if ($deleteForm.length) {
                    const $deleteBtn = $deleteForm.find('button[type="submit"]').first();
                    if ($deleteBtn.length) {
                        iconOnlyBtn($deleteBtn, 'bi-trash3', 'programme-action-btn--danger');
                        $deleteForm.addClass('d-inline m-0');
                        $group.append($deleteForm);
                    }
                } else if ($disabledDelete.length) {
                    iconOnlyBtn($disabledDelete, 'bi-trash3', 'programme-action-btn--danger is-disabled');
                    $disabledDelete.prop('disabled', true).attr('aria-disabled', 'true');
                    $group.append($disabledDelete);
                }

                $actionCell.empty().append($group);
                $row.addClass('cmdm-row-decorated');
            });
        }

        function updateCmdmRowBadge($checkbox, isActive) {
            const $badge = $checkbox.closest('tr').find('.cmdm-status-badge');
            if ($badge.length) {
                $badge
                    .removeClass('programme-status-badge--active programme-status-badge--inactive')
                    .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
                    .text(isActive ? 'Active' : 'Inactive');
            }
        }

        $(tableSelector).on('draw.dt', function() {
            $(tableSelector + ' tbody tr').removeClass('cmdm-row-decorated');
            decorateCmdmRows();
        });

        $(tableSelector).on('init.dt', function() {
            decorateCmdmRows();
        });

        if ($.fn.DataTable.isDataTable(tableSelector)) {
            decorateCmdmRows();
        }

        $(document).on('click', '.swal2-cancel, .swal2-deny', function() {
            setTimeout(function() {
                $(tableSelector + ' tbody .status-toggle').each(function() {
                    updateCmdmRowBadge($(this), $(this).is(':checked'));
                });
            }, 0);
        });

        document.getElementById('showConclusionAlert').addEventListener('click', function() {
            document.getElementById('conclusionForm').reset();
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(addModalEl).show();
            } else if (window.jQuery) {
                $('#conclusionModal').modal('show');
            }
        });

        document.getElementById('submitConclusionForm').addEventListener('click', function(e) {
            e.preventDefault();

            const course = document.getElementById('course_master_pk').value;
            const memo = document.getElementById('memo_type_master_pk').value;
            const conclusion = document.getElementById('memo_conclusion_master_pk').value;
            const status = document.getElementById('active_inactive').value;

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
                            $('#memoDecisionTable').DataTable().ajax.reload(null, false);
                            document.getElementById('conclusionForm').reset();
                            if (window.bootstrap && bootstrap.Modal) {
                                bootstrap.Modal.getOrCreateInstance(addModalEl).hide();
                            } else {
                                $('#conclusionModal').modal('hide');
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message || 'Something went wrong!'
                        });
                    }
                })
                .catch(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Please try again later.'
                    });
                });
        });

        $(document).on('click', '.editConclusion', function() {
            const id = $(this).data('id');
            const course = $(this).data('course');
            const memo = $(this).data('memo');
            const conclusion = $(this).data('conclusion');
            const status = $(this).data('status');

            $('#edit_id').val(id);
            $('#edit_course_master_pk').val(course).trigger('change');
            $('#edit_memo_type_master_pk').val(memo).trigger('change');
            $('#edit_memo_conclusion_master_pk').val(conclusion).trigger('change');
            $('#edit_active_inactive').val(status).trigger('change');

            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(editModalEl).show();
            } else if (window.jQuery) {
                $('#editconclusionModal').modal('show');
            }
        });

        $('#edit_submitConclusionForm').on('click', function(e) {
            e.preventDefault();

            const data = {
                id: $('#edit_id').val(),
                course_master_pk: $('#edit_course_master_pk').val(),
                memo_type_master_pk: $('#edit_memo_type_master_pk').val(),
                memo_conclusion_master_pk: $('#edit_memo_conclusion_master_pk').val(),
                active_inactive: $('#edit_active_inactive').val()
            };

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
                            if (window.bootstrap && bootstrap.Modal) {
                                bootstrap.Modal.getOrCreateInstance(editModalEl).hide();
                            } else {
                                $('#editconclusionModal').modal('hide');
                            }
                        });
                    }
                });
        });
    });
</script>
@endsection
