@extends('admin.layouts.master')

@section('title', 'Memo Type Master')

@section('setup_content')
<div class="container-fluid">
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" >
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Memo Type Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <!-- Add Group Mapping -->
                                <!-- <a href="javascript:void(0);" id="showMemoAlert"
                                    class="btn btn-primary d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">add</i>
                                    Add Memo Type
                                </a> -->
                                <button id="showMemoAlert" class="btn btn-primary">
                                    Add Memo Type
                                </button>
                            </div>
                        </div>
                    </div>
                    <hr>

    <div class="card mmt-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="mmtDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="memotypemaster-table"></div>
            </div>

            <div class="programme-dt-panel mmt-dt-scroll">
                {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                <div id="mmtDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="memotypemaster-table"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Memo Type (shared modal) -->
<div class="modal fade mmt-form-modal" id="mmtMemoTypeModal" tabindex="-1" aria-labelledby="mmtMemoTypeModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered mmt-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title mb-0 fw-bold" id="mmtMemoTypeModalLabel">Add Memo Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-4 pb-2">
                <form id="memoTypeForm" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="pk" id="memo_pk" value="">

                    <div class="mb-4">
                        <label for="memo_type_name" class="form-label cgt-field-label mb-2">
                            Memo Type Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="memo_type_name"
                               id="memo_type_name"
                               class="form-control rounded-3"
                               placeholder="eg. General Medicine"
                               autocomplete="off">
                        <small class="text-danger d-none mt-1" id="memo_type_name_error">Required</small>
                    </div>

                    <div class="mb-4">
                        <label for="active_inactive" class="form-label cgt-field-label mb-2">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select name="active_inactive" id="active_inactive" class="form-select rounded-3">
                            <option value="">Select Status</option>
                            <option value="1">Active</option>
                            <option value="2">Inactive</option>
                        </select>
                        <small class="text-danger d-none mt-1" id="active_inactive_error">Required</small>
                    </div>

                    <div class="mb-0">
                        <label for="memo_doc_upload" class="form-label cgt-field-label mb-2">
                            Attachment
                        </label>
                        <div class="mmt-file-upload rounded-3">
                            <label for="memo_doc_upload" class="mmt-file-upload-btn mb-0">Choose file</label>
                            <span class="mmt-file-upload-name" id="mmt_file_name">No File Chosen</span>
                            <input type="file"
                                   name="memo_doc_upload"
                                   id="memo_doc_upload"
                                   class="mmt-file-upload-input"
                                   accept=".pdf,.doc,.docx">
                        </div>
                        <small class="text-danger d-none mt-1 d-block" id="memo_doc_upload_error"></small>
                        <div id="mmt_existing_doc_wrap" class="mt-2 d-none">
                            <a href="#" target="_blank" rel="noopener noreferrer" id="mmt_existing_doc_link" class="small text-primary text-decoration-none">
                                View Existing Document
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top border-0 gap-2 justify-content-end pt-3 pb-4 px-4">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4 py-2" id="mmtFormSubmit">Create Memo Type</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
    $(function() {
        const tableSelector = '#memotypemaster-table';
        const storeUrl = "{{ route('master.memo.type.master.store') }}";
        const csrfToken = "{{ csrf_token() }}";

        const mmtModalEl = document.getElementById('mmtMemoTypeModal');
        let mmtModalMode = 'add';

        if (mmtModalEl && mmtModalEl.parentElement && mmtModalEl.parentElement !== document.body) {
            document.body.appendChild(mmtModalEl);
        }

        function showMmtModal() {
            if (!mmtModalEl) {
                return;
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(mmtModalEl).show();
            } else if (window.jQuery) {
                $(mmtModalEl).modal('show');
            }
        }

        function hideMmtModal() {
            if (!mmtModalEl) {
                return;
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(mmtModalEl).hide();
            } else if (window.jQuery) {
                $(mmtModalEl).modal('hide');
            }
        }

        function updateMmtFileName() {
            const input = document.getElementById('memo_doc_upload');
            const label = document.getElementById('mmt_file_name');
            if (!input || !label) {
                return;
            }
            label.textContent = (input.files && input.files.length) ? input.files[0].name : 'No File Chosen';
        }

        $('#memo_doc_upload').on('change', updateMmtFileName);

        function resetMmtForm() {
            const $form = $('#memoTypeForm');
            $form.find('#memo_pk').val('');
            $form.find('#memo_type_name').val('').removeClass('is-invalid');
            $form.find('#active_inactive').val('');
            $form.find('#memo_doc_upload').val('');
            $('#mmt_file_name').text('No File Chosen');
            $form.find('#memo_type_name_error, #active_inactive_error').addClass('d-none').text('Required');
            $form.find('#memo_doc_upload_error').addClass('d-none').text('');
            $('#mmt_existing_doc_wrap').addClass('d-none');
            $('#mmt_existing_doc_link').attr('href', '#');
        }

        function openMmtModal(mode, data) {
            mmtModalMode = mode;
            const isAdd = mode === 'add';

            $('#mmtMemoTypeModalLabel').text(isAdd ? 'Add Memo Type' : 'Edit Memo Type');
            $('#mmtFormSubmit').text(isAdd ? 'Create Memo Type' : 'Update');

            resetMmtForm();

            if (!isAdd && data) {
                $('#memo_pk').val(data.pk || '');
                $('#memo_type_name').val(data.name || '');
                $('#active_inactive').val(
                    data.status === 1 || data.status === '1' ? '1' :
                    (data.status === 2 || data.status === '2' ? '2' : '')
                );

                if (data.file) {
                    $('#mmt_existing_doc_wrap').removeClass('d-none');
                    $('#mmt_existing_doc_link').attr('href', data.file);
                }
            }

            showMmtModal();
        }

        if (mmtModalEl) {
            mmtModalEl.addEventListener('shown.bs.modal', function() {
                $('#memo_type_name').trigger('focus');
            });
        }

        function reloadMmtTable() {
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().ajax.reload(null, false);
            }
        }

        function iconOnlyBtn($btn, iconClass, extraClass) {
            $btn.removeClass('btn btn-sm btn-outline-primary btn-outline-danger btn-outline-secondary d-inline-flex align-items-center gap-1');
            $btn.addClass('programme-action-btn ' + (extraClass || ''));
            $btn.find('.material-icons').remove();
            $btn.find('span').remove();
            if (!$btn.find('.bi').length) {
                $btn.append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
            }
        }

        function buildToggleControl($toggle) {
            const $label = $('<label>', {
                class: 'programme-action-toggle-icon mmt-action-toggle mb-0',
                'aria-label': 'Toggle memo type status'
            });

            $toggle.detach()
                .addClass('mmt-status-toggle-input')
                .appendTo($label);

            $label.append('<i class="bi bi-toggle-off mmt-toggle-icon mmt-toggle-icon--off" aria-hidden="true"></i>');
            $label.append('<i class="bi bi-toggle-on mmt-toggle-icon mmt-toggle-icon--on" aria-hidden="true"></i>');

            return $label;
        }

        function decorateMmtRows() {
            $(tableSelector + ' tbody tr').each(function() {
                const $row = $(this);
                if ($row.hasClass('mmt-row-decorated')) {
                    return;
                }

                const $cells = $row.find('td');
                if ($cells.length < 5) {
                    return;
                }

                const $nameCell = $cells.eq(1);
                const $docCell = $cells.eq(2);
                const $statusCell = $cells.eq(3);
                const $actionCell = $cells.eq(4);

                const $docLink = $docCell.find('a[href]');
                const viewUrl = $docLink.length ? $docLink.attr('href') : '';

                if (viewUrl) {
                    $docCell.html('<span class="mmt-doc-text mmt-doc-text--available">Available</span>');
                } else {
                    $docCell.html('<span class="mmt-doc-text mmt-doc-text--na">NA</span>');
                }

                const $toggle = $statusCell.find('.status-toggle').first();
                const isActive = $toggle.length ? $toggle.is(':checked') : false;
                const badgeClass = isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive';

                $statusCell.empty().append(
                    $('<span>', {
                        class: 'badge rounded-pill programme-status-badge mmt-status-badge ' + badgeClass,
                        text: isActive ? 'Active' : 'Inactive'
                    })
                );

                const $editMemo = $actionCell.find('.editMemo').first().detach();
                const $deleteBtn = $actionCell.find('.deleteBtn').first().detach();
                const $disabledDelete = $actionCell.find('button[disabled]').not('.deleteBtn').first().detach();

                const $group = $('<div>', {
                    class: 'd-inline-flex align-items-center programme-action-group',
                    role: 'group',
                    'aria-label': 'Memo type actions'
                });

                if (viewUrl) {
                    $group.append(
                        $('<a>', {
                            href: viewUrl,
                            target: '_blank',
                            rel: 'noopener noreferrer',
                            class: 'mmt-view-doc programme-action-btn',
                            'aria-label': 'View document',
                            html: '<i class="bi bi-eye" aria-hidden="true"></i>'
                        })
                    );
                } else {
                    $group.append(
                        $('<span>', {
                            class: 'mmt-view-doc programme-action-btn is-disabled',
                            'aria-disabled': 'true',
                            title: 'No document available',
                            html: '<i class="bi bi-eye" aria-hidden="true"></i>'
                        })
                    );
                }

                if ($editMemo.length) {
                    iconOnlyBtn($editMemo, 'bi-pencil');
                    $group.append($editMemo);
                }

                if ($toggle.length) {
                    $group.append(buildToggleControl($toggle));
                }

                if ($deleteBtn.length) {
                    iconOnlyBtn($deleteBtn, 'bi-trash3', 'programme-action-btn--danger');
                    $group.append($deleteBtn);
                } else if ($disabledDelete.length) {
                    iconOnlyBtn($disabledDelete, 'bi-trash3', 'programme-action-btn--danger is-disabled');
                    $disabledDelete.prop('disabled', true).attr('aria-disabled', 'true');
                    $group.append($disabledDelete);
                }

                $actionCell.empty().append($group);
                $nameCell.find('label').contents().unwrap();

                $row.addClass('mmt-row-decorated');
            });
        }

        function updateMmtRowBadge($checkbox, isActive) {
            const $badge = $checkbox.closest('tr').find('.mmt-status-badge');
            if ($badge.length) {
                $badge
                    .removeClass('programme-status-badge--active programme-status-badge--inactive')
                    .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
                    .text(isActive ? 'Active' : 'Inactive');
            }
        }

        $(tableSelector).on('draw.dt', function() {
            $(tableSelector + ' tbody tr').removeClass('mmt-row-decorated');
            decorateMmtRows();
        });

        $(tableSelector).on('init.dt', function() {
            const api = $(tableSelector).DataTable();
            if (api.settings()[0].oScroll.sX) {
                api.columns.adjust();
            }
            decorateMmtRows();
        });

        if ($.fn.DataTable.isDataTable(tableSelector)) {
            decorateMmtRows();
        }

        $(document).on('click', '.swal2-cancel, .swal2-deny', function() {
            setTimeout(function() {
                $(tableSelector + ' tbody .status-toggle').each(function() {
                    updateMmtRowBadge($(this), $(this).is(':checked'));
                });
            }, 0);
        });

        document.getElementById('showMemoAlert').addEventListener('click', function() {
            openMmtModal('add');
        });

        $(document).on('click', '.editMemo', function(e) {
            e.preventDefault();
            e.stopPropagation();

            openMmtModal('edit', {
                pk: $(this).data('pk'),
                name: $(this).data('name'),
                status: $(this).data('status'),
                file: $(this).data('file') || ''
            });
        });

        $('#mmtFormSubmit').on('click', function() {
            const $form = $('#memoTypeForm');
            const name = $form.find('#memo_type_name');
            const status = $form.find('#active_inactive');

            $form.find('#memo_type_name_error, #active_inactive_error').addClass('d-none');
            name.removeClass('is-invalid');
            status.removeClass('is-invalid');

            let isValid = true;

            if (!name.val().trim()) {
                $form.find('#memo_type_name_error').removeClass('d-none');
                name.addClass('is-invalid').focus();
                isValid = false;
            } else if (!status.val()) {
                $form.find('#active_inactive_error').removeClass('d-none');
                status.addClass('is-invalid').focus();
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            const formData = new FormData($form[0]);

            $.ajax({
                url: storeUrl,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status) {
                        hideMmtModal();
                        resetMmtForm();
                        reloadMmtTable();
                        Swal.fire('Success', response.message, 'success');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        if (errors.memo_type_name) {
                            $form.find('#memo_type_name_error').text(errors.memo_type_name[0]).removeClass('d-none');
                            name.addClass('is-invalid');
                        }
                        if (errors.active_inactive) {
                            $form.find('#active_inactive_error').text(errors.active_inactive[0]).removeClass('d-none');
                            status.addClass('is-invalid');
                        }
                        if (errors.memo_doc_upload) {
                            $form.find('#memo_doc_upload_error').text(errors.memo_doc_upload[0]).removeClass('d-none');
                        }
                    } else {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Server error or session expired', 'error');
                    }
                }
            });
        });

        $(document).on('click', '.deleteBtn', function(e) {
            e.preventDefault();

            const btn = $(this);
            const url = btn.data('url');
            const pk = btn.data('pk');

            Swal.fire({
                title: 'Are you sure?',
                text: 'This record is permanent deleted',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        beforeSend: function() {
                            btn.prop('disabled', true);
                        },
                        success: function(res) {
                            if (res.status) {
                                Swal.fire('Deleted!', res.message, 'success');
                                reloadMmtTable();
                            } else {
                                Swal.fire('Error!', res.message, 'error');
                                btn.prop('disabled', false);
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                            btn.prop('disabled', false);
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
