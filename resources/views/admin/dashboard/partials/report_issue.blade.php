{{-- Floating "Report Issue" launcher + "Report a problem" modal (dashboard only) --}}
<button type="button" class="dash-report-fab" data-bs-toggle="modal" data-bs-target="#reportIssueModal"
    aria-label="Report an issue">
    <i class="bi bi-exclamation-lg dash-report-fab-icon" aria-hidden="true"></i>
    <span class="dash-report-fab-label">Report Issue</span>
</button>

<div class="modal fade" id="reportIssueModal" tabindex="-1" aria-labelledby="reportIssueModalLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content dash-report-modal">
            <form id="reportIssueForm" novalidate>
                @csrf
                <input type="hidden" name="page_url" value="{{ url()->current() }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="reportIssueModalLabel">Report a problem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reportIssueModule" class="form-label">Department that you are facing issues
                            with<span class="text-danger">*</span></label>
                        <select class="form-select" id="reportIssueModule" name="menu_group_id" required>
                            <option value="">Select Department</option>
                            @foreach ($issueReportModules ?? [] as $module)
                                <option value="{{ $module['id'] }}">{{ $module['name'] }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" data-error-for="menu_group_id"></div>
                    </div>

                    <div class="mb-3">
                        <label for="reportIssueSubModule" class="form-label">Sub-Module</label>
                        <input type="text" class="form-control" id="reportIssueSubModule" name="sub_module"
                            placeholder="eg. OT Attendance" maxlength="255">
                        <div class="invalid-feedback" data-error-for="sub_module"></div>
                    </div>

                    <div class="mb-3">
                        <label for="reportIssueDescription" class="form-label">Issue Description<span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" id="reportIssueDescription" name="description" rows="4"
                            placeholder="eg. Lorem ipsum dolor sit" maxlength="5000" required></textarea>
                        <div class="invalid-feedback" data-error-for="description"></div>
                    </div>

                    <div class="mb-1">
                        <label for="reportIssueAttachment" class="form-label">Attachment</label>
                        <input type="file" class="form-control" id="reportIssueAttachment" name="attachment"
                            accept=".jpg,.jpeg,.png,.pdf,.csv,.xlsx">
                        <div class="invalid-feedback" data-error-for="attachment"></div>
                        <div class="dash-report-hint">Supported Documents: .jpg .png .pdf .csv .xlsx</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="reportIssueSubmit">Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            var form = document.getElementById('reportIssueForm');
            if (!form) return;

            var modalEl = document.getElementById('reportIssueModal');
            var submitBtn = document.getElementById('reportIssueSubmit');

            function clearErrors() {
                form.querySelectorAll('.is-invalid').forEach(function(el) {
                    el.classList.remove('is-invalid');
                });
            }

            function showErrors(errors) {
                Object.keys(errors || {}).forEach(function(field) {
                    var input = form.querySelector('[name="' + field + '"]');
                    var slot = form.querySelector('[data-error-for="' + field + '"]');
                    if (slot) slot.textContent = errors[field][0];
                    if (input) input.classList.add('is-invalid');
                });
            }

            modalEl.addEventListener('hidden.bs.modal', function() {
                form.reset();
                clearErrors();
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                clearErrors();

                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Reporting...';

                fetch('{{ route('admin.dashboard.report-issue') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new FormData(form)
                    })
                    .then(function(res) {
                        return res.json().then(function(data) {
                            return {
                                ok: res.ok,
                                data: data
                            };
                        });
                    })
                    .then(function(result) {
                        if (result.ok && result.data.success) {
                            bootstrap.Modal.getInstance(modalEl).hide();
                            Swal.fire({
                                icon: 'success',
                                title: 'Reported',
                                text: result.data.message
                            });
                            return;
                        }
                        if (result.data.errors) showErrors(result.data.errors);
                        if (!result.data.errors) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Could not report',
                                text: result.data.message || 'Please try again.'
                            });
                        }
                    })
                    .catch(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Could not report',
                            text: 'Network error. Please try again.'
                        });
                    })
                    .finally(function() {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Report';
                    });
            });
        })();
    </script>
@endpush
