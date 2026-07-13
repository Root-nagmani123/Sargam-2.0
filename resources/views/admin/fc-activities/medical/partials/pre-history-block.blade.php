@php
    $ph = $preHistory ?? null;
    $line = fn (?string $v) => ($v !== null && trim((string) $v) !== '') ? e($v) : '—';
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 bg-white border-bottom d-flex align-items-center justify-content-between">
        <span class="small fw-semibold text-uppercase text-muted" style="letter-spacing:.04em;">Previous medical history</span>
        @unless($ph)
            <span class="badge rounded-1 bg-secondary-subtle text-secondary border">None</span>
        @endunless
    </div>
    <div class="card-body py-3">
        @unless($ph)
            <p class="text-muted small mb-0">No pre-medical history was recorded for this trainee and course. If the trainee completed Step&nbsp;3 during FC registration, ensure <code>fc_pre_history.userid</code> matches the OT <code>username</code> and <code>course</code> matches this session name.</p>
        @else
            <dl class="row mb-0 small">
                <dt class="col-md-4 col-lg-3 text-dark fw-medium pt-1">Allergy / illness / injury / disability / asthma / slip disc / transfusion</dt>
                <dd class="col-md-8 col-lg-9 pt-1 border-bottom border-light pb-2 mb-2 mb-md-0">{{ $line($ph->allergy_illness) }}</dd>
                <dt class="col-md-4 col-lg-3 text-dark fw-medium pt-1">Prolonged medication</dt>
                <dd class="col-md-8 col-lg-9 pt-1 border-bottom border-light pb-2 mb-2 mb-md-0">{{ $line($ph->prolonged_medication) }}</dd>
                <dt class="col-md-4 col-lg-3 text-dark fw-medium pt-1">Hospital / operation / injury</dt>
                <dd class="col-md-8 col-lg-9 pt-1 border-bottom border-light pb-2 mb-2 mb-md-0">{{ $line($ph->hospital_history) }}</dd>
                <dt class="col-md-4 col-lg-3 text-dark fw-medium pt-1">High-altitude illness</dt>
                <dd class="col-md-8 col-lg-9 pt-1 border-bottom border-light pb-2 mb-2 mb-md-0">{{ $line($ph->altitude_illness) }}</dd>
                <dt class="col-md-4 col-lg-3 text-dark fw-medium pt-1">Other significant health information</dt>
                <dd class="col-md-8 col-lg-9 pt-1 border-bottom border-light pb-2 mb-2 mb-md-0">{{ $line($ph->additional_info) }}</dd>
                <dt class="col-md-4 col-lg-3 text-dark fw-medium pt-1">Supporting document</dt>
                <dd class="col-md-8 col-lg-9 pt-1 border-bottom border-light pb-2 mb-2 mb-md-0">
                    @if($ph->doc_path)
                        <a href="{{ asset($ph->doc_path) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">View uploaded file</a>
                    @else
                        —
                    @endif
                </dd>
                @if($ph->course)
                    <dt class="col-md-4 col-lg-3 text-dark fw-medium pt-1">Stored course key</dt>
                    <dd class="col-md-8 col-lg-9 pt-1 mb-0"><code>{{ $ph->course }}</code></dd>
                @endif
            </dl>
        @endunless
    </div>
</div>
