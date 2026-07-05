@extends('admin.layouts.master')
@section('title', 'Activity Medical')

@push('styles')
<style>
    .fc-status-grid-hero {
        background: linear-gradient(135deg, rgba(26, 60, 110, 0.06) 0%, rgba(26, 60, 110, 0.02) 100%);
        border: 1px solid rgba(26, 60, 110, 0.12);
        border-radius: 12px;
        padding: 1.1rem 1.35rem;
    }
    .fc-medical-export-toolbar .btn {
        white-space: nowrap;
    }
</style>
@endpush

@section('setup_content')
<div class="container-fluid px-3">
    <x-breadcrum title="FC Activities — Medical" :showStatusPill="false"></x-breadcrum>

    <div class="fc-status-grid-hero mb-3">
        <div class="row align-items-start g-3">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="fw-bold mb-0" style="color: #1a3c6e;">Medical — trainees</h4>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-normal">Medical</span>
                </div>
                <p class="text-muted small mb-0 lh-lg">
                    Filter the list below, then use <strong>Print</strong>, <strong>PDF</strong>, or <strong>Excel</strong> to export the <em>same</em> filtered rows. Exports use the LBSNAA report layout.
                </p>
            </div>
            <div class="col-lg-5">
                <div class="d-flex flex-wrap gap-2 justify-content-lg-end align-items-center fc-medical-export-toolbar">
                    <a href="{{ route('fc-reg.admin.activities.status.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-building"></i> Department picker
                    </a>
                    <a href="{{ route('fc-reg.admin.activities.index') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-house"></i> Activities home
                    </a>
                    <a href="{{ route('fc-reg.admin.activities.reports.summary') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-graph-up"></i> Reports
                    </a>
                    <a href="#" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" id="medicalExportPrint" target="_blank" rel="noopener" title="Print (current filters)">
                        <i class="bi bi-printer"></i> Print
                    </a>
                    <a href="#" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" id="medicalExportPdf" title="Download PDF (current filters)">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                    <a href="#" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" id="medicalExportExcel" title="Download Excel (current filters)">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-2 align-items-end mt-2 pt-2 border-top border-white border-opacity-50">
            <div class="col-md-4 col-lg-3">
                <label class="form-label small mb-1">Course</label>
                <select id="medicalFilterCourse" class="form-select form-select-sm">
                    <option value="">All courses</option>
                    @if($activeCourses->isNotEmpty())
                        <optgroup label="— Active —">
                            @foreach($activeCourses as $c)
                                <option value="{{ $c->pk }}">{{ $c->course_name }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                    @if($archivedCourses->isNotEmpty())
                        <optgroup label="— Archived —">
                            @foreach($archivedCourses as $c)
                                <option value="{{ $c->pk }}">{{ $c->course_name }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label small mb-1">Service (contains)</label>
                <input type="text" id="medicalFilterService" class="form-control form-control-sm" placeholder="Optional" autocomplete="off">
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label small mb-1">Consultation marked</label>
                <select id="medicalFilterConsultation" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <button type="button" id="medicalApplyFilters" class="btn btn-primary btn-sm w-100 w-md-auto">Apply filters</button>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table id="fcMedicalOtTable" class="table table-sm table-hover mb-0 w-100" data-export-title="FC Medical trainees">
                <thead class="table-light">
                    <tr>
                        <th>OT name</th>
                        <th>OT code</th>
                        <th>Course</th>
                        <th>Service</th>
                        <th>Pre-history</th>
                        <th>Consultation</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMedicalPreHistory" tabindex="-1" aria-labelledby="modalMedicalPreHistoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="modalMedicalPreHistoryLabel">Pre-medical history</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-2" id="modalMedicalPreHistoryBody">
                <div class="text-muted small">Loading…</div>
            </div>
        </div>
    </div>
</div>
@endsection

@include('admin.fc-activities.partials.datatable-tools')

@push('scripts')
<script>
$(function () {
    var exportPrintBase = @json(route('fc-reg.admin.activities.medical.export.print'));
    var exportPdfBase = @json(route('fc-reg.admin.activities.medical.export.pdf'));
    var exportExcelBase = @json(route('fc-reg.admin.activities.medical.export.excel'));

    function medicalExportQueryString() {
        var p = new URLSearchParams();
        var c = $('#medicalFilterCourse').val();
        var s = $('#medicalFilterService').val();
        if (s) {
            s = String(s).trim();
        }
        var cons = $('#medicalFilterConsultation').val();
        if (c) {
            p.set('course_filter', c);
        }
        if (s) {
            p.set('service_filter', s);
        }
        if (cons !== '' && cons !== undefined && cons !== null) {
            p.set('consultation_filter', cons);
        }
        return p.toString();
    }

    function refreshMedicalExportLinks() {
        var q = medicalExportQueryString();
        var suffix = q ? ('?' + q) : '';
        $('#medicalExportPrint').attr('href', exportPrintBase + suffix);
        $('#medicalExportPdf').attr('href', exportPdfBase + suffix);
        $('#medicalExportExcel').attr('href', exportExcelBase + suffix);
    }

    refreshMedicalExportLinks();
    $('#medicalFilterCourse, #medicalFilterConsultation').on('change', refreshMedicalExportLinks);
    $('#medicalFilterService').on('input', refreshMedicalExportLinks);

    var $t = $('#fcMedicalOtTable');
    if (!$t.length || !$.fn.DataTable) {
        return;
    }

    var dt = $t.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('fc-reg.admin.activities.medical.data')),
            type: 'GET',
            data: function (d) {
                d.course_filter = $('#medicalFilterCourse').val() || '';
                d.service_filter = ($('#medicalFilterService').val() || '').trim();
                d.consultation_filter = $('#medicalFilterConsultation').val() || '';
            }
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[0, 'asc']],
        scrollX: true,
        autoWidth: false,
        dom: '<"row align-items-center mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row align-items-center mt-2"<"col-md-5"i><"col-md-7"p>>',
        columnDefs: [
            { targets: -1, className: 'text-nowrap text-end' },
            { targets: -2, className: 'text-nowrap', orderable: true, searchable: false }
        ],
        columns: [
            { data: 'otname', name: 'otname' },
            { data: 'otcode', name: 'otcode' },
            { data: 'course_display_name', name: 'course_display_name', searchable: false, orderable: false },
            { data: 'service', name: 'service' },
            { data: 'pre_history_badge', name: 'pre_history_exists', orderable: true, searchable: false },
            { data: 'consultation', name: 'consultation_required', orderable: true, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#medicalApplyFilters').on('click', function () {
        refreshMedicalExportLinks();
        dt.ajax.reload(null, false);
    });

    var modalEl = document.getElementById('modalMedicalPreHistory');
    var modalBody = document.getElementById('modalMedicalPreHistoryBody');
    var modalTitle = document.getElementById('modalMedicalPreHistoryLabel');

    $(document).on('change', '.js-medical-consultation', function () {
        var $cb = $(this);
        var id = $cb.data('id');
        var url = $cb.data('url');
        var checked = $cb.prop('checked');
        var token = $('meta[name="csrf-token"]').attr('content');
        if (!id || !url || !token) return;
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: token,
                fc_ot_detail_id: id,
                consultation_required: checked ? 1 : 0
            },
            error: function () {
                $cb.prop('checked', !checked);
                alert('Could not update consultation flag.');
            }
        });
    });

    $(document).on('click', '.js-medical-prehistory', function () {
        var url = $(this).data('url');
        var otname = $(this).data('otname') || '';
        var otcode = $(this).data('otcode') || '';
        if (!url || !modalEl || !modalBody) return;

        modalTitle.textContent = 'Pre-medical — ' + otname + ' (' + otcode + ')';
        modalBody.innerHTML = '<div class="text-muted small py-3">Loading…</div>';

        if (window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        $.getJSON(url)
            .done(function (res) {
                modalBody.innerHTML = res.html || '<p class="text-muted mb-0">No content.</p>';
                if (res.course) {
                    modalTitle.textContent = 'Pre-medical — ' + (res.otname || otname) + ' (' + (res.otcode || otcode) + ') — ' + res.course;
                }
            })
            .fail(function () {
                modalBody.innerHTML = '<div class="alert alert-danger mb-0 small">Could not load pre-medical history.</div>';
            });
    });
});
</script>
@endpush
