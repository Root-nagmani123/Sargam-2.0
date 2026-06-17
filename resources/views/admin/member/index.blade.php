@extends('admin.layouts.master')

@section('title', 'Employee Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/member-master-admin.css') }}?v={{ @filemtime(public_path('css/member-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Member"></x-breadcrum>
 <div id="status-msg"></div>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" >
            <div class="card-body">
                <div class="row">
                        <div class="col-6">
                            <h4>Member</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('member.create') }}" class="btn btn-primary">+ Add Member</a>
                                {{-- <a href="#" class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#vertical-center-scroll-modal">Bulk Upload</a> --}}
                                <a href="{{ route('member.excel.export') }}" class="btn btn-secondary">Export</a>
                            </div>
                        </div>
                    </div>
                    <!-- Vertically centered modal -->
                    <div class="modal fade" id="vertical-center-scroll-modal" tabindex="-1"
                        aria-labelledby="vertical-center-modal" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header d-flex align-items-center">
                                    <h4 class="modal-title" id="myLargeModalLabel">
                                        Bulk Upload for member
                                    </h4>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="" method="POST">
                                        <label for="" class="form-label">Upload CSV</label>
                                        <input type="file" name="file" id="file" class="form-control">
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit"
                                        class="btn bg-success-subtle text-success  waves-effect text-start">
                                        Submit
                                    </button>
                                    <button type="button"
                                        class="btn bg-danger-subtle text-danger  waves-effect text-start"
                                        data-bs-dismiss="modal">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table']) !!}
                    </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
(function () {
    var tableSelector = '#member-table';

    function iconOnlyLink($link, iconClass, extraClass, label) {
        $link.addClass('em-action-btn ' + (extraClass || ''));
        $link.attr('aria-label', label || $link.text().trim());
        $link.empty().append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
    }

    function iconOnlyBtn($btn, iconClass, extraClass, label) {
        $btn.removeClass('btn btn-sm btn-primary btn-success btn-danger btn-outline-primary btn-outline-danger btn-outline-secondary');
        $btn.addClass('em-action-btn ' + (extraClass || ''));
        $btn.attr('aria-label', label || $btn.text().trim());
        $btn.empty().append('<i class="bi ' + iconClass + '" aria-hidden="true"></i>');
    }

    function decorateMemberRows() {
        if (typeof jQuery === 'undefined') {
            return;
        }
        var $ = jQuery;

        $(tableSelector + ' tbody tr').each(function () {
            var $row = $(this);
            if ($row.hasClass('em-row-decorated')) {
                return;
            }

            var $cells = $row.find('td');
            if (!$cells.length) {
                return;
            }

            var $actionCell = $cells.last();
            var $editLink = $actionCell.find('a.btn-primary, a[href*="edit"]').first().detach();
            var $viewLink = $actionCell.find('a.btn-success, a[href*="show"]').first().detach();
            var $deleteForm = $actionCell.find('form').first().detach();
            var $deleteBtn = $deleteForm.length ? $deleteForm.find('button[type="submit"]').first() : $();

            var $group = $('<div>', {
                class: 'em-member-actions',
                role: 'group',
                'aria-label': 'Employee actions'
            });

            if ($editLink.length) {
                iconOnlyLink($editLink, 'bi-pencil', 'em-action-edit', 'Edit employee');
                $group.append($editLink);
            }

            if ($viewLink.length) {
                iconOnlyLink($viewLink, 'bi-eye', 'em-action-view', 'View employee');
                $group.append($viewLink);
            }

            if ($deleteForm.length && $deleteBtn.length) {
                iconOnlyBtn($deleteBtn, 'bi-trash', 'em-action-delete', 'Delete employee');
                $deleteForm.addClass('d-inline m-0');
                $group.append($deleteForm);
            }

            $actionCell.empty().append($group);
            $row.addClass('em-row-decorated');
        });
    }

    function bindMemberTableUi() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.dataTable) {
            return;
        }
        var $ = jQuery;

        $(tableSelector).on('draw.dt init.dt', function () {
            $(tableSelector + ' tbody tr').removeClass('em-row-decorated');
            decorateMemberRows();
        });

        if ($.fn.DataTable.isDataTable(tableSelector)) {
            decorateMemberRows();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindMemberTableUi);
    } else {
        bindMemberTableUi();
    }
})();
</script>
@endpush
