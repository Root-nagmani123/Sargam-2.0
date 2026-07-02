@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@php
    $val = fn ($name) => old($name, $data[$name] ?? '');
    $sc  = $val('status_clause');
    // The four clauses (i)–(iv): value = schema option, plus bilingual display text.
    $clauses = [
        ['I am unmarried / a widower / a widow',
         'कि मैं अविवाहित / विधुर / विधवा हूँ।', 'That I am unmarried / a widower / a widow;'],
        ['I am married and have only one spouse living',
         'कि मैं विवाहित हूँ और मेरी केवल एक जीवित पत्नी / पति है।', 'That I am married and have only one spouse living;'],
        ['I am married and have more than one spouse living (exemption applied for)',
         'कि मैं विवाहित हूँ और मेरी एक से अधिक जीवित पत्नियाँ हैं। छूट प्रदान संबंधी प्रार्थना-पत्र संलग्न है।', 'That I have entered into or contracted a marriage with a person having a spouse living. Application for grant of exemption is enclosed.'],
        ['I am about to marry a person who has a spouse living (exemption applied for)',
         'कि मैं ऐसे व्यक्ति के साथ विवाह करने जा रहा/रही हूँ जिसकी पहले ही एक या एक से अधिक जीवित पत्नियाँ हैं। छूट प्रदान संबंधी प्रार्थना-पत्र संलग्न है।', 'That I have entered into and contracted a marriage with another person during the life-time of my spouse. Application for grant of exemption is enclosed.'],
    ];
@endphp

@section('content')
@include('fc.registration.partials.fc-form-theme')

