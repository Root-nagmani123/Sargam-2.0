@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@section('css')
    @parent
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endsection

@section('setup_content')
<style>

/* Choices.js Bootstrap-like styling */
.datatables .choices__inner {
    min-height: calc(2.25rem + 2px);
    border-radius: 0.375rem;
    border: 1px solid #ced4da;
    padding: 0.375rem 0.75rem;
    background-color: #fff;
}

.datatables .choices__list--single .choices__item {
    padding: 0;
    margin: 0;
}

.datatables .choices__list--dropdown {
    border-radius: 0.375rem;
    border-color: #ced4da;
}

.datatables .choices.is-focused .choices__inner,
.datatables .choices.is-open .choices__inner {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
<div class="container-fluid">
    <x-breadcrum title="MDO/Escort Exemption" />
    <x-session_message />
    <div class="datatables">
        <div class="card shadow-sm border-0 rounded-3 overflow-hidden" style="border-left: 4px solid #004a93;">
            <div class="card-header border-0 bg-body-tertiary pb-2 pb-md-3">
                <div class="row align-items-center g-3 mdo-header-row">
                    <div class="col-12 col-lg-4">
                        <h4 class="mb-0 fw-bold text-dark">MDO/Escort Exemption</h4>
                    </div>
                    <div class="col-12 col-lg-4 text-lg-center text-start">
                        <div class="btn-group shadow-sm rounded-pill overflow-hidden" role="group"
                            aria-label="Course Status Filter">
                                @php
                                    $activeParams = ['filter' => 'active'];
                                    $archiveParams = ['filter' => 'archive'];
                                    // Preserve other filters if they exist in request
                                    if (request('course_filter')) {
                                        $activeParams['course_filter'] = request('course_filter');
                                        $archiveParams['course_filter'] = request('course_filter');
                                    }
                                    if (request('year_filter')) {
                                        $activeParams['year_filter'] = request('year_filter');
                                        $archiveParams['year_filter'] = request('year_filter');
                                    }
                                    if (request('duty_type_filter')) {
                                        $activeParams['duty_type_filter'] = request('duty_type_filter');
                                        $archiveParams['duty_type_filter'] = request('duty_type_filter');
                                    }
                                    if (request('time_from_filter')) {
                                        $activeParams['time_from_filter'] = request('time_from_filter');
                                        $archiveParams['time_from_filter'] = request('time_from_filter');
                                    }
                                    if (request('time_to_filter')) {
                                        $activeParams['time_to_filter'] = request('time_to_filter');
                                        $archiveParams['time_to_filter'] = request('time_to_filter');
                                    }
                                    if (request('from_date_filter')) {
                                        $activeParams['from_date_filter'] = request('from_date_filter');
                                        $archiveParams['from_date_filter'] = request('from_date_filter');
                                    }
                                    if (request('to_date_filter')) {
                                        $activeParams['to_date_filter'] = request('to_date_filter');
                                        $archiveParams['to_date_filter'] = request('to_date_filter');
                                    }
                                @endphp
                                <a href="{{ route('mdo-escrot-exemption.index', $activeParams) }}"
                                    class="btn {{ ($filter ?? 'active') === 'active' ? 'btn-success active' : 'btn-outline-secondary' }} px-3 px-md-4 fw-semibold"
                                    id="filterActive" aria-pressed="{{ ($filter ?? 'active') === 'active' ? 'true' : 'false' }}">
                                    <i class="bi bi-check-circle me-1"></i> Active
                                </a>
                                <a href="{{ route('mdo-escrot-exemption.index', $archiveParams) }}"
                                    class="btn {{ ($filter ?? 'active') === 'archive' ? 'btn-success active' : 'btn-outline-secondary' }} px-3 px-md-4 fw-semibold"
                                    id="filterArchive" aria-pressed="{{ ($filter ?? 'active') === 'archive' ? 'true' : 'false' }}">
                                    <i class="bi bi-archive me-1"></i> Archive
                                </a>
                            </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div
                            class="d-flex justify-content-lg-end justify-content-start align-items-end gap-2 mdo-action-buttons flex-wrap">
                            <!-- Print / Download Button -->
                            <button type="button" id="printDownloadBtn"
                                class="btn btn-outline-info d-flex align-items-center gap-1 px-3 py-2 rounded shadow-sm">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 20px; vertical-align: middle;">print</i>
                                <span class="d-none d-sm-inline">Print / Download</span>
                                <span class="d-sm-none">Print</span>
                            </button>
                            <!-- Add New Button -->
                            <a href="{{ route('mdo-escrot-exemption.create') }}"
                                class="btn btn-primary d-flex align-items-center gap-1 px-3 py-2 rounded shadow-sm">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 20px; vertical-align: middle;">add</i>
                                <span class="d-none d-md-inline">Add New MDO/Escort Exemption</span>
                                <span class="d-md-none">Add New</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-3 pt-md-4">
                                    <!-- Filters Section -->
                                    <div class="row mb-3 align-items-end g-2">
                        <!-- Course Filter -->
                        <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-3">
                            <label for="course_filter" class="form-label fw-semibold">Course:</label>
                            <select id="course_filter" class="form-select">
                                <option value="">-- All Courses --</option>
                                @foreach ($courseMaster as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Year Filter -->
                        <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-3">
                            <label for="year_filter" class="form-label fw-semibold">Year:</label>
                            <select id="year_filter" class="form-select">
                                <option value="">-- All Years --</option>
                                @foreach ($years as $year => $yearValue)
                                <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Duty Type Filter -->
                        <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-3">
                            <label for="duty_type_filter" class="form-label fw-semibold">Duty type:</label>
                            <select id="duty_type_filter" class="form-select">
                                <option value="">-- All Duty Types --</option>
                                @foreach ($dutyTypes as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- From Date Filter -->
                        <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-3">
                            <label for="from_date_filter" class="form-label fw-semibold">From Date:</label>
                            <input type="date" id="from_date_filter" class="form-control" value="{{ request('from_date_filter') }}">
                        </div>
                        
                        <!-- To Date Filter -->
                        <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-3">
                            <label for="to_date_filter" class="form-label fw-semibold">To Date:</label>
                            <input type="date" id="to_date_filter" class="form-control" value="{{ request('to_date_filter') }}">
                        </div>
                        
                        <!-- Time From Filter -->
                        <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-3">
                            <label for="time_from_filter" class="form-label fw-semibold">Time From:</label>
                            <input type="time" id="time_from_filter" class="form-control">
                        </div>
                        
                        <!-- Time To Filter -->
                        <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-3">
                            <label for="time_to_filter" class="form-label fw-semibold">Time To:</label>
                            <input type="time" id="time_to_filter" class="form-control">
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3 mb-2 mb-lg-3 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary w-100 w-sm-auto" id="resetFilters">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                            </button>
                        </div>
                    </div>
                    
                    <!-- Total Records Row -->
                    <div class="row mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold d-block">&nbsp;</label>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary fs-6 px-3 py-2 d-inline-flex align-items-center">
                                    <i class="bi bi-list-check me-2"></i> Total Records: <strong class="ms-1" id="total-records-count">0</strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 text-md-end">
                            <label class="form-label fw-semibold d-block">&nbsp;</label>
                           
                        </div>
                    </div>
                    
                    <!-- Hidden input for filter status -->
                    <input type="hidden" id="filter_status" value="{{ $filter ?? 'active' }}">
                <div class="table-responsive">

                    {!! $dataTable->table(['class' => 'table text-nowrap']) !!}

                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Choices.js on filter selects
    if (typeof Choices !== 'undefined') {
        document.querySelectorAll('.datatables select').forEach(function (el) {
            if (el.dataset.choicesInitialized === 'true') return;

            new Choices(el, {
                allowHTML: false,
                searchPlaceholderValue: 'Search...',
                removeItemButton: !!el.multiple,
                shouldSort: false,
                placeholder: true,
                placeholderValue: el.getAttribute('placeholder') || el.options[0]?.text || 'Select an option',
            });

            el.dataset.choicesInitialized = 'true';
        });
    }

    var table = $('#mdoescot-table').DataTable();
    
    // Update total records count on initial load and after each draw
    table.on('draw.dt', function() {
        var info = table.page.info();
        $('#total-records-count').text(info.recordsFiltered || info.recordsTotal || 0);
    });

    // Reload DataTable on filter change
    $('#course_filter, #year_filter, #duty_type_filter, #from_date_filter, #to_date_filter').on('change', function() {
        table.ajax.reload();
    });

    // Reload DataTable on time filter change
    $('#time_from_filter, #time_to_filter').on('change', function() {
        table.ajax.reload();
    });

    // Reset Filters functionality
    $('#resetFilters').on('click', function() {
        // Redirect to the base route with active filter to clear all URL parameters and reset filters
        window.location.href = '{{ route("mdo-escrot-exemption.index", ["filter" => "active"]) }}';
    });

    // Pass all filters to server
    $('#mdoescot-table').on('preXhr.dt', function(e, settings, data) {
        data.filter = $('#filter_status').val() || 'active';
        data.course_filter = $('#course_filter').val();
        data.year_filter = $('#year_filter').val();
        data.duty_type_filter = $('#duty_type_filter').val();
        data.time_from_filter = $('#time_from_filter').val();
        data.time_to_filter = $('#time_to_filter').val();
        data.from_date_filter = $('#from_date_filter').val();
        data.to_date_filter = $('#to_date_filter').val();
    });

    // Print / Download functionality
    $('#printDownloadBtn').on('click', function() {
        var table = $('#mdoescot-table').DataTable();

        // Clone the table and remove action column
        var tableClone = $('#mdoescot-table').clone();
        tableClone.find('th:last-child, td:last-child').remove();

        // Create a new window for printing
        var printWindow = window.open('', '_blank');
        var tableHtml = '<!DOCTYPE html><html><head><title>MDO/Escort Exemption</title>';
        tableHtml += '<style>';
        tableHtml += 'body { font-family: Arial, sans-serif; margin: 20px; }';
        tableHtml += 'table { border-collapse: collapse; width: 100%; margin-top: 20px; }';
        tableHtml += 'th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }';
        tableHtml += 'th { background-color: #b72a2a; color: white; font-weight: bold; }';
        tableHtml += 'tr:nth-child(even) { background-color: #f2f2f2; }';
        tableHtml += 'h2 { color: #004a93; margin-bottom: 20px; }';
        tableHtml += '@media print { body { margin: 0; } @page { margin: 1cm; } }';
        tableHtml += '</style></head><body>';
        tableHtml += '<h2>MDO/Escort Exemption</h2>';

        // Get the table HTML without action column
        var cleanTable = tableClone[0].outerHTML;
        // Remove any action-related content
        cleanTable = cleanTable.replace(/<th[^>]*>Actions<\/th>/gi, '');
        cleanTable = cleanTable.replace(/<td[^>]*>[\s\S]*?(edit|delete|Actions)[\s\S]*?<\/td>/gi, '');

        tableHtml += cleanTable;
        tableHtml += '</body></html>';

        printWindow.document.write(tableHtml);
        printWindow.document.close();

        // Wait for content to load, then print
        setTimeout(function() {
            printWindow.print();
        }, 250);
    });
});
</script>
@endpush