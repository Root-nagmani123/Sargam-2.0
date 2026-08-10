@extends('admin.layouts.master')

@section('title', 'Manage Priorities')

@push('styles')
{{-- Shared Centcom index chrome — the same file Manage Categories / Sub-Categories
     use, so the three pages cannot drift apart. See docs/new-design-index-page.md. --}}
<link rel="stylesheet"
      href="{{ asset('css/issue-management-admin.css') }}?v={{ @filemtime(public_path('css/issue-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // Search, sort and paging are DataTables' now; ?cols= is appended to these
    // links by ipUpdateExportCols().
    $exportQuery = [];
@endphp
<div class="container-fluid ic-page">
    <x-breadcrum title="Manage Priorities" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="ipAddBtn" data-bs-toggle="modal" data-bs-target="#addPriorityModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Priority</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1 --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 ic-secondary-actions">
        {{-- More than one download format → dropdown, per §1 of the doc. --}}
        <div class="dropdown">
            <button type="button" id="ipDownloadToggle"
                    class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="ipDownloadToggle">
                <li><a class="dropdown-item" id="ipDownloadLink"
                       href="{{ route('admin.issue-priorities.export', array_merge(['format' => 'csv'], $exportQuery)) }}">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV</a></li>
                <li><a class="dropdown-item" id="ipExcelLink"
                       href="{{ route('admin.issue-priorities.export', array_merge(['format' => 'excel'], $exportQuery)) }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" id="ipPdfLink"
                       href="{{ route('admin.issue-priorities.export', array_merge(['format' => 'pdf'], $exportQuery)) }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF</a></li>
            </ul>
        </div>
        <a href="{{ route('admin.issue-priorities.export', array_merge(['format' => 'print'], $exportQuery)) }}"
           id="ipPrintLink"
           target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: columns + search on the right (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 ic-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="ipBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#ipColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- Always-visible search box: datatable-global-ui.js moves DataTables'
                         own filter in here, so it filters as you type instead of
                         reloading the page on Enter. --}}
                    <div id="ipDtSearch" class="programme-dt-search" data-dt-search-for="issuePrioritiesTable"></div>
                </div>
                <hr>
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive datatables">
                    {{-- id is what the DataTable in @@section('scripts') binds to. --}}
                    <table class="table" id="issuePrioritiesTable">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Priority</th>
                                <th scope="col">Description</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        {{-- Rows come from IssuePriorityController::data() over ajax
                             (server-side paging), so this stays empty. --}}
                        <tbody></tbody>
                    </table>
                </div>

                {{-- No Blade pager: the DataTable pages this grid from the server,
                     one draw at a time. --}}
            </div>

        </div>
    </div>
</div>

<!-- Add Priority Modal -->
<div class="modal fade" id="addPriorityModal" tabindex="-1" aria-labelledby="addPriorityModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ic-modal border-0 shadow">
            <form action="{{ route('admin.issue-priorities.store') }}" method="POST" id="addPriorityForm">
                @csrf
                <div class="modal-header ic-modal-header">
                    <h5 class="modal-title" id="addPriorityModalLabel">Add Priority</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ic-modal-body">
                    <div class="ic-field-card">
                        <div class="mb-3">
                            <label for="ip_priority" class="ic-form-label">Priority Name<span class="ic-req">*</span></label>
                            <input type="text" class="form-control ic-control" id="ip_priority" name="priority"
                                   placeholder="e.g. High" maxlength="100" required>
                        </div>
                        <div class="mb-0">
                            <label for="ip_description" class="ic-form-label">Description</label>
                            <textarea class="form-control ic-control" id="ip_description" name="description" rows="3"
                                      placeholder="e.g. Lorem Ipsum dolor sit amet"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ic-modal-footer">
                    <button type="button" class="btn ic-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ic-btn-submit">Add Priority</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Priority Modal -->
<div class="modal fade" id="editPriorityModal" tabindex="-1" aria-labelledby="editPriorityModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ic-modal border-0 shadow">
            <form id="editPriorityForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header ic-modal-header">
                    <h5 class="modal-title" id="editPriorityModalLabel">Edit Priority</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                {{-- Same card / label / control language as Add, plus Status —
                     this grid has no inline switch, so Edit is the only way to set it. --}}
                <div class="modal-body ic-modal-body">
                    <div class="ic-field-card">
                        <div class="mb-3">
                            <label for="edit_ip_priority" class="ic-form-label">Priority Name<span class="ic-req">*</span></label>
                            <input type="text" class="form-control ic-control" id="edit_ip_priority" name="priority"
                                   placeholder="e.g. High" maxlength="100" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_ip_description" class="ic-form-label">Description</label>
                            <textarea class="form-control ic-control" id="edit_ip_description" name="description" rows="3"
                                      placeholder="e.g. Lorem Ipsum dolor sit amet"></textarea>
                        </div>
                        <div class="mb-0">
                            <label for="edit_ip_status" class="ic-form-label">Status<span class="ic-req">*</span></label>
                            <select class="form-select ic-control" id="edit_ip_status" name="status" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ic-modal-footer">
                    <button type="button" class="btn ic-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ic-btn-submit">Update Priority</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="ipColumnVisibilityModal" tabindex="-1" aria-labelledby="ipColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="ipColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="issuePriorityColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/* Server-side DataTable — search, sort and paging all run in SQL via data(),
   so the browser only ever holds the page it is showing. */
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var $ = window.jQuery;
        if (!$ || !$.fn.DataTable) { return; }

        var $table = $('#issuePrioritiesTable');
        if (!$table.length || $.fn.DataTable.isDataTable($table)) { return; }

        $table.DataTable({
            serverSide: true,
            /* datatable-global-ui.js turns DataTables' native ordering OFF for
               server-side tables unless this opt-in is present, and sorts only the
               rows already loaded instead. We want ORDER BY over the whole set. */
            sargamServerOrder: true,
            processing: true,
            ajax: { url: '{{ route('admin.issue-priorities.data') }}' },
            order: [[1, 'asc']],                 // Priority Name A→Z
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            searchDelay: 400,
            /* name= is what the endpoint maps back to a real column for search
               and ORDER BY; data= is the key in each JSON row. */
            columns: [
                { data: 'id', name: 'id' },
                { data: 'priority_name', name: 'priority_name' },
                { data: 'description', name: 'description' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            language: {
                processing: 'Loading…',
                search: 'Search priorities:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ priorities',
                infoEmpty: 'No priorities',
                infoFiltered: '(filtered from _MAX_ total)',
                zeroRecords: 'No matching priorities found',
                emptyTable: 'No priorities found.',
                paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
            },
            drawCallback: function () {
                if (typeof window.adjustAllDataTables === 'function') {
                    try { window.adjustAllDataTables(); } catch (e) { /* noop */ }
                }
            }
        });
    });
})();

function editPriority(id, name, description, status) {
    document.getElementById('edit_priority').value = name || '';
    document.getElementById('edit_description').value = description || '';
    document.getElementById('edit_status').value = status;
    
    const form = document.getElementById('editPriorityForm');
    form.action = "{{ url('admin/issue-priorities') }}/" + id;
    
    const modal = new bootstrap.Modal(document.getElementById('editPriorityModal'));
    modal.show();
}
</script>
@endpush
