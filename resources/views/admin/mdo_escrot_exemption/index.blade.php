@extends('admin.layouts.master')

@section('title', 'MDO Escrot Exemption')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="MDO/Escort Exemption" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>MDO/Escort Exemption</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-end mb-3 gap-2">
                                <!-- Print / Download Button -->
                                <button type="button" id="printDownloadBtn"
                                    class="btn btn-info px-3 py-2 rounded shadow-sm">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">print</i>
                                    Print / Download
                                </button>
                                <!-- Add New Button -->
                                <a href="{{ route('mdo-escrot-exemption.create') }}"
                                    class="btn btn-primary px-3 py-2 rounded shadow-sm">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">add</i>
                                    Add New MDO/Escort Exemption
                                </a>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Course, Year, Time From, Time To, and Duty Type Filters -->
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label for="course_filter" class="form-label fw-semibold">Course:</label>
                            <select id="course_filter" class="form-select">
                                <option value="">-- All Courses --</option>
                                @foreach ($courseMaster as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="year_filter" class="form-label fw-semibold">Year:</label>
                            <select id="year_filter" class="form-select">
                                <option value="">-- All Years --</option>
                                @foreach ($years as $year => $yearValue)
                                <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="duty_type_filter" class="form-label fw-semibold">Duty type:</label>
                            <select id="duty_type_filter" class="form-select">
                                <option value="">-- All Duty Types --</option>
                                @foreach ($dutyTypes as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="time_from_filter" class="form-label fw-semibold">Time From:</label>
                            <input type="time" id="time_from_filter" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label for="time_to_filter" class="form-label fw-semibold">Time To:</label>
                            <input type="time" id="time_to_filter" class="form-control">
                        </div>
                    </div>

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

    // Reload DataTable on filter change
    $('#course_filter, #year_filter, #duty_type_filter').on('change', function() {
        table.ajax.reload();
    });

    // Reload DataTable on time filter change
    $('#time_from_filter, #time_to_filter').on('change', function() {
        table.ajax.reload();
    });

    // Pass all filters to server
    $('#mdoescot-table').on('preXhr.dt', function(e, settings, data) {
        data.course_filter = $('#course_filter').val();
        data.year_filter = $('#year_filter').val();
        data.duty_type_filter = $('#duty_type_filter').val();
        data.time_from_filter = $('#time_from_filter').val();
        data.time_to_filter = $('#time_to_filter').val();
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