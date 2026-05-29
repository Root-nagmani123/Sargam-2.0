@extends('admin.layouts.master')

@section('title', 'Employee Master')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/member-master-admin.css') }}?v={{ @filemtime(public_path('css/member-master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid em-member-page">
    <x-breadcrum title="Employee Master">
        <a href="{{ route('member.create') }}"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold text-nowrap shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Employee Master</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3 em-toolbar-actions">
        <a href="{{ route('member.excel.export') }}"
            class="em-btn-outline"
            aria-label="Download employee data">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
    </div>

    <div class="card em-dt-card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div id="emDtSearch" class="programme-dt-search ms-lg-auto" data-dt-search-for="member-table"></div>
            </div>

            <div class="programme-dt-panel em-dt-panel">
                <div class="table-responsive em-dt-scroll">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="emDtFooter"
                    class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="member-table"></div>
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
