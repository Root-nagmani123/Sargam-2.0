@extends('admin.layouts.master')

@section('title', 'LBSNAA Directory')

@section('content')
<div class="container-fluid lbs-directory">
    <x-breadcrum title="LBSNAA Directory"></x-breadcrum>

    {{-- ===== Download (export) — top right, above card ===== --}}
    <div class="d-flex justify-content-end mb-3">
        <form method="GET" action="{{ route('admin.directory.lbsnaa') }}" class="dropdown m-0">
            <button type="button" class="lbs-download-btn" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                <i class="material-icons material-symbols-rounded">download</i>
                <span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end lbs-dropdown-menu">
                <li>
                    <button type="submit" name="export" value="csv" class="dropdown-item d-flex align-items-center gap-2">
                        <i class="material-icons material-symbols-rounded">description</i> CSV
                    </button>
                </li>
                <li>
                    <button type="submit" name="export" value="excel" class="dropdown-item d-flex align-items-center gap-2">
                        <i class="material-icons material-symbols-rounded">grid_on</i> Excel
                    </button>
                </li>
            </ul>
        </form>
    </div>

    <div class="card border-0 lbs-card">
        <div class="card-body p-0">

            {{-- ===== Toolbar ===== --}}
            <div class="lbs-toolbar d-flex flex-wrap align-items-center gap-2 px-3 px-lg-4 py-3">

                <div class="ms-auto d-flex align-items-center gap-2">
                    {{-- Columns visibility --}}
                    <button type="button" id="lbsColumnsBtn" class="lbs-columns-btn"
                            data-bs-toggle="modal" data-bs-target="#lbsnaaColumnsModal">
                        <span>Columns</span><i class="material-icons material-symbols-rounded">view_column</i>
                    </button>

                    {{-- Expandable search (client-side DataTables search) --}}
                    <div class="lbs-search-wrap" id="lbsSearchWrap">
                        <input
                            id="quickSearch"
                            type="text"
                            class="lbs-search-field"
                            placeholder="Search name, email, mobile, designation…"
                            autocomplete="off"
                        >
                        <button type="button" class="lbs-search-btn" id="lbsSearchToggle" aria-label="Search">
                            <i class="material-icons material-symbols-rounded">search</i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ===== Table ===== --}}
            <div class="table-responsive lbs-directory-scroll">
                <table class="table align-middle mb-0" id="lbsnaaDirectoryTable">
                    <thead>
                        <tr>
                            <th class="lbs-col-sno">S. No.</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Section</th>
                            <th>Address</th>
                            <th>Office Est.</th>
                            <th>Contact Number</th>
                            <th>Residence</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                            @php
                                $name = trim(($employee->first_name ?? '') . ' ' . ($employee->middle_name ?? '') . ' ' . ($employee->last_name ?? ''));
                                $email = $employee->officalemail ?: $employee->email;
                            @endphp
                            <tr>
                                <td class="lbs-col-sno">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="lbs-name-cell">
                                        @if(!empty($employee->profile_picture))
                                            <img
                                                src="{{ asset('storage/' . $employee->profile_picture) }}"
                                                alt="{{ $name ?: 'photo' }}"
                                                class="lbs-avatar-photo directory-photo"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                        @else
                                            <span class="lbs-avatar-letter">{{ strtoupper(mb_substr($name ?: '?', 0, 1)) }}</span>
                                        @endif
                                        <span class="lbs-emp-name">{{ $name ?: '-' }}</span>
                                    </div>
                                </td>
                                <td>{{ $employee->designation_name ?: '-' }}</td>
                                <td>{{ $employee->department_name ?: '-' }}</td>
                                <td class="lbs-address-cell" title="{{ $employee->current_address }}">{{ $employee->current_address ?: '-' }}</td>
                                <td>{{ $employee->office_extension_no ?: '-' }}</td>
                                <td>{{ $employee->mobile ?: '-' }}</td>
                                <td>{{ $employee->residence_no ?: '-' }}</td>
                                <td>{{ $email ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- ===== Column Visibility modal ===== --}}
    <div class="modal fade lbs-columns-modal" id="lbsnaaColumnsModal" tabindex="-1" aria-labelledby="lbsnaaColumnsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lbsnaaColumnsModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="lbs-col-grid" id="lbsColumnsGrid"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn lbs-modal-close-btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* ===== Page / Card ===== */
