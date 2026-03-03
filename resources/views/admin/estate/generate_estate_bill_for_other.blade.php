@extends('admin.layouts.master')

@section('title', 'Generate Estate Bill for Other - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Generate Estate Bill for Other"></x-breadcrum>
    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold text-dark mb-1">Generate Estate Bill for Other</h1>
            <p class="text-muted small mb-4">Contract employees. Select Bill Month and click Show to list bills.</p>
            <form id="billForOtherFilterForm" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="bill_month" class="form-label">Bill Month <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="month" class="form-control" id="bill_month" name="bill_month" value="{{ date('Y-m') }}" required>
                        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    </div>
                    <small class="text-muted d-block">Select Bill Month</small>
                </div>
                <div class="col-12 col-md-2 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="check_all_bills" name="check_all">
                        <label class="form-check-label" for="check_all_bills">Check All</label>
                    </div>
                </div>
                <div class="col-12 col-md-4 d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary" id="btnShow">
                        <i class="bi bi-search me-1"></i> Show
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" id="btnVerifySelected" title="Mark selected bills as verified (notify employee)">
                        <i class="bi bi-check2-circle me-1"></i> Verify Selected Bills
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSaveAsDraft" title="Save selected bills as draft">
                        <i class="bi bi-save me-1"></i> Save As Draft
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table text-nowrap mb-0" id="billForOtherTable">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 40px;"><input type="checkbox" id="billForOtherCheckAll" class="form-check-input" title="Select all"></th>
                            <th>S.NO.</th>
                            <th>NAME</th>
                            <th>SECTION</th>
                            <th>HOUSE NO.</th>
                            <th>FROM DATE</th>
                            <th>TO DATE</th>
                            <th>METER NO.</th>
                            <th>PREVIOUS METER READING</th>
                            <th>CURRENT METER READING</th>
                            <th>UNIT CONSUMED</th>
                            <th>TOTAL CHARGE</th>
                            <th>LICENCE FEE</th>
                            <th>WATER CHARGE</th>
                            <th>GRAND TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="noDataRow">
                            <td colspan="15" class="text-center text-muted py-4">Select Bill Month and click Show to load data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
#billForOtherTable .dtr-control,
#billForOtherTable th.dtr-control,
#billForOtherTable td.dtr-control { display: none !important; }
@media (max-width: 767.98px) {
    .table-scroll-vertical-sm { max-height: 65vh; overflow-y: auto; overflow-x: auto; -webkit-overflow-scrolling: touch; }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    var dataTableInstance = null;
    var dataUrl = "{{ route('admin.estate.generate-estate-bill-for-other.data') }}";

    function formatMoney(n) {
        if (n == null || n === '' || isNaN(n)) return '—';
        return '₹ ' + parseFloat(n).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function escapeHtml(str) {
        if (str == null || str === '') return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }

    function loadBillForOther() {
        var billMonth = $('#bill_month').val();
        if (!billMonth) return;

        $('#noDataRow').remove();
        $('#billForOtherTable tbody').html('<tr><td colspan="15" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: dataUrl,
            type: 'GET',
            data: { bill_month: billMonth },
            dataType: 'json',
            success: function(res) {
                if (dataTableInstance && $.fn.DataTable.isDataTable('#billForOtherTable')) {
                    dataTableInstance.destroy();
                    dataTableInstance = null;
                }
                var tbody = $('#billForOtherTable tbody');
                tbody.empty();

                var data = (res && res.data) ? res.data : [];
                if (data.length === 0) {
                    tbody.append('<tr id="noDataRow"><td colspan="15" class="text-center text-muted py-4">No data available for the selected month.</td></tr>');
                } else {
                    data.forEach(function(row) {
                        var pk = row.pk != null ? row.pk : '';
                        tbody.append(
                            '<tr>' +
                            '<td class="text-center"><input type="checkbox" class="form-check-input bill-row-check" value="' + escapeHtml(pk) + '" data-pk="' + escapeHtml(pk) + '"></td>' +
                            '<td>' + escapeHtml(row.sno || '') + '</td>' +
                            '<td>' + escapeHtml(row.name || '—') + '</td>' +
                            '<td>' + escapeHtml(row.section || '—') + '</td>' +
                            '<td>' + escapeHtml(row.house_no || '—') + '</td>' +
                            '<td>' + escapeHtml(row.from_date || '—') + '</td>' +
                            '<td>' + escapeHtml(row.to_date || '—') + '</td>' +
                            '<td>' + (String(row.meter_no || '').replace(/\n/g, '<br>') || '—') + '</td>' +
                            '<td>' + (String(row.prev_reading || '').replace(/\n/g, '<br>') || '—') + '</td>' +
                            '<td>' + (String(row.curr_reading || '').replace(/\n/g, '<br>') || '—') + '</td>' +
                            '<td>' + escapeHtml(row.unit_consumed ?? '—') + '</td>' +
                            '<td>' + formatMoney(row.total_charge) + '</td>' +
                            '<td>' + formatMoney(row.licence_fee) + '</td>' +
                            '<td>' + formatMoney(row.water_charges) + '</td>' +
                            '<td class="fw-semibold">' + formatMoney(row.grand_total) + '</td>' +
                            '</tr>'
                        );
                    });
                }

                dataTableInstance = $('#billForOtherTable').DataTable({
                    order: [[1, 'asc']],
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
                    responsive: false,
                    autoWidth: false,
                    scrollX: true,
                    dom: '<"row flex-nowrap align-items-center py-2"<"col-12 col-sm-6 col-md-6 mb-2 mb-md-0"l><"col-12 col-sm-6 col-md-6"f>>rt<"row align-items-center py-2"<"col-12 col-sm-5 col-md-5"i><"col-12 col-sm-7 col-md-7"p>>'
                });
            },
            error: function() {
                if (dataTableInstance && $.fn.DataTable.isDataTable('#billForOtherTable')) {
                    dataTableInstance.destroy();
                    dataTableInstance = null;
                }
                $('#billForOtherTable tbody').empty().append(
                    '<tr id="noDataRow"><td colspan="15" class="text-center text-danger py-4">Failed to load data. Please try again.</td></tr>'
                );
            }
        });
    }

    function getSelectedBillPks() {
        var pks = [];
        $('#billForOtherTable .bill-row-check:checked').each(function() {
            var pk = $(this).data('pk');
            if (pk && parseInt(pk, 10) > 0) pks.push(parseInt(pk, 10));
        });
        return pks;
    }

    function showStatusMessage(msg, type) {
        type = type || 'success';
        var alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' : 'alert-warning');
        var icon = type === 'success' ? 'check_circle' : (type === 'error' ? 'error' : 'info');
        $('#status-msg').html(
            '<div class="alert ' + alertClass + ' alert-dismissible fade show shadow-sm" role="alert">' +
            '<i class="material-icons material-symbols-rounded me-2">' + icon + '</i> ' + msg +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
        ).show();
        setTimeout(function() {
            $('#status-msg').fadeOut();
        }, 4000);
    }

    $('#billForOtherFilterForm').on('submit', function(e) {
        e.preventDefault();
        loadBillForOther();
    });

    function syncCheckAll() {
        var total = $('#billForOtherTable .bill-row-check').length;
        var checked = $('#billForOtherTable .bill-row-check:checked').length;
        var allChecked = total > 0 && total === checked;
        $('#billForOtherCheckAll').prop('checked', allChecked);
        $('#check_all_bills').prop('checked', allChecked);
    }
    $('#billForOtherCheckAll').on('change', function() {
        var checked = this.checked;
        $('#billForOtherTable .bill-row-check').each(function() { this.checked = checked; });
        $('#check_all_bills').prop('checked', checked);
    });
    $('#check_all_bills').on('change', function() {
        var checked = this.checked;
        $('#billForOtherTable .bill-row-check').each(function() { this.checked = checked; });
        $('#billForOtherCheckAll').prop('checked', checked);
    });
    $(document).on('change', '#billForOtherTable .bill-row-check', function() {
        syncCheckAll();
    });

    $('#btnVerifySelected').on('click', function() {
        var pks = getSelectedBillPks();
        if (pks.length === 0) {
            showStatusMessage('Please select at least one bill to verify.', 'warning');
            return;
        }
        var btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            url: "{{ route('admin.estate.generate-estate-bill-for-other.verify-selected') }}",
            type: 'POST',
            data: { pks: pks, _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false);
                if (res.status && res.message) {
                    showStatusMessage(res.message, 'success');
                    loadBillForOther();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (xhr.responseJSON && xhr.responseJSON.errors) ? JSON.stringify(xhr.responseJSON.errors) : 'Failed to verify bills.';
                showStatusMessage(msg, 'error');
            }
        });
    });

    $('#btnSaveAsDraft').on('click', function() {
        var pks = getSelectedBillPks();
        if (pks.length === 0) {
            showStatusMessage('Please select at least one bill to save as draft.', 'warning');
            return;
        }
        var btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            url: "{{ route('admin.estate.generate-estate-bill-for-other.save-as-draft') }}",
            type: 'POST',
            data: { pks: pks, _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false);
                if (res.status && res.message) {
                    showStatusMessage(res.message, 'success');
                    loadBillForOther();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (xhr.responseJSON && xhr.responseJSON.errors) ? JSON.stringify(xhr.responseJSON.errors) : 'Failed to save as draft.';
                showStatusMessage(msg, 'error');
            }
        });
    });
});
</script>
@endpush
