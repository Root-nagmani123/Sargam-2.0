{{-- Shared "Confirm Delete?" dialog for the Club/ Society module.

     Include once per page, then call csConfirmDelete() in place of Swal.fire().
     It resolves with the usual SweetAlert result, so callers keep checking
     result.isConfirmed. SweetAlert2 is loaded globally by the admin layout. --}}

@push('styles')
<style>
    .cs-confirm-delete.swal2-popup {
        width: 410px;
        padding: 24px;
        border-radius: 12px;
    }

    .cs-confirm-delete .swal2-icon.cs-confirm-delete-icon {
        width: 82px;
        height: 82px;
        margin: 0 auto 16px;
        border: 0;
        background: #ffd6dd;
        color: #ff0040;
    }

    .cs-confirm-delete .swal2-icon.cs-confirm-delete-icon .swal2-icon-content {
        font-size: 30px;
        font-weight: 400;
    }

    .cs-confirm-delete .swal2-title {
        padding: 0;
        font-size: 20px;
        font-weight: 600;
        color: #000;
    }

    .cs-confirm-delete .swal2-html-container {
        margin: 8px 0 0;
        font-size: 14px;
        color: #333;
    }

    .cs-confirm-delete .swal2-actions {
        gap: 16px;
        margin-top: 24px;
    }

    .cs-confirm-delete .swal2-actions .btn {
        min-width: 100px;
        height: 48px;
        padding: 0 24px;
        border-radius: 4px;
        font-weight: 500;
    }

    .cs-confirm-delete .cs-confirm-delete-ok {
        min-width: 133px;
        background: #ff0040;
        border-color: #ff0040;
        color: #fff;
    }

    .cs-confirm-delete .cs-confirm-delete-ok:hover,
    .cs-confirm-delete .cs-confirm-delete-ok:focus {
        background: #e0003a;
        border-color: #e0003a;
        color: #fff;
    }

    .cs-confirm-delete .cs-confirm-delete-cancel {
        background: #fff;
        border: 1px solid #ff0040;
        color: #ff0040;
    }

    .cs-confirm-delete .cs-confirm-delete-cancel:hover,
    .cs-confirm-delete .cs-confirm-delete-cancel:focus {
        background: #fff0f3;
        color: #ff0040;
    }
</style>
@endpush

@push('scripts')
<script>
    window.csConfirmDelete = function () {
        return Swal.fire({
            title: 'Confirm Delete?',
            text: "Are you sure you want to delete this record? This action can't be undone.",
            iconHtml: '!',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'cs-confirm-delete',
                icon: 'cs-confirm-delete-icon',
                confirmButton: 'btn cs-confirm-delete-ok',
                cancelButton: 'btn cs-confirm-delete-cancel'
            }
        });
    };
</script>
@endpush
