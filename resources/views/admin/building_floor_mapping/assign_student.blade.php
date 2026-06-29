@extends('admin.layouts.master')

@section('title', 'Assign Student Hostel')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    .assign-student-page .as-upload-dropzone,
    #importModal .as-upload-dropzone {
        border: 2px dashed #d0d5dd;
        background: #f9fafb;
        padding: 2.5rem 1.5rem;
        cursor: pointer;
        transition: border-color .15s ease, background-color .15s ease;
    }
    #importModal .as-upload-dropzone:hover,
    #importModal .as-upload-dropzone.is-dragover {
        border-color: #004a93;
        background: #fff;
    }
    #importModal .as-upload-icon {
        font-size: 2.5rem;
        color: #98a2b3;
        line-height: 1;
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid assign-student-page">
    <x-breadcrum title="Assign Student Hostel">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Assign Student Hostel via Import</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Print / Download) --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <button type="button" class="btn programme-dt-btn-columns" id="asPrintBtn" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
        <a href="{{ route('hostel.building.map.export') }}" class="btn programme-dt-btn-columns" title="Download">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="asBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#asColumnVisibilityModal"
                        title="Show / hide columns" style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="asDtSearch" class="programme-dt-search" data-dt-search-for="othostelroomdetails-table"></div>
                </div>
            </div>

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

