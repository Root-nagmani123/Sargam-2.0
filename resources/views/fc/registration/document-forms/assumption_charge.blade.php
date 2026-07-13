@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php $val = fn ($name) => old($name, $data[$name] ?? ''); $tm = $val('time_of_assumption'); @endphp

@push('styles')
<style>
    .ac-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px; box-shadow:0 6px 22px rgba(0,40,90,.06);
        padding:2.4rem 2.6rem; margin-bottom:1.25rem; font-family:'Times New Roman', Georgia, serif; color:#111; }
    @media (max-width:575.98px){ .ac-paper{ padding:1.6rem 1.1rem; } }
    .ac-doc{ max-width:820px; margin:0 auto; }
    .ac-title-hi{ text-align:center; font-weight:700; font-size:1.15rem; margin:0; }
    .ac-title{ text-align:center; font-weight:700; font-size:1.1rem; text-decoration:underline; margin:.15rem 0 0; }
    .ac-body{ font-size:1.05rem; line-height:2.15; text-align:justify; margin:1.6rem 0 0; text-indent:2rem; }
    .ac-body.hi{ margin-top:1.3rem; }
    .ac-foot{ margin-top:2rem; font-size:1.02rem; line-height:2.1; }
    .ac-foot .r{ float:right; text-align:left; }
    .blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; font-family:inherit; font-size:inherit;
        padding:0 .35rem 1px; color:#0b3d91; font-weight:600; outline:none; min-width:110px; }
    .blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .blank--mid{ min-width:200px; }
    .blank--sm{ min-width:120px; }
    select.blank{ border-bottom:1px dotted #64748b; }
    .mirror-out{ color:#0b3d91; font-weight:600; border-bottom:1px dotted #94a3b8; padding:0 .3rem; display:inline-block; min-width:90px; }
    .lang-tag{ display:inline-block; font-size:.68rem; font-weight:700; letter-spacing:.6px; color:#64748b;
        border:1px solid var(--fc-line); border-radius:20px; padding:.1rem .6rem; margin-bottom:.4rem; text-transform:uppercase; }
    .ac-copies{ text-align:center; font-size:.8rem; color:#7a8699; margin-top:1.2rem; }
</style>
@endpush

<div class="fc-form-page">
<div class="fc-shell">
    <div class="fc-band">
        <div class="fc-band__row">
            <div class="fc-band__ico"><i class="bi bi-pencil-square"></i></div>
            <div><h4>{{ $template['title'] }}</h4><p>{{ $template['title_hi'] ?? '' }}</p></div>
            <a href="{{ route('fc-reg.forms.step', [$form, $step]) }}" class="btn btn-light btn-sm ms-auto rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i>Back to Documents</a>
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
            <div class="ac-doc">
                <div class="ac-title-hi">कार्यभार-ग्रहण प्रमाणपत्र</div>
                <div class="ac-title">CERTIFICATE OF ASSUMPTION OF CHARGE</div>

                {{-- Hindi certificate — candidate types their own Hindi; blank by default --}}
                <div style="font-size:.72rem; color:#64748b; font-style:italic; margin:.6rem 0 0;">हिन्दी विवरण यहाँ भरें (वैकल्पिक) — या रिक्त छोड़कर मुद्रित प्रति पर हाथ से भरें। / Enter the Hindi details here (optional), or leave blank to hand-fill on the print.</div>
                <p class="ac-body hi">
                    प्रमाणित किया जाता है कि मैंने आज दिनांक
                    <input type="text" name="hi[doa]" class="blank blank--sm" value="{{ $data['_hi']['doa'] ?? '' }}" autocomplete="off">
                    <input type="text" name="hi[tm]" class="blank" value="{{ $data['_hi']['tm'] ?? '' }}" placeholder="पूर्वाह्न / अपराह्न" autocomplete="off">
                    में लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी में (सेवा)
                    <input type="text" name="hi[svc]" class="blank blank--mid" value="{{ $data['_hi']['svc'] ?? '' }}" placeholder="सेवा का नाम" autocomplete="off">
                    के पद का कार्यभार ग्रहण कर लिया है।
                </p>

                {{-- English certificate (fillable) --}}
                <p class="ac-body">
                    Certified that I have on the
                    <select name="time_of_assumption" class="blank">
                        <option value="">forenoon / afternoon</option>
                        <option value="Forenoon" {{ $tm==='Forenoon'?'selected':'' }}>forenoon</option>
                        <option value="Afternoon" {{ $tm==='Afternoon'?'selected':'' }}>afternoon</option>
                    </select>
                    of this day
                    <input type="date" name="date_of_assumption" class="blank blank--sm" value="{{ $val('date_of_assumption') }}">
                    assumed the charge of the office of
                    <input type="text" name="service" class="blank blank--mid" value="{{ $val('service') }}" placeholder="name of service">
                    (Service) in Lal Bahadur Shastri National Academy of Administration, Mussoorie.
                </p>

                <div class="ac-foot">
                    <div class="ac-foot r">
                        हस्ताक्षर / Signature: ______________________<br>
                        नाम / Name: <input type="text" name="officer_name" class="blank blank--mid" required value="{{ $val('officer_name') }}"><br>
                        पदनाम / Designation: <input type="text" name="designation" class="blank blank--mid" value="{{ $val('designation') }}">
                    </div>
                    <div>स्थान / Place: <strong>मसूरी / Mussoorie</strong></div>
                    <div>दिनांक / Dated: <input type="date" name="declaration_date" class="blank blank--sm" value="{{ $val('declaration_date') }}"></div>
                    <div style="clear:both;"></div>
                </div>

                <div class="ac-copies"><i class="bi bi-info-circle me-1"></i>The generated PDF prints <strong>two identical copies</strong> of this certificate on one page (one for you, one for the office record).</div>
            </div>
        </div>

        {{-- Signature upload --}}
        @if(! empty($template['signatures']))
            <div class="card fc-card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3"><h6 class="mb-0 text-uppercase small fw-bold text-muted">Signatures / हस्ताक्षर</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($template['signatures'] as $i => $sig)
                            @php $existingSig = $data['_signatures'][$i] ?? null; @endphp
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{!! $sig !!}</label>
                                <input type="file" name="signature[{{ $i }}]" class="form-control form-control-sm @error('signature.'.$i) is-invalid @enderror" accept="image/*">
                                @error('signature.'.$i)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                @if($existingSig)<div class="mt-2"><img src="{{ asset('storage/'.$existingSig) }}" style="max-height:48px;border:1px solid #ddd;padding:2px;background:#fff;"></div>@endif
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i>Optional — you may also print the generated PDF and sign it physically.</small>
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

{{-- The Hindi certificate is intentionally left blank (filled by hand on the printed
     copy); the English certificate carries the typed values. No mirroring needed. --}}
@endsection