@push('styles')
<style>
    .ms-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px; box-shadow:0 6px 22px rgba(0,40,90,.06);
        padding:2.4rem 2.6rem; margin-bottom:1.25rem; font-family:'Times New Roman', Georgia, serif; color:#111; }
    @media (max-width:575.98px){ .ms-paper{ padding:1.6rem 1.1rem; } }
    .ms-doc{ max-width:840px; margin:0 auto; }
    .ms-doc + .ms-doc{ border-top:2px dashed #cbd5e1; margin-top:2.2rem; padding-top:2.2rem; }
    .ms-docno{ text-align:right; font-weight:700; font-size:.9rem; }
    .ms-title{ text-align:center; font-weight:700; font-size:1.12rem; text-decoration:underline; margin:.2rem 0 0; }
    .ms-title-en{ text-align:center; font-weight:700; font-size:1rem; text-decoration:underline; margin:.15rem 0 0; }
    .ms-sub{ text-align:center; font-size:.82rem; color:#444; margin:.2rem 0 0; }
    .ms-lead{ font-size:1.02rem; line-height:2; margin:1.5rem 0 0; }
    .ms-clause{ display:flex; gap:.55rem; margin:.7rem 0 0; font-size:.98rem; line-height:1.7; }
    .ms-clause input{ margin-top:.4rem; }
    .ms-rn{ font-weight:600; min-width:26px; }
    .ms-clause .en{ display:block; color:#333; }
    .ms-affirm{ font-size:1rem; line-height:1.9; margin:1.3rem 0 0; text-align:justify; }
    .ms-sign{ margin-top:1.6rem; font-size:1rem; line-height:2.2; }
    .ms-note{ font-size:.86rem; margin-top:1.2rem; font-style:italic; }
    .blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; font-family:inherit; font-size:inherit;
        padding:0 .35rem 1px; color:#0b3d91; font-weight:600; outline:none; min-width:130px; }
    .blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .blank--wide{ min-width:300px; }
    .blank--sm{ min-width:120px; }
    .mirror-out{ color:#0b3d91; font-weight:600; border-bottom:1px dotted #94a3b8; padding:0 .3rem; display:inline-block; min-width:200px; }
    .ms-ex__title{ text-align:center; font-weight:700; text-decoration:underline; }
    .ms-ex__to{ margin-top:1rem; }
    .ms-ex__body{ font-size:1rem; line-height:1.9; margin-top:1rem; text-align:justify; }
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

        {{-- ═══════════ DECLARATION ═══════════ --}}
        <div class="ms-paper">
            <div class="ms-doc">
                <div class="ms-docno">Document-4</div>
                <div class="ms-title">विवाह संबंधी घोषणापत्र</div>
                <div class="ms-title-en">DECLARATION REGARDING MARITAL STATUS</div>
                <div class="ms-sub">[Rule 21 and GIDs of CCS (Conduct) Rules, 1964]</div>

                <div class="ms-lead">
                    <strong>1.</strong> मैं / I, Shri/Smt./Kumari
                    <input type="text" name="officer_name" class="blank blank--wide" required value="{{ $val('officer_name') }}" data-mirror="name">
                    घोषणा करता/करती हूँ / declare as under —
                </div>

                @foreach($clauses as $i => $c)
                    <label class="ms-clause">
                        <input type="radio" name="status_clause" value="{{ $c[0] }}" {{ (string)$sc===(string)$c[0]?'checked':'' }} @if($i===0) required @endif>
                        <span class="ms-rn">({{ ['i','ii','iii','iv'][$i] }})</span>
                        <span>{{ $c[1] }} <span class="en">{{ $c[2] }}</span></span>
                    </label>
                @endforeach

                <div class="ms-affirm">
                    <strong>2.</strong> मैं सत्यनिष्ठा से प्रतिज्ञा करता/करती हूँ कि उपर्युक्त घोषणा सत्य है और मैं यह भी समझता/समझती हूँ कि मेरी नियुक्ति के बाद इस घोषणा के गलत सिद्ध होने पर मुझे सेवा से बरखास्त किया जा सकता है।
                    <span class="d-block">I solemnly affirm that the above declaration is true and I understand that in the event of the declaration being found to be incorrect after my appointment, I shall be liable to be dismissed from service.</span>
                </div>

                <div class="ms-sign">
                    <div>दिनांक / Date: <input type="date" name="declaration_date" class="blank blank--sm" value="{{ $val('declaration_date') }}">
                        &nbsp;&nbsp;&nbsp; हस्ताक्षर / Full Signature: ______________________</div>
                    <div>स्थान / Place: <input type="text" name="place" class="blank" value="{{ $val('place') }}">
                        &nbsp;&nbsp;&nbsp; नाम स्पष्ट अक्षरों में / Name (in Block Letters): <span class="mirror-out" data-mirror-out="name">&nbsp;</span></div>
                    <div>पदनाम / Designation: <input type="text" name="designation" class="blank blank--wide" value="{{ $val('designation') }}" placeholder="name of service followed by (Probationer)"></div>
                </div>

                <div class="ms-note">टिप्पणी: कृपया उपर्युक्त कथनों में से जो लागू न हों उन्हें काट दें। (*Note: Please delete clause/clauses not applicable.)</div>
            </div>
        </div>

        {{-- ═══════════ EXEMPTION APPLICATION (para 1(iii)/1(iv)) ═══════════ --}}
        <div class="ms-paper">
            <div class="ms-doc">
                <div class="ms-ex__title">रियायत प्रदान करने के लिए आवेदन पत्र<br>APPLICATION FOR GRANT OF EXEMPTION</div>
                <div style="text-align:center; font-size:.85rem;">(घोषणा का पैरा 1(iii) / 1(iv) देखें) &middot; (Vide para 1(iii) / 1(iv) of the Declaration)</div>
                <div class="ms-ex__to">सेवा में / To,<br>&emsp;The Secretary, Department of Personnel &amp; Training (DoPT), New Delhi</div>
                <div class="ms-ex__body">
                    महोदय / Sir,<br>
                    मेरा अनुरोध है कि नीचे बताए गए कारणों को ध्यान में रखते हुए, मुझे एक से अधिक जीवित पत्नी रखने / ऐसी महिला जिसका ऐसे व्यक्ति से विवाह हुआ हो जिसकी पहले से एक या अधिक जीवित पत्नियाँ हों — की सेवा में भर्ती पर प्रतिबंध से छूट प्रदान की जाए।
                    <span class="d-block">I request that, in view of the reasons stated below, I may be granted exemption from the operation of the restriction on recruitment to service of one having more than one wife living / a woman who is married to a person already having one or more wives living.</span>
                </div>
                <div class="mt-2">कारण / Reasons:
                    <textarea name="exemption_reasons" rows="3" class="form-control mt-1" style="border:1px solid #cbd5e1;border-radius:6px;" placeholder="mention reason if applicable, or write &quot;Not applicable&quot;">{{ $val('exemption_reasons') }}</textarea>
                </div>
                <div style="text-align:right; margin-top:1.2rem;">भवदीय / Yours faithfully,<br>हस्ताक्षर / Signature ______________________</div>
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

@push('scripts')
<script>
(function () {
    var src = document.querySelector('[name="officer_name"]');
    function sync(){ document.querySelectorAll('[data-mirror-out="name"]').forEach(function(o){ o.textContent = (src && src.value) || ' '; }); }
    if (src) { src.addEventListener('input', sync); src.addEventListener('change', sync); }
    sync();
})();
</script>
@endpush
@endsection
