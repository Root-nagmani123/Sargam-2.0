@extends('admin.layouts.master')

@section('title', 'Request For House - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    {{-- Breadcrumb: Home > My Requests / Complaints > Request For House --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="#">My Requests / Complaints</a></li>
            <li class="breadcrumb-item active" aria-current="page">Request For House</li>
        </ol>
    </nav>

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h4 fw-bold text-body mb-2">Request For House</h1>
            <p class="text-body-secondary small mb-4">
                This page displays all list of request details added in the system, and provides options to manage records.
            </p>

            {{-- Main data table --}}
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0" id="requestForHouseTable">
                    <caption class="visually-hidden">Request For House – list of request details</caption>
                    <thead class="table-primary">
                        <tr>
                            <th scope="col" class="text-nowrap">S.NO.</th>
                            <th scope="col" class="text-nowrap">REQUEST ID</th>
                            <th scope="col" class="text-nowrap">REQUEST DATE</th>
                            <th scope="col" class="text-nowrap">NAME / ID</th>
                            <th scope="col" class="text-nowrap">DATE OF JOINING IN ACADEMY</th>
                            <th scope="col" class="text-nowrap">STATUS OF REQUEST</th>
                            <th scope="col" class="text-nowrap">ALLOTED HOUSE</th>
                            <th scope="col" class="text-nowrap">ELIGIBILITY TYPE</th>
                            <th scope="col" class="text-nowrap">POSSESSION FROM</th>
                            <th scope="col" class="text-nowrap">POSSESSION TO</th>
                            <th scope="col" class="text-nowrap">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $requestList = $requests ?? collect(); @endphp
                        @forelse($requestList as $index => $row)
                        <tr>
                            <td></td>
                            <td>{{ $row->request_id ?? '—' }}</td>
                            <td>{{ $row->request_date ?? '—' }}</td>
                            <td>{{ ($row->name ?? '—') }} ({{ $row->emp_id ?? '—' }})</td>
                            <td>{{ $row->doj_academy ?? '—' }}</td>
                            <td>{{ $row->status ?? '—' }}</td>
                            <td>{{ $row->alloted_house ?? '—' }}</td>
                            <td>{{ $row->eligibility_type ?? '—' }}</td>
                            <td>{{ $row->possession_from ?? '—' }}</td>
                            <td>{{ $row->possession_to ?? '—' }}</td>
                            <td class="text-nowrap">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary btn-change-request"
                                        data-request-id="{{ $row->pk }}">
                                    Change
                                </button>
                                @if(!empty($row->change_approved))
                                    <span class="text-success small d-block mt-1">(Your request has been approved)</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-center text-body-secondary py-4">No request records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Change Request Details Modal --}}
<div class="modal fade" id="changeRequestDetailsModal" tabindex="-1" aria-labelledby="changeRequestDetailsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="changeRequestDetailsModalLabel">
                    <i class="material-icons me-2">edit</i>Change Request Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="changeRequestDetailsModalBody">
                <div id="changeRequestDetailsModalLoading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    <p class="mt-3 text-body-secondary small mb-0">Loading form...</p>
                </div>
                <div id="changeRequestDetailsModalContent"></div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    #requestForHouseTable thead th {
        background-color: var(--bs-primary);
        color: var(--bs-white);
        font-weight: 600;
        border-color: var(--bs-primary);
        padding: 0.75rem 0.5rem;
        font-size: 0.8125rem;
    }
    #requestForHouseTable tbody td {
        padding: 0.65rem 0.5rem;
        font-size: 0.875rem;
    }
    #requestForHouseTable tbody tr:nth-of-type(even) {
        background-color: rgba(var(--bs-primary-rgb), 0.04);
    }
    #requestForHouseTable tbody tr:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.08);
    }
    /* Let Bootstrap handle horizontal scroll via table-responsive wrapper */
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('changeRequestDetailsModal');
    var modalBody = document.getElementById('changeRequestDetailsModalContent');
    var modalLoading = document.getElementById('changeRequestDetailsModalLoading');
    var modalUrlTemplate = '{{ route("admin.estate.change-request-details.modal", ["id" => "__ID__"]) }}';

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-change-request');
        if (!btn) return;
        e.preventDefault();
        var id = btn.getAttribute('data-request-id');
        if (!id) return;

        var url = modalUrlTemplate.replace('__ID__', encodeURIComponent(id));
        modalBody.innerHTML = '';
        modalLoading.classList.remove('d-none');

        var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();

        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(res) { return res.text(); })
        .then(function(html) {
            modalLoading.classList.add('d-none');
            modalBody.innerHTML = html;
            [].forEach.call(modalBody.querySelectorAll('script'), function(script) {
                var el = document.createElement('script');
                el.textContent = script.textContent;
                document.body.appendChild(el);
                document.body.removeChild(el);
            });
            if (window.initChangeRequestDetailsCascade) window.initChangeRequestDetailsCascade(modalBody);
        })
        .catch(function() {
            modalLoading.classList.add('d-none');
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form. Please try again.</div>';
        });
    });

    modalEl.addEventListener('hidden.bs.modal', function() {
        modalBody.innerHTML = '';
    });

    if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
        var table = jQuery('#requestForHouseTable');
        if (table.length && !jQuery.fn.DataTable.isDataTable(table)) {
            var dt = table.DataTable({
                responsive: false,
                autoWidth: false,
                scrollX: false,
                ordering: true,
                searching: true,
                lengthChange: true,
                pageLength: 10,
                // Default sort by Request Date (index 2)
                order: [[2, 'desc']],
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                columnDefs: [
                    // S.NO. column: dynamic serial number 1,2,3... respecting paging
                    {
                        targets: 0,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    // Change column (last)
                    { targets: 10, orderable: false, searchable: false }
                ],
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
                dom: '<"row align-items-center mb-3"<"col-12 col-md-4"l><"col-12 col-md-8"f>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>'
            });

            // Ensure S.NO. column always shows 1,2,3... based on current
            // sorting, searching and paging (no matter how user interacts).
            dt.on('order.dt search.dt draw.dt', function () {
                dt.column(0, {search: 'applied', order: 'applied'}).nodes().each(function (cell, i) {
                    cell.innerHTML = i + 1;
                });
            });
            dt.draw();
        }
    }
});
</script>
@endpush
@endsection
