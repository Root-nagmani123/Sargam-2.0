{{-- Column chooser filled by AthleticsGrid.init(). $table = table id. --}}
<div class="modal fade" id="{{ $table }}Colvis" tabindex="-1" aria-labelledby="{{ $table }}ColvisLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="{{ $table }}ColvisLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 am-colvis" id="{{ $table }}ColvisGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="ux4g-btn-outline-primary ux4g-btn-m" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