.lbs-directory .lbs-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(16, 24, 40, .06), 0 1px 2px rgba(16, 24, 40, .04);
    overflow: hidden;
}

/* ===== Download button (above card) ===== */
.lbs-download-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border: 1px solid #cfe0f5;
    border-radius: 8px;
    background: #fff;
    color: #1565c0;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.2;
    cursor: pointer;
    transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
}
.lbs-download-btn:hover,
.lbs-download-btn:focus {
    background: #f3f8ff;
    border-color: #9cc2ee;
    box-shadow: 0 2px 8px rgba(13, 71, 161, .12);
}
.lbs-download-btn i { font-size: 16px; }

/* ===== Toolbar ===== */
.lbs-toolbar {
    background: #fff;
    border-bottom: 1px solid #eef0f3;
}
.lbs-filters-text {
    font-size: 14px;
    font-weight: 500;
    color: #8a93a2;
    margin-right: 2px;
}

/* Reset Filters */
.lbs-reset-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 1px solid #e35d6a;
    border-radius: 8px;
    background: #fff;
    color: #d6293e;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.2;
    text-decoration: none;
    cursor: pointer;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.lbs-reset-btn:hover {
    background: #fdecee;
    color: #b71d2b;
    border-color: #d6293e;
}
.lbs-reset-btn i { font-size: 15px; }

