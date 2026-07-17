@extends('admin.layouts.master')
@php
    $vitals = $vitalsOrdered ?? collect();
    $nm = trim((string) $ot->otname);
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($nm !== '' ? $nm : '?', 0, 1));
    $medicalReportBreadcrumbs = [
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'FC Activities', 'url' => route('fc-reg.admin.activities.index')],
        ['label' => 'Medical', 'url' => route('fc-reg.admin.activities.medical.index')],
        ['label' => 'Report: '.$ot->otname],
    ];
    $medicalReportPdfFilename = 'medical-report-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($ot->otcode ?? 'ot')).'.pdf';
    $pathologyHasRecord = ($pathReports ?? collect())->isNotEmpty() || ($finalFindings ?? collect())->isNotEmpty();
@endphp
@section('title', 'Medical report — '.$ot->otname)
@push('styles')
<style>
    /* html2pdf captures the screen (not @media print) — hide entry-only blocks during PDF generation */
    #fcMedicalReportPrintRoot.is-pdf-capture .no-print {
        display: none !important;
    }

    @media print {
        @page { size: A4; margin: 12mm; }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        #sargamLoader,
        .sargam-loader-overlay,
        header.topbar,
        aside.side-mini-panel,
        aside.with-vertical,
        .sidebar-google-style,
        #fcMedicalReportToolbar,
        .no-print,
        .d-print-none { display: none !important; }

        #main-wrapper .page-wrapper { display: block !important; padding: 0 !important; margin: 0 !important; }
        #main-wrapper .body-wrapper { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
        #main-content { padding: 0 !important; margin: 0 !important; }
        .tab-content, .tab-pane { display: block !important; opacity: 1 !important; visibility: visible !important; }

        #fcMedicalReportPrintRoot { padding: 0 !important; max-width: 100% !important; }
        #fcMedicalReportPrintRoot .card {
            break-inside: avoid;
            box-shadow: none !important;
            border: 1px solid #dee2e6 !important;
        }
        #fcMedicalReportPrintRoot .table { font-size: 0.85rem; }
    }
