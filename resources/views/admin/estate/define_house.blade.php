@extends('admin.layouts.master')

@section('title', 'Define House - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Define House</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row flex-md-nowrap justify-content-between align-items-start align-items-md-center gap-3 mb-4 no-print">
        <h2 class="mb-0">Define House</h2>
        <div class="d-flex flex-wrap gap-2 flex-shrink-0">
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addEstateHouseModal">
                <i class="bi bi-plus-circle"></i>
                <span>Add Define House</span>
            </button>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Show / hide columns">
                    <i class="bi bi-columns-gap"></i>
                    <span class="d-none d-md-inline ms-1">Show / hide columns</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" id="defineHouseColumnToggleMenu"></ul>
            </div>
            <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2" id="btnDefineHousePrint" title="Print">
                <i class="material-icons material-symbols-rounded">print</i>
                <span class="d-none d-md-inline">Print</span>
            </button>
        </div>
    </div>
    <div id="defineHouseAlerts"></div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Estate House List</h5>
        </div>
        <div class="card-body">
            <div class="define-house-table-wrapper table-responsive">
                <table class="table table-bordered table-hover" id="defineHouseTable">
                    <thead class="table-primary">
                        <tr>
                            <th>S.No.</th>
                            <th>Estate Name</th>
                            <th>Unit Type</th>
                            <th>Building Name</th>
                            <th>Unit Sub Type</th>
                            <th>House No.</th>
                            <th>Meter No. 1</th>
                            <th>Water Charge</th>
                            <th>Electric Charge</th>
                            <th>Licence Fee</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Define House Modal -->
<div class="modal fade" id="addEstateHouseModal" tabindex="-1" aria-labelledby="addEstateHouseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEstateHouseModalLabel">Add Define House</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Please Add Define House</p>
                <div id="defineHouseModalAlerts"></div>
                <form id="addEstateHouseForm">
                    @csrf
                    <input type="hidden" id="edit_house_pk" name="edit_house_pk" value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="estate_campus_master_pk" class="form-label">Estate Name <span class="text-danger">*</span></label>
                            <select class="form-select" id="estate_campus_master_pk" name="estate_campus_master_pk" required>
                                <option value="">--Select--</option>
                                @foreach($campuses ?? [] as $c)
                                    <option value="{{ $c->pk }}">{{ $c->campus_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select Estate Name</small>
                        </div>
                        <div class="col-md-6">
                            <label for="estate_unit_type_master_pk" class="form-label">Unit Name <span class="text-danger">*</span></label>
                            <select class="form-select" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk" required>
                                <option value="">--Select--</option>
                                @foreach($unitTypes ?? [] as $ut)
                                    <option value="{{ $ut->pk }}">{{ $ut->unit_type }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select Unit</small>
                        </div>
                        <div class="col-md-6">
                            <label for="estate_block_master_pk" class="form-label">Building <span class="text-danger">*</span></label>
                            <select class="form-select" id="estate_block_master_pk" name="estate_block_master_pk" required>
                                <option value="">--Select--</option>
                            </select>
                            <small class="text-muted">Select Building</small>
                        </div>
                        <div class="col-md-6">
                            <label for="estate_unit_sub_type_master_pk" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="estate_unit_sub_type_master_pk" name="estate_unit_sub_type_master_pk" required>
                                <option value="">--Select--</option>
                                @foreach($unitSubTypes ?? [] as $ust)
                                    <option value="{{ $ust->pk }}">{{ $ust->unit_sub_type }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select Unit Sub Type</small>
                        </div>
                        <div class="col-md-6">
                            <label for="water_charge" class="form-label">Water Charges <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="water_charge" name="water_charge" value="0.00" step="0.01" min="0" required>
                            <small class="text-muted">Water Charges</small>
                        </div>
                        <div class="col-md-6">
                            <label for="electric_charge" class="form-label">Fixed Electricity Charges <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="electric_charge" name="electric_charge" value="0.00" step="0.01" min="0" required>
                            <small class="text-muted">Fix Electricity Charges</small>
                        </div>
                    </div>

                    <!-- Repeatable section: House No. to Under Renovation -->
                    <div class="border rounded p-3 mt-3 mb-3 bg-light">
                        <div id="houseRowsContainer">
                            <div class="house-row row g-3 mb-3 align-items-end" data-row="0">
                                <div class="col-md-6">
                                    <label class="form-label">House No. <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control house_no" name="house_no[]" placeholder="Enter House No." required>
                                    <small class="text-muted">Enter House No.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Meter No. 1 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control meter_one" name="meter_one[]" placeholder="Enter Meter No. 1" inputmode="numeric" pattern="[0-9]*" autocomplete="off">
                                    <small class="text-muted">Enter Meter No. 1</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Meter No. 2</label>
                                    <input type="text" class="form-control meter_two" name="meter_two[]" placeholder="Enter Meter No. 2" inputmode="numeric" pattern="[0-9]*" autocomplete="off">
                                    <small class="text-muted">Enter Meter No. 2</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Licence Fee</label>
                                    <input type="number" class="form-control licence_fee" name="licence_fee[]" value="0.00" step="0.01" min="0" placeholder="Enter Licence Fee">
                                    <small class="text-muted">Enter Licence Fee</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <input type="hidden" class="vacant_renovation_status" name="vacant_renovation_status[]" value="1">
                                    <div class="d-flex gap-3 pt-2 flex-wrap">
                                        <div class="form-check">
                                            <input class="form-check-input status-radio" type="radio" name="vacant_renovation_radio_0" value="1" checked>
                                            <label class="form-check-label">Vacant</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input status-radio" type="radio" name="vacant_renovation_radio_0" value="2">
                                            <label class="form-check-label">Occupied</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input status-radio" type="radio" name="vacant_renovation_radio_0" value="0">
                                            <label class="form-check-label">Under Renovation</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-success btn-sm" id="addHouseRowBtn" title="Add House"><i class="bi bi-plus-lg"></i></button>
                            <button type="button" class="btn btn-danger btn-sm" id="removeHouseRowBtn" title="Remove last house row"><i class="bi bi-dash-lg"></i></button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Remarks (optional)" maxlength="200"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveEstateHouseBtn">
                    <i class="bi bi-save me-1"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>.ts-dropdown { z-index: 1060 !important; }</style>
<style>
@media print {
    @page { size: A4 landscape; margin: 8mm; }
    .no-print { display: none !important; }
    #defineHouseTable_wrapper .dataTables_length,
    #defineHouseTable_wrapper .dataTables_filter,
    #defineHouseTable_wrapper .dataTables_paginate { display: none !important; }
    .define-house-table-wrapper,
    #defineHouseTable_wrapper .dataTables_scroll,
    #defineHouseTable_wrapper .dataTables_scrollBody,
    #defineHouseTable_wrapper .dataTables_scrollHead { overflow: visible !important; }
    #defineHouseTable_wrapper .dataTables_scrollBody { height: auto !important; max-height: none !important; }
    #defineHouseTable_wrapper .dataTables_scrollHead { display: none !important; }
    #defineHouseTable_wrapper table, #defineHouseTable_wrapper table.dataTable { width: 100% !important; }
    body { zoom: 0.78; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    #defineHouseTable_wrapper th, #defineHouseTable_wrapper td { white-space: normal !important; word-break: break-word; font-size: 11px; padding: 0.35rem 0.4rem !important; }
    #defineHouseTable_wrapper thead { display: table-header-group; }
}
.define-house-table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
$(document).ready(function() {
    const blocksUrl = "{{ route('admin.estate.define-house.blocks') }}";
    const storeUrl = "{{ route('admin.estate.define-house.store') }}";
    const dataUrl = "{{ route('admin.estate.define-house.data') }}";
    const editUrlBase = "{{ route('admin.estate.define-house.show', ['id' => '__ID__']) }}".replace('__ID__', '');
    const updateUrlBase = "{{ route('admin.estate.define-house.update', ['id' => '__ID__']) }}".replace('__ID__', '');
    const deleteUrlBase = "{{ route('admin.estate.define-house.destroy', ['id' => '__ID__']) }}".replace('__ID__', '');

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function showPageAlert(type, message) {
        var icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        var html = '<div class="alert alert-' + type + ' alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">' +
            '<i class="bi ' + icon + ' me-2 flex-shrink-0" aria-hidden="true"></i>' +
            '<span class="flex-grow-1">' + escapeHtml(message) + '</span>' +
            '<button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>' +
        '</div>';
        $('#defineHouseAlerts').html(html);
        $('html, body').animate({ scrollTop: 0 }, 150);
    }

    function showModalAlert(type, message) {
        var icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        var html = '<div class="alert alert-' + type + ' alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">' +
            '<i class="bi ' + icon + ' me-2 flex-shrink-0" aria-hidden="true"></i>' +
            '<span class="flex-grow-1">' + escapeHtml(message) + '</span>' +
            '<button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>' +
        '</div>';
        $('#defineHouseModalAlerts').html(html);
        // keep focus inside modal
        var $modalBody = $('#addEstateHouseModal .modal-body');
        if ($modalBody.length) {
            $modalBody.animate({ scrollTop: 0 }, 150);
        }
    }

    // Meter inputs: allow digits only (type/paste/drag-drop)
    $(document).on('input', '.meter_one, .meter_two', function() {
        var val = String($(this).val() || '');
        var cleaned = val.replace(/\D/g, '');
        if (val !== cleaned) {
            $(this).val(cleaned);
        }
    });

    var estateTsOpts = { allowEmptyOption: true, create: false, dropdownParent: 'body', maxOptions: null, hideSelected: false, placeholder: '--Select--', onInitialize: function() { this.activeOption = null; } };
    function initDefineHouseTs(el, placeholder) {
        if (!el || typeof TomSelect === 'undefined') return null;
        if (el.tomselect) { try { el.tomselect.destroy(); } catch (e) {} }
        return new TomSelect(el, $.extend(true, {}, estateTsOpts, { placeholder: placeholder || '--Select--' }));
    }
    function getSelVal(el) { return (el && el.tomselect) ? el.tomselect.getValue() : $(el).val(); }

    var tsCampus = null, tsUnitType = null, tsBlock = null, tsUnitSub = null;
    var elCampus = document.getElementById('estate_campus_master_pk');
    var elUnitType = document.getElementById('estate_unit_type_master_pk');
    var elBlock = document.getElementById('estate_block_master_pk');
    var elUnitSub = document.getElementById('estate_unit_sub_type_master_pk');
    if (elCampus) tsCampus = initDefineHouseTs(elCampus, '--Select--');
    if (elUnitType) tsUnitType = initDefineHouseTs(elUnitType, '--Select--');
    if (elBlock) tsBlock = initDefineHouseTs(elBlock, '--Select--');
    if (elUnitSub) tsUnitSub = initDefineHouseTs(elUnitSub, '--Select--');

    // Load buildings when estate (campus) changes
    $(document).on('change', '#estate_campus_master_pk', function() {
        var campusId = getSelVal(this);
        var buildingEl = document.getElementById('estate_block_master_pk');
        if (tsBlock) { try { tsBlock.destroy(); } catch (e) {} tsBlock = null; }
        $(buildingEl).html('<option value="">--Select--</option>');
        if (!campusId) {
            if (buildingEl) tsBlock = initDefineHouseTs(buildingEl, '--Select--');
            return;
        }
        $.get(blocksUrl, { campus_id: campusId }, function(res) {
            if (res.status && res.data) {
                var $b = $(buildingEl);
                $b.find('option:not(:first)').remove();
                $.each(res.data, function(i, b) {
                    $b.append('<option value="'+b.pk+'">'+b.block_name+'</option>');
                });
            }
            if (buildingEl) tsBlock = initDefineHouseTs(buildingEl, '--Select--');
        });
    });

    // DataTable server-side
    var table = $('#defineHouseTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: dataUrl,
            type: 'GET'
        },
        columns: [
            { data: null, orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'estate_name', name: 'estate_name' },
            { data: 'unit_type', name: 'unit_type' },
            { data: 'building_name', name: 'building_name' },
            { data: 'unit_sub_type', name: 'unit_sub_type' },
            { data: 'house_no', name: 'house_no' },
            { data: 'meter_one', name: 'meter_one' },
            { data: 'water_charge', name: 'water_charge', render: function(v) { return v != null ? parseFloat(v).toFixed(2) : '0.00'; } },
            { data: 'electric_charge', name: 'electric_charge', render: function(v) { return v != null ? parseFloat(v).toFixed(2) : '0.00'; } },
            { data: 'licence_fee', name: 'licence_fee', render: function(v) { return v != null ? parseFloat(v).toFixed(2) : '0.00'; } },
            {
                data: null,
                name: 'status',
                render: function(data, type, row) {
                    var used = parseInt(row.used_home_status != null ? row.used_home_status : 0, 10);
                    var vr = parseInt(row.vacant_renovation_status != null ? row.vacant_renovation_status : 1, 10);
                    // Under Renovation: vacant_renovation_status = 0
                    if (vr === 0) {
                        return 'Under Renovation';
                    }
                    // Otherwise, status comes from used_home_status: 1 = Occupied, 0 = Vacant
                    return used === 1 ? 'Occupied' : 'Vacant';
                }
            },
            { data: 'pk', orderable: false, searchable: false, render: function(pk) {
                var deleteUrl = (deleteUrlBase.slice(-1) === '/' ? deleteUrlBase : deleteUrlBase + '/') + pk;
                return '<div class="d-flex flex-nowrap gap-1 justify-content-center">' +
                       '<button type="button" class="btn btn-sm btn-warning btn-edit-house" title="Edit" data-pk="'+pk+'"><i class="bi bi-pencil"></i></button>' +
                       '<button type="button" class="btn btn-sm btn-danger btn-delete-house" title="Delete" data-url="'+deleteUrl+'"><i class="bi bi-trash"></i></button>' +
                       '</div>';
            }}
        ],
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: { first: "First", last: "Last", next: "Next", previous: "Previous" }
        },
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Show / hide columns
    function buildDefineHouseColumnToggle() {
        var menu = $('#defineHouseColumnToggleMenu');
        menu.empty();
        table.columns().every(function(i) {
            var col = this;
            var header = $(col.header()).text().trim();
            if (!header || header === 'Actions') return;
            var $li = $('<li><label class="dropdown-item d-flex align-items-center gap-2 cursor-pointer mb-0"><input type="checkbox" class="form-check-input column-toggle-define-house" data-column="' + i + '"> ' + header + '</label></li>');
            $li.find('input').prop('checked', col.visible());
            menu.append($li);
        });
    }
    $(document).on('change', '.column-toggle-define-house', function() {
        var colIdx = $(this).data('column');
        table.column(colIdx).visible($(this).prop('checked'));
    });
    table.on('draw', function() { buildDefineHouseColumnToggle(); });
    buildDefineHouseColumnToggle();

    // Print: build HTML from visible columns (excluding Actions) and open print window
    function buildDefineHousePrintableHtml() {
        var visibleIndexes = [];
        table.columns().every(function(i) {
            var header = ($(this.header()).text() || '').trim();
            if (!header || header === 'Actions') return;
            if (this.visible()) visibleIndexes.push(i);
        });
        var html = '<table class="table table-bordered table-striped">';
        html += '<thead><tr>';
        visibleIndexes.forEach(function(colIdx) {
            var h = ($(table.column(colIdx).header()).text() || '').trim();
            html += '<th>' + h + '</th>';
        });
        html += '</tr></thead><tbody>';
        table.rows({ search: 'applied' }).nodes().each(function(rowNode) {
            var $row = $(rowNode);
            if ($row.hasClass('child')) return;
            html += '<tr>';
            visibleIndexes.forEach(function(colIdx) {
                var cellNode = table.cell(rowNode, colIdx).node();
                var cellHtml = '';
                if (cellNode) {
                    var $cell = $(cellNode).clone();
                    $cell.find('input, button, select, textarea').remove();
                    $cell.find('a.btn, .btn, .form-check-input').remove();
                    cellHtml = ($cell.html() || '').trim();
                }
                html += '<td>' + cellHtml + '</td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table>';
        return html;
    }
    function openDefineHousePrintWindow(tableHtml) {
        var title = 'Define House';
        var win = window.open('', '_blank');
        if (!win) { window.print(); return; }
        win.document.open();
        win.document.write(
            '<!doctype html><html><head><meta charset="utf-8">' +
            '<title>' + title + '</title>' +
            '<style>@page{size:A4 landscape;margin:8mm;}body{font-family:Arial,sans-serif;font-size:11px;}h2{margin:0 0 8px 0;font-size:14px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #333;padding:4px 6px;}thead{display:table-header-group;}tr{page-break-inside:avoid;}</style></head><body><h2>' + title + '</h2>' + tableHtml + '</body></html>'
        );
        win.document.close();
        setTimeout(function() { win.focus(); win.print(); win.close(); }, 250);
    }
    $('#btnDefineHousePrint').on('click', function() {
        if (!$.fn.DataTable.isDataTable('#defineHouseTable')) { window.print(); return; }
        var originalLen = table.page.len();
        var originalPage = table.page();
        var restored = false;
        var safeRestore = function() {
            if (restored) return;
            restored = true;
            table.page.len(originalLen);
            table.page(originalPage);
            table.draw(false);
        };
        table.one('draw', function() {
            setTimeout(function() {
                var tableHtml = buildDefineHousePrintableHtml();
                openDefineHousePrintWindow(tableHtml);
                setTimeout(safeRestore, 800);
            }, 250);
        });
        table.page.len(-1).draw();
    });

    // Save Estate House (AJAX) - add multiple or edit single
    $('#saveEstateHouseBtn').on('click', function() {
        syncStatusHidden();
        reindexHouseRowNames();
        $('#defineHouseModalAlerts').html('');
        var $form = $('#addEstateHouseForm');
        var editPk = $('#edit_house_pk').val();
        var $rows = $('#houseRowsContainer .house-row');
        var valid = true;
        var meterValid = true;
        $rows.each(function() {
            if (!$(this).find('.house_no').val().trim()) valid = false;
            var meterVal = $(this).find('.meter_one').val().trim();
            if (!meterVal) {
                meterValid = false;
            } else if (!/^[0-9]+$/.test(meterVal)) {
                meterValid = false;
            }
        });
        if (!valid || !meterValid || !$form[0].checkValidity()) {
            $form[0].reportValidity();
            if (!valid) showModalAlert('danger', 'Please enter House No. for all rows.');
            else if (!meterValid) showModalAlert('danger', 'Please enter numeric Meter No. 1 for all rows.');
            return;
        }
        var btn = $(this);
        btn.prop('disabled', true);
        var url = editPk ? (updateUrlBase.slice(-1) === '/' ? updateUrlBase : updateUrlBase + '/') + editPk : storeUrl;
        var method = editPk ? 'PUT' : 'POST';
        var data = $form.serialize();
        if (editPk) data += '&_method=PUT';
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).done(function(res) {
            if (res.success) {
                $('#addEstateHouseModal').modal('hide');
                $('#edit_house_pk').val('');
                $form[0].reset();
                $('#water_charge, #electric_charge').val('0.00');
                $('#houseRowsContainer .house-row:gt(0)').remove();
                $('#houseRowsContainer .house-row').first().find('.licence_fee').val('0.00');
                $('#houseRowsContainer .house-row').first().find('.vacant_renovation_status').val('1');
                $('#houseRowsContainer .house-row').first().find('.status-radio[value="1"]').prop('checked', true);
                $('#houseRowsContainer .house-row').first().find('.status-radio[value="0"], .status-radio[value="2"]').prop('checked', false);
                $('#addHouseRowBtn, #removeHouseRowBtn').show();
                table.ajax.reload(null, false);
                showPageAlert('success', res.message || (editPk ? 'Estate house updated.' : 'Estate house(s) added successfully.'));
            }
        }).fail(function(xhr) {
            var msg = 'Failed to save.';
            if (xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseJSON.errors) {
                    // pick first validation error
                    try {
                        var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                        if (firstKey && xhr.responseJSON.errors[firstKey] && xhr.responseJSON.errors[firstKey][0]) {
                            msg = xhr.responseJSON.errors[firstKey][0];
                        } else {
                            msg = JSON.stringify(xhr.responseJSON.errors);
                        }
                    } catch (e) {
                        msg = 'Validation failed.';
                    }
                }
            }
            // Show inside modal so user sees it in context
            showModalAlert('danger', msg);
        }).always(function() {
            btn.prop('disabled', false);
        });
    });

    // Reset form when modal is closed
    $('#addEstateHouseModal').on('hidden.bs.modal', function() {
        $('#defineHouseModalAlerts').html('');
        $('#edit_house_pk').val('');
        $('#addEstateHouseModalLabel').text('Add Define House');
        $('#addHouseRowBtn, #removeHouseRowBtn').show();
        $('#addEstateHouseForm')[0].reset();
        $('#water_charge, #electric_charge').val('0.00');
        if (tsCampus) tsCampus.setValue('', true);
        if (tsUnitType) tsUnitType.setValue('', true);
        if (tsUnitSub) tsUnitSub.setValue('', true);
        if (tsBlock) { try { tsBlock.destroy(); } catch (e) {} tsBlock = null; }
        $('#estate_block_master_pk').html('<option value="">--Select--</option>');
        if (elBlock) tsBlock = initDefineHouseTs(elBlock, '--Select--');
        $('#houseRowsContainer .house-row:gt(0)').remove();
        $('#houseRowsContainer .house-row').first().find('.licence_fee').val('0.00');
        $('#houseRowsContainer .house-row').first().find('.vacant_renovation_status').val('1');
        $('#houseRowsContainer .house-row').first().find('.status-radio').attr('name', 'vacant_renovation_radio_0');
        $('#houseRowsContainer .house-row').first().find('.status-radio[value="1"]').prop('checked', true);
        $('#houseRowsContainer .house-row').first().find('.status-radio[value="0"], .status-radio[value="2"]').prop('checked', false);
        updateGlobalRemoveButton();
    });

    // Add House row (House No. to Under Renovation section)
    var houseRowIndex = 1;
    $('#addHouseRowBtn').on('click', function() {
        var $container = $('#houseRowsContainer');
        var $first = $container.find('.house-row').first();
        var $clone = $first.clone();
        var radioName = 'vacant_renovation_radio_' + houseRowIndex;
        $clone.removeAttr('data-row').attr('data-row', houseRowIndex);
        $clone.find('input').val('');
        $clone.find('.house_no').attr('placeholder', 'Enter House No.');
        $clone.find('.meter_one').attr('placeholder', 'Enter Meter No. 1');
        $clone.find('.meter_two').attr('placeholder', 'Enter Meter No. 2');
        $clone.find('.licence_fee').val('0.00');
        $clone.find('.vacant_renovation_status').val('1');
        $clone.find('.status-radio').attr('name', radioName);
        $clone.find('.status-radio[value="1"]').prop('checked', true);
        $clone.find('.status-radio[value="0"], .status-radio[value="2"]').prop('checked', false);
        $container.append($clone);
        houseRowIndex++;
        updateGlobalRemoveButton();
    });

    // Remove last House row (only global - button, no per-row minus)
    $('#removeHouseRowBtn').on('click', function() {
        var $rows = $('#houseRowsContainer .house-row');
        if ($rows.length > 1) {
            $rows.last().remove();
            updateGlobalRemoveButton();
        }
    });

    // Status radio: update hidden in same row; use unique name per row so only one selected per row
    $(document).on('change', '.status-radio', function() {
        $(this).closest('.house-row').find('.vacant_renovation_status').val($(this).val());
    });

    function updateGlobalRemoveButton() {
        var $rows = $('#houseRowsContainer .house-row');
        $('#removeHouseRowBtn').prop('disabled', $rows.length <= 1);
    }

    updateGlobalRemoveButton();

    // Before save: sync each row's hidden from checked radio; ensure every row has 0 or 1
    function syncStatusHidden() {
        $('#houseRowsContainer .house-row').each(function() {
            var $row = $(this);
            var checked = $row.find('.status-radio:checked').val();
            var $hidden = $row.find('.vacant_renovation_status');
            if (checked !== undefined && checked !== '') {
                $hidden.val(checked);
            } else {
                $hidden.val('1');
            }
        });
    }

    // Before submit: set explicit array indices [0], [1], ... so backend receives all rows (fixes vacant_renovation_status.1 required)
    function reindexHouseRowNames() {
        $('#houseRowsContainer .house-row').each(function(idx) {
            var $row = $(this);
            $row.find('.house_no').attr('name', 'house_no[' + idx + ']');
            $row.find('.meter_one').attr('name', 'meter_one[' + idx + ']');
            $row.find('.meter_two').attr('name', 'meter_two[' + idx + ']');
            $row.find('.licence_fee').attr('name', 'licence_fee[' + idx + ']');
            $row.find('.vacant_renovation_status').attr('name', 'vacant_renovation_status[' + idx + ']');
        });
    }

    // Edit Estate House
    $(document).on('click', '.btn-edit-house', function() {
        var pk = $(this).data('pk');
        var url = (editUrlBase.slice(-1) === '/' ? editUrlBase : editUrlBase + '/') + pk;
        $.get(url, function(res) {
            if (!res || !res.pk) { showPageAlert('danger', 'Could not load house.'); return; }
            $('#edit_house_pk').val(res.pk);
            $('#addEstateHouseModalLabel').text('Edit Estate House');
            if (tsCampus) tsCampus.setValue(String(res.estate_campus_master_pk || ''), true);
            else $('#estate_campus_master_pk').val(res.estate_campus_master_pk);
            if (tsUnitType) tsUnitType.setValue(String(res.estate_unit_master_pk || ''), true);
            else $('#estate_unit_type_master_pk').val(res.estate_unit_master_pk);
            if (tsBlock) { try { tsBlock.destroy(); } catch (e) {} tsBlock = null; }
            $('#estate_block_master_pk').html('<option value="">--Select--</option><option value="'+res.estate_block_master_pk+'" selected>'+escapeHtml(res.building_name || '')+'</option>');
            if (elBlock) tsBlock = initDefineHouseTs(elBlock, '--Select--');
            if (tsBlock) tsBlock.setValue(String(res.estate_block_master_pk || ''), true);
            if (tsUnitSub) tsUnitSub.setValue(String(res.estate_unit_sub_type_master_pk || ''), true);
            else $('#estate_unit_sub_type_master_pk').val(res.estate_unit_sub_type_master_pk);
            $('#water_charge').val(res.water_charge);
            $('#electric_charge').val(res.electric_charge);
            $('#remarks').val(res.remarks || '');
            $('#houseRowsContainer .house-row:gt(0)').remove();
            var $row = $('#houseRowsContainer .house-row').first();
            $row.find('.house_no').val(res.house_no);
            $row.find('.meter_one').val(res.meter_one || '');
            $row.find('.meter_two').val(res.meter_two || '');
            $row.find('.licence_fee').val(res.licence_fee);
            $row.find('.vacant_renovation_status').val(res.vacant_renovation_status);
            $row.find('.status-radio').attr('name', 'vacant_renovation_radio_0');
            $row.find('.status-radio[value="1"]').prop('checked', res.vacant_renovation_status == 1);
            $row.find('.status-radio[value="2"]').prop('checked', res.vacant_renovation_status == 2);
            $row.find('.status-radio[value="0"]').prop('checked', res.vacant_renovation_status == 0);
            $('#addHouseRowBtn').hide();
            $('#removeHouseRowBtn').hide();
            $('#addEstateHouseModal').modal('show');
        }).fail(function() { showPageAlert('danger', 'Could not load house.'); });
    });

    // Delete disabled for Define House (intentionally removed)

    // When opening Add modal (not edit), reset title and show + -
    $('#addEstateHouseModal').on('show.bs.modal', function(e) {
        if ($('#edit_house_pk').val() === '') {
            $('#addEstateHouseModalLabel').text('Add Define House');
            $('#addHouseRowBtn').show();
            $('#removeHouseRowBtn').show();
        }
    });

    // Delete Estate House - simple confirm + alert
    $(document).on('click', '.btn-delete-house', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        if (!url) return;
        if (!confirm('Are you sure you want to delete this house? This action cannot be undone.')) {
            return;
        }
        $.ajax({
            url: url,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}',
            },
            success: function(res) {
                if (res && res.success) {
                    table.ajax.reload(null, false);
                    showPageAlert('success', res.message || 'Estate house deleted successfully.');
                } else {
                    var msg = (res && res.message) ? res.message : 'Failed to delete.';
                    showPageAlert('danger', msg);
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete.';
                showPageAlert('danger', msg);
            }
        });
    });
});
</script>
@endpush