/* Columns button */
.lbs-columns-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    border: 1px solid #d8dde5;
    border-radius: 8px;
    background: #fff;
    color: #475467;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.2;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease;
}
.lbs-columns-btn:hover { border-color: #b6bfca; background: #f8fafc; }
.lbs-columns-btn i { font-size: 16px; color: #667085; }

/* Search (expandable) */
.lbs-search-wrap {
    display: flex;
    align-items: center;
    justify-content: flex-end;
}
.lbs-search-field {
    width: 0;
    opacity: 0;
    padding: 0;
    border: 1px solid transparent;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    outline: none;
    transition: width .25s ease, opacity .2s ease, padding .25s ease, border-color .15s ease;
}
.lbs-search-wrap.expanded .lbs-search-field {
    width: 240px;
    opacity: 1;
    padding: 8px 12px;
    margin-right: 8px;
    border-color: #d8dde5;
}
.lbs-search-wrap.expanded .lbs-search-field:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, .12);
}
.lbs-search-btn {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: 1px solid #d8dde5;
    border-radius: 8px;
    background: #fff;
    color: #667085;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease, color .15s ease;
}
.lbs-search-btn:hover { border-color: #b6bfca; background: #f8fafc; color: #344054; }
.lbs-search-btn i { font-size: 17px; }

/* Dropdown menu */
.lbs-dropdown-menu {
    border: 1px solid #eaecf0;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(16, 24, 40, .12);
    padding: 6px;
    font-size: 14px;
    min-width: 180px;
}
.lbs-dropdown-menu .dropdown-item {
    border-radius: 7px;
    padding: 8px 10px;
    color: #344054;
    cursor: pointer;
}
.lbs-dropdown-menu .dropdown-item:hover { background: #f4f7fb; }

/* ===== Column Visibility modal ===== */
.lbs-columns-modal .modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 24px 48px rgba(16, 24, 40, .18);
    overflow: hidden;
}
.lbs-columns-modal .modal-header {
    border-bottom: 1px solid #eceff3;
    padding: 22px 28px;
}
.lbs-columns-modal .modal-title {
    font-size: 22px;
    font-weight: 700;
    color: #101828;
}
.lbs-columns-modal .modal-body { padding: 24px 28px; }
.lbs-columns-modal .modal-footer {
    border-top: 0;
    padding: 8px 28px 26px;
}
.lbs-col-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
.lbs-col-chip {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
    padding: 16px 18px;
    border: 1px solid #d5dae1;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 500;
    color: #1d2939;
    cursor: pointer;
    background: #fff;
    transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
}
.lbs-col-chip:hover { border-color: #9cc2ee; background: #f7faff; }
.lbs-col-chip .form-check-input {
    width: 20px;
    height: 20px;
    margin: 0;
    flex-shrink: 0;
    border-color: #98a2b3;
    border-radius: 5px;
    cursor: pointer;
}
.lbs-col-chip .form-check-input:checked {
    background-color: #2f6fed;
    border-color: #2f6fed;
}
.lbs-col-chip.is-checked { border-color: #9cc2ee; background: #f7faff; }
.lbs-modal-close-btn {
    border: 1px solid #2f6fed;
    color: #2f6fed;
    background: #fff;
    font-weight: 600;
    font-size: 15px;
    padding: 10px 26px;
    border-radius: 12px;
    transition: background .15s ease, color .15s ease;
}
.lbs-modal-close-btn:hover { background: #2f6fed; color: #fff; }

/* ===== Table ===== */
#lbsnaaDirectoryTable {
    border-collapse: separate;
    border-spacing: 0;
    min-width: 1080px;
}
#lbsnaaDirectoryTable thead th {
    font-size: 13px;
    font-weight: 500;
    color: #98a2b3;
    text-transform: none;
    letter-spacing: normal;
    padding: 14px 20px;
    border-bottom: 1px solid #eef0f3;
    background: #fcfcfd;
    white-space: nowrap;
    vertical-align: middle;
}
#lbsnaaDirectoryTable tbody td {
    font-size: 14px;
    color: #475467;
    padding: 16px 20px;
    border-bottom: 1px solid #f2f4f7;
    vertical-align: middle;
    white-space: nowrap;
}
#lbsnaaDirectoryTable tbody tr:last-child td { border-bottom: none; }
#lbsnaaDirectoryTable tbody tr { transition: background-color .15s ease; }
#lbsnaaDirectoryTable tbody tr:hover { background: #f9fafb; }
.lbs-col-sno { width: 70px; color: #667085; }
.lbs-address-cell {
    max-width: 240px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Name cell + avatar */
.lbs-name-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 190px;
}
.lbs-avatar-photo,
.lbs-avatar-letter {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    flex-shrink: 0;
}
.lbs-avatar-photo {
    object-fit: cover;
    border: 1px solid #eef0f3;
}
.lbs-avatar-letter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 15px;
    color: #3f51b5;
    background: #e8eaf6;
    text-transform: uppercase;
}
.lbs-emp-name {
    font-weight: 500;
    color: #101828;
    white-space: nowrap;
}

/* ===== DataTables wrapper / footer / pagination ===== */
/* Remove the empty top toolbar row (search is hidden) */
#lbsnaaDirectoryTable_wrapper > .row:first-child { display: none !important; }
/* Hide the built-in DataTables search; the toolbar search is used instead */
.dataTables_wrapper .dataTables_filter { display: none !important; }

#lbsnaaDirectoryTable_wrapper .dt-bottom-bar {
    border-top: 1px solid #eef0f3;
    margin-top: 0 !important;
    padding: 14px 20px;
}
.dataTables_wrapper .dataTables_paginate,
.dataTables_wrapper .dataTables_paginate .paginate_button,
.dataTables_wrapper .dataTables_paginate .page-item,
.dataTables_wrapper .dataTables_paginate .page-link {
    transition: none !important;
}
.dataTables_wrapper .dataTables_paginate { margin: 0 !important; padding: 0 !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 6px 12px !important;
    margin: 0 3px !important;
    border: 1px solid transparent !important;
    border-radius: 8px !important;
    font-size: 13px !important;
    color: #667085 !important;
    background: transparent !important;
    min-width: 34px;
    text-align: center;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    border: 1px solid #2f6fed !important;
    color: #2f6fed !important;
    font-weight: 600 !important;
    background: #fff !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
    background: #f2f4f7 !important;
    color: #344054 !important;
    border-color: #eaecf0 !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
    color: #c3c9d2 !important;
    background: transparent !important;
    border-color: transparent !important;
}
.dataTables_wrapper .dataTables_length { margin: 0 !important; }
.dataTables_wrapper .dataTables_length select {
    border: 1px solid #d8dde5;
    border-radius: 8px;
    padding: 5px 10px;
    font-size: 13px;
    margin: 0 6px;
    color: #344054;
}
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dt-showing-label {
    font-size: 13px;
    color: #667085;
    font-weight: 500;
}

