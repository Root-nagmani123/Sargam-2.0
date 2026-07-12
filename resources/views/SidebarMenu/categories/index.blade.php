@extends('admin.layouts.master')
@section('title', 'Sidebar Categories')
@section('setup_content')
@php
    $sidebarCategoryDatatableLang = [
        'emptyTable' => 'No categories found.',
        'zeroRecords' => 'No matching categories.',
        'processing' => 'Loading data…',
        'search' => 'Search:',
        'searchPlaceholder' => 'Search categories…',
        'lengthMenu' => 'Show _MENU_ entries',
        'info' => 'Showing _START_ to _END_ of _TOTAL_ categories',
        'infoEmpty' => 'No categories to display',
        'infoFiltered' => '(filtered from _MAX_ total categories)',
        'paginate' => [
            'first' => 'First',
            'last' => 'Last',
            'next' => 'Next',
            'previous' => 'Previous',
        ],
    ];
@endphp
<style>
    /* GIGW: minimum touch target ~44×44px, visible keyboard focus */
    .sidebar-categories-page .btn-gigw-touch {
        min-height: 2.75rem;
        min-width: 2.75rem;
        padding-inline: 1rem;
    }
    .sidebar-categories-page .btn-gigw-touch:focus-visible {
        outline: 3px solid #004a93;
        outline-offset: 2px;
        box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.25);
    }
    .sidebar-categories-page .modal .btn:focus-visible {
        outline: 3px solid #004a93;
        outline-offset: 2px;
    }
    .sidebar-categories-page .gigw-table-btn {
        min-height: 2.75rem;
        align-items: center;
    }
    .sidebar-categories-page .gigw-icon-only-btn {
        width: 2.75rem;
        height: 2.75rem;
        padding: 0;
    }
    .sidebar-categories-page .gigw-table-btn:focus-visible {
        outline: 3px solid #004a93;
        outline-offset: 2px;
    }
    .sidebar-categories-page .gigw-switch-touch {
        width: 3rem;
        height: 1.5rem;
        cursor: pointer;
    }
    .sidebar-categories-page .gigw-switch-touch:focus-visible {
        outline: 3px solid #004a93;
        outline-offset: 3px;
    }
    .sidebar-categories-page #sidebar-category-table_wrapper .dataTables_filter input {
        min-height: 2.75rem;
        margin-left: 0.5rem;
    }
    .sidebar-categories-page #sidebar-category-table_wrapper .dataTables_length select {
        min-height: 2.75rem;
        min-width: 5rem;
        margin-left: 0.35rem;
        margin-right: 0.35rem;
    }
    .sidebar-categories-page #sidebar-category-table_wrapper .dataTables_paginate .page-link {
        min-height: 2.75rem;
        min-width: 2.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .sidebar-categories-page #sidebar-category-table_wrapper .dataTables_paginate .page-link:focus-visible {
        outline: 3px solid #004a93;
        outline-offset: 2px;
    }
    .sidebar-categories-page #sidebar-category-table {
        --bs-table-striped-bg: rgba(0, 74, 147, 0.04);
    }

    /* Design-system polish */
    .sidebar-categories-page .btn-primary { border-radius: var(--ds-radius-1); font-weight: 600; }
    .sidebar-categories-page #sidebar-category-table thead th {
        background: var(--ds-surface-2) !important;
        color: var(--ds-ink-muted) !important;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        font-weight: 600;
        border-bottom: 1px solid var(--ds-line);
        white-space: nowrap;
    }
    .sidebar-categories-page #sidebar-category-table tbody td {
        font-size: 0.9rem;
        color: var(--ds-ink);
        vertical-align: middle;
    }
    #CategoryModal .modal-content,
    #sidebarCatColumnVisibilityModal .modal-content {
        border: 0;
        border-radius: var(--ds-radius-2);
        box-shadow: 0 10px 40px rgba(16, 24, 40, .18);
    }
    #CategoryModal .form-control,
    #CategoryModal .form-select { border-radius: var(--ds-radius-1); }
    #CategoryModal .form-control:focus,
    #CategoryModal .form-select:focus { border-color: #86b7fe; box-shadow: var(--ds-focus-ring); }
