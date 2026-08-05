@extends('admin.layouts.master')

@section('title', 'Manage Categories')

@push('styles')
{{-- Shared Centcom index chrome — same file the Manage Sub-Categories grid uses,
     so the two pages cannot drift apart. See docs/new-design-index-page.md §3b/§3c. --}}
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
@endphp
<div class="container-fluid ic-page">
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
        <div class="modal-content ic-modal border-0 shadow">
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
        <div class="modal-content ic-modal border-0 shadow">
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
