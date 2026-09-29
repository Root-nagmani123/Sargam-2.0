@extends('admin.layouts.master')

@section('title', 'Useful Links')

@push('styles')
{{-- Module stylesheet, shared with the _form partial that renders inside the
     modal AND on the standalone create/edit pages.
     See docs/new-design-index-page.md §3b/§3c. --}}
<link rel="stylesheet"
      href="{{ asset('css/useful-links-admin.css') }}?v={{ @filemtime(public_path('css/useful-links-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid ul-page">
    <x-breadcrum title="Useful Links" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="ulAddBtn" data-url="{{ route('admin.setup.useful_links.create') }}">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Useful Link</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Exports — ABOVE the card, per §1. Nothing here filters by status, so the
         row keeps its place with the buttons alone on the right. --}}
    <div class="d-flex flex-wrap align-items-end justify-content-end gap-3 mb-3">
        <div class="d-flex flex-wrap align-items-end gap-2 ul-secondary-actions">
            {{-- ?q / ?cols are stamped on by ulUpdateExportLinks(), so a download
                 carries the same search and columns as the grid. --}}
            <a href="{{ route('admin.setup.useful_links.export', ['format' => 'csv']) }}"
               id="ulDownloadLink"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Download as CSV">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </a>
            <a href="{{ route('admin.setup.useful_links.export', ['format' => 'print']) }}"
               id="ulPrintLink" target="_blank" rel="noopener"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </a>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: reorder controls left, columns + search right (§2). --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4
                        programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Order</span>
                    <button type="button" class="btn programme-dt-btn-columns" id="ulSaveOrder" disabled>
                        <i class="bi bi-check2" aria-hidden="true"></i><span>Save Order</span>
                    </button>
                    <span class="ul-reorder-hint" id="ulReorderHint">Drag a row by its handle to reorder.</span>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="ulBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#ulColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- datatable-global-ui.js moves DataTables' own filter in here. --}}
                    <div id="ulDtSearch" class="programme-dt-search" data-dt-search-for="usefulLinksTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Client-side, and deliberately UNPAGINATED and UNSORTED — see
                         the DataTable options below. Drag-to-reorder only means
                         anything when every row is in the DOM in its real order. --}}
                    <table id="usefulLinksTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S.No.</th>
                                <th scope="col">Label</th>
                                <th scope="col">URL</th>
                                <th scope="col">File</th>
                                <th scope="col">Order</th>
                                <th scope="col">Opens In</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($usefulLinks as $index => $link)
                                <tr data-usefullink-id="{{ $link->id }}">
                                    <td class="text-center ul-sno">{{ $index + 1 }}</td>
                                    <td>{{ $link->label }}</td>
                                    <td class="ul-col-url">
                                        @if (filled($link->url))
                                            <a href="{{ $link->url }}" class="ul-url" target="_blank"
                                               rel="noopener noreferrer">{{ $link->url }}</a>
                                        @else
                                            <span class="ul-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($link->file_path)
                                            <a href="{{ asset('storage/' . $link->file_path) }}" class="ul-file"
                                               target="_blank" rel="noopener"
                                               title="{{ basename($link->file_path) }}">
                                                <i class="bi bi-paperclip" aria-hidden="true"></i>View File
                                            </a>
                                        @else
                                            <span class="ul-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="ul-order">
                                            <span class="ul-order__num">{{ $link->position }}</span>
                                            <span class="ul-drag-handle" draggable="true" title="Drag to reorder"
                                                  aria-label="Drag to reorder {{ $link->label }}">
                                                <i class="material-icons material-symbols-rounded" aria-hidden="true">drag_handle</i>
                                            </span>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="ul-tab{{ $link->target_blank ? ' ul-tab--new' : '' }}">
                                            {{ $link->target_blank ? 'New Tab' : 'Same Tab' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ul-act-group" role="group" aria-label="Actions for {{ $link->label }}">
                                            <button type="button" class="ul-act ul-act--edit ul-edit-btn"
                                                    data-url="{{ route('admin.setup.useful_links.edit', encrypt($link->id)) }}"
                                                    title="Edit {{ $link->label }}"
                                                    aria-label="Edit useful link {{ $link->label }}">
                                                <span class="ul-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                <span class="ul-act__label">Edit</span>
                                            </button>

                                            <form action="{{ route('admin.setup.useful_links.delete', encrypt($link->id)) }}"
                                                  method="POST" class="ul-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ul-act ul-act--del"
                                                        data-label="{{ $link->label }}"
                                                        aria-label="Delete useful link {{ $link->label }}">
                                                    <span class="ul-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                                                    <span class="ul-act__label">Delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant A — datatable-global-ui.js fills this in. Paging
                     is off, so it renders the count alone (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="usefulLinksTable"></div>
            </div>

        </div>
    </div>

    {{-- ⚠️ This modal MUST live inside @section. It previously sat between
         @endsection and @push, where Blade echoes it straight to the output
         buffer instead of into the layout: the page then had no
         #usefulLinksModal at all, so `modalEl.querySelector(...)` threw on
         DOMContentLoaded and took the whole script down with it — drag-reorder,
         Save Order and the Add/Edit modal all dead — while the stray bytes
         leaked ahead of the response. --}}
    <div class="modal fade" id="usefulLinksModal" tabindex="-1" aria-labelledby="usefulLinksModalLabel"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content ul-modal border-0 shadow">
                <div class="modal-header ul-modal-header">
                    <div>
                        <h5 class="modal-title" id="usefulLinksModalLabel">Add Useful Link</h5>
                        <p class="ul-modal-sub" id="usefulLinksModalSub">Point to a URL, or upload a file.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ul-modal-body">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading…</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Column Visibility Modal -->
    <div class="modal fade" id="ulColumnVisibilityModal" tabindex="-1"
         aria-labelledby="ulColumnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="ulColumnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="ulColumnToggleGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    var REORDER_URL = "{{ route('admin.setup.useful_links.bulk-reorder') }}";
    var TOKEN = $('meta[name="csrf-token"]').attr('content') || '';

    var $table = $('#usefulLinksTable');
    var $tbody = $table.find('tbody');
    var $saveOrder = $('#ulSaveOrder');
    var $hint = $('#ulReorderHint');

    /* ── DataTable (client-side) ─────────────────────────────────────────────
       Paging and sorting are BOTH off, on purpose. Drag-to-reorder writes
       whatever order the <tr>s are in, so if DataTables were paginating, Save
       Order would post only the visible page's ids and silently renumber just
       those; if it were sorting, the DOM order would not be the real order at
       all. This list is a short master list, so showing it whole costs nothing.

       ⚠️ `ordering: false` alone does NOT work: datatable-global-ui.js
       normalises client-side tables and flips `ordering` back to true. The
       per-column `columnDefs` route is explicitly left alone by that patch. ── */
    var dt = $table.DataTable({
        autoWidth: false,
        paging: false,
        info: true,
        columnDefs: [{ orderable: false, targets: '_all' }],
        responsive: false,
        language: {
            emptyTable: '<div class="ul-empty">' +
                '<i class="bi bi-link-45deg d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Useful Links Found</h6>' +
                '<p class="mb-0 small">Get started by adding your first useful link.</p>' +
                '</div>',
            zeroRecords: '<div class="ul-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Useful Links Found</h6>' +
                '<p class="mb-0 small">No link matches your search.</p>' +
                '</div>'
        }
    });

    /* ── Column visibility (DataTables column API) ────────────────────────────
       Hidden columns are stored by LABEL, not index: an index would point at a
       different column the moment one is added, silently hiding the wrong one. */
    var COL_KEY = 'usefulLinksGrid:hiddenColumns:v1';

    /* Header index -> the export key the server understands
       (UsefulLinksSetupController::exportColumnDefs()). Positional: '' marks a
       column that is not in the export at all — here, Action.
       ⚠️ Adding a column to the table means adding an entry here too. */
    var UL_EXPORT_COLUMN_KEYS = ['sno', 'label', 'url', 'file', 'position', 'target', ''];
    var UL_EXPORT_COL_COUNT = UL_EXPORT_COLUMN_KEYS.filter(Boolean).length;

    function ulUpdateExportLinks() {
        var keys = [];
        dt.columns().every(function () {
            var key = UL_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });

        var term = dt.search() || '';

        ['ulDownloadLink', 'ulPrintLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');

            params.delete('q');
            if (term !== '') { params.set('q', term); }

            params.delete('cols');
            // Omit ?cols= entirely while nothing is hidden — the server reads
            // "no cols" as "every column".
            if (keys.length !== UL_EXPORT_COL_COUNT) { params.set('cols', keys.join(',')); }

            var qs = params.toString();
            link.href = base + (qs ? '?' + qs : '');
        });
    }

    // Search-as-you-type has to re-stamp the links and re-check the drag lock.
    dt.on('search.dt', function () {
        ulUpdateExportLinks();
        syncReorderLock();
    });

    function getHiddenCols() {
        try {
            var parsed = JSON.parse(localStorage.getItem(COL_KEY) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) { return []; }
    }

    function persistHiddenCols(cols) {
        try { localStorage.setItem(COL_KEY, JSON.stringify(cols)); } catch (e) { /* noop */ }
    }

    function buildColumnToggles() {
        var $grid = $('#ulColumnToggleGrid');
        var hidden = getHiddenCols();

        dt.columns().every(function () {
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (title) { this.visible(hidden.indexOf(title) === -1, false); }
        });
        dt.columns.adjust();

        if (!$grid.length) { return; }
        $grid.empty();

        dt.columns().every(function () {
            var index = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'ulcolvis_' + index;
            var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(title) === -1);

            $checkbox.on('change', function () {
                var cols = getHiddenCols();
                var pos = cols.indexOf(title);
                if (this.checked) {
                    if (pos !== -1) { cols.splice(pos, 1); }
                } else if (pos === -1) {
                    cols.push(title);
                }
                persistHiddenCols(cols);
                dt.column(index).visible(this.checked, false);
                dt.columns.adjust();
                ulUpdateExportLinks();
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
    ulUpdateExportLinks();

    /* ══ Drag to reorder ════════════════════════════════════════════════════
       Only valid over the FULL list. A search hides rows, so dropping between
       two visible rows would place the dragged row somewhere unrelated in the
       real sequence — the handles are locked out while a search is active and
       the hint says why. ── */
    var draggedRow = null;
    var initialOrder = [];

    function currentOrder() {
        return $tbody.find('tr[data-usefullink-id]').map(function () {
            return parseInt(this.getAttribute('data-usefullink-id'), 10);
        }).get().filter(Boolean);
    }

    function setDirty(dirty) {
        $saveOrder.prop('disabled', !dirty);
    }

    function isSearching() {
        return (dt.search() || '') !== '';
    }

    function syncReorderLock() {
        var locked = isSearching();
        $('.ul-page').toggleClass('is-reorder-locked', locked);
        $tbody.find('.ul-drag-handle').attr('draggable', locked ? 'false' : 'true');
        $hint.text(locked
            ? 'Clear the search to reorder — dragging needs the full list.'
            : 'Drag a row by its handle to reorder.');
        if (locked) { setDirty(false); }
    }

    /* Renumber S.No. and the Order badge from the DOM after every drop, so the
       screen never shows a sequence the user has not saved yet as if it were
       the stored one — the numbers track the pending order. */
    function renumberRows() {
        $tbody.find('tr[data-usefullink-id]').each(function (i) {
            $(this).find('.ul-sno').text(i + 1);
            $(this).find('.ul-order__num').text(i + 1);
        });
    }

    syncReorderLock();
    setDirty(false);

    $tbody.on('dragstart', function (e) {
        if (isSearching()) { e.preventDefault(); return; }
        var handle = e.target.closest ? e.target.closest('.ul-drag-handle') : null;
        if (!handle) { return; }

        draggedRow = handle.closest('tr[data-usefullink-id]');
        if (!draggedRow) { return; }

        initialOrder = currentOrder();
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        e.originalEvent.dataTransfer.setData('text/plain', draggedRow.getAttribute('data-usefullink-id'));
        $(draggedRow).addClass('ul-row-dragging');
    });

    $tbody.on('dragend', function () {
        $(draggedRow).removeClass('ul-row-dragging');
        $tbody.find('tr.ul-row-over').removeClass('ul-row-over');
        draggedRow = null;
    });

    $tbody.on('dragover', function (e) {
        e.preventDefault();
        if (!draggedRow) { return; }
        var over = e.target.closest ? e.target.closest('tr[data-usefullink-id]') : null;
        if (!over || over === draggedRow) { return; }
        $tbody.find('tr.ul-row-over').removeClass('ul-row-over');
        $(over).addClass('ul-row-over');
    });

    $tbody.on('dragleave', function (e) {
        var row = e.target.closest ? e.target.closest('tr[data-usefullink-id]') : null;
        if (row) { $(row).removeClass('ul-row-over'); }
    });

    $tbody.on('drop', function (e) {
        e.preventDefault();
        if (!draggedRow) { return; }

        var dropRow = e.target.closest ? e.target.closest('tr[data-usefullink-id]') : null;
        $tbody.find('tr.ul-row-over').removeClass('ul-row-over');
        if (!dropRow || dropRow === draggedRow) { return; }

        var rect = dropRow.getBoundingClientRect();
        if (e.originalEvent.clientY > rect.top + rect.height / 2) {
            dropRow.after(draggedRow);
        } else {
            dropRow.before(draggedRow);
        }

        renumberRows();
        setDirty(JSON.stringify(initialOrder) !== JSON.stringify(currentOrder()));
    });

    $saveOrder.on('click', function () {
        var order = currentOrder();
        if (order.length < 2) { return; }

        var $btn = $(this);
        $btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
            '<span>Saving…</span>'
        );

        $.ajax({
            url: REORDER_URL,
            type: 'POST',
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
            data: JSON.stringify({ order: order }),
            success: function () {
                if (typeof toastr !== 'undefined') { toastr.success('Order updated successfully.'); }
                window.location.reload();
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-check2" aria-hidden="true"></i><span>Save Order</span>');
                if (typeof toastr !== 'undefined') {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not save the new order.');
                }
            }
        });
    });

    /* ══ Add / Edit modal ═══════════════════════════════════════════════════
       Both modes load _form.blade.php over AJAX into the same modal; only the
       title, the sub-line and the URL differ (§3c). ── */
    var modalEl = document.getElementById('usefulLinksModal');
    var $modalBody = $('#usefulLinksModal .modal-body');

    function loadForm(url, title, sub) {
        $('#usefulLinksModalLabel').text(title);
        $('#usefulLinksModalSub').text(sub);
        $modalBody.html(
            '<div class="text-center py-4">' +
            '<div class="spinner-border text-primary" role="status">' +
            '<span class="visually-hidden">Loading…</span></div></div>'
        );

        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        $.ajax({
            url: url,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (html) { $modalBody.html(html); },
            error: function () {
                $modalBody.html('<div class="alert alert-danger mb-0">Failed to load the form.</div>');
            }
        });
    }

    $('#ulAddBtn').on('click', function () {
        loadForm($(this).data('url'), 'Add Useful Link', 'Point to a URL, or upload a file.');
    });

    $(document).on('click', '.ul-edit-btn', function () {
        loadForm($(this).data('url'), 'Edit Useful Link', 'Update the link or replace its file.');
    });

    /* The form posts over AJAX so the modal can show field errors in place. */
    $(document).on('submit', '#usefulLinkForm', function (e) {
        e.preventDefault();

        var form = this;
        var $form = $(form);
        var $submit = $form.find('[type="submit"]');
        var $pairError = $form.find('.ul-pair-error');
        var $url = $form.find('#usefulLinkUrl');
        var $file = $form.find('#usefulLinkFile');
        var $removeFile = $form.find('#removeFile');

        function clearPairError() {
            $url.removeClass('is-invalid');
            $file.removeClass('is-invalid');
            $pairError.text('');
        }

        var hasUrl = $.trim($url.val() || '') !== '';
        var hasNewFile = !!($file[0] && $file[0].files && $file[0].files.length);
        var keepsFile = $form.data('hasExistingFile') === 1 && !($removeFile.length && $removeFile.is(':checked'));

        clearPairError();
        if (!hasUrl && !hasNewFile && !keepsFile) {
            $url.addClass('is-invalid');
            $file.addClass('is-invalid');
            $pairError.text('Please provide either a URL or a file.');
            return;
        }

        var caption = $submit.html();
        $submit.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
            '<span>Saving…</span>'
        );

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: new FormData(form),
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function () {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                window.location.reload();
            },
            error: function (xhr) {
                $submit.prop('disabled', false).html(caption);

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    var pair = (errors.url_or_file || errors.url || errors.file || [])[0];
                    if (pair) {
                        $url.addClass('is-invalid');
                        $file.addClass('is-invalid');
                        $pairError.text(pair);
                    }
                    $.each(errors, function (field, msgs) {
                        $form.find('[name="' + field + '"]').addClass('is-invalid');
                        if (typeof toastr !== 'undefined') { toastr.error(msgs[0]); }
                    });
                    return;
                }

                $pairError.text('Save failed. Please try again.');
            }
        });
    });

    /* ── Delete: confirm before submitting ───────────────────────────────── */
    $(document).on('submit', '.ul-delete-form', function (e) {
        var form = this;
        if ($(form).data('confirmed')) { return; }
        e.preventDefault();

        var label = $(form).find('.ul-act--del').data('label') || 'this link';

        if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') {
            if (window.confirm('Delete "' + label + '"? This cannot be undone.')) {
                $(form).data('confirmed', true);
                form.submit();
            }
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'Delete "' + label + '"? Any uploaded file is removed too.',
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
});
</script>
@endpush
