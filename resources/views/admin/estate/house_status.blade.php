@extends('admin.layouts.master')

@section('title', 'House Status - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-breadcrum title="House Status"></x-breadcrum>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table text-nowrap" id="houseStatusTable">
                    <thead>
                        <tr>
                            <th>Sno.</th>
                            <th>Qtr No</th>
                            <th>Building Name</th>
                            <th>Type</th>
                            <th>Allottee Name (Ms/Mr/Mrs.)</th>
                            <th>Section/Designation</th>
                            <th>Mobile Number</th>
                            <th>Alloted Date</th>
                            <th>Occupied Date</th>
                            <th>Vacated Date</th>
                            <th>Status (O - Occupied / V - Vacated)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="noDataRow">
                            <td colspan="11" class="text-center text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var dataTableInstance = null;

    function destroyDataTable() {
        if (dataTableInstance && $.fn.DataTable.isDataTable('#houseStatusTable')) {
            dataTableInstance.destroy();
            $('#houseStatusTable tbody').empty();
            dataTableInstance = null;
        }
    }

    function escapeHtml(str) {
        if (str == null || str === '') return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function loadHouseStatus() {
        $('#noDataRow').remove();
        $('#houseStatusTable tbody').html('<tr><td colspan="11" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: '{{ route("admin.estate.reports.house-status.data") }}',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                destroyDataTable();
                var tbody = $('#houseStatusTable tbody');
                tbody.empty();
                if (res.status && res.data && res.data.length > 0) {
                    $.each(res.data, function(i, row) {
                        tbody.append(
                            '<tr>' +
                                '<td>' + (row.sno != null ? row.sno : (i + 1)) + '</td>' +
                                '<td>' + escapeHtml(row.qtr_no || '—') + '</td>' +
                                '<td>' + escapeHtml(row.building_name || '—') + '</td>' +
                                '<td>' + escapeHtml(row.type || '—') + '</td>' +
                                '<td>' + escapeHtml(row.allottee_name || 'VACANT') + '</td>' +
                                '<td>' + escapeHtml(row.section_designation || '') + '</td>' +
                                '<td>' + escapeHtml(row.mobile_number || '') + '</td>' +
                                '<td>' + escapeHtml(row.alloted_date || '') + '</td>' +
                                '<td>' + escapeHtml(row.occupied_date || '') + '</td>' +
                                '<td>' + escapeHtml(row.vacated_date || '') + '</td>' +
                                '<td>' + escapeHtml(row.status || '') + '</td>' +
                            '</tr>'
                        );
                    });
                } else {
                    tbody.append('<tr id="noDataRow"><td colspan="11" class="text-center text-muted">No data available.</td></tr>');
                }
                dataTableInstance = $('#houseStatusTable').DataTable({
                    order: [[0, 'asc']],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    },
                    responsive: true,
                    autoWidth: false,
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
                });
            },
            error: function(xhr) {
                destroyDataTable();
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load data.';
                $('#houseStatusTable tbody').empty().append('<tr><td colspan="11" class="text-center text-danger">' + msg + '</td></tr>');
            }
        });
    }

    loadHouseStatus();
});
</script>
@endpush
