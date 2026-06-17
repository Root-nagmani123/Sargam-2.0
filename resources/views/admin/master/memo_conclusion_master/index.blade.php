@extends('admin.layouts.master')

@section('title', 'Memo Conclusion Master')

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Memo Conclusion Master" />
    <div class="card" >
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Memo Conclusion Master</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <!-- Add Group Mapping -->
                        <a href="javascript:void(0)" id="showConclusionAlert"
                            class="btn btn-primary d-flex align-items-center">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">add</i>
                            Add Memo Conclusion
                        </a>
                    </div>
                </div>
            </div>
            <hr>

    <div class="card mcm-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="mcmDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="memoconclusionmaster-table"></div>
            </div>

            <div class="programme-dt-panel mcm-dt-scroll">
                {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                <div id="mcmDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="memoconclusionmaster-table"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Memo Conclusion (shared modal) -->
<div class="modal fade mcm-form-modal" id="mcmConclusionModal" tabindex="-1" aria-labelledby="mcmConclusionModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered mcm-modal-dialog">
        <div class="modal-content cgt-form-modal border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title mb-0 fw-bold" id="mcmConclusionModalLabel">Add Memo Conclusion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-4 pb-2">
                <form id="conclusionForm" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="id" id="mcm_edit_id" value="">

                    <div class="mb-4">
                        <label for="discussion_name" class="form-label cgt-field-label mb-2">
                            Discussion Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="discussion_name"
                               id="discussion_name"
                               class="form-control rounded-3"
                               placeholder="eg. General Meeting"
                               autocomplete="off">
                        <small class="text-danger d-none mt-1" id="discussion_name_error">Required</small>
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
                        <label for="pt_discusion" class="form-label cgt-field-label mb-2">
                            PT Discussion
                        </label>
                        <input type="text"
                               name="pt_discusion"
                               id="pt_discusion"
                               class="form-control rounded-3"
                               placeholder="eg. Lorem Ipsum Dolor"
                               autocomplete="off">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top border-0 gap-2 justify-content-end pt-3 pb-4 px-4">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-3 px-4 py-2" id="mcmFormSubmit">Create Memo Conclusion</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
    $(function() {
        const tableSelector = '#memoconclusionmaster-table';
        const storeUrl = "{{ route('master.memo.conclusion.master.store') }}";
        const csrfToken = "{{ csrf_token() }}";

        const mcmModalEl = document.getElementById('mcmConclusionModal');
        let mcmModalMode = 'add';

        if (mcmModalEl && mcmModalEl.parentElement && mcmModalEl.parentElement !== document.body) {
            document.body.appendChild(mcmModalEl);
        }

        function showMcmModal() {
            if (!mcmModalEl) {
                return;
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(mcmModalEl).show();
            } else if (window.jQuery) {
                $(mcmModalEl).modal('show');
            }
        }

        function hideMcmModal() {
            if (!mcmModalEl) {
                return;
            }
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(mcmModalEl).hide();
            } else if (window.jQuery) {
                $(mcmModalEl).modal('hide');
            }
        }

        function resetMcmForm() {
            const $form = $('#conclusionForm');
            $form.find('#mcm_edit_id').val('');
            $form.find('#discussion_name').val('').removeClass('is-invalid');
            $form.find('#pt_discusion').val('');
            $form.find('#active_inactive').val('');
            $form.find('#discussion_name_error, #active_inactive_error').addClass('d-none').text('Required');
        }

        function openMcmModal(mode, data) {
            mcmModalMode = mode;
            const isAdd = mode === 'add';

            $('#mcmConclusionModalLabel').text(isAdd ? 'Add Memo Conclusion' : 'Edit Memo Conclusion');
            $('#mcmFormSubmit').text(isAdd ? 'Create Memo Conclusion' : 'Update');

            resetMcmForm();

            if (!isAdd && data) {
                $('#mcm_edit_id').val(data.pk || '');
                $('#discussion_name').val(data.discussion_name || '');
                $('#pt_discusion').val(data.pt_discusion || '');
                $('#active_inactive').val(
                    data.active_inactive === 1 || data.active_inactive === '1' ? '1' :
                    (data.active_inactive === 2 || data.active_inactive === '2' ? '2' : '')
                );
            }

            showMcmModal();
        }

        if (mcmModalEl) {
            mcmModalEl.addEventListener('shown.bs.modal', function() {
                $('#discussion_name').trigger('focus');
            });
        }

        function reloadMcmTable() {
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
                class: 'programme-action-toggle-icon mcm-action-toggle mb-0',
                'aria-label': 'Toggle memo conclusion status'
            });

            $toggle.detach().addClass('mcm-status-toggle-input').appendTo($label);
            $label.append('<i class="bi bi-toggle-off mcm-toggle-icon mcm-toggle-icon--off" aria-hidden="true"></i>');
            $label.append('<i class="bi bi-toggle-on mcm-toggle-icon mcm-toggle-icon--on" aria-hidden="true"></i>');

            return $label;
        }

        function decorateMcmRows() {
            $(tableSelector + ' tbody tr').each(function() {
                const $row = $(this);
                if ($row.hasClass('mcm-row-decorated')) {
                    return;
                }

                const $cells = $row.find('td');
                if ($cells.length < 5) {
                    return;
                }

                const $nameCell = $cells.eq(1);
                const $ptCell = $cells.eq(2);
                const $statusCell = $cells.eq(3);
                const $actionCell = $cells.eq(4);

                $nameCell.addClass('mcm-col-name');
                $ptCell.addClass('mcm-col-pt');

                const $toggle = $statusCell.find('.status-toggle').first();
                const isActive = $toggle.length ? $toggle.is(':checked') : false;

                $statusCell.empty().append(
                    $('<span>', {
                        class: 'badge rounded-pill programme-status-badge mcm-status-badge ' +
                            (isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive'),
                        text: isActive ? 'Active' : 'Inactive'
                    })
                );

                const $editBtn = $actionCell.find('.editshowConclusionAlert').first().detach();
                const $deleteBtn = $actionCell.find('.deleteBtn').first().detach();
                const $disabledDelete = $actionCell.find('button[disabled]').not('.deleteBtn').first().detach();

                const $group = $('<div>', {
                    class: 'd-inline-flex align-items-center programme-action-group',
                    role: 'group',
                    'aria-label': 'Memo conclusion actions'
                });

                if ($editBtn.length) {
                    iconOnlyBtn($editBtn, 'bi-pencil');
                    $group.append($editBtn);
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
                $row.addClass('mcm-row-decorated');
            });
        }

        function updateMcmRowBadge($checkbox, isActive) {
            const $badge = $checkbox.closest('tr').find('.mcm-status-badge');
            if ($badge.length) {
                $badge
                    .removeClass('programme-status-badge--active programme-status-badge--inactive')
                    .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
                    .text(isActive ? 'Active' : 'Inactive');
            }
        }

        $(tableSelector).on('draw.dt', function() {
            $(tableSelector + ' tbody tr').removeClass('mcm-row-decorated');
            decorateMcmRows();
        });

        $(tableSelector).on('init.dt', function() {
            const api = $(tableSelector).DataTable();
            if (api.settings()[0].oScroll.sX) {
                api.columns.adjust();
            }
            decorateMcmRows();
        });

        if ($.fn.DataTable.isDataTable(tableSelector)) {
            decorateMcmRows();
        }

        $(document).on('click', '.swal2-cancel, .swal2-deny', function() {
            setTimeout(function() {
                $(tableSelector + ' tbody .status-toggle').each(function() {
                    updateMcmRowBadge($(this), $(this).is(':checked'));
                });
            }, 0);
        });

        document.getElementById('showConclusionAlert').addEventListener('click', function() {
            openMcmModal('add');
        });

        $(document).on('click', '.editshowConclusionAlert', function(e) {
            e.preventDefault();
            e.stopPropagation();

            openMcmModal('edit', {
                pk: $(this).data('pk'),
                discussion_name: $(this).data('discussion_name'),
                pt_discusion: $(this).data('pt_discusion'),
                active_inactive: $(this).data('active_inactive')
            });
        });

        $('#mcmFormSubmit').on('click', function() {
            const $form = $('#conclusionForm');
            const discussion = $form.find('#discussion_name');
            const status = $form.find('#active_inactive');

            $form.find('#discussion_name_error, #active_inactive_error').addClass('d-none');
            discussion.removeClass('is-invalid');
            status.removeClass('is-invalid');

            let isValid = true;

            if (!discussion.val().trim()) {
                $form.find('#discussion_name_error').removeClass('d-none');
                discussion.addClass('is-invalid').focus();
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
                        hideMcmModal();
                        resetMcmForm();
                        reloadMcmTable();
                        Swal.fire('Success', response.message, 'success');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        if (errors.discussion_name) {
                            $form.find('#discussion_name_error').text(errors.discussion_name[0]).removeClass('d-none');
                            discussion.addClass('is-invalid');
                        }
                        if (errors.active_inactive) {
                            $form.find('#active_inactive_error').text(errors.active_inactive[0]).removeClass('d-none');
                            status.addClass('is-invalid');
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

            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone!',
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
                        success: function(res) {
                            if (res.status) {
                                Swal.fire('Deleted!', res.message, 'success');
                                reloadMcmTable();
                            } else {
                                Swal.fire('Error!', res.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Something went wrong.', 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
