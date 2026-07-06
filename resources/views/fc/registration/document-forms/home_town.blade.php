@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php $val = fn ($name) => old($name, $data[$name] ?? ''); @endphp

@push('styles')
<style>
    .ht-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px; box-shadow:0 6px 22px rgba(0,40,90,.06);
        padding:2.4rem 2.6rem; margin-bottom:1.25rem; font-family:'Times New Roman', Georgia, serif; color:#111; }
    @media (max-width:575.98px){ .ht-paper{ padding:1.6rem 1.1rem; } }
    .ht-doc{ max-width:820px; margin:0 auto; }
    .ht-docno{ text-align:right; font-weight:700; font-size:.9rem; }
    .ht-title{ text-align:center; font-weight:700; font-size:1.15rem; text-decoration:underline; margin:.2rem 0 0; }
    .ht-sub{ text-align:center; font-size:.82rem; color:#444; margin:.25rem 0 0; }
    .ht-lead{ font-size:1.02rem; line-height:1.9; margin:1.6rem 0 0; }
    .ht-lead .hi{ display:block; }
    .ht-line{ font-size:1.02rem; line-height:2.2; margin-top:1rem; }
    .ht-line .lbl{ font-weight:600; }
    .ht-reason{ font-size:1.02rem; line-height:1.9; margin-top:1.2rem; }
    .ht-sign{ margin-top:1.8rem; font-size:1.02rem; line-height:2.2; }
    .blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; font-family:inherit; font-size:inherit;
        padding:0 .35rem 1px; color:#0b3d91; font-weight:600; outline:none; min-width:130px; }
    .blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .blank--wide{ min-width:320px; }
    .blank--sm{ min-width:120px; }
    .ht-def{ margin-top:1.8rem; border-top:1px solid #d5deeb; padding-top:1rem; font-size:.86rem; line-height:1.6; }
    .ht-def__hd{ font-weight:700; }
    .ht-def ol{ margin:.5rem 0 0; padding-left:1.4rem; }
    .ht-accepted{ text-align:center; font-weight:700; text-decoration:underline; margin-top:1.4rem; }
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

        <div class="ht-paper">
            <div class="ht-doc">
                <div class="ht-docno">Document-5</div>
                <div class="ht-title">गृह नगर घोषणा &nbsp; DECLARATION OF HOME TOWN</div>
                <div class="ht-sub">For the purpose of Leave Travel Concession (MHA Memo No. 43/715/57-Ests.(A), dated 24-06-1958)</div>

                <div class="ht-lead">
                    मैं घोषणा करता/करती हूँ कि अवकाश यात्रा रियायत हेतु मेरा गृह नगर/गांव निम्नलिखित है —
                    <span class="hi">I declare that my &lsquo;Home-Town&rsquo; for the purpose of Leave Travel Concession is:</span>
                </div>

                <div class="ht-line">
                    <span class="lbl">नगर/गांव का नाम / Name of Town/Village:</span>
                    <input type="text" name="town_village" class="blank blank--wide" required value="{{ $val('town_village') }}">
                </div>
                <div class="ht-line">
                    <span class="lbl">जिला / District:</span>
                    <input type="text" name="district" class="blank" value="{{ $val('district') }}">
                    &nbsp;&nbsp;<span class="lbl">राज्य / State:</span>
                    <input type="text" name="state" class="blank" value="{{ $val('state') }}">
                </div>

                <div class="ht-reason">
                    उपर्युक्त स्थान को &lsquo;गृह नगर&rsquo; घोषित किये जाने के निम्नलिखित कारण* हैं —
                    <span class="d-block">Reasons* for declaring the above place as my &lsquo;HOME-TOWN&rsquo; are given below:</span>
                    <input type="text" name="reason" class="blank blank--wide" value="{{ $val('reason') }}" placeholder="mention reason (a), (b), (c) or (d) whichever is applicable" style="min-width:420px;">
                </div>

                <div class="ht-sign">
                    <div style="text-align:right;">हस्ताक्षर / Signature: ______________________</div>
                    <div>नाम स्पष्ट अक्षरों में / Name in Block Letters:
                        <input type="text" name="officer_name" class="blank blank--wide" required value="{{ $val('officer_name') }}"></div>
                    <div>पदनाम / Designation:
                        <input type="text" name="designation" class="blank blank--wide" value="{{ $val('designation') }}" placeholder="name of your service followed by (Probationer)"></div>
                    <div>स्थान / Place:
                        <input type="text" name="place" class="blank" value="{{ $val('place') }}">
                        &nbsp;&nbsp;तारीख / Dated:
                        <input type="date" name="declaration_date" class="blank blank--sm" value="{{ $val('declaration_date') }}"></div>
                </div>

                <div class="ht-def">
                    <div class="ht-def__hd">Definition of the term &ldquo;Home Town&rdquo; for the purpose of Leave Travel Concession (MHA Memo No. 43/715/57-Ests.(A) dated 24-06-1958). The declaration may be made based on the criteria below / &ldquo;गृह नगर&rdquo; की परिभाषा — घोषणा निम्नलिखित मानदंडों के आधार पर की जा सकती है:</div>
                    <ol type="a">
                        @foreach($template['notes'] ?? [] as $n)<li>{!! preg_replace('/^\([a-d]\)\s*/', '', $n) !!}</li>@endforeach
                    </ol>
                </div>

                <div class="ht-accepted">स्वीकृत / ACCEPTED</div>
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
@endsection
