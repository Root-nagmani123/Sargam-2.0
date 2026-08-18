{{-- Floating "Report Issue" launcher + "Report a problem" modal (dashboard only) --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('css/issue-reports-admin.css') }}?v={{ @filemtime(public_path('css/issue-reports-admin.css')) ?: time() }}">
@endpush

<button type="button" class="dash-report-fab" data-bs-toggle="modal" data-bs-target="#reportIssueModal"
    aria-label="Report an issue" title="Report Issue">
    {{-- Sticker badge: warning mark over "Report Issue" wrapped on the disc's bottom arc. --}}
    <svg class="dash-report-fab-art" viewBox="0 0 96 96" aria-hidden="true" focusable="false">
        <defs>
            <path id="dashReportArc" d="M 12,48 A 36,36 0 0 0 84,48" />
        </defs>
        <g class="dash-report-fab-mark">
            <circle cx="48" cy="34" r="15" />
            <path d="M48 26.5V37" />
        </g>
        <circle class="dash-report-fab-dot" cx="48" cy="42.6" r="2.1" />
        <text class="dash-report-fab-text">
            <textPath href="#dashReportArc" xlink:href="#dashReportArc" startOffset="50%"
                text-anchor="middle">Report Issue</textPath>
        </text>
    </svg>
</button>

{{-- Same modal language as the Reported Issues screens — docs/new-design-index-page.md §3c --}}
<div class="modal fade ir-modal" id="reportIssueModal" tabindex="-1" aria-labelledby="reportIssueModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="reportIssueForm" novalidate>
                @csrf
                <input type="hidden" name="page_url" value="{{ url()->current() }}">

                <div class="ir-modal-header">
                    <h5 class="ir-modal-title" id="reportIssueModalLabel">Report a problem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="ir-modal-body">
                    <div class="ir-field-card">
                        <div class="ir-field">
                            <label for="reportIssueModule" class="ir-form-label">Department that you are facing
                                issues with<span class="ir-req">*</span></label>
                            <select class="form-select ir-control" id="reportIssueModule" name="menu_group_id" required>
                                <option value="">Select Department</option>
                                @foreach ($issueReportModules ?? [] as $module)
                                    <option value="{{ $module['id'] }}">{{ $module['name'] }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-error-for="menu_group_id"></div>
                        </div>

                        <div class="ir-field">
                            <label for="reportIssueSubModule" class="ir-form-label">Sub-Module</label>
                            <input type="text" class="form-control ir-control" id="reportIssueSubModule"
                                name="sub_module" placeholder="eg. OT Attendance" maxlength="255">
                            <div class="invalid-feedback" data-error-for="sub_module"></div>
                        </div>

                        <div class="ir-field">
                            <label for="reportIssueDescription" class="ir-form-label">Issue Description<span
                                    class="ir-req">*</span></label>
                            <textarea class="form-control ir-control" id="reportIssueDescription" name="description"
                                rows="4" placeholder="eg. Unable to submit OT attendance for July" maxlength="5000"
                                required></textarea>
                            <div class="invalid-feedback" data-error-for="description"></div>
                        </div>

                        <div class="ir-field">
                            <label for="reportIssueAttachment" class="ir-form-label">Attachment</label>
                            <input type="file" class="form-control ir-control" id="reportIssueAttachment"
                                name="attachment" accept=".jpg,.jpeg,.png,.pdf,.csv,.xlsx">
                            <div class="invalid-feedback" data-error-for="attachment"></div>
                            <div class="ir-hint">Supported Documents: .jpg .png .pdf .csv .xlsx</div>
                        </div>
                    </div>
                </div>

                <div class="ir-modal-footer text-end d-flex justify-content-end">
                    <button type="button" class="btn ir-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ir-btn-submit" id="reportIssueSubmit">Report</button>
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
