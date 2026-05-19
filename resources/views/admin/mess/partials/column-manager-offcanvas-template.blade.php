<template id="messColManagerOffcanvasTemplate">
    <div class="offcanvas offcanvas-end mess-column-manager-offcanvas" tabindex="-1"
         id="messColManagerOffcanvas-__TABLE_ID__"
         aria-labelledby="messColManagerOffcanvasLabel-__TABLE_ID__"
         data-bs-scroll="true">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-semibold" id="messColManagerOffcanvasLabel-__TABLE_ID__">__TITLE__</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column gap-3">
            <p class="small text-muted mb-0">Drag to reorder, toggle visibility, or rename headers. Saved in your browser.</p>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="messColManagerMoveUp-__TABLE_ID__" title="Move up">
                    <i class="material-symbols-rounded" style="font-size:1rem">arrow_upward</i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="messColManagerMoveDown-__TABLE_ID__" title="Move down">
                    <i class="material-symbols-rounded" style="font-size:1rem">arrow_downward</i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" id="messColManagerReset-__TABLE_ID__">Reset</button>
            </div>
            <ul class="list-group list-group-flush border rounded mess-col-manager-list" id="messColManagerList-__TABLE_ID__"></ul>
            <div class="border rounded p-3 bg-body-tertiary">
                <h6 class="small fw-semibold mb-2">Add display column</h6>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small mb-1">Column label</label>
                        <input type="text" class="form-control form-control-sm" id="messColManagerAddLabel-__TABLE_ID__" placeholder="Label">
                    </div>
                    <div class="col-12">
                        <label class="form-label small mb-1">Show data from</label>
                        <select class="form-select form-select-sm" id="messColManagerAddSource-__TABLE_ID__"></select>
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-sm btn-primary w-100" id="messColManagerAdd-__TABLE_ID__">Add column</button>
                    </div>
                </div>
                <ul class="list-group list-group-flush mt-2" id="messColManagerAliases-__TABLE_ID__"></ul>
            </div>
            <div class="mt-auto pt-2 border-top d-flex gap-2">
                <button type="button" class="btn btn-primary flex-grow-1" id="messColManagerSave-__TABLE_ID__">Save &amp; apply</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Close</button>
            </div>
        </div>
    </div>
</template>
