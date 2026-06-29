@extends('admin.layouts.master')
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <style>
        #MenuGroupModal .choices {
            width: 100%;
            margin-bottom: 0;
        }

        #MenuGroupModal .modal-content {
            border: 0;
            box-shadow: 0 1rem 2rem rgba(2, 32, 71, 0.16) !important;
        }

        #MenuGroupModal .modal-header {
            border-bottom: 1px solid #eef2f7 !important;
            padding: 1rem 1rem 0.85rem;
        }

        #MenuGroupModal .modal-body {
            padding: 1rem;
            background: linear-gradient(180deg, #fafcff 0%, #ffffff 55%);
        }

        #MenuGroupModal .form-label {
            font-weight: 600;
            color: #1f2a37;
            margin-bottom: 0.4rem;
        }

        /* Do not combine .form-select on .choices__inner — Bootstrap’s chevron + Choices arrow = double arrows.
           Strip any select background chevron; Choices provides its own. */
        #MenuGroupModal .choices .choices__inner,
        #MenuGroupModal .choices__inner.form-select {
            background-image: none !important;
            -webkit-appearance: none !important;
            appearance: none !important;
            min-height: calc(1.5em + 0.75rem + 2px);
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            background-color: #fff;
        }

        #MenuGroupModal .choices__list--dropdown {
            z-index: 2000;
            border-radius: 0.375rem;
            margin-top: 2px;
            border: 1px solid #d7e1ef;
            overflow: hidden;
        }

        #MenuGroupModal .choices__list--dropdown .choices__input {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            margin: 0.5rem;
            width: calc(100% - 1rem) !important;
        }

        #MenuGroupModal .choices.is-invalid .choices__inner,
        #MenuGroupModal .choices.has-error .choices__inner {
            border-color: var(--bs-form-invalid-border-color, #dc3545);
        }

        /* Icon row: keep preview + Choices on one line */
        #MenuGroupModal .menu-group-icon-select-col .choices {
            min-width: 0;
        }

        #MenuGroupModal .mg-icon-option {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            width: 100%;
            min-height: 1.5rem;
        }

        #MenuGroupModal .mg-icon-option .mg-icon-glyph {
            font-size: 1.15rem;
            line-height: 1.1;
            color: #0d6efd;
            width: 1.2rem;
            text-align: center;
            flex: 0 0 1.2rem;
        }

        #MenuGroupModal .mg-icon-option .mg-icon-label {
            font-family: var(--bs-font-sans-serif);
            font-size: 0.92rem;
            color: #1f2a37;
            letter-spacing: 0.01em;
        }

        #MenuGroupModal .choices__list--dropdown .choices__item--choice {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        #MenuGroupModal .choices__list--dropdown .choices__item--choice.is-highlighted {
            background-color: #edf4ff;
            color: #0d47a1;
        }

        #MenuGroupModal .form-control,
        #MenuGroupModal .form-select {
            border-color: #d9e2ef;
            box-shadow: none !important;
        }
    </style>