</style>
@endpush
@section('setup_content')
<div class="container-fluid px-3">
    <div class="d-print-none">
        <x-breadcrum title="Medical report" :items="$medicalReportBreadcrumbs" :showStatusPill="false"></x-breadcrum>
    </div>
    <div id="fcMedicalReportToolbar" class="d-flex justify-content-end align-items-start flex-wrap gap-2 mb-3 no-print">
        <div class="btn-group shadow-sm" role="group" aria-label="Export report">
            <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" id="fcMedicalReportBtnPrint" title="Print (or choose Save as PDF in the print dialog)">
                <i class="material-icons material-symbols-rounded" style="font-size:18px;">print</i>
                Print
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1" id="fcMedicalReportBtnPdf" title="Download PDF with the same layout as this page">
                <i class="material-icons material-symbols-rounded" style="font-size:18px;">picture_as_pdf</i>
                PDF
            </button>
        </div>
        <a href="{{ route('fc-reg.admin.activities.medical.index') }}" class="btn btn-outline-secondary btn-sm align-self-start">Medical list</a>
    </div>

    <div id="fcMedicalReportPrintRoot">
        <div class="mb-3 pb-2 border-bottom border-opacity-25">
            <h4 class="fw-bold mb-1" style="color:#1a3c6e;">Medical report</h4>
            <p class="text-muted small mb-0">
                <span class="badge rounded-1 bg-light text-dark border">{{ $course }}</span>
                <span class="ms-2"><code class="user-select-all">{{ $ot->otcode }}</code></span>
                <span class="d-none d-print-inline text-muted ms-2">· Printed {{ now()->format('d-m-Y H:i') }}</span>
            </p>
        </div>

        {{-- Trainee summary --}}
        <div class="card border-0 shadow-sm mb-3 overflow-hidden">
            <div class="card-body p-0">
                <div class="px-3 py-3 border-bottom" style="background: linear-gradient(135deg, #f0f5fc 0%, #fff 55%);">
                    <div class="row g-3 align-items-start">
                        <div class="col-12 col-lg-5">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 text-white fw-bold"
                                     style="width:52px;height:52px;background:#1a3c6e;font-size:1.1rem;"
                                     aria-hidden="true">{{ $initial }}</div>
                                <div class="flex-grow-1 min-w-0">
                                    <h5 class="fw-semibold mb-2" style="color:#1a3c6e;">{{ $ot->otname }}</h5>
                                    <div class="row g-2 small text-muted row-cols-2 row-cols-md-3">
                                        <div><span class="text-dark fw-medium">Gender</span><br>{{ $ot->gender ?: '—' }}</div>
                                        <div><span class="text-dark fw-medium">DOB</span><br>{{ $ot->dob ?: '—' }}</div>
                                        <div><span class="text-dark fw-medium">Mobile</span><br>{{ $ot->mobileno ?: '—' }}</div>
                                        @if(filled($ot->service))
                                            <div class="col-12"><span class="text-dark fw-medium">Service</span><br>{{ $ot->service }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-7">
                            <div class="rounded-3 border bg-white px-2 py-2 px-lg-3 py-lg-2 h-100">
                                <div class="d-flex align-items-baseline justify-content-between gap-2 mb-1">
                                    <span class="small text-uppercase text-muted fw-semibold" style="letter-spacing:.04em;font-size:.72rem;">BMI summary</span>
                                </div>
                                <p class="text-muted mb-2 mb-lg-2" style="font-size: 0.72rem; line-height: 1.35;">Latest H/W/BMI; history shown as a compact table when available.</p>

                                <div class="d-flex justify-content-between align-items-end gap-1 gap-sm-2 pb-2 mb-2 border-bottom border-opacity-25">
                                    <div>
                                        <span class="text-muted d-block" style="font-size: 0.65rem;">H (m)</span>
                                        <span class="fw-semibold small">{{ $height ?: '—' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted d-block" style="font-size: 0.65rem;">W (kg)</span>
                                        <span class="fw-semibold small">{{ $weight ?: '—' }}</span>
                                    </div>
                                    <div class="text-end">
                                        <span class="text-muted d-block" style="font-size: 0.65rem;">BMI</span>
                                        <span class="fw-bold" style="color:#1a3c6e;font-size:1.05rem;">{{ $bmi ?: '—' }}</span>
                                        @if($bmiClass)
                                            <span class="badge ms-1 {{ match($bmiClass) {
                                                'Underweight' => 'text-bg-warning',
                                                'Normal' => 'text-bg-secondary',
                                                'Overweight' => 'text-bg-warning',
                                                'Obesity' => 'text-bg-danger',
                                                default => 'text-bg-secondary',
                                            } }}" style="font-size: 0.65rem; vertical-align: middle;">{{ $bmiClass }}</span>
                                        @endif
                                    </div>
                                </div>

                                @if(!empty($bmiComparison))
                                    @php
                                        $fc = $bmiComparison['first'];
                                        $lc = $bmiComparison['latest'];
                                        $badgeCls = fn ($cls) => match ($cls ?? '') {
                                            'Underweight' => 'text-bg-warning',
                                            'Normal' => 'text-bg-secondary',
                                            'Overweight' => 'text-bg-warning',
                                            'Obesity' => 'text-bg-danger',
                                            default => 'text-bg-secondary',
                                        };
                                    @endphp
                                    <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size: 0.65rem; letter-spacing: 0.04em;">First vs latest</div>
                                    <div class="table-responsive mb-1">
                                        <table class="table table-sm table-bordered mb-0 align-middle text-center" style="font-size: 0.78rem;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-start text-muted fw-normal py-1" style="width:36%;"></th>
                                                    <th class="py-1">First</th>
                                                    <th class="py-1">Latest</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="text-start text-muted py-1">H (m)</td>
                                                    <td class="fw-medium py-1">{{ $fc['height'] }}</td>
                                                    <td class="fw-medium py-1">{{ $lc['height'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start text-muted py-1">W (kg)</td>
                                                    <td class="fw-medium py-1">{{ $fc['weight'] }}</td>
                                                    <td class="fw-medium py-1">{{ $lc['weight'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-start text-muted py-1">BMI</td>
                                                    <td class="py-1">
                                                        {{ $fc['bmi'] }}
                                                        @if(!empty($fc['class']))
                                                            <span class="badge {{ $badgeCls($fc['class']) }}" style="font-size: 0.6rem;">{{ $fc['class'] }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-1">
                                                        {{ $lc['bmi'] }}
                                                        @if(!empty($lc['class']))
                                                            <span class="badge {{ $badgeCls($lc['class']) }}" style="font-size: 0.6rem;">{{ $lc['class'] }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center px-2 py-1 rounded border bg-light" style="font-size: 0.78rem;">
                                        <span class="text-muted">Δ BMI</span>
                                        <span class="fw-bold" style="color:#1a3c6e;">
                                            {{ $bmiComparison['delta_bmi'] > 0 ? '+' : '' }}{{ $bmiComparison['delta_bmi'] }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Vitals from activity master --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 bg-white border-bottom d-flex align-items-center justify-content-between">
                <span class="small fw-semibold text-uppercase text-muted" style="letter-spacing:.04em;">Vitals &amp; measurements</span>
            </div>
            <div class="card-body py-3">
                @if($vitals->isNotEmpty())
                    <p class="small text-muted mb-3">
                        <strong>Medical department</strong> activities always store a <strong>new row</strong> each time you save (height, weight, vitals, etc.) so history is not lost.
                        Other modules may use <span class="badge bg-secondary bg-opacity-25 text-dark border">repeat</span> in Activity setup for the same behaviour.
                        Chronological history appears below; two or more readings use a table with first → latest comparison for numeric values.
                    </p>
                    <div class="row g-3">
                        @foreach($vitals as $v)
                            @php
                                $readings = $v['readings'] ?? [];
                                $rc = (int) ($v['reading_count'] ?? count($readings));
                                $compare = $v['compare'] ?? null;
                            @endphp
                            <div class="col-12">
                                <div class="rounded-3 border overflow-hidden h-100 bg-white shadow-sm">
                                    <div class="px-3 py-2 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom bg-light bg-opacity-50">
                                        <div class="fw-semibold" style="color:#1a3c6e;">{{ $v['label'] }}</div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            @if(($v['entry_policy'] ?? '') === 'repeat')
                                                <span class="badge rounded-1 bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Repeat</span>
                                            @endif
                                            @if($rc > 0)
                                                <span class="badge rounded-1 bg-light text-dark border">{{ $rc }} reading{{ $rc === 1 ? '' : 's' }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="p-3">
                                        @if($rc === 0)
                                            <span class="text-muted">—</span>
                                        @elseif($rc === 1)
                                            <div class="d-flex flex-column flex-sm-row flex-sm-wrap align-items-sm-baseline justify-content-sm-between gap-2">
                                                <div class="fs-6 fw-medium mb-0">{{ $readings[0]['value'] !== null && $readings[0]['value'] !== '' ? $readings[0]['value'] : '—' }}</div>
                                                <div class="small text-muted text-sm-end flex-shrink-0">{{ $readings[0]['when'] }} · {{ $readings[0]['by'] }}</div>
                                            </div>
                                        @else
                                            <div class="table-responsive mb-2">
                                                <table class="table table-sm table-bordered mb-0 align-middle">
                                                    <thead class="table-light">
                                                        <tr class="small text-uppercase text-muted" style="letter-spacing:.03em;">
                                                            <th style="width:3rem;">#</th>
                                                            <th style="min-width:9rem;">Date / time</th>
                                                            <th>Value</th>
                                                            <th style="min-width:6rem;">Recorded by</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($readings as $idx => $row)
                                                            <tr>
                                                                <td class="text-muted">{{ $idx + 1 }}</td>
                                                                <td class="small">{{ $row['when'] }}</td>
                                                                <td class="fw-medium">{{ $row['value'] !== null && $row['value'] !== '' ? $row['value'] : '—' }}</td>
                                                                <td class="small text-muted">{{ $row['by'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            @if($compare)
                                                <div class="rounded-2 px-3 py-2 small border" style="background: linear-gradient(90deg, rgba(26,60,110,0.06) 0%, rgba(255,255,255,1) 40%);">
                                                    <span class="text-muted">First reading:</span> <strong>{{ $compare['first'] }}</strong>
                                                    <span class="text-muted mx-2">→</span>
                                                    <span class="text-muted">Latest:</span> <strong>{{ $compare['last'] }}</strong>
                                                    <span class="badge ms-2 {{ $compare['delta'] > 0 ? 'text-bg-warning' : ($compare['delta'] < 0 ? 'text-bg-info' : 'text-bg-secondary') }}">
                                                        Δ {{ $compare['delta'] > 0 ? '+' : '' }}{{ $compare['delta'] }}
                                                    </span>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted small mb-0">No medical activity fields are configured for this department. Add height, weight, and other items under <strong>Activity setup</strong> for the medical department.</p>
                @endif
            </div>
        </div>

        @include('admin.fc-activities.medical.partials.pre-history-block')

        <form id="pathoForm" enctype="multipart/form-data" class="mb-0">
            @csrf
            <input type="hidden" name="otcode" value="{{ $ot->otcode }}">
            <input type="hidden" name="course_master_pk" value="{{ $ot->course_master_pk }}">

            {{-- Screen / print dialog only — omitted from PDF (html2pdf uses screen snapshot; .no-print + .is-pdf-capture) --}}
            <div class="card border-0 shadow-sm mb-3 no-print">
                <div class="card-header py-2 bg-white border-bottom">
                    <span class="small fw-semibold text-uppercase text-muted" style="letter-spacing:.04em;">Pathology &amp; findings</span>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label small fw-medium" for="file1">Upload pathology report (PDF)</label>
                            <input type="file" name="file1" id="file1" class="form-control form-control-sm" accept="application/pdf">
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label small fw-medium" for="textfindings">Final findings</label>
                            <textarea class="form-control form-control-sm" rows="4" name="textfindings" id="textfindings" placeholder="Enter findings text…"></textarea>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary btn-sm px-4">Save</button>
                    </div>
                </div>
            </div>

            @if($pathologyHasRecord)
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header py-2 bg-white border-bottom">
                        <span class="small fw-semibold text-uppercase text-muted" style="letter-spacing:.04em;">Pathology &amp; findings <span class="fw-normal text-muted">(on record)</span></span>
                    </div>
                    <div class="card-body py-3">
                        @if($pathReports->isNotEmpty())
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing:.04em;">Uploaded pathology reports</div>
                                <ul class="list-unstyled small mb-0">
                                    @foreach($pathReports as $pr)
                                        @if($pr->path_report)
                                            <li class="mb-1">
                                                <a href="{{ asset($pr->path_report) }}" target="_blank" rel="noopener">View uploaded report</a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if($finalFindings->isNotEmpty())
                            <div class="{{ $pathReports->isNotEmpty() ? 'mt-0' : '' }}">
                                <div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing:.04em;">Recorded findings</div>
                                <ol class="small mb-0 ps-3">
                                    @foreach($finalFindings as $ff)
                                        <li class="mb-1">{{ $ff->findings }}</li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </form>

    </div>{{-- /#fcMedicalReportPrintRoot --}}
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function () {
    var printBtn = document.getElementById('fcMedicalReportBtnPrint');
    var pdfBtn = document.getElementById('fcMedicalReportBtnPdf');
    var root = document.getElementById('fcMedicalReportPrintRoot');
    var pdfName = @json($medicalReportPdfFilename);

    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }

    if (pdfBtn && root) {
        pdfBtn.addEventListener('click', function () {
            if (typeof html2pdf === 'undefined') {
                window.alert('PDF export is still loading. Please wait a few seconds and try again.');
                return;
            }
            var btn = pdfBtn;
            var html = btn.innerHTML;
            var scrollY = window.scrollY;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> PDF…';
            window.scrollTo(0, 0);
            root.classList.add('is-pdf-capture');

            var opt = {
                margin: [10, 10, 12, 10],
                filename: pdfName,
                image: { type: 'jpeg', quality: 0.92 },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    logging: false,
                    scrollY: 0,
                    windowWidth: document.documentElement.scrollWidth
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css', 'legacy'] }
            };

            html2pdf().set(opt).from(root).save().then(function () {
                root.classList.remove('is-pdf-capture');
                window.scrollTo(0, scrollY);
                btn.disabled = false;
                btn.innerHTML = html;
            }).catch(function (err) {
                console.error(err);
                root.classList.remove('is-pdf-capture');
                window.scrollTo(0, scrollY);
                window.alert('Could not create PDF. Try Print and use “Save as PDF” instead.');
                btn.disabled = false;
                btn.innerHTML = html;
            });
        });
    }

    var form = document.getElementById('pathoForm');
    if (form && typeof jQuery !== 'undefined') {
        jQuery(form).on('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(this);
            jQuery.ajax({
                type: 'POST',
                url: @json(route('fc-reg.admin.activities.medical.upload')),
                data: fd,
                contentType: false,
                processData: false,
                success: function (resp) {
                    if (resp.status === 'ok') {
                        alert('Saved successfully.');
                        location.reload();
                    } else {
                        alert('Error while saving.');
                    }
                },
                error: function () {
                    alert('Error while saving.');
                }
            });
        });
    }
})();
</script>
@endpush
