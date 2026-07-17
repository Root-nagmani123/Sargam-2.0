{{-- "Leave the Form?" confirmation, shown by the wizard's Cancel button.
     Shared by create + edit. --}}
<div class="modal fade" id="facultyLeaveModal" tabindex="-1" aria-labelledby="facultyLeaveTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-body text-center px-4 py-5">
                <div class="mb-4">
                    <i class="bi bi-exclamation-circle" style="font-size:3rem;color:#f79009;" aria-hidden="true"></i>
                </div>
                <h5 class="fw-bold mb-2" id="facultyLeaveTitle">Leave the Form?</h5>
                <p class="text-body-secondary mb-4">
                    Are you sure you want to leave the form? No information will be save.
                </p>
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-warning rounded-1 px-4 fw-semibold" data-bs-dismiss="modal">
                        Cancel, Continue Editing
                    </button>
                    <a href="{{ route('faculty.index') }}" class="btn btn-warning rounded-1 px-4 fw-semibold text-white" id="facultyLeaveConfirm">
                        Yes, Leave
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