<!-- Column Visibility Modal -->
<div class="modal fade" id="asColumnVisibilityModal" tabindex="-1" aria-labelledby="asColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="asColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="asColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Import Excel Modal (functionality unchanged) -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" enctype="multipart/form-data" id="importExcelForm">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="importModalLabel">Assign Student Hostel via Import</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{-- Progress --}}
                    <div class="progress rounded-1 mb-4" style="height: 6px;" role="progressbar"
                         aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
                        <div class="progress-bar bg-primary rounded-1" id="asImportProgress" style="width: 50%;"></div>
                    </div>

                    {{-- Step 1: upload --}}
                    <div id="asImportStep1">

                        {{-- Course Selector --}}
                        <div class="mb-3">
                            <label for="importCourse" class="form-label fw-semibold">
                                Select Course <span class="text-danger">*</span>
                            </label>
                            <select name="course_master_pk" id="importCourse" class="form-select" required>
                                <option value="">-- Select Course --</option>
                                @foreach ($courses as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback d-none" id="importCourseError">Please select a course.</div>
                        </div>

                        <div class="as-upload-dropzone rounded-3 text-center" id="asUploadDropzone" role="button" tabindex="0">
                            <i class="bi bi-file-earmark-arrow-up as-upload-icon d-block mb-2" aria-hidden="true"></i>
                            <p class="fw-semibold text-body mb-1">Drag or click here to upload your file</p>
                            <p class="text-muted small mb-0">
                                Allowed: .xlsx, .xls, .csv | Max ~500 MB |
                                <a href="{{ asset('admin_assets/sample/ot_hostel_excel_upload.xlsx') }}" class="text-primary fw-semibold" download>Sample File</a>
                            </p>
                            <p class="small text-primary fw-medium mt-2 mb-0 d-none" id="asImportFileName"></p>
                        </div>
                        <input type="file" name="file" id="importFile" class="visually-hidden" accept=".xlsx, .xls, .csv" required>

                        <div id="importErrors" class="alert d-none mt-3 mb-0">
                            <h6 class="mb-2"><i class="bi bi-exclamation-circle me-1"></i> Validation Errors Found</h6>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 10%;">Row</th>
                                            <th>Errors</th>
                                        </tr>
                                    </thead>
                                    <tbody id="importErrorTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Step 2: preview --}}
                    <div id="asImportStep2" class="d-none">
                        <p class="text-muted small mb-2">
                            Course: <strong id="asPreviewCourseName"></strong>
                        </p>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                                <thead>
                                    <tr>
                                        <th class="text-center">S. No.</th>
                                        <th>User Name</th>
                                        <th>Hostel Room Name</th>
                                    </tr>
                                </thead>
                                <tbody id="asPreviewBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-primary rounded-1 px-4 btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary rounded-1 px-4" id="asImportNext">Next</button>
                    <button type="button" class="btn btn-primary rounded-1 px-4 d-none" id="asImportAssign">Assign Students Hostel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
    $(document).ready(function () {
        var TABLE_ID = '#othostelroomdetails-table';
        var table;

        /* ---- Relocate search + build footer (pagination + count) ---- */
        function enhanceAsDtControls() {
            var $wrapper = $(TABLE_ID + '_wrapper');
            if (!$wrapper.length) {
                return;
            }

            var $searchSlot = $('#asDtSearch');
            var $footer = $('#asDtFooter');

            if (!$searchSlot.find('.dataTables_filter').length) {
                var $filter = $wrapper.find('.dataTables_filter').first();
                if ($filter.length) {
                    $filter.find('input')
                        .addClass('form-control shadow-none')
                        .attr('placeholder', 'Search')
                        .attr('aria-label', 'Search students');
                    $filter.find('label').contents().filter(function () {
                        return this.nodeType === 3;
                    }).remove();
                    $searchSlot.append($filter);
                }
            }

            if ($footer.data('dtReady')) {
                updateAsDtCount();
                return;
            }

            var $paginate = $wrapper.find('.dataTables_paginate').first();
            var $length = $wrapper.find('.dataTables_length').first();
            var $info = $wrapper.find('.dataTables_info').first();

            if (!$footer.length || (!$paginate.length && !$length.length)) {
                return;
            }

            var $pagCol = $('<div class="programme-dt-pagination"></div>');
            var $countCol = $('<div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto"></div>');

            if ($paginate.length) {
                $paginate.find('.pagination').addClass('mb-0');
                $pagCol.append($paginate);
            }

            if ($length.length) {
                var $select = $length.find('select').addClass('form-select form-select-sm');
                $length.find('label')
                    .empty()
                    .append(document.createTextNode('Showing '))
                    .append($select)
                    .append(document.createTextNode(' '));
                $countCol.append($length);
            }

            if ($info.length) {
                $info.addClass('mb-0');
                $countCol.append($info);
            }

            $footer.append($pagCol).append($countCol);
            $footer.data('dtReady', true);
            updateAsDtCount();
        }

        function updateAsDtCount() {
            if (!table) {
                return;
            }
            var info = table.page.info();
            var $info = $('#asDtFooter .dataTables_info');
            if ($info.length && info && info.recordsDisplay !== undefined) {
                $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
            }
        }

        /* ---- Column show / hide (DataTables API) ---- */
        var asColStorageKey = 'asGrid:hiddenColumns:v1';

        function asGetHiddenCols() {
            try {
                var raw = localStorage.getItem(asColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function asPersistHiddenCols(arr) {
            try { localStorage.setItem(asColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupAsColumns(dt) {
            if (!dt) {
                return;
            }
            var hidden = asGetHiddenCols();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#asColumnToggleGrid');
            if (!$grid.length) {
                return;
            }
            $grid.empty();

            dt.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) {
                    return;
                }

                var inputId = 'ascolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = asGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    asPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        /* ---- Wait for Yajra DataTable init ---- */
        setTimeout(function () {
            if (!$.fn.DataTable.isDataTable(TABLE_ID)) {
                return;
            }
            table = $(TABLE_ID).DataTable();

            enhanceAsDtControls();
            updateAsDtCount();
            setupAsColumns(table);

            var $wrapper = $(TABLE_ID + '_wrapper');
            $(TABLE_ID).on('draw.dt', function () {
                if ($wrapper.find('.dataTables_paginate').length && !$('#asDtFooter .dataTables_paginate').length) {
                    $('#asDtFooter').empty().data('dtReady', false);
                    enhanceAsDtControls();
                }
                updateAsDtCount();
            });

            setTimeout(function () {
                enhanceAsDtControls();
                updateAsDtCount();
            }, 300);
        }, 150);

        /* ---- Print ---- */
        $('#asPrintBtn').on('click', function () {
            window.print();
        });

        /* ===========================================================
           Import wizard: Step 1 (upload) -> Step 2 (preview) -> commit
           =========================================================== */
        var PREVIEW_URL = '{{ route("hostel.building.map.assign.hostel.to.student.preview") }}';
        var COMMIT_URL  = '{{ route("hostel.building.map.assign.hostel.to.student") }}';
        var CSRF = $('meta[name="csrf-token"]').attr('content');

        var $importModalEl = document.getElementById('importModal');
        var $fileInput = $('#importFile');

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
        }

        function asShowFailures(failures) {
            var $body = $('#importErrorTableBody').empty();
            (failures || []).forEach(function (f) {
                $body.append('<tr><td><span class="text-danger">' + f.row + '</span></td>' +
                    '<td><span class="text-danger">' + (f.errors || []).join('<br>') + '</span></td></tr>');
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
        $('#asUploadDropzone').on('click', function () { $fileInput.trigger('click'); });
        $('#asUploadDropzone').on('keydown', function (e) {
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
                        $body.append('<tr><td colspan="3" class="text-center text-muted py-3">No rows found in the file.</td></tr>');
                    }
                    rows.forEach(function (r, i) {
                        $body.append('<tr>' +
                            '<td class="text-center">' + (i + 1) + '</td>' +
                            '<td>' + (r.user_name || '') + '</td>' +
                            '<td>' + (r.hostel_room_name || '') + '</td>' +
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
