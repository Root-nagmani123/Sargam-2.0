{{--
    Offcanvas panel for MessColumnManager (paired with mess-master-datatables or data-mess-column-manager tables).
    @param string $tableId
    @param string|null $title Panel title
--}}
@php
    $tableId = $tableId ?? 'masterTable';
    $title = $title ?? 'Manage Columns';
@endphp

@include('components.mess-column-manager-assets')

<div class="offcanvas offcanvas-end mess-column-manager-offcanvas" tabindex="-1"
     id="messColManagerOffcanvas-{{ $tableId }}"
     aria-labelledby="messColManagerOffcanvasLabel-{{ $tableId }}"
     data-bs-scroll="true">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-semibold" id="messColManagerOffcanvasLabel-{{ $tableId }}">{{ $title }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column gap-3">
        <p class="small text-muted mb-0">
            Drag to reorder, toggle visibility, rename headers, or add display aliases. Preferences are saved in your browser.
        </p>

        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="messColManagerMoveUp-{{ $tableId }}" title="Move selected up">
                <i class="material-symbols-rounded align-middle" style="font-size:1rem">arrow_upward</i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="messColManagerMoveDown-{{ $tableId }}" title="Move selected down">
                <i class="material-symbols-rounded align-middle" style="font-size:1rem">arrow_downward</i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" id="messColManagerReset-{{ $tableId }}">Reset</button>
        </div>

        <ul class="list-group list-group-flush border rounded mess-col-manager-list" id="messColManagerList-{{ $tableId }}"></ul>

        <div class="border rounded p-3 bg-body-tertiary">
            <h6 class="small fw-semibold mb-2">Add display column</h6>
            <div class="row g-2 align-items-end">
                <div class="col-12">
                    <label class="form-label small mb-1" for="messColManagerAddLabel-{{ $tableId }}">Column label</label>
                    <input type="text" class="form-control form-control-sm" id="messColManagerAddLabel-{{ $tableId }}" placeholder="e.g. Notes">
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1" for="messColManagerAddSource-{{ $tableId }}">Show data from</label>
                    <select class="form-select form-select-sm" id="messColManagerAddSource-{{ $tableId }}"></select>
                </div>
                <div class="col-12">
                    <button type="button" class="btn btn-sm btn-primary w-100" id="messColManagerAdd-{{ $tableId }}">
                        <i class="material-symbols-rounded align-middle me-1" style="font-size:1rem">add</i> Add column
                    </button>
                </div>
            </div>
            <ul class="list-group list-group-flush mt-2" id="messColManagerAliases-{{ $tableId }}"></ul>
        </div>

        <div class="mt-auto pt-2 border-top d-flex gap-2">
            <button type="button" class="btn btn-primary flex-grow-1" id="messColManagerSave-{{ $tableId }}">Save &amp; apply</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Close</button>
        </div>
    </div>
</div>
