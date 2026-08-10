{{-- Change Request modal shell + wiring. Included by the Request For Estate
     listing and by the View Request page, so both raise a change request the
     same way. The body is fetched per row from admin.estate.raise-change-request.modal.

     On success it dispatches `rfe:change-request-created` on `document`
     (detail: { message }); the including page decides what to refresh.
     On a load failure it dispatches `rfe:change-request-error` (detail: { message }). --}}
<div class="modal fade ds-modal" id="raiseChangeRequestModal" tabindex="-1" aria-labelledby="raiseChangeRequestModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="raiseChangeRequestModalLabel">Change Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="raiseChangeRequestModalContent">
                <div class="modal-body text-center text-body-secondary py-5">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading…
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    var modalEl = document.getElementById('raiseChangeRequestModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    var changeModal = new bootstrap.Modal(modalEl);
    var $content = $('#raiseChangeRequestModalContent');
    var modalUrlTemplate = @json(route('admin.estate.raise-change-request.modal', ['id' => '__ID__']));

    function emit(name, message) {
        document.dispatchEvent(new CustomEvent(name, { detail: { message: message } }));
    }

    // The trigger keeps its href to the standalone page (ctrl-click / no-JS),
    // so intercept plain left clicks only.
    $(document).on('click', '.btn-raise-change-request', function(e) {
        if (e.which > 1 || e.ctrlKey || e.metaKey || e.shiftKey) return;
        e.preventDefault();

        var id = $(this).data('id');
        $content.html('<div class="modal-body text-center text-body-secondary py-5">' +
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading…</div>');
        changeModal.show();

        $.get(modalUrlTemplate.replace('__ID__', id))
            .done(function(html) {
                $content.html(html);
                // The partial ships its own cascade wiring; re-run it for the
                // markup we just injected.
                if (typeof window.initRaiseChangeRequestCascade === 'function') {
                    window.initRaiseChangeRequestCascade(modalEl);
                }
            })
            .fail(function(xhr) {
                changeModal.hide();
                emit('rfe:change-request-error', (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Unable to open the change request form.');
            });
    });

    $(document).on('submit', '#formRaiseChangeRequestModal', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('.js-change-submit');
        var $errors = $form.find('.js-change-errors');

        $errors.addClass('d-none').find('span').empty();
        $form.find('.field-error').empty();
        $form.find('.is-invalid').removeClass('is-invalid');
        $btn.prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function(res) {
                changeModal.hide();
                emit('rfe:change-request-created', (res && res.message) || 'Change request raised successfully.');
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function(key, msgs) {
                        var msg = Array.isArray(msgs) ? msgs[0] : msgs;
                        var $err = $form.find('.field-error[data-field="' + key + '"]');
                        if ($err.length) $err.text(msg);
                        $form.find('[name="' + key + '"]').addClass('is-invalid');
                    });
                    return; // field-level messages already explain the failure
                }
                $errors.removeClass('d-none').find('span').text(
                    (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Something went wrong. Please try again.'
                );
            },
            complete: function() { $btn.prop('disabled', false); }
        });
    });
});
</script>
@endpush
