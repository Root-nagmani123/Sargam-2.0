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
    // Query string carried across sort / paging / per-page changes.
    $baseQuery = ['q' => $search, 'per_page' => $perPage, 'sort' => $sortKey, 'dir' => $sortDir];

    $sortUrl = function (string $key) use ($baseQuery, $sortKey, $sortDir) {
        $dir = ($sortKey === $key && $sortDir === 'asc') ? 'desc' : 'asc';

        return request()->fullUrlWithQuery(array_merge($baseQuery, ['sort' => $key, 'dir' => $dir, 'page' => 1]));
    };

    $exportQuery = ['q' => $search, 'sort' => $sortKey, 'dir' => $sortDir];
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
        <a href="{{ route('admin.issue-priorities.export', array_merge(['format' => 'csv'], $exportQuery)) }}"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Download as CSV">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </a>
        <a href="{{ route('admin.issue-priorities.export', array_merge(['format' => 'print'], $exportQuery)) }}"
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

                    <button type="button" class="btn programme-dt-btn-columns" id="ipSearchToggle"
                            aria-label="Search priorities" title="Search"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>

                    <form method="GET" action="{{ route('admin.issue-priorities.index') }}"
                          class="ic-search-wrap {{ filled($search) ? '' : 'd-none' }}" id="ipSearchWrap">
                        <input type="hidden" name="per_page" value="{{ $perPage }}">
                        <input type="hidden" name="sort" value="{{ $sortKey }}">
                        <input type="hidden" name="dir" value="{{ $sortDir }}">
                        <input type="search" class="ic-search-input" id="ipSearchInput" name="q"
                               value="{{ $search }}" placeholder="Search priorities…" autocomplete="off"
                               aria-label="Search priorities">
                    </form>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="issuePrioritiesTable" data-sargam-dt-ui="false"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">
                                    <a class="ic-sort {{ $sortKey === 'priority' ? 'is-active' : '' }}" href="{{ $sortUrl('priority') }}">
                                        Priority
                                        <i class="bi {{ $sortKey === 'priority' && $sortDir === 'desc' ? 'bi-caret-down-fill' : 'bi-caret-up-fill' }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th scope="col">
                                    <a class="ic-sort {{ $sortKey === 'description' ? 'is-active' : '' }}" href="{{ $sortUrl('description') }}">
                                        Description
                                        <i class="bi {{ $sortKey === 'description' && $sortDir === 'desc' ? 'bi-caret-down-fill' : 'bi-caret-up-fill' }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th scope="col">
                                    <a class="ic-sort {{ $sortKey === 'status' ? 'is-active' : '' }}" href="{{ $sortUrl('status') }}">
                                        Status
                                        <i class="bi {{ $sortKey === 'status' && $sortDir === 'desc' ? 'bi-caret-down-fill' : 'bi-caret-up-fill' }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($priorities as $priority)
                                @php
                                    $isActive = (int) $priority->status === 1;
                                    // destroy() refuses a priority that any issue log references.
                                    $inUse = (int) ($priority->issue_logs_count ?? 0) > 0;
                                @endphp
                                <tr>
                                    <td>{{ $priorities->firstItem() + $loop->index }}</td>
                                    <td>{{ $priority->priority }}</td>
                                    <td class="ic-col-wrap">{{ $priority->description ?: '—' }}</td>
                                    <td data-order="{{ (int) $priority->status }}">
                                        <span class="status-pill badge rounded-1 {{ $isActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                            {{ $isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{-- Edit + Delete only — this grid has no inline status switch;
                                             status is set from the Edit modal. --}}
                                        <div class="ic-act-group" role="group" aria-label="Row actions">
                                            <button type="button" class="ic-act ic-act--edit ip-edit-btn" aria-label="Edit priority"
                                                    data-id="{{ $priority->pk }}"
                                                    data-name="{{ $priority->priority }}"
                                                    data-description="{{ $priority->description }}"
                                                    data-status="{{ (int) $priority->status }}">
                                                <span class="ic-act__icon"><i class="bi bi-pencil-square" aria-hidden="true"></i></span>
                                                <span class="ic-act__label">Edit</span>
                                            </button>

                                            @if($inUse)
                                                <span class="ic-act ic-act--del is-disabled" aria-disabled="true"
                                                      title="In use by {{ $priority->issue_logs_count }} issue(s) — cannot be deleted">
                                                    <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                    <span class="ic-act__label">Delete</span>
                                                </span>
                                            @else
                                                <form action="{{ route('admin.issue-priorities.destroy', $priority->pk) }}"
                                                      method="POST" class="ic-delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ic-act ic-act--del" aria-label="Delete priority"
                                                            data-name="{{ $priority->priority }}">
                                                        <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                        <span class="ic-act__label">Delete</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="ic-empty">
                                        <i class="bi bi-folder-x d-block mb-2" aria-hidden="true"></i>
                                        <h6 class="fw-semibold mb-1">No Priorities Found</h6>
                                        <p class="mb-0 small">
                                            {{ filled($search) ? 'No priority matches “' . $search . '”.' : 'Get started by adding your first priority.' }}
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant B — Laravel paginates this grid (§4) --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="programme-dt-pagination">
                        {{ $priorities->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <div class="dataTables_length">
                            <label class="mb-0">Showing
                                <select id="ipPerPage" class="form-select form-select-sm" aria-label="Rows per page">
                                    @foreach($perPageOptions as $option)
                                        <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <div class="dataTables_info">of {{ number_format($priorities->total()) }} items</div>
                    </div>
                </div>
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
$(function () {
    'use strict';

    /* ── Footer: rows-per-page ───────────────────────────────────────────── */
    $('#ipPerPage').on('change', function () {
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', this.value);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    });

    /* ── Toolbar: search toggle (server-side ?q=) ────────────────────────── */
    $('#ipSearchToggle').on('click', function () {
        var $wrap = $('#ipSearchWrap');
        $wrap.toggleClass('d-none');
        if (!$wrap.hasClass('d-none')) {
            $('#ipSearchInput').trigger('focus');
        }
    });

    // Clearing the box (the native "x" or emptying it) returns to the unfiltered list.
    $('#ipSearchInput').on('search', function () {
        if (this.value === '') {
            $('#ipSearchWrap').trigger('submit');
        }
    });

    /* ── Column visibility (plain table → toggle by column index) ────────── */
    var COL_KEY = 'issuePriorityGrid:hiddenColumns:v1';
    var $table = $('#issuePrioritiesTable');

    function getHiddenCols() {
        try {
            var parsed = JSON.parse(localStorage.getItem(COL_KEY) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) { return []; }
    }

    function persistHiddenCols(cols) {
        try { localStorage.setItem(COL_KEY, JSON.stringify(cols)); } catch (e) { /* noop */ }
    }

    function applyColumnVisibility(index, visible) {
        var nth = index + 1;
        $table.find('thead th:nth-child(' + nth + '), tbody td:nth-child(' + nth + ')')
              .toggle(visible);
    }

    function buildColumnToggles() {
        var $grid = $('#issuePriorityColumnToggleGrid');
        if (!$grid.length) { return; }
        var hidden = getHiddenCols();
        $grid.empty();

        $table.find('thead th').each(function (index) {
            var title = $(this).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var visible = hidden.indexOf(index) === -1;
            applyColumnVisibility(index, visible);

            var inputId = 'ipcolvis_' + index;
            var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', visible);

            $checkbox.on('change', function () {
                var cols = getHiddenCols();
                var pos = cols.indexOf(index);
                if (this.checked) {
                    if (pos !== -1) { cols.splice(pos, 1); }
                } else if (pos === -1) {
                    cols.push(index);
                }
                persistHiddenCols(cols);
                applyColumnVisibility(index, this.checked);
            });

            $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId)
                    .append($checkbox)
                    .append($('<span></span>').text(title))
            ).appendTo($grid);
        });
    }

    buildColumnToggles();

    /* ── Delete: confirm before submitting ───────────────────────────────── */
    $(document).on('submit', '.ic-delete-form', function (e) {
        var form = this;
        if ($(form).data('confirmed')) { return; }
        e.preventDefault();

        var name = $(form).find('.ic-act--del').data('name') || 'this priority';

        if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') {
            if (window.confirm('Delete "' + name + '"? This cannot be undone.')) {
                $(form).data('confirmed', true);
                form.submit();
            }
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'Delete "' + name + '"? This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d92d20',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) {
                $(form).data('confirmed', true);
                form.submit();
            }
        });
    });

    /* ── Edit modal ──────────────────────────────────────────────────────── */
    $(document).on('click', '.ip-edit-btn', function () {
        var $btn = $(this);
        $('#edit_ip_priority').val($btn.data('name'));
        $('#edit_ip_description').val($btn.data('description') || '');
        $('#edit_ip_status').val(String($btn.data('status')) === '1' ? '1' : '0');
        $('#editPriorityForm').attr('action', "{{ url('admin/issue-priorities') }}/" + $btn.data('id'));

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editPriorityModal')).show();
    });

    /* ── Add modal: reset on close so a stale entry can't leak back in ───── */
    document.getElementById('addPriorityModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addPriorityForm').reset();
    });
});
</script>
@endpush
