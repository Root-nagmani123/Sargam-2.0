{{-- Estate Master delete confirmation (Figma "Confirm Delete?", .ds-modal-confirm).
     The page sets #{key}DeleteForm's action to the row's destroy URL before showing it. --}}
<div class="modal fade ds-modal ds-modal-confirm em-modal" id="{{ $key }}DeleteModal" tabindex="-1"
    aria-labelledby="{{ $key }}DeleteModalLabel" aria-describedby="{{ $key }}DeleteText" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="{{ $key }}DeleteForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <div class="ds-confirm-icon" aria-hidden="true">!</div>
                    <h5 class="ds-confirm-title" id="{{ $key }}DeleteModalLabel">Confirm Delete?</h5>
                    <p class="ds-confirm-text" id="{{ $key }}DeleteText">Are you sure you want to delete the selected record? This action can’t be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ds-btn-primary em-btn-delete">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    // One submit only; the back/forward cache would restore the disabled state, so undo it.
    var $f = $('#' + @json($key) + 'DeleteForm');
    $f.on('submit', function () { $f.find('[type="submit"]').prop('disabled', true); });
    $(window).on('pageshow', function () { $f.find('[type="submit"]').prop('disabled', false); });
});
</script>
@endpush
