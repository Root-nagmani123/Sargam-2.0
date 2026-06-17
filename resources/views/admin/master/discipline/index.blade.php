@extends('admin.layouts.master')

@section('title', 'Discipline Master')

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Discipline Master"></x-breadcrum>
    <div class="card" >
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Discipline Master</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <!-- Add Group Mapping -->
                        <a href="{{ route('master.discipline.create') }}"
                            class="btn btn-primary d-flex align-items-center">
                            <i class="material-icons menu-icon material-symbols-rounded"
                                style="font-size: 24px;">add</i>
                            Add Discipline
                        </a>
                    </div>
                </div>
            </div>
            <hr>

    <div class="card dmc-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="dmcDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="discipline-table"></div>
            </div>

            <div class="programme-dt-panel dmc-dt-scroll">
                {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                <div id="dmcDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="discipline-table"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
    $(function() {
        const tableSelector = '#discipline-table';

        function iconOnlyLink($link, iconClass, extraClass) {
            $link.addClass('programme-action-btn ' + (extraClass || ''));
            $link.find('.material-icons').remove();
            if (!$link.find('.bi').length) {
                $link.append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
            }
        }

        function iconOnlyBtn($btn, iconClass, extraClass) {
            $btn.removeAttr('style');
            $btn.addClass('programme-action-btn ' + (extraClass || ''));
            $btn.find('.material-icons').remove();
            if (!$btn.find('.bi').length) {
                $btn.append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
            }
        }

        function buildToggleControl($toggle) {
            const $label = $('<label>', {
                class: 'programme-action-toggle-icon dmc-action-toggle mb-0',
                'aria-label': 'Toggle discipline status'
            });

            $toggle.detach().addClass('dmc-status-toggle-input').appendTo($label);
            $label.append('<i class="bi bi-toggle-off dmc-toggle-icon dmc-toggle-icon--off" aria-hidden="true"></i>');
            $label.append('<i class="bi bi-toggle-on dmc-toggle-icon dmc-toggle-icon--on" aria-hidden="true"></i>');

            return $label;
        }

        function decorateDmcRows() {
            $(tableSelector + ' tbody tr').each(function() {
                const $row = $(this);
                if ($row.hasClass('dmc-row-decorated')) {
                    return;
                }

                const $cells = $row.find('td');
                if ($cells.length < 6) {
                    return;
                }

                const $courseCell = $cells.eq(1);
                const $disciplineCell = $cells.eq(2);
                const $marksCell = $cells.eq(3);
                const $statusCell = $cells.eq(4);
                const $actionCell = $cells.eq(5);

                $courseCell.addClass('dmc-col-course');
                $disciplineCell.addClass('dmc-col-discipline');
                $marksCell.addClass('dmc-col-marks');

                const $toggle = $statusCell.find('.status-toggle').first();
                const isActive = $toggle.length ? $toggle.is(':checked') : false;

                $statusCell.empty().append(
                    $('<span>', {
                        class: 'badge rounded-pill programme-status-badge dmc-status-badge ' +
                            (isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive'),
                        text: isActive ? 'Active' : 'Inactive'
                    })
                );

                const $editLink = $actionCell.find('a[title="Edit"], a[href*="discipline"]').first().detach();
                const $deleteForm = $actionCell.find('form').first().detach();
                let $deleteBtn = $deleteForm.length
                    ? $deleteForm.find('button').first()
                    : $actionCell.find('button[disabled], button[title="Delete"]').first().detach();

                const $group = $('<div>', {
                    class: 'd-inline-flex align-items-center programme-action-group',
                    role: 'group',
                    'aria-label': 'Discipline actions'
                });

                if ($editLink.length) {
                    iconOnlyLink($editLink, 'bi-pencil');
                    $group.append($editLink);
                }

                if ($toggle.length) {
                    $group.append(buildToggleControl($toggle));
                }

                if ($deleteForm.length && $deleteBtn.length) {
                    iconOnlyBtn($deleteBtn, 'bi-trash3', 'programme-action-btn--danger');
                    $deleteForm.addClass('d-inline m-0');
                    $group.append($deleteForm);
                } else if ($deleteBtn && $deleteBtn.length) {
                    iconOnlyBtn($deleteBtn, 'bi-trash3', 'programme-action-btn--danger is-disabled');
                    $deleteBtn.prop('disabled', true).attr('aria-disabled', 'true');
                    $group.append($deleteBtn);
                }

                $actionCell.empty().append($group);
                $row.addClass('dmc-row-decorated');
            });
        }

        function updateDmcRowBadge($checkbox, isActive) {
            const $badge = $checkbox.closest('tr').find('.dmc-status-badge');
            if ($badge.length) {
                $badge
                    .removeClass('programme-status-badge--active programme-status-badge--inactive')
                    .addClass(isActive ? 'programme-status-badge--active' : 'programme-status-badge--inactive')
                    .text(isActive ? 'Active' : 'Inactive');
            }
        }

        $(tableSelector).on('draw.dt', function() {
            $(tableSelector + ' tbody tr').removeClass('dmc-row-decorated');
            decorateDmcRows();
        });

        $(tableSelector).on('init.dt', function() {
            const api = $(tableSelector).DataTable();
            if (api.settings()[0].oScroll.sX) {
                api.columns.adjust();
            }
            decorateDmcRows();
        });

        if ($.fn.DataTable.isDataTable(tableSelector)) {
            decorateDmcRows();
        }

        $(document).on('click', '.swal2-cancel, .swal2-deny', function() {
            setTimeout(function() {
                $(tableSelector + ' tbody .status-toggle').each(function() {
                    updateDmcRowBadge($(this), $(this).is(':checked'));
                });
            }, 0);
        });
    });
</script>
@endpush
