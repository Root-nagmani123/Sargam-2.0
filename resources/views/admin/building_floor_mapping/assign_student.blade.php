@extends('admin.layouts.master')

@section('title', 'Assign Student Hostel')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
<style>
    /* Import modal: drag-and-drop upload target. */
    .mst-modal.as-import-modal .as-upload-dropzone {
        border: 2px dashed var(--ds-line);
        background: var(--ds-surface);
        padding: var(--ds-space-5) var(--ds-space-4);
        cursor: pointer;
        transition: border-color .15s ease, background-color .15s ease;
    }
    .mst-modal.as-import-modal .as-upload-dropzone:hover,
    .mst-modal.as-import-modal .as-upload-dropzone.is-dragover {
        border-color: var(--ds-primary);
        background: var(--ds-surface-2);
    }
    .mst-modal.as-import-modal .as-upload-dropzone:focus-visible {
        outline: 0;
        border-color: var(--ds-primary);
        box-shadow: var(--ds-focus-ring);
    }
    .mst-modal.as-import-modal .as-upload-icon {
        font-size: 2.5rem;
        color: var(--ds-ink-muted);
        line-height: 1;
    }
    .mst-modal.as-import-modal .as-step-hint {
        color: var(--ds-ink-muted);
        font-size: 0.8125rem;
        margin: calc(-1 * var(--ds-space-2)) 0 var(--ds-space-3);
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid mst-page assign-student-page">
    <x-breadcrum title="Assign Student Hostel" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Assign Student Hostel via Import</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card (§1). Download is
         the controller's one .xlsx export; Print prints this screen, and the
         master-admin.css print rules drop the toolbar, pager and Action column. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 mst-secondary-actions">
        <a href="{{ route('hostel.building.map.export') }}"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel (.xlsx)">
            <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
        </a>
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="asPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="asBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#asColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="asDtSearch" class="programme-dt-search" data-dt-search-for="othostelroomdetails-table"></div>
                </div>
            </div>

            {{-- Search, pager and "Showing N of M items" are relocated into the
                 slots by public/js/datatable-global-ui.js. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="asDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="othostelroomdetails-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="asColumnVisibilityModal" tabindex="-1" aria-labelledby="asColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="asColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="asColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Import Excel: Step 1 upload -> Step 2 preview -> assign -->
<div class="modal fade mst-modal as-import-modal" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" enctype="multipart/form-data" id="importExcelForm">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="importModalLabel">Assign Student Hostel via Import</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{-- Progress: 50% on step 1, 100% on step 2 (driven by the script). --}}
                    <div class="progress rounded-1 mb-4" style="height: 6px;" role="progressbar"
                         aria-label="Import progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
                        <div class="progress-bar bg-primary rounded-1" id="asImportProgress" style="width: 50%;"></div>
                    </div>

                    {{-- Step 1: upload --}}
                    <div id="asImportStep1">
                        <h2 class="mst-form-section-title h6 mb-2">Step 1 of 2 · Choose the course and file</h2>
                        <p class="as-step-hint">Nothing is saved yet — the next step shows what the file contains.</p>

                        <div class="mst-field-card">
                            <div class="mb-3">
                                <label for="importCourse" class="mst-form-label d-block">
                                    Select Course <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select name="course_master_pk" id="importCourse"
                                        class="form-select mst-control mst-searchable"
                                        data-placeholder="Select Course" required aria-required="true">
                                    <option value="">-- Select Course --</option>
                                    @foreach ($courses as $pk => $name)
                                        <option value="{{ $pk }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback d-none" id="importCourseError">Please select a course.</div>
                            </div>

                            <span class="mst-form-label d-block" id="asImportFileLabel">
                                Excel / CSV File <span class="mst-req" aria-hidden="true">*</span>
                            </span>
                            <div class="as-upload-dropzone rounded-3 text-center" id="asUploadDropzone" role="button" tabindex="0"
                                 aria-labelledby="asImportFileLabel" aria-describedby="asImportFileHelp">
                                <i class="bi bi-file-earmark-arrow-up as-upload-icon d-block mb-2" aria-hidden="true"></i>
                                <p class="fw-semibold text-body mb-1">Drag or click here to upload your file</p>
                                <p class="text-muted small mb-0" id="asImportFileHelp">
                                    Columns: <strong>user_name</strong> &amp; <strong>hostel_room_name</strong> |
                                    Allowed: .xlsx, .xls, .csv | Max 10 MB |
                                    <a href="{{ asset('admin_assets/sample/ot_hostel_excel_upload.xlsx') }}" class="text-primary fw-semibold" download>Sample File</a>
                                </p>
                                <p class="small text-primary fw-medium mt-2 mb-0 d-none" id="asImportFileName"></p>
                            </div>
                            <input type="file" name="file" id="importFile" class="visually-hidden" accept=".xlsx, .xls, .csv" required>
                        </div>

                        <div id="importErrors" class="alert alert-danger d-none mt-3 mb-0" role="alert">
                            <h3 class="h6 mb-2"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> Validation Errors Found</h3>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm align-middle mb-0">
                                    <caption class="visually-hidden">Rows the file could not import</caption>
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" style="width: 10%;">Row</th>
                                            <th scope="col">Errors</th>
                                        </tr>
                                    </thead>
                                    <tbody id="importErrorTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Step 2: preview --}}
                    <div id="asImportStep2" class="d-none">
                        <h2 class="mst-form-section-title h6 mb-2">Step 2 of 2 · Review and assign</h2>
                        <p class="as-step-hint">
                            Course: <strong id="asPreviewCourseName"></strong> — check the rows, then choose
                            <strong>Assign Students Hostel</strong> to save them.
                        </p>
                        <div class="programme-dt-panel">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                                    <caption class="visually-hidden">Rows that will be assigned</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col" class="text-nowrap">S. No.</th>
                                            <th scope="col">User Name</th>
                                            <th scope="col">Hostel Room Name</th>
                                        </tr>
                                    </thead>
                                    <tbody id="asPreviewBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end">
                    {{-- .btn-cancel is a hook: custom.js resets #importExcelForm on it. --}}
                    <button type="button" class="btn mst-btn-cancel px-4 btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn mst-btn-submit px-4" id="asImportNext">Next</button>
                    <button type="button" class="btn mst-btn-submit px-4 d-none" id="asImportAssign">Assign Students Hostel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
<script>
    $(document).ready(function () {
        var TABLE_ID = '#othostelroomdetails-table';

        /* Search box, pagination and the "Showing N of M items" count are relocated
           into #asDtSearch / #asDtFooter by the global enhancer
           (public/js/datatable-global-ui.js). Do NOT rebuild them here. */

        /* ---- Column show / hide (shared Columns modal) ---- */
        MstAdmin.columnVisibility({
            table: TABLE_ID,
            grid: '#asColumnToggleGrid',
            storageKey: 'sargam.assignStudentHostel.hiddenCols.{{ auth()->id() ?? 'guest' }}'
        });

        /* ---- Print ---- */
        $('#asPrintBtn').on('click', function () {
            window.print();
        });

        /* ===========================================================
           Import wizard: Step 1 (upload) -> Step 2 (preview) -> commit
           =========================================================== */
        var PREVIEW_URL = '{{ route("hostel.building.map.assign.hostel.to.student.preview") }}';
        var COMMIT_URL  = '{{ route("hostel.building.map.assign.hostel.to.student") }}';

        var $importModalEl = document.getElementById('importModal');
        var $fileInput = $('#importFile');

        // File contents are rendered as text, never as markup.
        function asEsc(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function asResetWizard() {
            $('#asImportStep1').removeClass('d-none');
            $('#asImportStep2').addClass('d-none');
            $('#asImportProgress').css('width', '50%').parent().attr('aria-valuenow', 50);
            $('#asImportNext').removeClass('d-none').prop('disabled', false).text('Next');
            $('#asImportAssign').addClass('d-none').prop('disabled', false).text('Assign Students Hostel');
            $('#asImportFileName').addClass('d-none').text('');
            $('#importErrors').addClass('d-none');
            $('#importCourseError').addClass('d-none');
            $('#importCourse').removeClass('is-invalid');
            $('#importErrorTableBody').empty();
            $('#asPreviewBody').empty();
            $('#asPreviewCourseName').text('');
            try { $('#importExcelForm')[0].reset(); } catch (e) {}
            // The course select is Select2: repaint it after the native reset.
            $('#importCourse').trigger('change.select2');
        }

        function asShowFailures(failures) {
            var $body = $('#importErrorTableBody').empty();
            (failures || []).forEach(function (f) {
                $body.append('<tr><td><span class="text-danger">' + asEsc(f.row) + '</span></td>' +
                    '<td><span class="text-danger">' + (f.errors || []).map(asEsc).join('<br>') + '</span></td></tr>');
            });
            $('#importErrors').removeClass('d-none');
        }

        function asValidFile() {
            var input = $fileInput[0];
            if (!input.files || !input.files.length) {
                alert('Please select a file to upload.');
                return false;
            }
            if (!/\.(xlsx|xls|csv)$/i.test(input.files[0].name)) {
                alert('Invalid file type. Please upload a .xlsx, .xls, or .csv file.');
                input.value = '';
                $('#asImportFileName').addClass('d-none').text('');
                return false;
            }
            return true;
        }

        // Dropzone: click + keyboard
        $('#asUploadDropzone').on('click', function (e) {
            if ($(e.target).closest('a').length) { return; } // the Sample File link downloads, not uploads
            $fileInput.trigger('click');
        });
        $('#asUploadDropzone').on('keydown', function (e) {
            if (e.target !== this) { return; }
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $fileInput.trigger('click'); }
        });
        $fileInput.on('change', function () {
            if (this.files && this.files.length) {
                $('#asImportFileName').removeClass('d-none').text(this.files[0].name);
                $('#importErrors').addClass('d-none');
            }
        });

        // Dropzone: drag & drop
        $('#asUploadDropzone').on('dragover', function (e) {
            e.preventDefault(); e.stopPropagation(); $(this).addClass('is-dragover');
        });
        $('#asUploadDropzone').on('dragleave drop', function (e) {
            e.preventDefault(); e.stopPropagation(); $(this).removeClass('is-dragover');
        });
        $('#asUploadDropzone').on('drop', function (e) {
            var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
            if (!files || !files.length) { return; }
            try {
                var dt = new DataTransfer();
                dt.items.add(files[0]);
                $fileInput[0].files = dt.files;
                $fileInput.trigger('change');
            } catch (err) {
                alert('Could not attach the dropped file. Please click to upload.');
            }
        });

        // Step 1 -> 2 : preview (parse without saving)
        $('#asImportNext').on('click', function () {
            var courseVal = $('#importCourse').val();
            var courseText = $('#importCourse option:selected').text();

            // Validate course selection
            if (!courseVal) {
                $('#importCourse').addClass('is-invalid');
                $('#importCourseError').removeClass('d-none');
                return;
            }
            $('#importCourse').removeClass('is-invalid');
            $('#importCourseError').addClass('d-none');

            if (!asValidFile()) { return; }
            $('#importErrors').addClass('d-none');

            var $btn = $(this).prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Checking...');

            var formData = new FormData($('#importExcelForm')[0]);

            $.ajax({
                url: PREVIEW_URL,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function (response) {
                    var rows = (response && response.rows) ? response.rows : [];
                    var $body = $('#asPreviewBody').empty();
                    $('#asPreviewCourseName').text(courseText);
                    if (!rows.length) {
                        $body.append('<tr class="mst-empty"><td colspan="3">No rows found in the file.</td></tr>');
                    }
                    rows.forEach(function (r, i) {
                        $body.append('<tr>' +
                            '<td>' + (i + 1) + '</td>' +
                            '<td>' + asEsc(r.user_name || '') + '</td>' +
                            '<td>' + asEsc(r.hostel_room_name || '') + '</td>' +
                            '</tr>');
                    });

                    $('#asImportStep1').addClass('d-none');
                    $('#asImportStep2').removeClass('d-none');
                    $('#asImportProgress').css('width', '100%').parent().attr('aria-valuenow', 100);
                    $('#asImportNext').addClass('d-none');
                    $('#asImportAssign').removeClass('d-none');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.failures) {
                        asShowFailures(xhr.responseJSON.failures);
                    } else {
                        alert((xhr.responseJSON && xhr.responseJSON.message) || 'Could not read the file. Please try again.');
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Next');
                }
            });
        });

        // Step 2 : commit (actually assigns)
        $('#asImportAssign').on('click', function () {
            var $btn = $(this).prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Assigning...');

            var formData = new FormData($('#importExcelForm')[0]);

            $.ajax({
                url: COMMIT_URL,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function () {
                    if (typeof toastr !== 'undefined') { toastr.success('Students assigned successfully.'); }
                    bootstrap.Modal.getInstance($importModalEl)?.hide();
                    if ($.fn.DataTable.isDataTable(TABLE_ID)) {
                        $(TABLE_ID).DataTable().ajax.reload(null, false);
                    } else {
                        location.reload();
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.failures) {
                        // Surface row errors back on step 1.
                        $('#asImportStep2').addClass('d-none');
                        $('#asImportStep1').removeClass('d-none');
                        $('#asImportProgress').css('width', '50%').parent().attr('aria-valuenow', 50);
                        $('#asImportAssign').addClass('d-none');
                        $('#asImportNext').removeClass('d-none');
                        asShowFailures(xhr.responseJSON.failures);
                    } else {
                        alert((xhr.responseJSON && xhr.responseJSON.message) || 'Assignment failed. Please try again.');
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Assign Students Hostel');
                }
            });
        });

        // Reset wizard whenever the modal opens/closes
        $importModalEl.addEventListener('show.bs.modal', asResetWizard);
        $importModalEl.addEventListener('hidden.bs.modal', asResetWizard);
    });
</script>
@endpush
