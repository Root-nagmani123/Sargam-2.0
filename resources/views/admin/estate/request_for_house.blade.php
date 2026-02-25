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
                This page displays all list of request details added in the system, and provides options to manage records such as add, edit, delete, excel upload, excel download, print, etc.
            </p>

            {{-- Table controls --}}
            <div class="row flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-4">
                <div class="col-auto">
                    <label class="form-label mb-0 me-2 d-inline-flex align-items-center">
                        <span class="me-2">Show</span>
                        <select class="form-select form-select-sm d-inline-block w-auto" id="entriesPerPage" aria-label="Entries per page">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="ms-2">entries</span>
                    </label>
                </div>
                <div class="col-auto d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" title="Show / hide columns">
                        <i class="bi bi-columns-gap me-1"></i> Show / hide columns
                    </button>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-success" title="Excel export"><i class="bi bi-file-earmark-excel"></i></button>
                        <button type="button" class="btn btn-outline-danger" title="PDF export"><i class="bi bi-file-earmark-pdf"></i></button>
                        <button type="button" class="btn btn-outline-secondary" title="Print"><i class="bi bi-printer"></i></button>
                    </div>
                    <label class="form-label mb-0 d-inline-flex align-items-center gap-2">
                        <span class="small text-nowrap">Search within table:</span>
                        <input type="search" class="form-control form-control-sm" id="tableSearch" placeholder="Search..." aria-label="Search within table" style="min-width: 180px;">
                    </label>
                </div>
            </div>

            {{-- Main data table --}}
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0" id="requestForHouseTable">
                    <caption class="visually-hidden">Request For House – list of request details</caption>
                    <thead class="table-primary">
                        <tr>
                            <th scope="col" class="text-center">
                                <input type="checkbox" class="form-check-input" id="selectAll" aria-label="Select all rows">
                            </th>
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
                            <th scope="col" class="text-nowrap">EXTENSION</th>
                            <th scope="col" class="text-nowrap">CHANGE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $requestList = $requests ?? []; @endphp
                        @forelse($requestList as $index => $row)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input row-select" value="{{ $row->id ?? $index }}" aria-label="Select row">
                            </td>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row->request_id ?? '—' }}</td>
                            <td>{{ $row->request_date ?? '—' }}</td>
                            <td>{{ ($row->name ?? '—') }} ({{ $row->emp_id ?? '—' }})</td>
                            <td>{{ $row->doj_academy ?? '—' }}</td>
                            <td>{{ $row->status ?? '—' }}</td>
                            <td>{{ $row->alloted_house ?? '—' }}</td>
                            <td>{{ $row->eligibility_type ?? '—' }}</td>
                            <td>{{ $row->possession_from ?? '—' }}</td>
                            <td>{{ $row->possession_to ?? '—' }}</td>
                            <td>
                                <a href="#" class="link-primary text-decoration-none">Extension</a>
                            </td>
                            <td>
                                <a href="#" class="link-primary text-decoration-none btn-change-request" data-request-id="{{ $row->id ?? $row->pk ?? $index }}">Change</a>
                                @if(!empty($row->change_approved))
                                <span class="text-success small d-block">(Your request has been approved)</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        {{-- Sample rows when no data --}}
                        <tr>
                            <td class="text-center"><input type="checkbox" class="form-check-input row-select" aria-label="Select row"></td>
                            <td>1</td>
                            <td>Chg-Req-223</td>
                            <td>02-02-2026</td>
                            <td>BALWANT SINGH (NNP00048)</td>
                            <td>08-04-2014</td>
                            <td>Approved</td>
                            <td>GA-III-01</td>
                            <td>Type-II</td>
                            <td>01-01-2025</td>
                            <td>31-12-2025</td>
                            <td><a href="#" class="link-primary text-decoration-none">Extension</a></td>
                            <td>
                                <a href="#" class="link-primary text-decoration-none btn-change-request" data-request-id="223">Change</a>
                                <span class="text-success small d-block">(Your request has been approved)</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center"><input type="checkbox" class="form-check-input row-select" aria-label="Select row"></td>
                            <td>2</td>
                            <td>Chg-Req-224</td>
                            <td>03-02-2026</td>
                            <td>EXAMPLE USER (EMP001)</td>
                            <td>01-06-2020</td>
                            <td>Pending</td>
                            <td>CA-II-01</td>
                            <td>Type-II</td>
                            <td>01-02-2025</td>
                            <td>31-01-2026</td>
                            <td><a href="#" class="link-primary text-decoration-none">Extension</a></td>
                            <td><a href="#" class="link-primary text-decoration-none btn-change-request" data-request-id="224">Change</a></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Table footer & pagination --}}
            <div class="row align-items-center justify-content-between gap-2 mt-3">
                <div class="col-auto small text-body-secondary">
                    Showing <span id="showingFrom">1</span> to <span id="showingTo">3</span> of <span id="totalEntries">3</span> entries.
                </div>
                <div class="col-auto">
                    <nav aria-label="Table pagination">
                        <ul class="pagination pagination-sm mb-0 flex-wrap">
                            <li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">First</a></li>
                            <li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a></li>
                            <li class="page-item active" aria-current="page"><a class="page-link" href="#">1</a></li>
                            <li class="page-item disabled"><a class="page-link" href="#" aria-disabled="true">Next</a></li>
                            <li class="page-item disabled"><a class="page-link" href="#" aria-disabled="true">Last</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Visit Links footer --}}
    <div class="mt-4 pt-3 border-top">
        <p class="small fw-semibold text-body mb-2">Visit Links:</p>
        <p class="small text-body-secondary mb-0">
            <a href="#" class="text-decoration-none">Request for Protocol Services</a>
            &nbsp;-&nbsp;
            <a href="#" class="text-decoration-none">Request For House</a>
            &nbsp;-&nbsp;
            <a href="#" class="text-decoration-none">Lodge a Complaint</a>
            &nbsp;-&nbsp;
            <a href="#" class="text-decoration-none">Vehicle Pass Request</a>
            &nbsp;-&nbsp;
            <a href="#" class="text-decoration-none">Request For Reprographic Services</a>
            &nbsp;-&nbsp;
            <a href="#" class="text-decoration-none">Request Employee ID Card</a>
            &nbsp;-&nbsp;
            <a href="#" class="text-decoration-none">Request For Family ID Card</a>
            &nbsp;-&nbsp;
            <a href="#" class="text-decoration-none">Extension/Duplicate IDCard</a>
        </p>
    </div>
</div>

{{-- Change Request Details Modal --}}
<div class="modal fade" id="changeRequestDetailsModal" tabindex="-1" aria-labelledby="changeRequestDetailsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="changeRequestDetailsModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Change Request Details
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
        })
        .catch(function() {
            modalLoading.classList.add('d-none');
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form. Please try again.</div>';
        });
    });

    modalEl.addEventListener('hidden.bs.modal', function() {
        modalBody.innerHTML = '';
    });
});
</script>
@endpush
@endsection
