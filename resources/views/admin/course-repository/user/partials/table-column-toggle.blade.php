@php
    $cruTableId = $cruTableId ?? 'cruTable';
    $cruColumnStorageKey = $cruColumnStorageKey ?? ('cru-columns-' . $cruTableId);
    $cruColumns = $cruColumns ?? [];
    $toggleable = array_values(array_filter($cruColumns, fn ($c) => empty($c['locked'])));
@endphp
@if(count($toggleable) > 0)
<div class="dropdown cru-column-toggle">
    <button type="button"
            class="btn btn-outline-secondary btn-sm dropdown-toggle d-inline-flex align-items-center gap-1"
            id="cruColToggleBtn-{{ $cruTableId }}"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            title="Show / hide columns">
        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
        <span class="d-none d-sm-inline">Columns</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end cru-column-toggle-menu shadow-sm"
        id="cruColToggleMenu-{{ $cruTableId }}"
        aria-labelledby="cruColToggleBtn-{{ $cruTableId }}"
        data-cru-table-id="{{ $cruTableId }}"
        data-cru-storage-key="{{ $cruColumnStorageKey }}">
        <li class="dropdown-header small text-muted py-1">Show / hide columns</li>
        @foreach($toggleable as $col)
            <li>
                <label class="dropdown-item d-flex align-items-center gap-2 mb-0 cursor-pointer">
                    <input type="checkbox"
                           class="form-check-input cru-col-toggle-checkbox"
                           data-table="{{ $cruTableId }}"
                           data-col="{{ $col['key'] }}"
                           {{ ($col['default'] ?? true) ? 'checked' : '' }}>
                    <span>{{ $col['label'] }}</span>
                </label>
            </li>
        @endforeach
        <li><hr class="dropdown-divider my-1"></li>
        <li>
            <button type="button"
                    class="dropdown-item small text-primary cru-col-toggle-reset"
                    data-table="{{ $cruTableId }}"
                    data-storage="{{ $cruColumnStorageKey }}">
                Reset to default
            </button>
        </li>
    </ul>
</div>
@endif
