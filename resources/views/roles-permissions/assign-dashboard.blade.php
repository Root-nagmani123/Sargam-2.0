@extends('admin.layouts.master')
@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <style>
        /* ── Choices.js icon dropdown ── */
        .dc-choices { width: 100%; }
        .dc-choices .choices__inner {
            background-image: none !important;
            -webkit-appearance: none !important;
            appearance: none !important;
            min-height: 38px;
            padding: 0.375rem 2rem 0.375rem 0.75rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            background-color: #fff;
            white-space: nowrap;
            overflow: hidden;
        }
        .dc-choices .choices__list--dropdown {
            z-index: 2000;
            border-radius: 0.375rem;
            margin-top: 2px;
            border: 1px solid #d7e1ef;
            overflow: hidden;
            max-height: 260px;
        }
        .dc-choices .choices__list--dropdown .choices__list {
            max-height: 210px;
            overflow-y: auto;
        }
        .dc-choices .choices__list--dropdown .choices__input {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            margin: 0.5rem;
            width: calc(100% - 1rem) !important;
        }
        .dc-choices .choices__list--dropdown .choices__item--choice {
            padding: 0.4rem 0.75rem;
        }
        .dc-choices .choices__list--dropdown .choices__item--choice.is-highlighted {
            background-color: #edf4ff;
            color: #0d47a1;
        }
        .dc-icon-option {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            width: 100%;
        }
        .dc-icon-option i {
            font-size: 1.05rem;
            color: #0d6efd;
            width: 1.2rem;
            text-align: center;
            flex: 0 0 1.2rem;
        }
        .dc-icon-option span {
            font-size: 0.875rem;
            color: #1f2a37;
        }
        /* ── Table icon cell ── */
        .dc-icon-sm {
            width: 30px !important;
            height: 30px !important;
            font-size: 13px !important;
            flex-shrink: 0;
        }
        /* ── Card preview ── */
        .dc-preview-card {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 10px 16px;
            min-width: 210px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .dc-preview-icon {
            width: 42px !important;
            height: 42px !important;
            font-size: 17px !important;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
        }
        /* stat-icon colour classes (dashboard-stat-cards.css is not loaded on this page) */
        .stat-icon-blue  { background: rgba(47, 128, 237, 0.12); color: #2f80ed; }
        .stat-icon-green { background: rgba(39, 174, 96, 0.12);  color: #27ae60; }
        .stat-icon-amber { background: rgba(232, 163, 23, 0.12); color: #c98a0e; }
        .stat-icon-rose  { background: rgba(220, 53, 69, 0.10);  color: #dc3545; }
        .stat-icon-navy  { background: rgba(30, 58, 95, 0.12);   color: #1e3a5f; }
        .dc-preview-body { display: flex; flex-direction: column; gap: 1px; }
        .dc-preview-label {
            font-size: 11px; color: #6c757d; margin: 0;
            line-height: 1.3; font-weight: 500;
            max-width: 130px; white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis;
        }
        .dc-preview-count {
            font-size: 22px; font-weight: 700; margin: 0;
            line-height: 1.1; color: #212529; letter-spacing: -0.5px;
        }
        .spin { animation: spin 1s linear infinite; display: inline-block; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
@endpush

@section('title', 'Assign Dashboard')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum
        title="Assign Dashboard"
        :items="[
            'Setup',
            'Hr Management',
            'Assign Dashboard',
        ]"
    />
    <x-session_message />
    <div class="datatables">
        <div class="card">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Assign Dashboard Cards ({{ ucfirst($role->name) }})</h4>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle" id="enabledCount">
                        {{ count($assignedCardIds) }} Enabled
                    </span>
                    <button class="btn btn-sm btn-primary d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addCardModal">
                        <i class="material-symbols-rounded" style="font-size:18px;">add</i>
                        <span class="d-none d-md-inline">Add New Card</span>
                    </button>
                    <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="material-symbols-rounded" style="font-size:16px;">arrow_back</i> Back to Roles
                    </a>
                </div>
            </div>
            <div class="card-body">

                <!-- Search & Filter Bar -->
                <div class="bg-body-tertiary border rounded-3 p-3 mb-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-9">
                            <label for="cardSearch" class="form-label small fw-semibold text-secondary mb-1">Search</label>
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="material-icons material-symbols-rounded text-muted">search</i></span>
                                <input type="text" id="cardSearch" class="form-control border-start-0 ps-0" placeholder="Search by name...">
                            </div>
                        </div>
                        <div class="col-9 col-lg-2">
                            <label for="statusFilter" class="form-label small fw-semibold text-secondary mb-1">Status</label>
                            <select id="statusFilter" class="form-select shadow-sm">
                                <option value="">All Status</option>
                                <option value="enabled">Enabled</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>
                        <div class="col-3 col-lg-1 d-grid">
                            <button class="btn btn-outline-secondary btn-sm" id="clearFilters" type="button" title="Clear filters">
                                <i class="material-icons material-symbols-rounded">clear</i>
                            </button>
                        </div>
                    </div>
                </div>

                <table class="table align-middle" id="dashboardCardTable">
                    <thead>
                        <tr>
                            <th>S No.</th>
                            <th>Name</th>
                            <th class="text-center">Icon</th>
                            <th>Order</th>
                            <th>Created Date</th>
                            <th class="text-center">Enable</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allCards as $index => $card)
                        <tr data-id="{{ $card->id }}">
                            <td class="text-muted small">{{ $index + 1 }}</td>
                            <td><span class="fw-medium">{{ $card->label }}</span></td>
                            <td class="text-center">
                                <span class="dc-icon-sm stat-icon-wrapper {{ $card->color_class }} d-inline-flex align-items-center justify-content-center">
                                    <i class="material-symbols-rounded">{{ $card->icon }}</i>
                                </span>
                            </td>
                            <td><span class="badge bg-primary">{{ $card->sort_order }}</span></td>
                            <td>{{ $card->created_at ? $card->created_at->format('d-m-Y') : '-' }}</td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input card-toggle" type="checkbox"
                                        data-id="{{ $card->id }}"
                                        {{ in_array($card->id, $assignedCardIds) ? 'checked' : '' }}>
                                    <label class="form-check-label"></label>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-1" role="group">
                                    <button class="btn btn-sm border-0 bg-transparent text-primary edit-card-btn d-inline-flex align-items-center justify-content-center"
                                        data-id="{{ $card->id }}"
                                        data-label="{{ $card->label }}"
                                        data-icon="{{ $card->icon }}"
                                        data-color="{{ $card->color_class }}"
                                        data-sort="{{ $card->sort_order }}"
                                        title="Edit">
                                        <i class="material-symbols-rounded" style="font-size:18px;">edit</i>
                                    </button>
                                    <button class="btn btn-sm border-0 bg-transparent text-danger delete-card-btn d-inline-flex align-items-center justify-content-center{{ in_array($card->id, $assignedCardIds) ? ' d-none' : '' }}"
                                        data-id="{{ $card->id }}"
                                        title="Delete">
                                        <i class="material-symbols-rounded" style="font-size:18px;">delete</i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════ Add New Card Modal ═══════════════════════════ -->
<div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" data-bs-backdrop="static" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="addCardModalLabel">Add New Dashboard Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="addCardForm">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label fw-medium" for="cardLabel">Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="label" id="cardLabel" placeholder="e.g. Pending Leave Applications">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="cardIconSelect">Icon <span class="text-danger">*</span></label>
                        <select class="w-100 dc-icon-choices" name="icon" id="cardIconSelect">
                            <option value="">Select icon…</option>
                            @foreach($materialIcons as $icon)
                                <option value="{{ $icon }}">{{ $icon }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-7">
                            <label class="form-label fw-medium" for="cardColor">Color</label>
                            <select class="form-select" name="color_class" id="cardColor">
                                <option value="stat-icon-blue">Blue</option>
                                <option value="stat-icon-green">Green</option>
                                <option value="stat-icon-amber">Amber</option>
                                <option value="stat-icon-rose">Rose</option>
                                <option value="stat-icon-navy">Navy</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-medium" for="cardSortOrder">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" id="cardSortOrder" value="99" min="1">
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Preview</label>
                        <div class="dc-preview-card" style="display:inline-flex;">
                            <div class="dc-preview-icon stat-icon-wrapper stat-icon-blue" id="previewIcon">
                                <i class="material-symbols-rounded" id="previewIconEl">apps</i>
                            </div>
                            <div class="dc-preview-body">
                                <p class="dc-preview-label" id="previewLabel">Card Label</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary" id="saveCardBtn">
                            <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">save</i> Save Card
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════ Edit Card Modal ═══════════════════════════ -->
<div class="modal fade" id="editCardModal" tabindex="-1" aria-labelledby="editCardModalLabel" data-bs-backdrop="static" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="editCardModalLabel">Edit Dashboard Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="editCardForm">
                    @csrf
                    <input type="hidden" id="editCardId">
                    <div class="form-group mb-3">
                        <label class="form-label fw-medium" for="editCardLabel">Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="label" id="editCardLabel" placeholder="e.g. Pending Leave Applications">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="editCardIconSelect">Icon <span class="text-danger">*</span></label>
                        <select class="w-100 dc-icon-choices" name="icon" id="editCardIconSelect">
                            <option value="">Select icon…</option>
                            @foreach($materialIcons as $icon)
                                <option value="{{ $icon }}">{{ $icon }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-7">
                            <label class="form-label fw-medium" for="editCardColor">Color</label>
                            <select class="form-select" name="color_class" id="editCardColor">
                                <option value="stat-icon-blue">Blue</option>
                                <option value="stat-icon-green">Green</option>
                                <option value="stat-icon-amber">Amber</option>
                                <option value="stat-icon-rose">Rose</option>
                                <option value="stat-icon-navy">Navy</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-medium" for="editCardSortOrder">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" id="editCardSortOrder" min="1">
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Preview</label>
                        <div class="dc-preview-card" style="display:inline-flex;">
                            <div class="dc-preview-icon stat-icon-wrapper stat-icon-blue" id="editPreviewIcon">
                                <i class="material-symbols-rounded" id="editPreviewIconEl">apps</i>
                            </div>
                            <div class="dc-preview-body">
                                <p class="dc-preview-label" id="editPreviewLabel">Card Label</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary" id="updateCardBtn">
                            <i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">save</i> Update Card
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
// ─── Choices.js helpers ─────────────────────────────────────────────────────
function dcEscHtml(v) {
    return String(v || '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function dcClassStr(v) { return Array.isArray(v) ? v.join(' ') : String(v || ''); }

function dcInitIconChoices(el) {
    if (!el || typeof window.Choices === 'undefined') return null;
    if (el._dcChoices) { try { el._dcChoices.destroy(); } catch(e){} el._dcChoices = null; }

    var inst = new Choices(el, {
        removeItemButton: false,
        shouldSort: false,
        searchEnabled: true,
        searchPlaceholderValue: 'Search icons…',
        placeholder: true,
        placeholderValue: 'Select icon…',
        itemSelectText: '',
        allowHTML: true,
        callbackOnCreateTemplates: function(template) {
            return {
                item: function(classNames, data) {
                    var val = dcEscHtml(data.value);
                    var lbl = dcEscHtml(data.label);
                    return template(
                        '<div class="' + dcClassStr(classNames.item) + ' ' + dcClassStr(classNames.itemSelectable) + '" ' +
                        'data-item data-id="' + data.id + '" data-value="' + val + '" ' +
                        (data.active ? 'aria-selected="true"' : '') + '>' +
                        '<span class="dc-icon-option">' +
                        '<i class="material-symbols-rounded">' + val + '</i>' +
                        '<span>' + lbl + '</span>' +
                        '</span></div>'
                    );
                },
                choice: function(classNames, data) {
                    var val = dcEscHtml(data.value);
                    var lbl = dcEscHtml(data.label);
                    return template(
                        '<div class="' + dcClassStr(classNames.item) + ' ' + dcClassStr(classNames.itemChoice) + '" ' +
                        'data-select-text="" data-choice ' +
                        'data-id="' + data.id + '" data-value="' + val + '" ' +
                        (data.disabled ? 'data-choice-disabled aria-disabled="true"' : 'data-choice-selectable') + '>' +
                        '<span class="dc-icon-option">' +
                        '<i class="material-symbols-rounded">' + val + '</i>' +
                        '<span>' + lbl + '</span>' +
                        '</span></div>'
                    );
                }
            };
        },
        classNames: {
            containerOuter: ['choices', 'dc-choices'],
            containerInner: ['choices__inner'],
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
    el._dcChoices = inst;
    return inst;
}

function dcDestroyChoices(el) {
    if (!el || !el._dcChoices) return;
    try { el._dcChoices.destroy(); } catch(e) {}
    el._dcChoices = null;
}

function dcSetChoiceByValue(el, val) {
    if (!el || !el._dcChoices || !val) return;
    try { el._dcChoices.setChoiceByValue(val); }
    catch(e) { try { el._dcChoices.setChoiceByValue(String(val)); } catch(e2){} }
}

// ─── Add modal ───────────────────────────────────────────────────────────────
var addIconEl = document.getElementById('cardIconSelect');

$('#addCardModal')
    .on('shown.bs.modal', function() {
        window.requestAnimationFrame(function() {
            dcInitIconChoices(addIconEl);
            // hook change for preview
            $(addIconEl).off('change.dcAdd').on('change.dcAdd', function() {
                updateAddPreview();
            });
        });
    })
    .on('hidden.bs.modal', function() {
        dcDestroyChoices(addIconEl);
        $(addIconEl).off('change.dcAdd');
    });

function updateAddPreview() {
    var label = $('#cardLabel').val() || 'Card Label';
    var icon  = $(addIconEl).val() || 'apps';
    var color = $('#cardColor').val() || 'stat-icon-blue';
    $('#previewLabel').text(label);
    $('#previewIconEl').text(icon);
    $('#previewIcon').attr('class', 'dc-preview-icon stat-icon-wrapper ' + color);
}
$('#cardLabel').on('input', updateAddPreview);
$('#cardColor').on('change', updateAddPreview);

// ─── Edit modal ──────────────────────────────────────────────────────────────
var editIconEl = document.getElementById('editCardIconSelect');

$('#editCardModal')
    .on('shown.bs.modal', function() {
        window.requestAnimationFrame(function() {
            dcInitIconChoices(editIconEl);
            var currentIcon = $(editIconEl).data('pending-value') || '';
            if (currentIcon) { dcSetChoiceByValue(editIconEl, currentIcon); }
            $(editIconEl).removeData('pending-value');
            // hook change for preview
            $(editIconEl).off('change.dcEdit').on('change.dcEdit', function() {
                updateEditPreview();
            });
        });
    })
    .on('hidden.bs.modal', function() {
        dcDestroyChoices(editIconEl);
        $(editIconEl).off('change.dcEdit');
    });

function updateEditPreview() {
    var label = $('#editCardLabel').val() || 'Card Label';
    var icon  = $(editIconEl).val() || 'apps';
    var color = $('#editCardColor').val() || 'stat-icon-blue';
    $('#editPreviewLabel').text(label);
    $('#editPreviewIconEl').text(icon);
    $('#editPreviewIcon').attr('class', 'dc-preview-icon stat-icon-wrapper ' + color);
}
$('#editCardLabel').on('input', updateEditPreview);
$('#editCardColor').on('change', updateEditPreview);

// Open edit modal (populate fields first, Choices init happens on shown.bs.modal)
$(document).on('click', '.edit-card-btn', function() {
    var id    = $(this).data('id');
    var label = $(this).data('label');
    var icon  = $(this).data('icon');
    var color = $(this).data('color');
    var sort  = $(this).data('sort');

    $('#editCardId').val(id);
    $('#editCardLabel').val(label);
    // store icon value; Choices will apply it after init in shown.bs.modal
    $(editIconEl).data('pending-value', icon).val(icon);
    $('#editCardColor').val(color);
    $('#editCardSortOrder').val(sort);
    // update preview immediately (Choices not yet inited, uses native val)
    $('#editPreviewLabel').text(label || 'Card Label');
    $('#editPreviewIconEl').text(icon || 'apps');
    $('#editPreviewIcon').attr('class', 'dc-preview-icon stat-icon-wrapper ' + (color || 'stat-icon-blue'));
    $('#editCardModal').modal('show');
});

// ─── DataTable ───────────────────────────────────────────────────────────────
$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    if (settings.nTable.id !== 'dashboardCardTable') return true;
    var status = $('#statusFilter').val();
    if (!status) return true;
    var row = settings.aoData[dataIndex].nTr;
    var isChecked = $(row).find('.card-toggle').is(':checked');
    return (status === 'enabled' && isChecked) || (status === 'disabled' && !isChecked);
});

var cardTable = null;

$(document).ready(function() {
    cardTable = $('#dashboardCardTable').DataTable({
        responsive: false,
        autoWidth: false,
        scrollX: true,
        pageLength: 10,
        order: [],
        columnDefs: [
            { orderable: false, targets: [2, 5, 6] },
            { searchable: false, targets: [0, 2, 3, 4, 5, 6] },
            { width: '50px',  targets: [0] },
            { width: '70px',  targets: [2] },
            { width: '80px',  targets: [3] },
            { width: '110px', targets: [4] },
            { width: '90px',  targets: [5] },
            { width: '100px', targets: [6] },
        ],
        dom: "<'row'<'col-12'tr>>" +
             "<'row mt-3 align-items-center dt-bottom-bar'<'col-md-6 dt-bottom-paginate'p><'col-md-6 dt-bottom-info d-flex align-items-center justify-content-md-end gap-2'il>>",
        language: {
            info: 'of _MAX_ items',
            infoFiltered: '(filtered from _MAX_ total)',
            infoEmpty: 'of 0 items',
            emptyTable: 'No cards available.',
            zeroRecords: 'No cards found matching your filters.',
            paginate: { previous: '&lsaquo;', next: '&rsaquo;' }
        }
    });

    $('#cardSearch').on('input', function() { cardTable.search($(this).val()).draw(); });
    $('#statusFilter').on('change', function() { cardTable.draw(); });
    $('#clearFilters').on('click', function() {
        $('#cardSearch').val('');
        $('#statusFilter').val('');
        cardTable.search('').draw();
    });
});

// ─── Enable toggle ───────────────────────────────────────────────────────────
$(document).on('change', '.card-toggle', function() {
    var cardId    = $(this).data('id');
    var isChecked = $(this).is(':checked') ? 1 : 0;
    var $toggle   = $(this);

    $.ajax({
        url: "{{ route('assign.roles.dashboard', encrypt($role->id)) }}",
        type: "POST",
        data: { _token: "{{ csrf_token() }}", card_id: cardId, status: isChecked },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                updateEnabledCount();
                // Enabled cards must not be deletable — only disabled ones show the delete option.
                $toggle.closest('tr').find('.delete-card-btn').toggleClass('d-none', isChecked === 1);
            }
            else { toastr.error(response.message); $toggle.prop('checked', !isChecked); }
        },
        error: function() { toastr.error('Something went wrong'); $toggle.prop('checked', !isChecked); }
    });
});

function updateEnabledCount() {
    $('#enabledCount').text($('.card-toggle:checked').length + ' Enabled');
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    var d = new Date(dateStr);
    if (isNaN(d.getTime())) return '-';
    return String(d.getDate()).padStart(2,'0') + '-' +
           String(d.getMonth()+1).padStart(2,'0') + '-' +
           d.getFullYear();
}

// ─── Add Card submit ─────────────────────────────────────────────────────────
$('#addCardForm').on('submit', function(e) {
    e.preventDefault();
    var $btn  = $('#saveCardBtn');
    var label = $('#cardLabel').val().trim();
    var icon  = $(addIconEl).val();

    if (!label) { toastr.error('Label is required.'); return; }
    if (!icon)  { toastr.error('Please select an icon.'); return; }

    $btn.prop('disabled', true).html('<i class="material-symbols-rounded spin me-1" style="font-size:16px;vertical-align:middle;">refresh</i> Saving...');

    $.ajax({
        url: "{{ route('dashboard.cards.store') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            label: label,
            icon: icon,
            color_class: $('#cardColor').val(),
            sort_order: $('#cardSortOrder').val()
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#addCardModal').modal('hide');
                var card = response.card;
                var totalRows = cardTable.rows().count();
                var newRow =
                    '<tr data-id="'+card.id+'">' +
                    '<td class="text-muted small">'+(totalRows+1)+'</td>' +
                    '<td><span class="fw-medium">'+card.label+'</span></td>' +
                    '<td class="text-center"><span class="dc-icon-sm stat-icon-wrapper '+card.color_class+' d-inline-flex align-items-center justify-content-center"><i class="material-symbols-rounded">'+card.icon+'</i></span></td>' +
                    '<td><span class="badge bg-primary">'+card.sort_order+'</span></td>' +
                    '<td>'+formatDate(card.created_at)+'</td>' +
                    '<td class="text-center"><div class="form-check form-switch d-inline-block"><input class="form-check-input card-toggle" type="checkbox" data-id="'+card.id+'"><label class="form-check-label"></label></div></td>' +
                    '<td class="text-center"><div class="d-inline-flex align-items-center gap-1"><button class="btn btn-sm border-0 bg-transparent text-primary edit-card-btn d-inline-flex align-items-center justify-content-center" data-id="'+card.id+'" data-label="'+card.label+'" data-icon="'+card.icon+'" data-color="'+card.color_class+'" data-sort="'+card.sort_order+'" title="Edit"><i class="material-symbols-rounded" style="font-size:18px;">edit</i></button><button class="btn btn-sm border-0 bg-transparent text-danger delete-card-btn d-inline-flex align-items-center justify-content-center" data-id="'+card.id+'" title="Delete"><i class="material-symbols-rounded" style="font-size:18px;">delete</i></button></div></td>' +
                    '</tr>';
                cardTable.row.add($(newRow)).draw(false);
                $('#addCardForm')[0].reset();
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function(field, msgs) { toastr.error(msgs[0]); });
            } else {
                toastr.error(xhr.responseJSON?.message || 'Something went wrong');
            }
        },
        complete: function() { $btn.prop('disabled', false).html('<i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">save</i> Save Card'); }
    });
});

// ─── Edit Card submit ─────────────────────────────────────────────────────────
$('#editCardForm').on('submit', function(e) {
    e.preventDefault();
    var $btn  = $('#updateCardBtn');
    var id    = $('#editCardId').val();
    var label = $('#editCardLabel').val().trim();
    var icon  = $(editIconEl).val();

    if (!label) { toastr.error('Label is required.'); return; }
    if (!icon)  { toastr.error('Please select an icon.'); return; }

    $btn.prop('disabled', true).html('<i class="material-symbols-rounded spin me-1" style="font-size:16px;vertical-align:middle;">refresh</i> Updating...');

    $.ajax({
        url: '/dashboard-cards/' + id,
        type: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            _method: 'PUT',
            label: label,
            icon: icon,
            color_class: $('#editCardColor').val(),
            sort_order: $('#editCardSortOrder').val()
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#editCardModal').modal('hide');
                var card = response.card;
                var $row = $('tr[data-id="'+card.id+'"]');
                $row.find('td:eq(1)').html('<span class="fw-medium">'+card.label+'</span>');
                $row.find('td:eq(2)').html('<span class="dc-icon-sm stat-icon-wrapper '+card.color_class+' d-inline-flex align-items-center justify-content-center"><i class="material-symbols-rounded">'+card.icon+'</i></span>');
                $row.find('td:eq(3)').html('<span class="badge bg-primary">'+card.sort_order+'</span>');
                $row.find('.edit-card-btn').data('label', card.label).data('icon', card.icon).data('color', card.color_class).data('sort', card.sort_order);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function(field, msgs) { toastr.error(msgs[0]); });
            } else {
                toastr.error(xhr.responseJSON?.message || 'Something went wrong');
            }
        },
        complete: function() { $btn.prop('disabled', false).html('<i class="material-symbols-rounded me-1" style="font-size:16px;vertical-align:middle;">save</i> Update Card'); }
    });
});

// ─── Delete Card ─────────────────────────────────────────────────────────────
$(document).on('click', '.delete-card-btn', function() {
    var id   = $(this).data('id');
    var $btn = $(this);

    if (!confirm('Are you sure you want to delete this card? It will be removed from all roles.')) return;

    $btn.prop('disabled', true);

    $.ajax({
        url: '/dashboard-cards/' + id,
        type: 'POST',
        data: { _token: "{{ csrf_token() }}", _method: 'DELETE' },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                cardTable.row($('tr[data-id="'+id+'"]')).remove().draw(false);
                updateEnabledCount();
            } else {
                toastr.error(response.message);
            }
        },
        error: function() { toastr.error('Something went wrong'); },
        complete: function() { $btn.prop('disabled', false); }
    });
});
</script>
@endsection
