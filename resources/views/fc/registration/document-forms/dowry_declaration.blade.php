@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php $val = fn ($name) => old($name, $data[$name] ?? ''); $mc = $val('marital_choice'); @endphp

@push('styles')
<style>
    .dw-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px; box-shadow:0 6px 22px rgba(0,40,90,.06);
        padding:2.4rem 2.6rem; margin-bottom:1.25rem; font-family:'Times New Roman', Georgia, serif; color:#111; }
    @media (max-width:575.98px){ .dw-paper{ padding:1.6rem 1.1rem; } }
    .dw-doc{ max-width:840px; margin:0 auto; }
    .dw-doc + .dw-doc{ border-top:2px dashed #cbd5e1; margin-top:2.2rem; padding-top:2.2rem; }
    .dw-docno{ text-align:right; font-weight:700; font-size:.9rem; }
    .dw-title{ text-align:center; font-weight:700; font-size:1.15rem; text-decoration:underline; margin:.2rem 0 0; letter-spacing:2px; }
    .dw-sub{ text-align:center; font-size:.82rem; color:#444; margin:.25rem 0 0; }
    .dw-body{ font-size:1rem; line-height:1.95; text-align:justify; margin:1.2rem 0 0; }
    .dw-clause{ margin:.5rem 0 0 1.6rem; font-size:1rem; line-height:1.8; }
    .dw-nb{ font-weight:600; margin-top:.8rem; }
    .dw-sign{ margin-top:1.6rem; font-size:1rem; line-height:2.2; }
    .dw-copy{ margin-top:1.2rem; font-size:1rem; line-height:2; }
    .dw-ref{ margin-top:1.6rem; border-top:1px solid #d5deeb; padding-top:1rem; font-size:.84rem; line-height:1.55; font-family:system-ui,sans-serif; }
    .dw-ref__hd{ font-weight:700; color:#14315e; }
    .lang-tag{ display:inline-block; font-size:.68rem; font-weight:700; letter-spacing:.6px; color:#64748b;
        border:1px solid var(--fc-line); border-radius:20px; padding:.1rem .6rem; margin-bottom:.5rem; text-transform:uppercase; }
    .blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; font-family:inherit; font-size:inherit;
        padding:0 .35rem 1px; color:#0b3d91; font-weight:600; outline:none; min-width:130px; }
    .blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .blank--wide{ min-width:280px; }
    .blank--sm{ min-width:120px; }
    .dw-tick{ font-size:1rem; }
    .mirror-out{ color:#0b3d91; font-weight:600; border-bottom:1px dotted #94a3b8; padding:0 .3rem; display:inline-block; min-width:120px; }
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

        <div class="dw-paper">
            {{-- ─────────────── ENGLISH (fillable) ─────────────── --}}
            <div class="dw-doc" lang="en">
                <span class="lang-tag">English</span>
                <div class="dw-docno">Document-3</div>
                <div class="dw-title">DECLARATION</div>
                <div class="dw-sub">Under the Dowry Prohibition Act, 1961 (Rule 11-A, AIS (Conduct) Rules, 1968 / Rule 13-A, CCS (Conduct) Rules, 1964)</div>

                <p class="dw-body">
                    WHEREAS the provisions of Rule 11-A of the All India Services (Conduct) Rules, 1968 / Rule 13-A of the Central
                    Civil Services (Conduct) Rules, 1964 have been specifically brought to my notice;
                </p>
                <p class="dw-body">
                    AND WHEREAS on date I am
                    <label class="dw-tick"><input type="radio" name="marital_choice" value="Unmarried" {{ $mc==='Unmarried'?'checked':'' }}> unmarried</label> /
                    <label class="dw-tick"><input type="radio" name="marital_choice" value="Married" {{ $mc==='Married'?'checked':'' }}> married</label>:
                </p>
                <p class="dw-body">
                    Now therefore, I,
                    <input type="text" name="officer_name" class="blank blank--wide" required value="{{ $val('officer_name') }}" data-mirror="name">
                    (<input type="text" name="service" class="blank" value="{{ $val('service') }}" data-mirror="svc" placeholder="name of service">), Probationer, do hereby undertake that I shall not —
                </p>
                <div class="dw-clause">(a) give or take, or abet the giving or taking of, dowry; or</div>
                <div class="dw-clause">(b) demand, directly or indirectly, from the parents or guardians of the bride or bridegroom, as the case may be, any dowry.</div>
                <div class="dw-nb">N.B. &mdash; &ldquo;Dowry&rdquo; shall have the same meaning as in the Dowry Prohibition Act, 1961.</div>
                <p class="dw-body">
                    I affix my signature to this declaration in the full understanding that any breach of the rules or law relating to
                    dowry shall render me liable to appropriate action.
                </p>

                <div class="dw-sign">
                    <div>Place: <input type="text" name="place" class="blank" value="{{ $val('place') }}" data-mirror="place">
                        &nbsp;&nbsp;&nbsp; Signature: ______________________</div>
                    <div>Dated: <input type="date" name="declaration_date" class="blank blank--sm" value="{{ $val('declaration_date') }}" data-mirror="ddate">
                        &nbsp;&nbsp;&nbsp; (Name of Officer in Block Letters): <span class="mirror-out" data-mirror-out="name">&nbsp;</span></div>
                </div>

                <div class="dw-copy">
                    <em>Copy of the declaration to:</em>
                    <div>(Name of Parent / Guardian): <input type="text" name="parent_guardian_name" class="blank blank--wide" value="{{ $val('parent_guardian_name') }}" data-mirror="pgname"></div>
                    <div>Address: <textarea name="parent_guardian_address" rows="2" class="blank" style="border:1px solid #cbd5e1;border-radius:6px;min-width:340px;" data-mirror="pgaddr">{{ $val('parent_guardian_address') }}</textarea></div>
                </div>
            </div>

            {{-- ─────────────── HINDI (mirrors) ─────────────── --}}
            <div class="dw-doc" lang="hi">
                <span class="lang-tag">हिन्दी</span>
                <div class="dw-title" style="letter-spacing:normal;">घोषणा</div>

                <p class="dw-body">
                    मुझे अखिल भारतीय सेवा (आचरण) नियमावली, 1968 के नियम 11-ए तथा केन्द्रीय सिविल सेवा (आचरण) नियमावली, 1964 के
                    नियम 13-ए के उपबंधों की विशेष तौर पर जानकारी दी गई है;
                </p>
                <p class="dw-body">और मैं आज की तारीख में अविवाहित हूँ / विवाहित हूँ (जो लागू हो);</p>
                <p class="dw-body">
                    मैं, <span class="mirror-out" data-mirror-out="name">&nbsp;</span> (परिवीक्षाधीन का नाम),
                    <span class="mirror-out" data-mirror-out="svc">&nbsp;</span> (सेवा का नाम), परिवीक्षाधीन एतद्द्वारा यह वचन देता/देती हूँ कि मैं —
                </p>
                <div class="dw-clause">(क) न दहेज दूँगा/दूँगी, न दहेज लूँगा/लूँगी और न ही दहेज देने अथवा लेने के लिए दुष्प्रेरित करूँगा/करूँगी, अथवा</div>
                <div class="dw-clause">(ख) वधू अथवा वर (जो भी लागू हो) के माता-पिता या अभिभावक से प्रत्यक्ष अथवा अप्रत्यक्ष रूप से दहेज की मांग नहीं करूँगा/करूँगी।</div>
                <div class="dw-nb">टिप्पणी: यहाँ &ldquo;दहेज&rdquo; से वही अर्थ अभिप्रेत है जो दहेज प्रतिषेध अधिनियम, 1961 (1961 का 28) में दिया गया है।</div>
                <p class="dw-body">मैंने यह बात भली-भांति जानते हुए इस घोषणा पर हस्ताक्षर किए हैं कि दहेज से संबंधित नियमों अथवा विधि का उल्लंघन करने पर मेरे विरुद्ध उपयुक्त कार्रवाई की जा सकती है।</p>

                <div class="dw-sign">
                    <div>स्थान: <span class="mirror-out" data-mirror-out="place">&nbsp;</span> &nbsp;&nbsp;&nbsp; हस्ताक्षर: ______________________</div>
                    <div>तारीख: <span class="mirror-out" data-mirror-out="ddate">&nbsp;</span> &nbsp;&nbsp;&nbsp; (नाम साफ अक्षरों में): <span class="mirror-out" data-mirror-out="name">&nbsp;</span></div>
                </div>
                <div class="dw-copy">
                    प्रतिलिपि:
                    <div>(माता-पिता / अभिभावक का नाम): <span class="mirror-out" data-mirror-out="pgname">&nbsp;</span></div>
                    <div>पता: <span class="mirror-out" data-mirror-out="pgaddr">&nbsp;</span></div>
                </div>
            </div>

            {{-- Rule clarification (reference; reproduced in full in the generated PDF) --}}
            <div class="dw-ref">
                <div class="dw-ref__hd">Clarification on the mentioned Rule / उल्लिखित नियम के बारे में स्पष्टीकरण</div>
                <p class="mb-1"><strong>Rule 11-A, AIS (Conduct) Rules, 1968 &middot; Rule 13-A, CCS (Conduct) Rules, 1964:</strong> No member of the Service / no Government servant shall (i) give or take or abet the giving or taking of dowry; or (ii) demand, directly or indirectly, from the parents or guardian of a bride or bridegroom, as the case may be, any dowry. <em>&ldquo;Dowry&rdquo; has the same meaning as in the Dowry Prohibition Act, 1961 (28 of 1961).</em></p>
                <p class="mb-0 text-muted"><i class="bi bi-info-circle me-1"></i>The full bilingual rule clarification and the extracts from the Dowry Prohibition Act, 1961 and the Indian Penal Code are reproduced on the generated PDF.</p>
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
    function fmtDate(v){ var m=/^(\d{4})-(\d{2})-(\d{2})$/.exec(v||''); return m?(m[3]+'-'+m[2]+'-'+m[1]):(v||''); }
    function sync(el){
        var key = el.getAttribute('data-mirror');
        var text = (el.type === 'date') ? fmtDate(el.value) : (el.value || '');
        document.querySelectorAll('[data-mirror-out="' + key + '"]').forEach(function (o){ o.textContent = text || ' '; });
    }
    document.querySelectorAll('[data-mirror]').forEach(function (el){
        el.addEventListener('input', function(){ sync(el); }); el.addEventListener('change', function(){ sync(el); }); sync(el);
    });
})();
</script>
@endpush
@endsection