</style>
<div class="container-fluid py-3 sidebar-categories-page">
    <x-breadcrum title="Sidebar Categories" />
    <x-session_message />
    <section class="datatables" aria-labelledby="sidebar-categories-heading">
        <div class="ds-card">
            <div class="ds-card-body p-3 p-md-4">
                <header class="row align-items-center g-3 mb-3 mb-md-4 pb-3 border-bottom border-light">
                    <div class="col-12 col-md">
                        <h2 id="sidebar-categories-heading" class="h5 mb-0 fw-semibold text-body">
                            Sidebar Categories
                        </h2>
                        <p class="small text-secondary mb-0 mt-1">
                            Manage sidebar menu groups. Use a clear name and a short URL slug.
                        </p>
                    </div>
                    <div class="col-12 col-md-auto">
                        <div class="d-flex flex-wrap align-items-center gap-2 justify-content-md-end">
                            <button type="button"
                                class="btn programme-dt-btn-columns btn-gigw-touch d-inline-flex align-items-center justify-content-center gap-2"
                                id="btnSidebarCatColumns"
                                data-bs-toggle="modal" data-bs-target="#sidebarCatColumnVisibilityModal"
                                title="Show / hide columns">
                                <span>Columns</span>
                                <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                            </button>
                            <button type="button"
                                class="btn btn-primary btn-gigw-touch d-inline-flex align-items-center justify-content-center gap-2"
                                onclick="CategoryModal()">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                <span>Add category</span>
                            </button>
                        </div>
                    </div>
                </header>
                <div class="overflow-hidden">
                    <x-data-table.table
                        :columns="$columns"
                        :filters="[]"
                        ajax-route="{{ route('sidebar.categories.index') }}"
                        id="sidebar-category-table"
                        table-class="table align-middle mb-0 caption-top shadow-sm"
                        :datatable-language="$sidebarCategoryDatatableLang"
                    />
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="CategoryModal" tabindex="-1" aria-labelledby="CategoryModalLabel"
    data-bs-backdrop="static" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header border-bottom border-light py-3">
                <h2 class="modal-title h5 fw-semibold mb-0" id="CategoryModalLabel">
                    Add or edit sidebar category
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="categoryForm" action="" method="post" novalidate>
                    @csrf
                    <input type="hidden" name="id" id="categoryId">
                    <div class="form-group mb-3">
                        <label class="form-label fw-medium" for="name">
                            Name
                            <span class="text-danger" aria-hidden="true">*</span>
                            <span class="visually-hidden">(required)</span>
                        </label>
                        <input type="text" class="form-control" name="name" id="name"
                            placeholder="Enter category name" value="{{ old('name') }}"
                            autocomplete="off" maxlength="100" required aria-required="true">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-medium" for="slug">
                            Slug
                            <span class="text-danger" aria-hidden="true">*</span>
                            <span class="visually-hidden">(required)</span>
                        </label>
                        <input type="text" class="form-control font-monospace" name="slug" id="slug"
                            placeholder="e.g. training-resources" value="{{ old('slug') }}"
                            readonly required aria-required="true" aria-describedby="slug-help"
                            autocomplete="off" maxlength="100">
                        <p id="slug-help" class="form-text mb-0">
                            Generated automatically from the category name (lowercase, hyphenated).
                        </p>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-medium" for="icon">Icon</label>
                        <input type="text" class="form-control font-monospace" name="icon" id="icon"
                            placeholder="e.g. bi-house" value="{{ old('icon') }}"
                            autocomplete="off" maxlength="100" aria-describedby="icon-help">
                        <p id="icon-help" class="form-text mb-0">Bootstrap Icons class name (optional).</p>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-medium" for="order">Display order</label>
                        <input type="number" class="form-control" name="order" id="order" placeholder="0"
                            value="{{ old('order') }}" inputmode="numeric" min="0" aria-describedby="order-help">
                        <p id="order-help" class="form-text mb-0">Lower numbers appear first (optional).</p>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-medium" for="is_active">Status</label>
                        <select class="form-select" name="is_active" id="is_active" required aria-required="true">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2 gap-sm-3 mt-4 pt-2 border-top border-light">
                        <button type="submit" class="btn btn-primary btn-gigw-touch order-2 order-sm-1"
                            id="SubmitCategoryForm">
                            <i class="bi bi-check-lg me-2" aria-hidden="true"></i>Save
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-gigw-touch order-1 order-sm-2"
                            data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2" aria-hidden="true"></i>Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="sidebarCatColumnVisibilityModal" tabindex="-1" aria-labelledby="sidebarCatColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom border-light py-3">
                <h2 class="modal-title h5 fw-semibold mb-0" id="sidebarCatColumnVisibilityLabel">Column Visibility</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close dialog"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="row g-3" id="sidebarCatColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary btn-gigw-touch" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
    $(document).on('click', '.edit-btn', function () {
        let data = $(this).data('item');
        CategoryModal(data);
    })

    function CategoryModal(data = null) {
        $('input[name="_method"]').remove();
        $('#slug').removeData('manual');
        if (data) {
            $('#categoryId').val(data.id);
            $('#name').val(data.name);
            $('#slug').val(data.slug);
            $('#icon').val(data.icon);
            $('#order').val(data.order);
            $('#is_active').val(data.is_active);
            $('#categoryForm').attr('action', '/sidebar/categories/' + data.id);
            $('#categoryForm').append('<input type="hidden" name="_method" value="PUT">');
        } else {
            $('#categoryForm')[0].reset();
            $('#categoryId').val('');
            $('#categoryForm').attr('action', '/sidebar/categories');
        }
        $('#CategoryModal').modal('show');
    }

    $('#name').on('keyup', function () {
        if ($('#slug').data('manual') !== true) {
            let slug = $(this).val()
                .toLowerCase()
                .trim()
                .replace(/ /g, '-')
                .replace(/[^\w-]+/g, '')
                .replace(/--+/g, '-');

            $('#slug').val(slug);
        }
    });

    $('#slug').on('keyup', function () {
        $(this).data('manual', true);
    });

    $(document).ready(function () {
        $.validator.addMethod("nameRegex", function(value, element) {
            return this.optional(element) || /^[A-Za-z .'-]+$/.test(value);
        }, "Name can only contain letters, spaces, ., ' and -.");

        // Slug validation (only lowercase, dash)
        $.validator.addMethod("slugRegex", function(value, element) {
            return this.optional(element) || /^[a-z0-9-]+$/.test(value);
        }, "Slug can only contain lowercase letters, numbers and hyphens.");


        $("#categoryForm").validate({
            ignore: ".ignore",
            rules: {
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100,
                    nameRegex: true,
                },
                slug: {
                    required: true,
                    minlength: 2,
                    maxlength: 100,
                    slugRegex: true
                },
                icon: {
                    maxlength: 100
                },
                order: {
                    required: false,
                    digits: true
                },
                is_active: {
                    required: true
                }
            },
            messages: {
                name: {
                    required: "Please enter category name",
                    minlength: "Name must be at least 2 characters",
                    maxlength: "Name must be less than 100 characters"
                },
                slug: {
                    required: "Slug is required",
                    minlength: "Slug must be at least 2 characters",
                    maxlength: "Slug must be less than 100 characters"
                },
                icon: {
                    maxlength: "Icon must be less than 100 characters"
                },
                order: {
                    digits: "Order must be a number"
                },
                is_active: {
                    required: "Please select status"
                }
            },
            errorClass: "is-invalid",
            validClass: "is-valid",
            errorElement: "div",
            highlight: function (element) {
                $(element).addClass("is-invalid").removeClass("is-valid");
            },
            unhighlight: function (element) {
                $(element).removeClass("is-invalid").addClass("is-valid");
            },
            errorPlacement: function (error, element) {
                error.addClass("invalid-feedback");
                element.closest(".form-group").append(error);
            },
            submitHandler: function (form) {
                let btn = $("#SubmitCategoryForm");
                btn.prop("disabled", true);
                btn.attr("aria-busy", "true");
                btn.html(
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                    '<span>Processing…</span>'
                );
                form.submit();
            }
        });

        $(document).on('change', '.sidebar-category-status-toggle', function () {
            let id = $(this).data('id');
            let value = $(this).is(':checked') ? 1 : 0;
            let column = $(this).data('column');
            
            $.ajax({
                url: "{{ route('sidebar.categories.status', ':id') }}".replace(':id', id),
                type: "GET",
                data: {
                    _token: "{{ csrf_token() }}",
                    is_active: value
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                    $('#sidebar-category-table').DataTable().ajax.reload();
                },
                error: function (xhr) {
                    toastr.error('Something went wrong');
                }
            });
        });
    });

    /* ---- Column Visibility (drives the live DataTable via its API) ----
       Page-scoped: reads columns from the #sidebar-category-table DataTable the
       shared data-table component initializes, and toggles them via the
       .column().visible() API. Persisted to localStorage. */
    (function () {
        var TABLE = '#sidebar-category-table';
        var storageKey = 'sidebarCategories:hiddenColumns:v1';

        function getHidden() {
            try {
                var raw = localStorage.getItem(storageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) { return []; }
        }
        function persist(arr) {
            try { localStorage.setItem(storageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupSidebarCatColumns(dt) {
            if (!dt) { return; }
            var hidden = getHidden();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#sidebarCatColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();

            dt.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }

                var inputId = 'sidebarcatcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = getHidden();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    persist(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        (function whenReady(tries) {
            tries = tries || 0;
            if ($.fn.DataTable && $.fn.DataTable.isDataTable(TABLE)) {
                setupSidebarCatColumns($(TABLE).DataTable());
            } else if (tries < 100) {
                setTimeout(function () { whenReady(tries + 1); }, 100);
            }
        })(0);
    })();
</script>
@endsection