{{-- COE question papers — Confirm Delete. Shared by every coe-* grid.
     Open it with bootstrap.Modal on #{{ $id ?? 'qpDeleteModal' }}; the page binds
     #{{ $confirmId ?? 'qpDeleteConfirm' }} to do the delete. --}}
<div class="modal fade" id="{{ $id ?? 'qpDeleteModal' }}" tabindex="-1"
     aria-labelledby="{{ $id ?? 'qpDeleteModal' }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content coe-modal border-0 shadow">
            <div class="coe-confirm">
                <div class="coe-confirm__icon coe-confirm__icon--danger" aria-hidden="true">!</div>
                <h5 class="coe-confirm__title" id="{{ $id ?? 'qpDeleteModal' }}Label">Confirm Delete?</h5>
                <p class="coe-confirm__text">Are you sure you want to delete selected record? This action can't be undone.</p>
                <div class="coe-confirm__actions">
                    <button type="button" class="btn coe-btn coe-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn coe-btn coe-btn-danger" id="{{ $confirmId ?? 'qpDeleteConfirm' }}">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
