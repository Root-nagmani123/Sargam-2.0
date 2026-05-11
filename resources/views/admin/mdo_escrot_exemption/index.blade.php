@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@section('setup_content')
<style>
.btn-group[role="group"] .btn {
    transition: all 0.3s ease-in-out;
    border-radius: 0;
}

.btn-group[role="group"] .btn:first-child {
    border-top-left-radius: 50rem !important;
    border-bottom-left-radius: 50rem !important;
}

.btn-group[role="group"] .btn:last-child {
    border-top-right-radius: 50rem !important;
    border-bottom-right-radius: 50rem !important;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
}

.btn-group .btn.active {
    box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 123, 255, 0.3);
}

.btn:focus-visible {
    outline: 3px solid #0d6efd;
    outline-offset: 2px;
}

.btn-outline-secondary {
    color: #333;
    border-color: #999;
}

.btn-outline-secondary:hover {
    background-color: #f8f9fa;
    border-color: #666;
}
</style>
<div class="container-fluid">
    <x-breadcrum title="MDO/Escort Exemption">
        <a href="{{ route('mdo-escrot-exemption.create') }}"
            class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center gap-1 rounded-1 shadow-sm px-3 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
            <span>Add New MDO/Escort Exemption</span>
        </a>
    </x-breadcrum>
    <div class="datatables">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">

                    <!-- Filters Section -->
                    <div class="row mb-3 align-items-end">
                        <!-- Course Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="course_filter" class="form-label fw-semibold">Course:</label>
                            <select id="course_filter" class="form-select">
                                <option value="">-- All Courses --</option>
                                @foreach ($courseMaster as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Year Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="year_filter" class="form-label fw-semibold">Year:</label>
                            <select id="year_filter" class="form-select">
                                <option value="">-- All Years --</option>
                                @foreach ($years as $year => $yearValue)
                                <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Duty Type Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="duty_type_filter" class="form-label fw-semibold">Duty type:</label>
                            <select id="duty_type_filter" class="form-select">
                                <option value="">-- All Duty Types --</option>
                                @foreach ($dutyTypes as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- From Date Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="from_date_filter" class="form-label fw-semibold">From Date:</label>
                            <input type="date" id="from_date_filter" class="form-control" value="{{ request('from_date_filter') }}">
                        </div>
                        
                        <!-- To Date Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="to_date_filter" class="form-label fw-semibold">To Date:</label>
                            <input type="date" id="to_date_filter" class="form-control" value="{{ request('to_date_filter') }}">
                        </div>
                        
                        <!-- Time From Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="time_from_filter" class="form-label fw-semibold">Time From:</label>
                            <input type="time" id="time_from_filter" class="form-control">
                        </div>
                        
                        <!-- Time To Filter -->
                        <div class="col-md-3 mb-3">
                            <label for="time_to_filter" class="form-label fw-semibold">Time To:</label>
                            <input type="time" id="time_to_filter" class="form-control">
                        </div>
                         <div class="col-md-3 mb-3 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary" id="resetFilters">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                            </button>
                        </div>
                    </div>
                    
                    <!-- Active/Archive Buttons Row -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-block">&nbsp;</label>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary fs-6 px-3 py-2 d-inline-flex align-items-center">
                                    <i class="bi bi-list-check me-2"></i> Total Records: <strong class="ms-1" id="total-records-count">0</strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <label class="form-label fw-semibold d-block">&nbsp;</label>
                           
                        </div>
                    </div>
                    
                    <!-- Hidden input for filter status -->
                    <input type="hidden" id="filter_status" value="{{ $filter ?? 'active' }}">

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
$(document).ready(function() {
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