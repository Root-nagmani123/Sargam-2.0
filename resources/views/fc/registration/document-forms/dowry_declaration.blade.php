@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php $val = fn ($name) => old($name, $data[$name] ?? ''); $mc = $val('marital_choice'); @endphp

@push('styles')
<style>
    .dw-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px; box-shadow:0 6px 22px rgba(0,40,90,.06);
        padding:2.6rem 3.2rem; margin:0 auto 1.25rem; font-family:'Times New Roman', Georgia, serif; color:#111; }
    @media (max-width:575.98px){ .dw-paper{ padding:1.6rem 1.1rem; } }
    /* Each document section fills the full paper width and shares one left edge */
    .dw-doc{ max-width:none; margin:0; }
    .dw-doc + .dw-doc{ border-top:2px dashed #cbd5e1; margin-top:2.2rem; padding-top:2.2rem; }
    .dw-docno{ text-align:right; font-weight:700; font-size:.9rem; }
    .dw-title{ text-align:center; font-weight:700; font-size:1.15rem; text-decoration:underline; margin:.2rem 0 0; letter-spacing:2px; }
    .dw-sub{ text-align:center; font-size:.82rem; color:#444; margin:.25rem 0 0; }
    .dw-hint{ font-size:.72rem; color:#64748b; font-style:italic; margin:.5rem 0 0; }
    .dw-body{ font-size:1rem; line-height:1.95; text-align:justify; margin:1.2rem 0 0; }
    .dw-clause{ margin:.5rem 0 0 1.6rem; font-size:1rem; line-height:1.8; }
    .dw-nb{ font-weight:600; margin-top:.8rem; }
    /* Rule-clarification sections (sample pages 2 & 4) */
    .dw-clhd{ text-align:center; font-weight:700; text-decoration:underline; font-size:1.02rem; margin:0; }
    .dw-rule{ font-weight:700; margin:1rem 0 0; font-size:1rem; }
    .dw-clbody{ margin:.4rem 0 0; font-size:1rem; line-height:1.85; }
    .dw-expl{ margin:.5rem 0 0; font-size:1rem; line-height:1.85; }
    .dw-sep{ text-align:center; font-weight:700; letter-spacing:3px; margin:1rem 0 0; }
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
    .blank--area{ border:1px solid #cbd5e1; border-radius:6px; min-width:340px; flex:1 1 260px; }
    .dw-tick{ font-size:1rem; }
    .mirror-out{ color:#0b3d91; font-weight:600; border-bottom:1px dotted #94a3b8; padding:0 .3rem; display:inline-block; min-width:120px; }

    /* Two-column, vertically-aligned signature block (Place|Signature, Dated|Name) */
    .dw-signtbl{ width:100%; border-collapse:collapse; table-layout:fixed; margin-top:1.6rem; }
    .dw-signtbl td{ padding:.5rem .4rem; vertical-align:bottom; font-size:1rem; line-height:1.9; }
    .dw-ruled{ display:inline-block; min-width:170px; border-bottom:1px solid #64748b; }
    /* Copy-to rows: label and field on a shared baseline so blanks line up */
    .dw-copy__row{ display:flex; align-items:flex-start; gap:.5rem; margin-top:.5rem; flex-wrap:wrap; }
    .dw-copy__lbl{ flex:0 0 auto; font-weight:600; }
    @media (max-width:575.98px){ .dw-signtbl,.dw-signtbl tbody,.dw-signtbl tr,.dw-signtbl td{ display:block; width:100%; } }
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
            {{-- ─────────────── HINDI (first — matches the official sample, Document-3 page 1) ─────────────── --}}
            <div class="dw-doc" lang="hi">
                <span class="lang-tag">हिन्दी</span>
                <div class="dw-docno">Document-3</div>
                <div class="dw-title" style="letter-spacing:normal;">घोषणा</div>

                <p class="dw-body">
                    मुझे अखिल भारतीय सेवा (आचरण) नियमावली, 1968 के नियम 11-ए तथा केन्द्रीय सिविल सेवा (आचरण) नियमावली, 1964 के
                    नियम 13-ए के उपबंधों की विशेष तौर पर जानकारी दी गई है;
                </p>
                <p class="dw-body">और मैं आज की तारीख में अविवाहित हूँ / विवाहित हूँ (जो लागू हो);</p>
                <p class="dw-body">
                    मैं, <input type="text" name="hi[name]" class="blank blank--wide" value="{{ $data['_hi']['name'] ?? '' }}" data-mirror="hi_name" autocomplete="off"> (परिवीक्षाधीन का नाम),
                    <input type="text" name="hi[svc]" class="blank" value="{{ $data['_hi']['svc'] ?? '' }}" autocomplete="off"> (सेवा का नाम), परिवीक्षाधीन एतद्द्वारा यह वचन देता/देती हूँ कि मैं —
                </p>
                <div class="dw-clause">(क) न दहेज दूँगा/दूँगी, न दहेज लूँगा/लूँगी और न ही दहेज देने अथवा लेने के लिए दुष्प्रेरित करूँगा/करूँगी, अथवा</div>
                <div class="dw-clause">(ख) वधू अथवा वर (जो भी लागू हो) के माता-पिता या अभिभावक से प्रत्यक्ष अथवा अप्रत्यक्ष रूप से दहेज की मांग नहीं करूँगा/करूँगी।</div>
                <div class="dw-nb">टिप्पणी: यहाँ &ldquo;दहेज&rdquo; से वही अर्थ अभिप्रेत है जो दहेज प्रतिषेध अधिनियम, 1961 (1961 का 28) में दिया गया है।</div>
                <p class="dw-body">मैंने यह बात भली-भांति जानते हुए इस घोषणा पर हस्ताक्षर किए हैं कि दहेज से संबंधित नियमों अथवा विधि का उल्लंघन करने पर मेरे विरुद्ध उपयुक्त कार्रवाई की जा सकती है।</p>

                <table class="dw-signtbl">
                    <tr>
                        <td>स्थान: <input type="text" name="hi[place]" class="blank" value="{{ $data['_hi']['place'] ?? '' }}" autocomplete="off"></td>
                        <td>हस्ताक्षर: <span class="dw-ruled">&nbsp;</span></td>
                    </tr>
                    <tr>
                        <td>तारीख: <input type="text" name="hi[ddate]" class="blank blank--sm" value="{{ $data['_hi']['ddate'] ?? '' }}" autocomplete="off"></td>
                        <td>(नाम साफ अक्षरों में): <span class="mirror-out" data-mirror-out="hi_name">&nbsp;</span></td>
                    </tr>
                </table>

                <div class="dw-copy">
                    प्रतिलिपि:
                    <div class="dw-copy__row"><span class="dw-copy__lbl">(माता-पिता / अभिभावक का नाम):</span> <input type="text" name="hi[pgname]" class="blank blank--wide" value="{{ $data['_hi']['pgname'] ?? '' }}" autocomplete="off"></div>
                    <div class="dw-copy__row"><span class="dw-copy__lbl">पता:</span> <textarea name="hi[pgaddr]" rows="2" class="blank blank--area">{{ $data['_hi']['pgaddr'] ?? '' }}</textarea></div>
                </div>
            </div>

            {{-- ─────────────── HINDI CLARIFICATION (sample Document-3 page 2) ─────────────── --}}
            <div class="dw-doc" lang="hi">
                <span class="lang-tag">हिन्दी</span>
                <div class="dw-clhd">उल्लिखित नियम के बारे में स्पष्टीकरण</div>

                <div class="dw-rule">अखिल भारतीय सेवा (आचरण) नियमावली, 1968 का नियम 11-ए</div>
                <div class="dw-clbody">(क) दहेज देना अथवा लेना — इस सेवा का कोई भी सदस्य</div>
                <div class="dw-clause">(i) न दहेज देगा, न दहेज लेगा और न ही दहेज देने या लेने के लिए दुष्प्रेरित करेगा; अथवा</div>
                <div class="dw-clause">(ii) वधू अथवा वर (जो भी लागू हो) के माता-पिता या अभिभावक से प्रत्यक्ष अथवा अप्रत्यक्ष रूप से किसी दहेज की मांग नहीं करेगा/करेगी।</div>
                <div class="dw-expl"><strong>स्पष्टीकरण:</strong> इस नियम के अंतर्गत &lsquo;दहेज&rsquo; से वही अर्थ अभिप्रेत है जो दहेज प्रतिषेध अधिनियम, 1961 (1961 का 28) में दिया गया है।</div>

                <div class="dw-rule">केन्द्रीय सिविल सेवा (आचरण) नियमावली, 1964 का नियम 13-ए — दहेज</div>
                <div class="dw-clbody">कोई भी सरकारी कर्मचारी —</div>
                <div class="dw-clause">(i) न दहेज देगा, न दहेज लेगा और न ही दहेज देने या लेने के लिए दुष्प्रेरित करेगा; अथवा</div>
                <div class="dw-clause">(ii) वधू अथवा वर (जो भी लागू हो) के माता-पिता अथवा अभिभावक से प्रत्यक्ष अथवा अप्रत्यक्ष रूप से किसी दहेज की मांग नहीं करेगा/करेगी।</div>
                <div class="dw-expl"><strong>स्पष्टीकरण:</strong> इस नियम के अंतर्गत &lsquo;दहेज&rsquo; से वही अर्थ अभिप्रेत है जो दहेज प्रतिषेध अधिनियम, 1961 (1961 का 28) में दिया गया है।</div>
                <div class="dw-sep">***</div>
            </div>

            {{-- ─────────────── ENGLISH (fillable — matches the official sample, Document-3 page 3) ─────────────── --}}
            <div class="dw-doc" lang="en">
                <span class="lang-tag">English</span>
                <div class="dw-docno">Document-3</div>
                <div class="dw-title">DECLARATION</div>

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

                <table class="dw-signtbl">
                    <tr>
                        <td>Place: <input type="text" name="place" class="blank" value="{{ $val('place') }}" data-mirror="place"></td>
                        <td>Signature: <span class="dw-ruled">&nbsp;</span></td>
                    </tr>
                    <tr>
                        <td>Dated: <input type="date" name="declaration_date" class="blank blank--sm" value="{{ $val('declaration_date') }}" data-mirror="ddate"></td>
                        <td>(Name of Officer in Block Letters): <span class="mirror-out" data-mirror-out="name">&nbsp;</span></td>
                    </tr>
                </table>

                <div class="dw-copy">
                    <em>Copy of the declaration to:</em>
                    <div class="dw-copy__row"><span class="dw-copy__lbl">(Name of Parent / Guardian):</span> <input type="text" name="parent_guardian_name" class="blank blank--wide" value="{{ $val('parent_guardian_name') }}" data-mirror="pgname"></div>
                    <div class="dw-copy__row"><span class="dw-copy__lbl">Address:</span> <textarea name="parent_guardian_address" rows="2" class="blank blank--area" data-mirror="pgaddr">{{ $val('parent_guardian_address') }}</textarea></div>
                </div>
            </div>

            {{-- ─────────────── ENGLISH CLARIFICATION + EXTRACTS (sample Document-3 page 4) ─────────────── --}}
            <div class="dw-doc" lang="en">
                <span class="lang-tag">English</span>
                <div class="dw-clhd">CLARIFICATION ON MENTIONED RULE</div>

                <div class="dw-rule">Rule 11-A of the All India Services (Conduct) Rules, 1968</div>
                <div class="dw-clbody">11-A. Giving or taking of dowry — No member of the Service shall —</div>
                <div class="dw-clause">(i) give or take or abet the giving or taking of dowry; or</div>
                <div class="dw-clause">(ii) demand, directly or indirectly, from the parents or guardian of a bride or bridegroom, as the case may be, any dowry.</div>
                <div class="dw-expl"><strong>Explanation:</strong> For the purpose of this rule, &ldquo;dowry&rdquo; has the same meaning as in the Dowry Prohibition Act, 1961 (28 of 1961).</div>

                <div class="dw-rule">Rule 13-A of the Central Civil Services (Conduct) Rules, 1964</div>
                <div class="dw-clbody">13-A. Dowry — No Government servant shall —</div>
                <div class="dw-clause">(i) give or take or abet the giving or taking of dowry; or</div>
                <div class="dw-clause">(ii) demand, directly or indirectly, from the parent or guardian of a bride or bridegroom, as the case may be, any dowry.</div>
                <div class="dw-expl"><strong>Explanation:</strong> For the purposes of this rule, &ldquo;dowry&rdquo; has the same meaning as in the Dowry Prohibition Act, 1961 (28 of 1961).</div>

                <div class="dw-clhd" style="margin-top:1.4rem;">EXTRACT FROM &ldquo;THE DOWRY PROHIBITION ACT, 1961&rdquo; (28 OF 1961)</div>
                <div class="dw-clbody"><strong>Section 2. Definition of &ldquo;dowry&rdquo;</strong> — In this Act, &ldquo;dowry&rdquo; means any property or valuable security given or agreed to be given either directly or indirectly —</div>
                <div class="dw-clause">(a) by one party to a marriage to the other party to the marriage, or</div>
                <div class="dw-clause">(b) by the parents of either party to a marriage or by any other person to either party to the marriage or to any other person; at or before or any time after the marriage in connection with the marriage of the said parties, but does not include dower or mahar in the case of persons to whom the Muslim Personal Law (Shariat) applies.</div>
                <div class="dw-expl"><strong>Explanation II</strong> — The expression &ldquo;valuable security&rdquo; has the same meaning as in Section 30 of the Indian Penal Code (45 of 1860).</div>

                <div class="dw-clhd" style="margin-top:1.4rem;">EXTRACT FROM THE INDIAN PENAL CODE (45 OF 1860)</div>
                <div class="dw-clbody"><strong>Section 30. &ldquo;Valuable Security&rdquo;</strong> — The words &ldquo;valuable security&rdquo; denote a document which is, or purports to be, a document whereby any legal right is created, extended, transferred, restricted, extinguished or released, or whereby any person acknowledges that he lies under legal liability, or has not a certain legal right.</div>
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
