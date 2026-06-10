@php
    $cruTableId = $cruTableId ?? 'cruTable';
    $cruColumnStorageKey = $cruColumnStorageKey ?? ('cru-columns-' . $cruTableId);
    $cruColumns = $cruColumns ?? [];
    $toggleable = array_values(array_filter($cruColumns, fn ($c) => empty($c['locked'])));
    $locked = array_values(array_filter($cruColumns, fn ($c) => !empty($c['locked'])));
    $cruColModalId = 'cruColVisModal-' . $cruTableId;
@endphp
@if(count($toggleable) > 0)
<div class="cru-column-toggle">
    <button type="button"
            class="btn btn-light border btn-sm d-inline-flex align-items-center gap-2 fw-semibold cru-colvis-trigger"
            id="cruColToggleBtn-{{ $cruTableId }}"
            data-bs-toggle="modal"
            data-bs-target="#{{ $cruColModalId }}"
            title="Show / hide columns">
        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
        <span class="d-none d-sm-inline">Columns</span>
    </button>

    <div class="modal fade cru-colvis-modal"
         id="{{ $cruColModalId }}"
         tabindex="-1"
         aria-labelledby="{{ $cruColModalId }}-label"
         aria-hidden="true"
         data-cru-table-id="{{ $cruTableId }}"
         data-cru-storage-key="{{ $cruColumnStorageKey }}">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-2 px-4 pt-4">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="{{ $cruColModalId }}-label">
                        <i class="bi bi-sliders2 text-primary" aria-hidden="true"></i>
                        Column Visibility
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <hr class="cru-colvis-divider mx-4 my-0">
                <div class="modal-body px-4 py-4">
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
                        @foreach($locked as $col)
                            <div class="col">
                                <label class="cru-colvis-chip cru-colvis-chip-locked d-flex align-items-center gap-2 mb-0"
                                       title="Always visible">
                                    <input type="checkbox" class="form-check-input m-0" checked disabled>
                                    <span class="text-truncate">{{ $col['label'] }}</span>
                                </label>
                            </div>
                        @endforeach
                        @foreach($toggleable as $col)
                            <div class="col">
                                <label class="cru-colvis-chip d-flex align-items-center gap-2 mb-0">
                                    <input type="checkbox"
                                           class="form-check-input m-0 cru-col-toggle-checkbox"
                                           data-table="{{ $cruTableId }}"
                                           data-col="{{ $col['key'] }}"
                                           {{ ($col['default'] ?? true) ? 'checked' : '' }}>
                                    <span class="text-truncate">{{ $col['label'] }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-between">
                    <button type="button"
                            class="btn btn-link btn-sm text-decoration-none p-0 d-inline-flex align-items-center gap-1 cru-col-toggle-reset"
                            data-table="{{ $cruTableId }}"
                            data-storage="{{ $cruColumnStorageKey }}">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                        Reset to default
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm px-4 fw-semibold" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
