@extends('admin.layouts.master')

@section('title', 'Manage Categories')

@push('styles')
<style>
    /* ── Manage Categories — "new design" index chrome (docs/new-design-index-page.md §3b).
       Everything is scoped to .issue-cat-page so nothing leaks into other modules. ── */

    /* Status column: soft badge only (display). The theme ships the *-subtle
       backgrounds but not the text-*-emphasis colours, so tint the label here. */
    .issue-cat-page .status-pill {
        padding: 0.4em 0.85em;
        font-weight: 600;
        font-size: 0.75rem;
    }
    .issue-cat-page .status-pill.bg-success-subtle { color: #146c43; }
    .issue-cat-page .status-pill.bg-danger-subtle  { color: #b02a37; }

    /* ── Row actions: icon over label ──
       Each action is an equal-width column holding a fixed-height icon strip
       above its caption. Equal widths keep the icon row evenly spaced even
       though "Edit" and "Deactivate" are very different label widths; the fixed
       strip keeps the glyphs and the switch on one baseline. */
    .issue-cat-page .ic-act-group {
        display: inline-flex;
        align-items: stretch;   /* equal heights → icon strips stay in line */
        justify-content: flex-start;
        gap: 0.25rem;
    }

    .issue-cat-page .ic-act {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        gap: 4px;
        min-width: 62px;        /* ≈ the widest caption ("Deactivate") */
        font-size: 0.72rem;
        font-weight: 500;
        line-height: 1;
        text-decoration: none;
        background: transparent;
        border: 0;
        padding: 0;
        margin: 0;
        cursor: pointer;
    }

    .issue-cat-page .ic-act__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 22px;           /* one strip for glyphs AND the switch */
    }
    .issue-cat-page .ic-act__icon > i {
        font-size: 1.1rem;
        line-height: 1;
    }
    /* No .form-check ancestor here, so neither Bootstrap's -2.5em nor
       custom.css:106's -2.375rem applies — but pin it anyway. */
    .issue-cat-page .ic-act__icon .form-check-input {
        margin: 0;
        float: none;
    }

    .issue-cat-page .ic-act__label { white-space: nowrap; }

    .issue-cat-page .ic-act--edit { color: #2563eb; }
    .issue-cat-page .ic-act--del { color: var(--bs-danger, #dc3545); }
    .issue-cat-page .ic-act--del.is-disabled {
        color: #98a2b3;
        cursor: not-allowed;
        opacity: 0.65;
    }
    .issue-cat-page .ic-act--toggle { color: #475467; }

    /* The delete <form> is only a wrapper — it must not add a box of its own. */
    .issue-cat-page .ic-delete-form {
        display: flex;
        margin: 0;
        padding: 0;
    }

    /* Keep the Action header over its content. */
    .issue-cat-page #issueCategoriesTable th:last-child,
    .issue-cat-page #issueCategoriesTable td:last-child {
        text-align: left;
        white-space: nowrap;
    }

    /* Sortable headers — same caret language as the DataTables pages. */
    .issue-cat-page .ic-sort {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        color: inherit;
        text-decoration: none;
        white-space: nowrap;
    }
    .issue-cat-page .ic-sort i { font-size: 0.7rem; color: #98a2b3; }
    .issue-cat-page .ic-sort.is-active i { color: #004384; }
    .issue-cat-page .ic-sort:hover { color: #004384; }

    /* Search: an icon button that reveals the server-side search input
       (the toggle variant — this grid is paginated by Laravel, not DataTables). */
    .issue-cat-page .ic-search-wrap { position: relative; width: 260px; max-width: 100%; }
    .issue-cat-page .ic-search-input {
        width: 100%;
        border: 1px solid #d0d5dd;
        border-radius: 8px;
        padding: 0.45rem 0.75rem;
        font-size: 0.875rem;
        color: #101828;
    }
    .issue-cat-page .ic-search-input:focus {
        outline: 0;
        border-color: #004384;
        box-shadow: 0 0 0 0.2rem rgba(0, 67, 132, 0.12);
    }

    /* ── Add / Edit modals ──
       One visual language for both: a tinted field card holding the labelled
       controls, a red Cancel and a solid brand submit. */
    #addCategoryModal .modal-content,
    #editCategoryModal .modal-content { border-radius: 12px; }

    .ic-modal-header {
        border-bottom: 1px solid #eaecf0;
        padding: 1rem 1.25rem;
    }
    .ic-modal-header .modal-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #101828;
        margin: 0;
    }

    .ic-modal-body { padding: 1.25rem; }

    /* The tinted card each field group lives in. */
    .ic-field-card {
        position: relative;
        background: #eef1fc;
        border-radius: 10px;
        padding: 1rem;
    }
    .ic-field-card + .ic-field-card { margin-top: 1rem; }

    .ic-form-label {
        display: block;
        margin-bottom: 0.375rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #1f2937;
    }
    .ic-req { color: #dc2626; margin-left: 1px; }

    #addCategoryModal .ic-control,
    #editCategoryModal .ic-control {
        background-color: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        color: #101828;
        box-shadow: none;
    }
    #addCategoryModal .ic-control::placeholder,
    #editCategoryModal .ic-control::placeholder { color: #9ca3af; }
    #addCategoryModal .ic-control:focus,
    #editCategoryModal .ic-control:focus {
        border-color: #004384;
        box-shadow: 0 0 0 0.2rem rgba(0, 67, 132, 0.12);
    }
    #addCategoryModal textarea.ic-control,
    #editCategoryModal textarea.ic-control { resize: vertical; min-height: 78px; }

    /* Repeat controls, bottom-right inside the card.
       Plain (non-!important) display so jQuery toggle()/show()/hide() work — see
       the .d-flex trap in docs/new-design-index-page.md §9. */
    .ic-field-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    .ic-field-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        flex-shrink: 0;
        border: 0;
        border-radius: 7px;
        color: #fff;
        font-size: 0.9rem;
        line-height: 1;
        transition: filter 0.15s ease;
    }
    .ic-field-btn:hover { filter: brightness(0.92); }
    .ic-field-btn--remove { background: #ef4444; }
    .ic-field-btn--add { background: #2563eb; }

    .ic-modal-footer {
        border-top: 0;
        justify-content: end;
        gap: 0.75rem;
        padding: 0.25rem 1.25rem 1.25rem;
    }
    #addCategoryModal .ic-btn-cancel,
    #editCategoryModal .ic-btn-cancel {
        min-width: 108px;
        border: 1px solid #fca5a5;
        border-radius: 8px;
        background: #fff;
        color: #dc2626;
        font-weight: 500;
    }
    #addCategoryModal .ic-btn-cancel:hover,
    #editCategoryModal .ic-btn-cancel:hover { background: #fef2f2; border-color: #f87171; }
    #addCategoryModal .ic-btn-submit,
    #editCategoryModal .ic-btn-submit {
        min-width: 140px;
        border: 1px solid #004384;
        border-radius: 8px;
        background: #004384;
        color: #fff;
        font-weight: 600;
    }
    #addCategoryModal .ic-btn-submit:hover,
    #editCategoryModal .ic-btn-submit:hover { background: #00356a; border-color: #00356a; }

    .issue-cat-page .ic-empty { padding: 2.5rem 1rem; text-align: center; color: #667085; }
    .issue-cat-page .ic-empty i { font-size: 2.5rem; opacity: 0.35; }

    @media print {
        .modern-breadcrumb-wrapper,
        .issue-cat-page .ic-toolbar,
        .issue-cat-page .ic-secondary-actions,
        .issue-cat-page .programme-dt-footer,
        .issue-cat-page th:last-child,
        .issue-cat-page td:last-child { display: none !important; }
    }
</style>
@endpush

@section('setup_content')
@php
    // Query string carried across sort / paging / per-page changes.
    $baseQuery = ['q' => $search, 'per_page' => $perPage, 'sort' => $sortKey, 'dir' => $sortDir];

    $sortUrl = function (string $key) use ($baseQuery, $sortKey, $sortDir) {
        $dir = ($sortKey === $key && $sortDir === 'asc') ? 'desc' : 'asc';

        return request()->fullUrlWithQuery(array_merge($baseQuery, ['sort' => $key, 'dir' => $dir, 'page' => 1]));
    };
@endphp
<div class="container-fluid issue-cat-page">
    <x-breadcrum title="Manage Categories" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="icAddBtn" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Category</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1 --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 ic-secondary-actions">
        <a href="{{ route('admin.issue-categories.export', ['format' => 'csv', 'q' => $search, 'sort' => $sortKey, 'dir' => $sortDir]) }}"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Download as CSV">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </a>
        <a href="{{ route('admin.issue-categories.export', ['format' => 'print', 'q' => $search, 'sort' => $sortKey, 'dir' => $sortDir]) }}"
           target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: columns + search on the right (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 ic-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="icBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#icColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <button type="button" class="btn programme-dt-btn-columns" id="icSearchToggle"
                            aria-label="Search categories" title="Search"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>

                    <form method="GET" action="{{ route('admin.issue-categories.index') }}"
                          class="ic-search-wrap {{ filled($search) ? '' : 'd-none' }}" id="icSearchWrap">
                        <input type="hidden" name="per_page" value="{{ $perPage }}">
                        <input type="hidden" name="sort" value="{{ $sortKey }}">
                        <input type="hidden" name="dir" value="{{ $sortDir }}">
                        <input type="search" class="ic-search-input" id="icSearchInput" name="q"
                               value="{{ $search }}" placeholder="Search categories…" autocomplete="off"
                               aria-label="Search categories">
                    </form>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="issueCategoriesTable" data-sargam-dt-ui="false"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">
                                    <a class="ic-sort {{ $sortKey === 'category' ? 'is-active' : '' }}" href="{{ $sortUrl('category') }}">
                                        Category
                                        <i class="bi {{ $sortKey === 'category' && $sortDir === 'desc' ? 'bi-caret-down-fill' : 'bi-caret-up-fill' }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th scope="col">
                                    <a class="ic-sort {{ $sortKey === 'description' ? 'is-active' : '' }}" href="{{ $sortUrl('description') }}">
                                        Description
                                        <i class="bi {{ $sortKey === 'description' && $sortDir === 'desc' ? 'bi-caret-down-fill' : 'bi-caret-up-fill' }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th scope="col">
                                    <a class="ic-sort {{ $sortKey === 'sub_categories' ? 'is-active' : '' }}" href="{{ $sortUrl('sub_categories') }}">
                                        Sub-Categories
                                        <i class="bi {{ $sortKey === 'sub_categories' && $sortDir === 'desc' ? 'bi-caret-down-fill' : 'bi-caret-up-fill' }}" aria-hidden="true"></i>
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
                            @forelse($categories as $category)
                                @php $isActive = (int) $category->status === 1; @endphp
                                <tr>
                                    <td>{{ $categories->firstItem() + $loop->index }}</td>
                                    <td>{{ $category->issue_category }}</td>
                                    <td>{{ $category->description ?: '—' }}</td>
                                    <td>{{ $category->subCategories->count() }}</td>
                                    <td data-order="{{ (int) $category->status }}">
                                        <span class="status-pill badge {{ $isActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                            {{ $isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{-- Every action is the same stack: a fixed-height icon strip over a
                                             caption, in an equal-width column — so the icons keep an even rhythm
                                             no matter how wide the captions are. --}}
                                        <div class="ic-act-group" role="group" aria-label="Row actions">
                                            <button type="button" class="ic-act ic-act--edit ic-edit-btn" aria-label="Edit category"
                                                    data-id="{{ $category->pk }}"
                                                    data-name="{{ $category->issue_category }}"
                                                    data-description="{{ $category->description }}"
                                                    data-status="{{ (int) $category->status }}">
                                                <span class="ic-act__icon"><i class="bi bi-pencil-square" aria-hidden="true"></i></span>
                                                <span class="ic-act__label">Edit</span>
                                            </button>

                                            {{-- NB: no .form-check/.form-switch wrapper. Those pull the input left by
                                                 -2.375rem (custom.css:106) for the switch-beside-label layout, which
                                                 would knock it off-centre here. The .status-toggle skin is keyed on
                                                 the input itself, so it still applies. --}}
                                            <label class="ic-act ic-act--toggle">
                                                <span class="ic-act__icon">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                           data-table="issue_category_master" data-column="status"
                                                           data-id="{{ $category->pk }}" {{ $isActive ? 'checked' : '' }}>
                                                </span>
                                                <span class="ic-act__label">{{ $isActive ? 'Activate' : 'Deactivate' }}</span>
                                            </label>

                                            @if($isActive)
                                                {{-- destroy() refuses to delete an active category — mirror that guard here. --}}
                                                <span class="ic-act ic-act--del is-disabled" aria-disabled="true"
                                                      title="Deactivate this category before deleting it">
                                                    <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                    <span class="ic-act__label">Delete</span>
                                                </span>
                                            @else
                                                <form action="{{ route('admin.issue-categories.destroy', $category->pk) }}"
                                                      method="POST" class="ic-delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ic-act ic-act--del" aria-label="Delete category"
                                                            data-name="{{ $category->issue_category }}">
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
                                    <td colspan="6" class="ic-empty">
                                        <i class="bi bi-folder-x d-block mb-2" aria-hidden="true"></i>
                                        <h6 class="fw-semibold mb-1">No Categories Found</h6>
                                        <p class="mb-0 small">
                                            {{ filled($search) ? 'No category matches “' . $search . '”.' : 'Get started by creating your first complaint category.' }}
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
                        {{ $categories->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <div class="dataTables_length">
                            <label class="mb-0">Showing
                                <select id="icPerPage" class="form-select form-select-sm" aria-label="Rows per page">
                                    @foreach($perPageOptions as $option)
                                        <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <div class="dataTables_info">of {{ number_format($categories->total()) }} items</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add Category Modal (supports adding several categories in one go) -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            {{-- novalidate: the submit handler below drops fully-blank extra cards.
                 Native `required` would block submit first and the user could never
                 get past an extra card they added and left empty. --}}
            <form action="{{ route('admin.issue-categories.store') }}" method="POST" novalidate>
                @csrf
                <div class="modal-header ic-modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ic-modal-body">
                    <div id="categoryFieldsContainer">
                        {{-- Template group. Clones of THIS node become groups 1..n, so keep it
                             free of any state the clone shouldn't inherit — syncFieldControls()
                             decides +/− visibility for every group after each change. --}}
                        <div class="ic-field-card category-field-group" data-index="0">
                            <div class="mb-3">
                                <label class="ic-form-label">Complaint<span class="ic-req">*</span></label>
                                <input type="text" class="form-control ic-control complaint-field"
                                       name="categories[0][issue_category]" placeholder="e.g. Accounts"
                                       maxlength="255" required>
                            </div>
                            <div class="mb-0">
                                <label class="ic-form-label">Description<span class="ic-req">*</span></label>
                                <textarea class="form-control ic-control description-field" rows="3"
                                          name="categories[0][description]"
                                          placeholder="e.g. Lorem Ipsum dolor sit amet" required></textarea>
                            </div>
                            <div class="ic-field-actions">
                                <button type="button" class="ic-field-btn ic-field-btn--remove remove-field-btn"
                                        title="Remove this category" aria-label="Remove this category">
                                    <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="ic-field-btn ic-field-btn--add add-field-btn"
                                        title="Add another category" aria-label="Add another category">
                                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ic-modal-footer">
                    <button type="button" class="btn ic-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ic-btn-submit">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header ic-modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                {{-- Same card / label / control language as Add — only the repeat
                     controls are absent, because Edit works on one row. --}}
                <div class="modal-body ic-modal-body">
                    <div class="ic-field-card">
                        <div class="mb-3">
                            <label for="edit_issue_category" class="ic-form-label">Complaint<span class="ic-req">*</span></label>
                            <input type="text" class="form-control ic-control" id="edit_issue_category" name="issue_category"
                                   placeholder="e.g. Accounts" maxlength="255" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="ic-form-label">Description</label>
                            <textarea class="form-control ic-control" id="edit_description" name="description" rows="3"
                                      placeholder="e.g. Lorem Ipsum dolor sit amet"></textarea>
                        </div>
                        <div class="mb-0">
                            <label for="edit_status" class="ic-form-label">Status<span class="ic-req">*</span></label>
                            <select class="form-select ic-control" id="edit_status" name="status" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ic-modal-footer">
                    <button type="button" class="btn ic-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ic-btn-submit">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="icColumnVisibilityModal" tabindex="-1" aria-labelledby="icColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="icColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="issueCatColumnToggleGrid"></div>
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
    $('#icPerPage').on('change', function () {
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', this.value);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    });

    /* ── Toolbar: search toggle (server-side ?q=) ────────────────────────── */
    $('#icSearchToggle').on('click', function () {
        var $wrap = $('#icSearchWrap');
        $wrap.toggleClass('d-none');
        if (!$wrap.hasClass('d-none')) {
            $('#icSearchInput').trigger('focus');
        }
    });

    // Clearing the box (the native "x" or emptying it) returns to the unfiltered list.
    $('#icSearchInput').on('search', function () {
        if (this.value === '') {
            $('#icSearchWrap').trigger('submit');
        }
    });

    /* ── Column visibility (plain table → toggle by column index) ────────── */
    var COL_KEY = 'issueCatGrid:hiddenColumns:v1';
    var $table = $('#issueCategoriesTable');

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
        var $grid = $('#issueCatColumnToggleGrid');
        if (!$grid.length) { return; }
        var hidden = getHiddenCols();
        $grid.empty();

        $table.find('thead th').each(function (index) {
            var title = $(this).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var visible = hidden.indexOf(index) === -1;
            applyColumnVisibility(index, visible);

            var inputId = 'iccolvis_' + index;
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

    /* ── Status toggle: repaint the row (badge + caption + delete guard) ──
       custom.js does the AJAX; the badge and the switch live in different
       columns, so reload rather than hand-mirroring them. ── */
    $(document).ajaxSuccess(function (event, xhr, settings) {
        var url = (settings && settings.url) ? settings.url : '';
        if (url.indexOf('toggle-status') === -1 && url.indexOf('toggleStatus') === -1) { return; }
        setTimeout(function () { window.location.reload(); }, 600);
    });

    /* ── Delete: confirm before submitting ───────────────────────────────── */
    $(document).on('submit', '.ic-delete-form', function (e) {
        var form = this;
        if ($(form).data('confirmed')) { return; }
        e.preventDefault();

        var name = $(form).find('.ic-act--del').data('name') || 'this category';

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
    $(document).on('click', '.ic-edit-btn', function () {
        var $btn = $(this);
        $('#edit_issue_category').val($btn.data('name'));
        $('#edit_description').val($btn.data('description') || '');
        $('#edit_status').val(String($btn.data('status')) === '1' ? '1' : '0');
        $('#editCategoryForm').attr('action', "{{ url('admin/issue-categories') }}/" + $btn.data('id'));

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editCategoryModal')).show();
    });

    /* ── Add modal: repeatable Complaint / Description cards ─────────────── */

    /* Single source of truth for the repeat controls and the field names, run
       after every add/remove. Deriving both from the current DOM (rather than
       nudging the previous/next card) means a clone can't inherit stale state. */
    function syncFieldCards() {
        var $groups = $('#categoryFieldsContainer .category-field-group');
        var last = $groups.length - 1;

        $groups.each(function (index) {
            $(this).attr('data-index', index);
            $(this).find('.complaint-field').attr('name', 'categories[' + index + '][issue_category]');
            $(this).find('.description-field').attr('name', 'categories[' + index + '][description]');

            // Remove only once there is something left to keep; add only on the last card.
            $(this).find('.remove-field-btn').toggle($groups.length > 1);
            $(this).find('.add-field-btn').toggle(index === last);
        });
    }

    $(document).on('click', '.add-field-btn', function () {
        var container = $('#categoryFieldsContainer');
        var newGroup = container.find('.category-field-group').first().clone();

        newGroup.find('.complaint-field, .description-field').val('').prop('disabled', false);
        newGroup.find('.is-invalid').removeClass('is-invalid');
        newGroup.find('.invalid-feedback').remove();

        container.append(newGroup);
        syncFieldCards();
        newGroup.find('.complaint-field').trigger('focus');
    });

    $(document).on('click', '.remove-field-btn', function () {
        var container = $('#categoryFieldsContainer');
        if (container.find('.category-field-group').length <= 1) { return; }

        $(this).closest('.category-field-group').fadeOut(200, function () {
            $(this).remove();
            syncFieldCards();
        });
    });

    $('#addCategoryModal').on('hidden.bs.modal', function () {
        var container = $('#categoryFieldsContainer');
        container.find('.category-field-group:not(:first)').remove();
        container.find('input, textarea').val('').prop('disabled', false);
        container.find('.is-invalid').removeClass('is-invalid');
        container.find('.invalid-feedback').remove();
        syncFieldCards();
    });

    syncFieldCards();

    $('#addCategoryModal form').on('submit', function (e) {
        var form = $(this);
        var isValid = true;
        var hasData = false;

        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').remove();

        $('#categoryFieldsContainer .category-field-group').each(function () {
            var complaint = $(this).find('.complaint-field').val().trim();
            var description = $(this).find('.description-field').val().trim();
            if (!complaint && !description) { return; }

            hasData = true;

            if (!complaint) {
                isValid = false;
                var $complaint = $(this).find('.complaint-field').addClass('is-invalid');
                if (!$complaint.next('.invalid-feedback').length) {
                    $complaint.after('<div class="invalid-feedback">Complaint field is required.</div>');
                }
            }
            if (!description) {
                isValid = false;
                var $description = $(this).find('.description-field').addClass('is-invalid');
                if (!$description.next('.invalid-feedback').length) {
                    $description.after('<div class="invalid-feedback">Description field is required.</div>');
                }
            }
        });

        if (!hasData || !isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: hasData ? 'Validation Error' : 'No Data',
                text: hasData
                    ? 'Please fill all required fields for each category entry.'
                    : 'Please add at least one category entry.',
                confirmButtonColor: '#004384'
            });
            return false;
        }

        // Drop half-filled groups so the store() loop never receives empty rows.
        $('#categoryFieldsContainer .category-field-group').each(function () {
            var complaint = $(this).find('.complaint-field').val().trim();
            var description = $(this).find('.description-field').val().trim();
            if (!complaint || !description) {
                // textarea too — a disabled control is not submitted.
                $(this).find('input, textarea').prop('disabled', true);
            }
        });
    });
});
</script>
@endpush
