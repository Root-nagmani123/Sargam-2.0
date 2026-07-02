@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php
    $val = fn ($name) => old($name, $data[$name] ?? '');
    $timeOpts = $template['sections'][0]['fields'][5]['options'] ?? ['Forenoon (FN)', 'Afternoon (AN)'];
@endphp

@push('styles')
<style>
    .ac-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px;
        box-shadow:0 6px 22px rgba(0,40,90,.06); padding:2.4rem 2.6rem; margin-bottom:1.25rem; }
    @media (max-width:575.98px){ .ac-paper{ padding:1.6rem 1.1rem; } }
    .ac-doc{ font-family:'Times New Roman', Georgia, serif; color:#111; max-width:820px; margin:0 auto; }
    .ac-doc + .ac-doc{ border-top:2px dashed #cbd5e1; margin-top:2.2rem; padding-top:2.2rem; }
    .ac-title{ text-align:center; font-weight:700; font-size:1.18rem; text-decoration:underline; letter-spacing:.3px; margin:0; }
    .ac-title-hi{ text-align:center; font-weight:700; font-size:1.05rem; margin:.4rem 0 0; }
    .ac-body{ font-size:1.05rem; line-height:2.3; text-align:justify; margin:1.9rem 0 0; }
    .ac-hindi .ac-body{ line-height:2.45; }
    .ac-lines{ margin:2rem 0 0; font-size:1.02rem; line-height:2.2; }
    .ac-sign{ margin-top:2.4rem; text-align:right; font-weight:600; }
    .lang-tag{ display:inline-block; font-size:.68rem; font-weight:700; letter-spacing:.6px; color:#64748b;
        border:1px solid var(--fc-line); border-radius:20px; padding:.1rem .6rem; margin-bottom:.5rem; text-transform:uppercase; }
    .blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; font-family:inherit; font-size:inherit;
        padding:0 .35rem 1px; color:#0b3d91; font-weight:600; outline:none; min-width:70px; }
    .blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .blank--name{ min-width:210px; }
    .blank--sm{ min-width:120px; }
    select.blank{ border-bottom:1px dotted #64748b; }
    .mirror-out{ color:#0b3d91; font-weight:600; border-bottom:1px dotted #94a3b8; padding:0 .3rem; display:inline-block; min-width:70px; }
</style>
@endpush

<div class="fc-form-page">
<div class="fc-shell">
    <div class="fc-band">
        <div class="fc-band__row">
            <div class="fc-band__ico"><i class="bi bi-pencil-square"></i></div>
            <div>
                <h4>{{ $template['title'] }}</h4>
                <p>{{ $template['title_hi'] ?? '' }}</p>
            </div>
            <a href="{{ route('fc-reg.forms.step', [$form, $step]) }}" class="btn btn-light btn-sm ms-auto rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i>Back to Documents
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger shadow-sm mb-3" role="alert">
            <strong class="d-block mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix the following:</strong>
            <ul class="mb-0 ps-3">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('fc-reg.forms.doc-form.save', [$form, $step, $field->field_name]) }}" enctype="multipart/form-data">
        @csrf

        <div class="ac-paper">
            {{-- ─────────────── ENGLISH ─────────────── --}}
            <div class="ac-doc" lang="en">
                <span class="lang-tag">English</span>
                <h2 class="ac-title">CERTIFICATE OF ASSUMPTION OF CHARGE</h2>

                <p class="ac-body">
                    Certified that I,
                    <input type="text" name="officer_name" class="blank blank--name" required
                           value="{{ $val('officer_name') }}" data-mirror="name" autocomplete="off">
                    (Name),
                    <input type="text" name="designation" class="blank blank--name"
                           value="{{ $val('designation') }}" data-mirror="designation" autocomplete="off">
                    (Designation), have assumed charge of the post of
                    <input type="text" name="post_assumed" class="blank blank--name"
                           value="{{ $val('post_assumed') }}" data-mirror="post" autocomplete="off">
                    at
                    <input type="text" name="place_of_posting" class="blank"
                           value="{{ $val('place_of_posting') }}" data-mirror="posting" autocomplete="off">
                    on
                    <input type="date" name="date_of_assumption" class="blank blank--sm"
                           value="{{ $val('date_of_assumption') }}" data-mirror="adate">
                    in the
                    <select name="time_of_assumption" class="blank" data-mirror="time">
                        <option value="">—</option>
                        @foreach($timeOpts as $opt)
                            <option value="{{ $opt }}" {{ (string)$val('time_of_assumption')===(string)$opt?'selected':'' }}>{{ $opt }}</option>
                        @endforeach
                    </select>.
                </p>

                <div class="ac-lines">
                    Place: <input type="text" name="place" class="blank" value="{{ $val('place') }}" data-mirror="place">
                    &nbsp;&nbsp;&nbsp;
                    Date: <input type="date" name="declaration_date" class="blank blank--sm" value="{{ $val('declaration_date') }}" data-mirror="ddate">
                </div>
                <div class="ac-sign">Signature of the Officer</div>
            </div>

            {{-- ─────────────── HINDI (mirrors the same values) ─────────────── --}}
            <div class="ac-doc ac-hindi" lang="hi">
                <span class="lang-tag">हिन्दी</span>
                <h2 class="ac-title-hi">कार्यभार ग्रहण प्रमाण-पत्र</h2>

                <p class="ac-body">
                    प्रमाणित किया जाता है कि मैंने,
                    <span class="mirror-out" data-mirror-out="name">&nbsp;</span> (नाम),
                    <span class="mirror-out" data-mirror-out="designation">&nbsp;</span> (पद नाम), ने
                    <span class="mirror-out" data-mirror-out="post">&nbsp;</span> पद का कार्यभार
                    <span class="mirror-out" data-mirror-out="posting">&nbsp;</span> में दिनांक
                    <span class="mirror-out" data-mirror-out="adate">&nbsp;</span> को
                    <span class="mirror-out" data-mirror-out="time">&nbsp;</span> ग्रहण कर लिया है।
                </p>

                <div class="ac-lines">
                    स्थान: <span class="mirror-out" data-mirror-out="place">&nbsp;</span>
                    &nbsp;&nbsp;&nbsp;
                    दिनांक: <span class="mirror-out" data-mirror-out="ddate">&nbsp;</span>
                </div>
                <div class="ac-sign">अधिकारी के हस्ताक्षर</div>
            </div>
        </div>

        {{-- Signature upload (optional — same as every other doc form) --}}
        @if(! empty($template['signatures']))
            <div class="card fc-card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 text-uppercase small fw-bold text-muted">Signatures / हस्ताक्षर</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($template['signatures'] as $i => $sig)
                            @php $existingSig = $data['_signatures'][$i] ?? null; @endphp
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{!! $sig !!}</label>
                                <input type="file" name="signature[{{ $i }}]"
                                       class="form-control form-control-sm @error('signature.'.$i) is-invalid @enderror" accept="image/*">
                                @error('signature.'.$i)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                @if($existingSig)<div class="mt-2"><img src="{{ asset('storage/'.$existingSig) }}" style="max-height:48px;border:1px solid #ddd;padding:2px;background:#fff;"></div>@endif
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i>Upload a scanned/photographed signature image (PNG/JPG, max 2&nbsp;MB). Optional — you may also print the generated PDF and sign it physically.</small>
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-between mb-5">
            <a href="{{ route('fc-reg.forms.step', [$form, $step]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Cancel</a>
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i>Save &amp; Generate PDF</button>
        </div>
    </form>
</div>
</div>

@push('scripts')
<script>
(function () {
    function fmtDate(v){ var m=/^(\d{4})-(\d{2})-(\d{2})$/.exec(v||''); return m?(m[3]+'-'+m[2]+'-'+m[1]):(v||''); }
    function sync(el){
        var key = el.getAttribute('data-mirror');
        var raw = el.value || '';
        var text = (el.type === 'date') ? fmtDate(raw) : raw;
        document.querySelectorAll('[data-mirror-out="' + key + '"]').forEach(function (o){ o.textContent = text || ' '; });
    }
    document.querySelectorAll('[data-mirror]').forEach(function (el){
        el.addEventListener('input', function(){ sync(el); });
        el.addEventListener('change', function(){ sync(el); });
        sync(el);
    });
})();
</script>
@endpush
@endsection
