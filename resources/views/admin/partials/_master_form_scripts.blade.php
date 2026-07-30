{{-- Shared create/edit-modal behaviour for the simple master lists
     (Country / State / District / City).

     Handles:
       • Opening the Edit modal pre-filled from the row's data-* attributes.
       • Keeping the Status badge in sync when the Actions-column toggle flips.
       • Re-opening the correct modal when server-side validation sends us back.

     Pages that need cascading selects (District / City) can listen for the
     `master:edit-open` event on document — it fires after the generic fields
     are populated and carries the clicked button's dataset.

     @include vars:
       updateUrl    string  — update route containing an __ID__ placeholder
       createModal  string  — id of the create modal (no '#')
       editModal    string  — id of the edit modal (no '#')
       fields       array   — map of data-attribute key => target selector,
                              e.g. ['name' => '#masterEditName', 'status' => '#masterEditStatus']
--}}
<script>
$(function () {
    var UPDATE_URL = @json($updateUrl);
    var CREATE_MODAL = @json($createModal);
    var EDIT_MODAL = @json($editModal);
    var FIELDS = @json($fields ?? []);

    function showModal(id) {
        var el = document.getElementById(id);
        if (el && window.bootstrap) { bootstrap.Modal.getOrCreateInstance(el).show(); }
    }

    /* ---- Edit: populate the modal from the row's data-* attributes ---- */
    $(document).on('click', '.master-edit-btn', function () {
        var data = this.dataset;
        $('#masterEditForm').attr('action', UPDATE_URL.replace('__ID__', data.pk));
        $('#masterEditPk').val(data.pk);

        Object.keys(FIELDS).forEach(function (key) {
            var $target = $(FIELDS[key]);
            if ($target.length) { $target.val(data[key] !== undefined ? data[key] : ''); }
        });

        // Let cascading pages (District / City) refine the selects.
        $(document).trigger('master:edit-open', [data]);

        showModal(EDIT_MODAL);
    });

    /* ---- Status badge follows the Actions-column toggle ----
       The global status-toggle handler persists the change; here we only
       reflect it in the row so the badge doesn't go stale. ---- */
    $(document).on('change', '.status-toggle', function () {
        var $row = $(this).closest('tr');
        var on = this.checked;
        $row.attr('data-status', on ? '1' : '0');
        $row.find('.master-status-badge')
            .toggleClass('bg-success', on)
            .toggleClass('bg-secondary', !on)
            .text(on ? 'Active' : 'Inactive');
        $row.find('.master-edit-btn').attr('data-status', on ? '1' : '0');
    });

@if($errors->any() && old('_form'))
    /* ---- Validation failed: re-open the modal the user was in.
       Field values are restored by `old()` in the modal markup itself; here we
       only need to re-point the edit form at the right record. ---- */
    @if(old('_form') === 'edit')
        (function () {
            var pk = @json(old('_pk'));
            if (pk) { $('#masterEditForm').attr('action', UPDATE_URL.replace('__ID__', pk)); }
            $(document).trigger('master:edit-open', [{ revalidate: true }]);
            showModal(EDIT_MODAL);
        })();
    @else
        showModal(CREATE_MODAL);
    @endif
@endif
});
</script>
