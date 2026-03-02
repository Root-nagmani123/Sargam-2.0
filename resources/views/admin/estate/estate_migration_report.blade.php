@extends('admin.layouts.master')

@section('title', 'Estate Migration Report (1998–2026) - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Estate Migration Report (1998–2026)"></x-breadcrum>

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h2 class="h6 fw-semibold text-body mb-0 d-flex align-items-center gap-2">
                <i class="material-symbols-rounded fs-5">filter_list</i>
                Filters
            </h2>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 g-md-4 align-items-end">
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="filter_allotment_year" class="form-label fw-medium">Allotment Year</label>
                    <select class="form-select" id="filter_allotment_year">
                        <option value="">— All Years —</option>
                        @foreach($years ?? [] as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="filter_campus_name" class="form-label fw-medium">Campus Name</label>
                    <select class="form-select" id="filter_campus_name">
                        <option value="">— All Campuses —</option>
                        @foreach($campuses ?? [] as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="filter_building_name" class="form-label fw-medium">Building Name</label>
                    <select class="form-select" id="filter_building_name">
                        <option value="">— All Buildings —</option>
                        @foreach($buildings ?? [] as $b)
                            <option value="{{ $b }}">{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="filter_type_of_building" class="form-label fw-medium">Type of Building</label>
                    <select class="form-select" id="filter_type_of_building">
                        <option value="">— All Types —</option>
                        @foreach($buildingTypes ?? [] as $bt)
                            <option value="{{ $bt }}">{{ $bt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="filter_house_no" class="form-label fw-medium">House No.</label>
                    <input type="text" class="form-control" id="filter_house_no" placeholder="House No.">
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="filter_employee_name" class="form-label fw-medium">Employee Name</label>
                    <input type="text" class="form-control" id="filter_employee_name" placeholder="Employee">
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="filter_department_name" class="form-label fw-medium">Department</label>
                    <select class="form-select" id="filter_department_name">
                        <option value="">— All Departments —</option>
                        @foreach($departments ?? [] as $d)
                            <option value="{{ $d }}">{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="filter_employee_type" class="form-label fw-medium">Employee Type</label>
                    <select class="form-select" id="filter_employee_type">
                        <option value="">— All Types —</option>
                        @foreach($employeeTypes ?? [] as $et)
                            <option value="{{ $et }}">{{ $et }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 d-flex flex-wrap gap-2">
                    <button type="button" id="btnResetFilters" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                        <i class="material-symbols-rounded" style="font-size: 1.1rem;">refresh</i>
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Estate Migration Report (1998–2026)</h1>
                    <p class="text-body-secondary small mb-0">Historical estate allotment records with filters. Browse older reports by year, campus, building, house, employee, department, and employee type.</p>
                </div>
            </div>

            <div class="table-responsive">
                {!! $dataTable->table([
                    'class' => 'table text-nowrap align-middle mb-0',
                    'aria-describedby' => 'estate-migration-report-caption'
                ]) !!}
            </div>
            <div id="estate-migration-report-caption" class="visually-hidden">Estate Migration Report list</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#estateMigrationReportTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        responsive: false,
        scrollX: true,
        autoWidth: false,
        order: [[1, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        language: {
            search: 'Search within table:',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'Showing 0 to 0 of 0 entries',
            infoFiltered: '(filtered from _MAX_ total entries)',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        },
        ajax: {
            url: "{{ route('admin.estate.reports.migration-report') }}",
            type: 'GET',
            data: function(d) {
                d.filter_allotment_year = $('#filter_allotment_year').val();
                d.filter_campus_name = $('#filter_campus_name').val();
                d.filter_building_name = $('#filter_building_name').val();
                d.filter_type_of_building = $('#filter_type_of_building').val();
                d.filter_house_no = $('#filter_house_no').val();
                d.filter_employee_name = $('#filter_employee_name').val();
                d.filter_department_name = $('#filter_department_name').val();
                d.filter_employee_type = $('#filter_employee_type').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center', width: '50px' },
            { data: 'allotment_year', name: 'allotment_year' },
            { data: 'campus_name', name: 'campus_name' },
            { data: 'building_name', name: 'building_name' },
            { data: 'type_of_building', name: 'type_of_building' },
            { data: 'house_no', name: 'house_no' },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'department_name', name: 'department_name' },
            { data: 'employee_type', name: 'employee_type' }
        ],
        dom: '<"row flex-wrap align-items-center gap-2 mb-3"<"col-12 col-sm-6 col-md-4"l><"col-12 col-sm-6 col-md-5"f>>rt<"row align-items-center mt-3"<"col-12 col-sm-6 col-md-5"i><"col-12 col-sm-6 col-md-7"p>>'
    });

    var filterOptionsUrl = "{{ route('admin.estate.reports.migration-report.filter-options') }}";

    function fillSelect($el, items, defaultText) {
        var html = '<option value="">' + defaultText + '</option>';
        if (items && items.length) {
            items.forEach(function(v) {
                html += '<option value="' + (v ? String(v).replace(/"/g, '&quot;') : '') + '">' + (v ? $('<div/>').text(v).html() : '') + '</option>';
            });
        }
        $el.html(html);
    }

    function getFilterParams() {
        return {
            year: $('#filter_allotment_year').val(),
            campus: $('#filter_campus_name').val(),
            building: $('#filter_building_name').val(),
            type: $('#filter_type_of_building').val(),
            department: $('#filter_department_name').val()
        };
    }

    function refreshCascadingFilters(resetFrom, opts, reloadTable) {
        if (resetFrom <= 1) {
            $('#filter_campus_name').val('');
            fillSelect($('#filter_campus_name'), opts.campuses || [], '— All Campuses —');
        }
        if (resetFrom <= 2) {
            $('#filter_building_name').val('');
            fillSelect($('#filter_building_name'), opts.buildings || [], '— All Buildings —');
        }
        if (resetFrom <= 3) {
            $('#filter_type_of_building').val('');
            fillSelect($('#filter_type_of_building'), opts.buildingTypes || [], '— All Types —');
        }
        if (resetFrom <= 4) {
            $('#filter_department_name').val('');
            fillSelect($('#filter_department_name'), opts.departments || [], '— All Departments —');
        }
        if (resetFrom <= 5) {
            $('#filter_employee_type').val('');
            fillSelect($('#filter_employee_type'), opts.employeeTypes || [], '— All Types —');
        }
        if (reloadTable) {
            table.ajax.reload(null, false);
        }
    }

    function fetchAndUpdateFilters(resetFrom, reloadTable) {
        $.get(filterOptionsUrl, getFilterParams(), function(opts) {
            refreshCascadingFilters(resetFrom, opts, reloadTable);
        });
    }

    $('#filter_allotment_year').on('change', function() {
        fetchAndUpdateFilters(1, true);
    });
    $('#filter_campus_name').on('change', function() {
        fetchAndUpdateFilters(2, true);
    });
    $('#filter_building_name').on('change', function() {
        fetchAndUpdateFilters(3, true);
    });
    $('#filter_type_of_building').on('change', function() {
        fetchAndUpdateFilters(4, true);
    });
    $('#filter_department_name').on('change', function() {
        fetchAndUpdateFilters(5, true);
    });
    $('#filter_employee_type').on('change', function() {
        table.ajax.reload(null, false);
    });

    $('#btnApplyFilters').on('click', function() {
        table.ajax.reload(null, false);
    });

    $('#btnResetFilters').on('click', function() {
        $('#filter_allotment_year, #filter_campus_name, #filter_building_name, #filter_type_of_building, #filter_department_name, #filter_employee_type').val('');
        $('#filter_house_no, #filter_employee_name').val('');
        $.get(filterOptionsUrl, {}, function(opts) {
            fillSelect($('#filter_allotment_year'), opts.years || [], '— All Years —');
            fillSelect($('#filter_campus_name'), opts.campuses || [], '— All Campuses —');
            fillSelect($('#filter_building_name'), opts.buildings || [], '— All Buildings —');
            fillSelect($('#filter_type_of_building'), opts.buildingTypes || [], '— All Types —');
            fillSelect($('#filter_department_name'), opts.departments || [], '— All Departments —');
            fillSelect($('#filter_employee_type'), opts.employeeTypes || [], '— All Types —');
            table.ajax.reload(null, false);
        });
    });

    $('#filter_house_no, #filter_employee_name').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            table.ajax.reload(null, false);
        }
    });
});
</script>
@endpush
