@extends('admin.layouts.master')
@section('title', 'Assign Dashboard')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Assign Dashboard" />
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
                        <i class="bi bi-plus-lg"></i>
                        <span class="d-none d-md-inline">Add New Card</span>
                    </button>
                    <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Roles
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
                                <input type="text" id="cardSearch" class="form-control border-start-0 ps-0" placeholder="Search by card name...">
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
                                <th>Preview</th>
                                <th class="text-center">Enable</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allCards as $index => $card)
                            <tr data-id="{{ $card->id }}">
                                <td class="text-muted small">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="dc-icon-sm stat-icon-wrapper {{ $card->color_class }}">
                                            <i class="bi {{ $card->icon }}"></i>
                                        </span>
                                        <span class="fw-medium">{{ $card->label }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if(str_starts_with($card->key, 'widget_'))
                                    <div class="dc-preview-card dc-preview-panel">
                                        <div class="dc-preview-icon stat-icon-wrapper {{ $card->color_class }}">
                                            <i class="bi {{ $card->icon }}"></i>
                                        </div>
                                        <div class="dc-preview-body">
                                            <p class="dc-preview-label">{{ $card->label }}</p>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:10px;">Dashboard Panel</span>
                                        </div>
                                    </div>
                                    @else
                                    <div class="dc-preview-card">
                                        <div class="dc-preview-icon stat-icon-wrapper {{ $card->color_class }}">
                                            <i class="bi {{ $card->icon }}"></i>
                                        </div>
                                        <div class="dc-preview-body">
                                            <p class="dc-preview-label">{{ $card->label }}</p>
                                            <p class="dc-preview-count">00</p>
                                        </div>
                                    </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input card-toggle" type="checkbox"
                                            data-id="{{ $card->id }}"
                                            {{ in_array($card->id, $assignedCardIds) ? 'checked' : '' }}>
                                        <label class="form-check-label"></label>
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

<!-- Add New Card Modal -->
<div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" data-bs-backdrop="static" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="addCardModalLabel">Add New Dashboard Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="alert alert-info border-0 py-2 small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Card ke appear hone ke baad, <strong>count logic</strong> developer ko <code>UserController@dashboard</code> mein add karni hogi.
                </div>
                <form id="addCardForm">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="form-label fw-medium" for="cardLabel">Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="label" id="cardLabel"
                            placeholder="e.g. Pending Leave Applications">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium" for="cardIcon">Icon <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" id="iconPreview"><i class="bi bi-grid"></i></span>
                                <input type="text" class="form-control" name="icon" id="cardIcon"
                                    placeholder="bi-grid" value="bi-grid">
                            </div>
                            <div class="form-text"><a href="https://icons.getbootstrap.com" target="_blank" class="text-decoration-none">Bootstrap Icons <i class="bi bi-box-arrow-up-right" style="font-size:10px"></i></a></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium" for="cardColor">Color</label>
                            <select class="form-select" name="color_class" id="cardColor">
                                <option value="stat-icon-blue">Blue</option>
                                <option value="stat-icon-green">Green</option>
                                <option value="stat-icon-amber">Amber</option>
                                <option value="stat-icon-rose">Rose</option>
                                <option value="stat-icon-navy">Navy</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="cardSortOrder">Sort Order</label>
                        <input type="number" class="form-control" name="sort_order" id="cardSortOrder"
                            value="99" min="1">
                    </div>

                    <!-- Live Preview -->
                    <div class="mb-3">
                        <label class="form-label fw-medium">Preview</label>
                        <div class="dc-preview-card" id="livePreview" style="display:inline-flex;">
                            <div class="dc-preview-icon stat-icon-wrapper stat-icon-blue" id="previewIcon">
                                <i class="bi bi-grid" id="previewIconEl"></i>
                            </div>
                            <div class="dc-preview-body">
                                <p class="dc-preview-label" id="previewLabel">Card Label</p>
                                <p class="dc-preview-count">00</p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary" id="saveCardBtn">
                            <i class="bi bi-save me-1"></i> Save Card
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
<script>
    // Custom status filter
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== 'dashboardCardTable') return true;
        var status = $('#statusFilter').val();
        if (!status) return true;
        var row = settings.aoData[dataIndex].nTr;
        var isChecked = $(row).find('.card-toggle').is(':checked');
        return (status === 'enabled' && isChecked) || (status === 'disabled' && !isChecked);
    });

    var cardTable = null;

    $(document).ready(function () {
        // Columns: 0=Sr, 1=Card Name, 2=Preview, 3=Enable
        cardTable = $('#dashboardCardTable').DataTable({
            responsive: false,
            autoWidth: false,
            scrollX: true,
            pageLength: 10,
            order: [],
            columnDefs: [
                { orderable: false, targets: [2, 3] },
                { searchable: false, targets: [0, 2, 3] },
                { width: '50px',  targets: [0] },
                { width: '90px',  targets: [3] }
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

        function applyFilters() {
            cardTable.search($('#cardSearch').val()).draw();
        }

        $('#cardSearch').on('input', applyFilters);
        $('#statusFilter').on('change', function () { cardTable.draw(); });
        $('#clearFilters').on('click', function () {
            $('#cardSearch').val('');
            $('#statusFilter').val('');
            cardTable.search('').draw();
        });
    });

    // Toggle handler
    $(document).on('change', '.card-toggle', function () {
        var cardId    = $(this).data('id');
        var isChecked = $(this).is(':checked') ? 1 : 0;
        var $toggle   = $(this);

        $.ajax({
            url: "{{ route('assign.roles.dashboard', $role->id) }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                card_id: cardId,
                status: isChecked
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    updateEnabledCount();
                } else {
                    toastr.error(response.message);
                    $toggle.prop('checked', !isChecked);
                }
            },
            error: function () {
                toastr.error('Something went wrong');
                $toggle.prop('checked', !isChecked);
            }
        });
    });

    function updateEnabledCount() {
        var count = $('.card-toggle:checked').length;
        $('#enabledCount').text(count + ' Enabled');
    }

    // ---- Add New Card modal: live preview ----
    function updatePreview() {
        var label = $('#cardLabel').val() || 'Card Label';
        var icon  = $('#cardIcon').val()  || 'bi-grid';
        var color = $('#cardColor').val() || 'stat-icon-blue';
        $('#previewLabel').text(label);
        $('#previewIconEl').attr('class', 'bi ' + icon);
        $('#iconPreview i').attr('class', 'bi ' + icon);
        $('#previewIcon').attr('class', 'dc-preview-icon stat-icon-wrapper ' + color);
    }
    $('#cardLabel, #cardIcon').on('input', updatePreview);
    $('#cardColor').on('change', updatePreview);

    // ---- Add New Card: AJAX submit ----
    $('#addCardForm').on('submit', function (e) {
        e.preventDefault();
        var $btn  = $('#saveCardBtn');
        var label = $('#cardLabel').val().trim();

        if (!label) {
            toastr.error('Label is required.');
            return;
        }

        $btn.prop('disabled', true).html('<i class="bi bi-arrow-repeat spin me-1"></i> Saving...');

        $.ajax({
            url: "{{ route('dashboard.cards.store') }}",
            type: "POST",
            data: {
                _token:      "{{ csrf_token() }}",
                label:       label,
                icon:        $('#cardIcon').val(),
                color_class: $('#cardColor').val(),
                sort_order:  $('#cardSortOrder').val()
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#addCardModal').modal('hide');
                    var card = response.card;
                    var totalRows = cardTable.rows().count();
                    var newRow =
                        '<tr data-id="'+card.id+'">' +
                        '<td class="text-muted small">'+(totalRows+1)+'</td>' +
                        '<td><div class="d-flex align-items-center gap-2"><span class="dc-icon-sm stat-icon-wrapper '+card.color_class+'"><i class="bi '+card.icon+'"></i></span><span class="fw-medium">'+card.label+'</span></div></td>' +
                        '<td><div class="dc-preview-card"><div class="dc-preview-icon stat-icon-wrapper '+card.color_class+'"><i class="bi '+card.icon+'"></i></div><div class="dc-preview-body"><p class="dc-preview-label">'+card.label+'</p><p class="dc-preview-count">00</p></div></div></td>' +
                        '<td class="text-center"><div class="form-check form-switch d-inline-block"><input class="form-check-input card-toggle" type="checkbox" data-id="'+card.id+'"><label class="form-check-label"></label></div></td>' +
                        '</tr>';
                    cardTable.row.add($(newRow)).draw(false);
                    $('#addCardForm')[0].reset();
                    $('#cardIcon').val('bi-grid');
                    updatePreview();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Something went wrong');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Save Card');
            }
        });
    });

    $('#addCardModal').on('show.bs.modal', function () {
        updatePreview();
    });
</script>
<style>
    .spin { animation: spin 1s linear infinite; display:inline-block; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    .dc-icon-sm {
        width: 30px !important;
        height: 30px !important;
        font-size: 13px !important;
        flex-shrink: 0;
    }

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
    }

    .dc-preview-body {
        display: flex;
        flex-direction: column;
        gap: 1px;
    }

    .dc-preview-label {
        font-size: 11px;
        color: #6c757d;
        margin: 0;
        line-height: 1.3;
        font-weight: 500;
        max-width: 130px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dc-preview-count {
        font-size: 22px;
        font-weight: 700;
        margin: 0;
        line-height: 1.1;
        color: #212529;
        letter-spacing: -0.5px;
    }
</style>
@endsection