/* ===== Responsive ===== */
@media (max-width: 991.98px) {
    .lbs-toolbar .ms-auto { width: 100%; justify-content: flex-start; margin-left: 0 !important; }
}
@media (max-width: 767.98px) {
    .lbs-directory-scroll {
        max-height: 72vh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    #lbsnaaDirectoryTable thead th { padding: 12px 14px; font-size: 12px; }
    #lbsnaaDirectoryTable tbody td { padding: 12px 14px; font-size: 12.5px; }
    .lbs-avatar-photo, .lbs-avatar-letter { width: 34px; height: 34px; font-size: 13px; }
    .lbs-search-wrap.expanded .lbs-search-field { width: 160px; }
    .lbs-col-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
}
@media (max-width: 480px) {
    .lbs-col-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    var table = $('#lbsnaaDirectoryTable').DataTable({
        responsive: false,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[1, 'asc']], // Sort by Name
        language: {
            search: '',
            searchPlaceholder: 'Search',
            info: 'of _MAX_ items',
            infoFiltered: '(filtered from _MAX_ total)',
            infoEmpty: 'of 0 items',
            lengthMenu: '_MENU_',
            paginate: { previous: '&lsaquo;', next: '&rsaquo;' }
        },
        dom: "<'row mb-3 align-items-center'<'col-md-6'><'col-md-6 dt-toolbar-search'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row mt-3 align-items-center dt-bottom-bar'<'col-md-6 dt-bottom-paginate'p><'col-md-6 dt-bottom-info d-flex align-items-center justify-content-md-end gap-2'il>>",
        columnDefs: [
            { targets: 0, searchable: false } // S. No.
        ],
        initComplete: function () {
            try {
                var api = this.api();
                var $container = $(api.table().container());
                var $info = $container.find('.dataTables_info');
                var $length = $container.find('.dataTables_length');
                if ($info.length && $length.length && !$container.find('.dt-showing-label').length) {
                    var $label = $('<span class="dt-showing-label">Showing</span>');
                    $label.insertBefore($info);
                    $length.insertBefore($info);
                }
            } catch (e) {}
        }
    });

    /* --- Expandable search (wired to client-side quick search) --- */
    var wrap = document.getElementById('lbsSearchWrap');
    var toggle = document.getElementById('lbsSearchToggle');
    var input = document.getElementById('quickSearch');
    if (toggle && wrap) {
        toggle.addEventListener('click', function () {
            wrap.classList.toggle('expanded');
            if (wrap.classList.contains('expanded') && input) { input.focus(); }
        });
    }
    $('#quickSearch').on('keyup', function () {
        table.search(this.value).draw();
    });

    /* --- Reset / clear search --- */
    $('#clearSearch').on('click', function () {
        $('#quickSearch').val('');
        table.search('').draw();
        if (wrap) { wrap.classList.remove('expanded'); }
    });

    /* --- Column Visibility modal --- */
    var grid = document.getElementById('lbsColumnsGrid');
    var modalEl = document.getElementById('lbsnaaColumnsModal');

    function buildColumnsGrid() {
        if (!grid || grid.getAttribute('data-built')) return;
        table.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().trim() || ('Column ' + (idx + 1));
            var checked = this.visible();
            var label = document.createElement('label');
            label.className = 'lbs-col-chip' + (checked ? ' is-checked' : '');
            label.innerHTML =
                '<input type="checkbox" class="form-check-input" data-col="' + idx + '" ' +
                    (checked ? 'checked' : '') + '>' +
                '<span>' + title + '</span>';
            grid.appendChild(label);
        });
        grid.addEventListener('change', function (e) {
            var cb = e.target;
            if (cb && cb.matches && cb.matches('input[data-col]')) {
                table.column(parseInt(cb.getAttribute('data-col'), 10)).visible(cb.checked);
                cb.closest('.lbs-col-chip').classList.toggle('is-checked', cb.checked);
            }
        });
        grid.setAttribute('data-built', '1');
    }

    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', buildColumnsGrid);
    }
});
</script>
@endpush

@endsection
