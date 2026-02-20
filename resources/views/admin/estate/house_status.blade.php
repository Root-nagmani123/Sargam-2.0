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
                            <th>Types</th>
                            <th>Grade Pay</th>
                            <th>House Available</th>
                            <th>House Under Construction</th>
                            <th>Total Projected Availability</th>
                            <th>Allotted to LBSNAA Employee</th>
                            <th>Other</th>
                            <th>Vacant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="noDataRow">
                            <td colspan="8" class="text-center text-muted">Loading...</td>
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

    function loadHouseStatus() {
        $('#noDataRow').remove();
        $('#houseStatusTable tbody').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');

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
                                '<td>' + (row.types || 'N/A') + '</td>' +
                                '<td>' + (row.grade_pay || '-') + '</td>' +
                                '<td>' + (row.house_available != null ? row.house_available : '0') + '</td>' +
                                '<td>' + (row.house_under_construction != null ? row.house_under_construction : '0') + '</td>' +
                                '<td>' + (row.total_projected != null ? row.total_projected : '0') + '</td>' +
                                '<td>' + (row.allotted_lbsnaa != null ? row.allotted_lbsnaa : '0') + '</td>' +
                                '<td>' + (row.other != null ? row.other : '0') + '</td>' +
                                '<td>' + (row.vacant != null ? row.vacant : '0') + '</td>' +
                            '</tr>'
                        );
                    });
                } else {
                    tbody.append('<tr id="noDataRow"><td colspan="8" class="text-center text-muted">No data available.</td></tr>');
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
                $('#houseStatusTable tbody').empty().append('<tr><td colspan="8" class="text-center text-danger">' + msg + '</td></tr>');
            }
        });
    }

    loadHouseStatus();
});
</script>
@endpush