@endpush
@section('title', 'Sidebar Menu Groups')
@section('setup_content')
    <div class="container-fluid">
        <x-breadcrum title="Sidebar Menu Groups" />
        <x-session_message />
        <div class="datatables">
            <div class="card" >
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="row">
                            <div class="col-6">
                                <h4>Sidebar Menu Groups</h4>
                            </div>
                            <div class="col-6">
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <a href="#" class="btn btn-primary d-flex align-items-center"
                                        onclick="MenuGroupModal()">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 20px; vertical-align: middle;">add</i>
                                        Add Menu Group
                                    </a>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <x-data-table.table :columns="$columns" :filters="[]"
                            ajax-route="{{route('sidebar.menu-groups.index')}}" id="sidebar-menu-group-table" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="MenuGroupModal" tabindex="-1" aria-labelledby="MenuGroupModalLabel"
        data-bs-backdrop="static" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content rounded-3 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="MenuGroupModalLabel">Add / Edit Sidebar Menu Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <form id="menuGroupForm" action="" method="POST">
                        @csrf
                        <div class="form-group mb-2">
                            <label class="form-label" for="category_id">Category <span class="text-danger">*</span></label>
                            <select class="w-100 menu-group-choices" name="category_id" id="category_id">
                                <option value="">Select Category</option>
                                @if(isset($categories) && $categories->count() > 0)
                                    @forelse($categories as $category)
                                        <option value="{{$category->id}}">{{$category->name}}</option>
                                    @empty
                                        <option value="">No Category Found</option>
                                    @endforelse
                                @else
                                    <option value="">No Category Found</option>
                                @endif
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="name"
                                placeholder="Enter menu group name" value="{{old('name')}}">
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label" for="icon">Icon <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-start gap-2 flex-wrap">
                                <span class="flex-shrink-0 pt-1 d-inline-flex align-items-center justify-content-center text-primary"
                                    style="min-width: 2.5rem; min-height: 2.5rem;" aria-hidden="true">
                                    <i id="iconPreview"
                                        class="material-icons menu-icon material-symbols-rounded fs-4 text-muted">apps</i>
                                </span>
                                <div class="flex-grow-1 menu-group-icon-select-col" style="min-width: 12rem;">
                                    <select class="w-100 menu-group-choices" name="icon" id="icon">
                                        <option value="">Select icon</option>
                                        @isset($materialIcons)
                                            @foreach ($materialIcons as $iconName)
                                                <option value="{{ $iconName }}"
                                                    @selected(old('icon') === $iconName)>{{ $iconName }}</option>
                                            @endforeach
                                        @endisset
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label" for="order">Order</label>
                            <input type="number" class="form-control" name="order" id="order" placeholder="0"
                                value="{{old('order')}}">
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" name="is_active" id="is_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-success d-inline-flex align-items-center"
                                id="SubmitMenuGroupForm"><i
                                    class="material-icons material-symbols-rounded me-2"
                                    style="font-size: 20px;">save</i>Save</button>
                            <button type="button" class="btn btn-secondary d-inline-flex align-items-center"
                                data-bs-dismiss="modal"><i
                                    class="material-icons material-symbols-rounded me-2"
                                    style="font-size: 20px;">cancel</i>Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        function menuGroupDestroyChoices(el) {
            if (!el) {
                return;
            }
            if (el._mgChoices) {
                try {
                    el._mgChoices.destroy();
                } catch (e) {
                }
                el._mgChoices = null;
            }
        }

        function menuGroupEscapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function menuGroupClassNamesToString(value) {
            if (Array.isArray(value)) {
                return value.join(' ');
            }
            return String(value || '');
        }

        function menuGroupInitChoices(el, placeholderText, searchPlaceholder, renderWithIcon) {
            if (!el || typeof window.Choices === 'undefined') {
                return null;
            }
            menuGroupDestroyChoices(el);
            var useIconTemplate = !!renderWithIcon;
            var instance = new Choices(el, {
                removeItemButton: false,
                shouldSort: false,
                searchEnabled: true,
                searchPlaceholderValue: searchPlaceholder || 'Search…',
                placeholder: true,
                placeholderValue: placeholderText || 'Select…',
                itemSelectText: '',
                allowHTML: useIconTemplate,
                shouldFlip: true,
                callbackOnCreateTemplates: useIconTemplate ? function (template) {
                    return {
                        item: function (classNames, data) {
                            var value = menuGroupEscapeHtml(data.value);
                            var label = menuGroupEscapeHtml(data.label);
                            var itemClass = menuGroupClassNamesToString(classNames.item);
                            var itemSelectableClass = menuGroupClassNamesToString(classNames.itemSelectable);
                            return template(
                                '<div class="' + itemClass + ' ' + itemSelectableClass + '" ' +
                                'data-item data-id="' + data.id + '" data-value="' + value + '" ' +
                                (data.active ? 'aria-selected="true"' : '') + '>' +
                                '<span class="mg-icon-option">' +
                                '<i class="material-icons material-symbols-rounded mg-icon-glyph">' + value + '</i>' +
                                '<span class="mg-icon-label">' + label + '</span>' +
                                '</span></div>'
                            );
                        },
                        choice: function (classNames, data) {
                            var value = menuGroupEscapeHtml(data.value);
                            var label = menuGroupEscapeHtml(data.label);
                            var itemClass = menuGroupClassNamesToString(classNames.item);
                            var itemChoiceClass = menuGroupClassNamesToString(classNames.itemChoice);
                            return template(
                                '<div class="' + itemClass + ' ' + itemChoiceClass + '" ' +
                                'data-select-text="" data-choice ' +
                                'data-id="' + data.id + '" data-value="' + value + '" ' +
                                (data.disabled ? 'data-choice-disabled aria-disabled="true"' : 'data-choice-selectable') + '>' +
                                '<span class="mg-icon-option">' +
                                '<i class="material-icons material-symbols-rounded mg-icon-glyph">' + value + '</i>' +
                                '<span class="mg-icon-label">' + label + '</span>' +
                                '</span></div>'
                            );
                        }
                    };
                } : null,
                classNames: {
                    containerInner: ['choices__inner', 'menu-group-choices-inner'],
                    input: ['choices__input', 'form-control', 'form-control-sm', 'border-0', 'shadow-none', 'my-1'],
                    inputCloned: ['choices__input--cloned'],
                    listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
                    item: ['choices__item', 'dropdown-item', 'rounded-0'],
                    itemSelectable: ['choices__item--selectable'],
                    itemDisabled: ['choices__item--disabled', 'disabled'],
                    itemChoice: ['choices__item--choice'],
                    placeholder: ['choices__placeholder', 'text-muted', 'opacity-75'],
                    highlightedState: ['is-highlighted', 'active'],
                    notice: ['choices__notice', 'dropdown-item-text', 'text-muted', 'small', 'py-2']
                }
            });
            el._mgChoices = instance;
            return instance;
        }

        function menuGroupSyncChoices(el) {
            if (!el || !el._mgChoices) {
                return;
            }
            var v = el.value;
            if (v === '' || v === null || typeof v === 'undefined') {
                el._mgChoices.removeActiveItems();
                return;
            }
            try {
                el._mgChoices.setChoiceByValue(v);
            } catch (err) {
                try {
                    el._mgChoices.setChoiceByValue(String(v));
                } catch (err2) {
                }
            }
        }

        function ensureIconOption(value) {
            if (!value) {
                return;
            }
            var $sel = $('#icon');
            var exists = $sel.find('option').filter(function () {
                return $(this).val() === value;
            }).length > 0;
            if (!exists) {
                $sel.append($('<option></option>').attr('value', value).attr('data-adhoc', '1')
                    .text(value + ' (custom)'));
            }
        }

        function clearAdhocIconOptions() {
            $('#icon option[data-adhoc="1"]').remove();
        }

        function syncIconPreview() {
            var name = $('#icon').val();
            var $i = $('#iconPreview');
            $i.attr('class', 'material-icons menu-icon material-symbols-rounded fs-4');
            if (name) {
                $i.removeClass('text-muted').addClass('text-primary');
                $i.text(name);
            } else {
                $i.addClass('text-muted');
                $i.text('apps');
            }
        }

        function initMenuGroupModalSelects() {
            var catEl = document.getElementById('category_id');
            var iconEl = document.getElementById('icon');

            if (typeof window.Choices === 'undefined') {
                $('#category_id, #icon').addClass('form-select');
                syncIconPreview();
                return;
            }

            $('#category_id, #icon').removeClass('form-select');

            menuGroupDestroyChoices(catEl);
            menuGroupDestroyChoices(iconEl);

            if (catEl) {
                menuGroupInitChoices(catEl, 'Type to search categories…', 'Search categories…', false);
                menuGroupSyncChoices(catEl);
                $(catEl).off('change.mg').on('change.mg', function () {
                    $(this).valid();
                });
            }

            if (iconEl) {
                menuGroupInitChoices(iconEl, 'Type to search icons…', 'Search icons…', true);
                menuGroupSyncChoices(iconEl);
                $(iconEl).off('change.mg').on('change.mg', function () {
                    syncIconPreview();
                    $(this).valid();
                });
            }

            syncIconPreview();
        }

        function destroyMenuGroupModalSelects() {
            menuGroupDestroyChoices(document.getElementById('category_id'));
            menuGroupDestroyChoices(document.getElementById('icon'));
            $('#category_id, #icon').off('change.mg');
            $('#category_id, #icon').addClass('form-select');
        }

        $('#MenuGroupModal')
            .off('shown.bs.modal.menuGroup hidden.bs.modal.menuGroup')
            .on('shown.bs.modal.menuGroup', function () {
                window.requestAnimationFrame(function () {
                    initMenuGroupModalSelects();
                });
            })
            .on('hidden.bs.modal.menuGroup', function () {
                destroyMenuGroupModalSelects();
            });

        $(document).on('click', '.edit-btn', function () {
            let data = $(this).data('item');
            MenuGroupModal(data);
        });

        function MenuGroupModal(data = null) {
            $('input[name="_method"]').remove();
            clearAdhocIconOptions();
            if (data) {
                $('#category_id').val(data.category_id);
                $('#name').val(data.name);
                ensureIconOption(data.icon);
                $('#icon').val(data.icon);
                $('#order').val(data.order);
                $('#is_active').val(data.is_active);
                $('#menuGroupForm').attr('action', '/sidebar/menu-groups/' + data.id);
                $('#menuGroupForm').append('<input type="hidden" name="_method" value="PATCH">');
            } else {
                $('#menuGroupForm')[0].reset();
                $('#menuGroupForm').attr('action', '/sidebar/menu-groups');
            }
            $('#MenuGroupModal').modal('show');
        }

        $(document).ready(function () {
            $.validator.addMethod("nameRegex", function (value, element) {
                return this.optional(element) || /^[A-Za-z .'-]+$/.test(value);
            }, "Name can only contain letters, spaces, ., ' and -.");

            // Slug validation (only lowercase, dash)
            $.validator.addMethod("slugRegex", function (value, element) {
                return this.optional(element) || /^[a-z0-9-]+$/.test(value);
            }, "Slug can only contain lowercase letters, numbers and hyphens.");


            $("#menuGroupForm").validate({
                ignore: ".ignore, :hidden:not(.menu-group-choices)",
                rules: {
                    category_id: {
                        required: true,
                    },
                    name: {
                        required: true,
                        minlength: 2,
                        maxlength: 100,
                        nameRegex: true,
                    },
                    icon: {
                        required: true,
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
                    category_id: {
                        required: "Please select category",
                    },
                    name: {
                        required: "Please enter menu group name",
                        minlength: "Name must be at least 2 characters",
                        maxlength: "Name must be less than 100 characters"
                    },
                    icon: {
                        required: "Please select an icon",
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
                    var $el = $(element);
                    $el.addClass("is-invalid").removeClass("is-valid");
                    $el.closest(".choices").addClass("is-invalid");
                },
                unhighlight: function (element) {
                    var $el = $(element);
                    $el.removeClass("is-invalid").addClass("is-valid");
                    $el.closest(".choices").removeClass("is-invalid");
                },
                errorPlacement: function (error, element) {
                    error.addClass("invalid-feedback");
                    var $wrap = $(element).closest(".choices");
                    if ($wrap.length) {
                        error.insertAfter($wrap);
                    } else {
                        element.closest(".form-group").append(error);
                    }
                },
                submitHandler: function (form) {
                    let btn = $("#SubmitMenuGroupForm");
                    btn.prop("disabled", true);
                    btn.html('<i class="fa fa-spinner fa-spin"></i> Processing...');
                    form.submit();
                }
            });

            $(document).on('change', '.sidebar-menu-group-status-toggle', function () {
                let id = $(this).data('id');
                let value = $(this).is(':checked') ? 1 : 0;
                let column = $(this).data('column');

                $.ajax({
                    url: "{{ route('sidebar.menu-groups.status', ':id') }}".replace(':id', id),
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
                        $('#sidebar-menu-group-table').DataTable().ajax.reload();
                    },
                    error: function (xhr) {
                        toastr.error('Something went wrong');
                    }
                });
            });
        });
    </script>
@endsection