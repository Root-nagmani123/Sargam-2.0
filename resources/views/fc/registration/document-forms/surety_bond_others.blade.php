@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php
    $val = fn ($name) => old($name, $data[$name] ?? '');
    $elig = $val('surety_eligibility');
@endphp

@push('styles')
<style>
    .sb-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px; box-shadow:0 6px 22px rgba(0,40,90,.06);
        padding:2.4rem 2.6rem; margin-bottom:1.25rem; font-family:'Times New Roman', Georgia, serif; color:#111; }
    @media (max-width:575.98px){ .sb-paper{ padding:1.5rem 1rem; } }
    .sb-doc{ max-width:840px; margin:0 auto; }
    .sb-doc + .sb-doc{ border-top:2px dashed #cbd5e1; margin-top:2.4rem; padding-top:2.4rem; }
    .sb-docno{ text-align:right; font-weight:700; font-size:.9rem; }
    .sb-title{ text-align:center; font-weight:700; font-size:1rem; text-decoration:underline; margin:.4rem 0 0; line-height:1.5; }
    .sb-to{ margin:1.4rem 0 0; font-size:1rem; }
    .sb-body{ font-size:1rem; line-height:2.15; text-align:justify; margin:1rem 0 0; text-indent:2.2rem; }
    .sb-lines{ margin-top:1.6rem; font-size:1rem; line-height:2.2; }
    .sb-sign{ margin-top:1.2rem; font-weight:600; }
    .sb-elig{ margin:.6rem 0 0; }
    .lang-tag{ display:inline-block; font-size:.68rem; font-weight:700; letter-spacing:.6px; color:#64748b;
        border:1px solid var(--fc-line); border-radius:20px; padding:.1rem .6rem; margin-bottom:.5rem; text-transform:uppercase; }
    .blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; font-family:inherit; font-size:inherit;
        padding:0 .35rem 1px; color:#0b3d91; font-weight:600; outline:none; min-width:80px; }
    .blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .blank--wide{ min-width:340px; }
    .blank--mid{ min-width:200px; }
    .blank--sm{ min-width:110px; }
    textarea.blank{ border:1px solid #cbd5e1; border-radius:6px; vertical-align:middle; min-width:340px; }
    .mirror-out{ color:#0b3d91; font-weight:600; border-bottom:1px dotted #94a3b8; padding:0 .3rem; display:inline-block; min-width:80px; }
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

        <div class="sb-paper">
            {{-- ─────────────── ENGLISH (fillable) ─────────────── --}}
            <div class="sb-doc" lang="en">
                <span class="lang-tag">English</span>
                <div class="sb-docno">Document-7-B</div>
                <div class="sb-title">
                    This bond to be typed and signed on India Non-Judicial Paper (Rs. 100/-) by Probationers of<br>
                    CENTRAL CIVIL SERVICES – Group-A (Other than All India Services)
                </div>

                <div class="sb-to">To,<br>&emsp;&emsp;The President of India,</div>

                <p class="sb-body">
                    Whereas I,
                    <textarea name="officer_name" rows="2" class="blank" required data-mirror="pname" placeholder="full name &amp; address of the Probationer">{{ $val('officer_name') }}</textarea>
                    a candidate recommended by the Union Public Service Commission, Class-I Service, on the results of the
                    Civil Service Examination, 20<input type="text" name="exam_year" class="blank blank--sm" value="{{ $val('exam_year') }}" data-mirror="exyear" autocomplete="off">
                    in the
                    <input type="text" name="service" class="blank blank--mid" value="{{ $val('service') }}" data-mirror="svc" placeholder="Name of Service" autocomplete="off">
                    (hereinafter referred to as &lsquo;the Probationer&rsquo;) being entitled, subject to compliance with the recruitment
                    rules of the
                    <input type="text" name="service_rule" class="blank blank--mid" value="{{ $val('service_rule') }}" data-mirror="srule" placeholder="name of your service" autocomplete="off">,
                    to receive from the President of India (hereinafter referred to as the Central Government) pay and allowances
                    during the period in which I am under training.
                </p>

                <p class="sb-body">
                    And whereas the Probationer is required to furnish Surety as herein contained. And whereas, at the request of
                    the Probationer, I,
                    <input type="text" name="surety_name" class="blank blank--mid" value="{{ $val('surety_name') }}" data-mirror="sname" placeholder="full name of the Surety" autocomplete="off">
                    residing at
                    <textarea name="surety_address" rows="2" class="blank" data-mirror="saddr" placeholder="residence address of the Surety">{{ $val('surety_address') }}</textarea>
                    working as
                    <input type="text" name="surety_occupation" class="blank blank--mid" value="{{ $val('surety_occupation') }}" data-mirror="socc" placeholder="occupation" autocomplete="off">
                    (hereinafter referred to as the Surety) have agreed to stand as Surety for the Probationer as herein contained.
                </p>

                <p class="sb-body">
                    Now, we, the Probationer and I, the Surety, jointly and severally, do hereby, in pursuance of the said rules,
                    promise and agree that in the event of the failure of the Probationer to complete his/her training to the
                    satisfaction of the Central Government, to refund to the Central Government on demand without demur any moneys
                    paid to him/her, including pay and travelling expenses. And it is agreed that the decision of the Central
                    Government as to the failure of the Probationer as aforesaid and the amount payable by the Probationer and the
                    Surety shall be final and binding on the Probationer and the Surety. The Surety hereby agrees that his/her
                    liability hereunder shall not be affected on account of the Central Government giving the Probationer extension
                    of time for payment of, or compounding, the amount payable hereunder, or on account of any indulgence shown to
                    the Probationer.
                </p>

                <div class="sb-lines">
                    Dated <input type="date" name="declaration_date" class="blank blank--sm" value="{{ $val('declaration_date') }}" data-mirror="ddate">.
                    <div class="sb-sign mt-3">Signature of the Probationer ______________________</div>
                    <div>Signed by the Probationer in the presence of —</div>
                    <div>Name of Witness: <input type="text" name="prob_witness_name" class="blank blank--mid" value="{{ $val('prob_witness_name') }}"></div>
                    <div>Address: <input type="text" name="prob_witness_address" class="blank blank--wide" value="{{ $val('prob_witness_address') }}"></div>
                    <div>Occupation: <input type="text" name="prob_witness_occupation" class="blank blank--mid" value="{{ $val('prob_witness_occupation') }}"></div>

                    <div class="sb-sign mt-3">Signature of the Surety ______________________</div>
                    <div>Signed by the Surety in the presence of —</div>
                    <div>Name of Witness: <input type="text" name="surety_witness_name" class="blank blank--mid" value="{{ $val('surety_witness_name') }}"></div>
                    <div>Address: <input type="text" name="surety_witness_address" class="blank blank--wide" value="{{ $val('surety_witness_address') }}"></div>
                    <div>Occupation: <input type="text" name="surety_witness_occupation" class="blank blank--mid" value="{{ $val('surety_witness_occupation') }}"></div>
                </div>

                <p class="sb-body" style="text-indent:0; margin-top:1.4rem;">
                    I, the Surety whose signature is appended to the above agreement, do hereby declare that
                    <span class="sb-elig d-block">
                        <label class="d-block"><input type="radio" name="surety_eligibility" value="In the permanent service of Government" {{ $elig==='In the permanent service of Government'?'checked':'' }}>
                            (a) I am in the permanent service of the Government of <span style="border-bottom:1px dotted #64748b; min-width:160px; display:inline-block;">&nbsp;</span>; <em>or</em></label>
                        <label class="d-block"><input type="radio" name="surety_eligibility" value="Ordinarily resident in India" {{ $elig==='Ordinarily resident in India'?'checked':'' }}>
                            (b) I am ordinarily resident in India and possess means which will enable me to repay to the Central Government the sums of money referred to, in the event of my being called upon to do so in accordance with the terms of the agreement.</label>
                    </span>
                    <em>(Strike out whichever is not applicable.)</em>
                </p>
            </div>

            {{-- ─────────────── HINDI (mirrors the same values) ─────────────── --}}
            <div class="sb-doc" lang="hi">
                <span class="lang-tag">हिन्दी</span>
                <div class="sb-docno">दस्तावेज़-7-बी</div>
                <div class="sb-title">केन्द्रीय सिविल सेवा (अखिल भारतीय सेवाओं के अलावा)</div>

                <div class="sb-to">सेवा में,<br>&emsp;&emsp;भारत के राष्ट्रपति,</div>

                <p class="sb-body">
                    जबकि मैं, <span class="mirror-out" data-mirror-out="pname">&nbsp;</span> (परिवीक्षाधीन का पूरा नाम और पता),
                    संघ लोक सेवा आयोग द्वारा सिविल सेवा परीक्षा, 20<span class="mirror-out" data-mirror-out="exyear">&nbsp;</span>
                    (परीक्षा का नाम) के परिणाम के आधार पर की गई सिफारिश के अनुसार
                    <span class="mirror-out" data-mirror-out="svc">&nbsp;</span> (सेवा का नाम) में नियुक्त उम्मीदवार,
                    जिसे इसमें आगे &lsquo;परिवीक्षाधीन&rsquo; कहा गया है, भर्ती नियमों के अनुपालन के अधीन
                    <span class="mirror-out" data-mirror-out="srule">&nbsp;</span> के प्रशिक्षण के दौरान, भारत के राष्ट्रपति
                    (जिन्हें इसमें आगे &lsquo;केन्द्र सरकार&rsquo; कहा गया है) से वेतन और भत्ते पाने का हकदार हूँ।
                </p>

                <p class="sb-body">
                    और जबकि परिवीक्षाधीन को इसमें उल्लिखित प्रतिभूति देनी होती है। और जबकि परिवीक्षाधीन के अनुरोध पर, मैं,
                    <span class="mirror-out" data-mirror-out="sname">&nbsp;</span> (प्रतिभू का पूरा नाम), निवासी
                    <span class="mirror-out" data-mirror-out="saddr">&nbsp;</span> (निवास स्थान का पता),
                    <span class="mirror-out" data-mirror-out="socc">&nbsp;</span> (व्यवसाय) के रूप में कार्यरत
                    (जिसे इसमें आगे &lsquo;प्रतिभू&rsquo; कहा गया है), इसमें उल्लिखित परिवीक्षाधीन का प्रतिभू बनने के लिए सहमत हूँ।
                </p>

                <p class="sb-body">
                    अब हम, परिवीक्षाधीन तथा मैं प्रतिभू, संयुक्त रूप से और पृथक-पृथक रूप से एतद्द्वारा वचन देते हैं और करार करते हैं कि
                    यदि परिवीक्षाधीन केन्द्र सरकार की संतुष्टि के अनुसार अपना प्रशिक्षण पूरा नहीं कर पाता है, तो वेतन और यात्रा व्यय सहित
                    परिवीक्षाधीन को भुगतान की गई किसी भी राशि की मांग किए जाने पर हम उसे अविलम्ब लौटा देंगे। यह भी करार किया जाता है कि
                    उपर्युक्त अनुसार परिवीक्षाधीन के असफल होने के संबंध में तथा परिवीक्षाधीन और प्रतिभू द्वारा देय राशि के संबंध में केन्द्र
                    सरकार का निर्णय परिवीक्षाधीन और प्रतिभू के लिए अंतिम और बाध्यकारी होगा। प्रतिभू एतद्द्वारा यह भी सहमत होता है कि उसकी
                    देयता, केन्द्र सरकार द्वारा परिवीक्षाधीन को भुगतान हेतु अधिक समय देने या देय राशि को माफ करने अथवा परिवीक्षाधीन के प्रति
                    किसी उदारता के कारण किसी प्रकार प्रभावित नहीं होगी।
                </p>

                <div class="sb-lines">
                    दिनांक <span class="mirror-out" data-mirror-out="ddate">&nbsp;</span>।
                    <div class="sb-sign mt-3">परिवीक्षाधीन के हस्ताक्षर ______________________</div>
                    <div>साक्षी की उपस्थिति में परिवीक्षाधीन द्वारा हस्ताक्षरित —</div>
                    <div>साक्षी का नाम: <span class="mirror-out" data-mirror-out="pwname">&nbsp;</span></div>
                    <div>पता: <span class="mirror-out" data-mirror-out="pwaddr">&nbsp;</span> &nbsp; व्यवसाय: <span class="mirror-out" data-mirror-out="pwocc">&nbsp;</span></div>

                    <div class="sb-sign mt-3">प्रतिभू के हस्ताक्षर ______________________</div>
                    <div>साक्षी की उपस्थिति में प्रतिभू द्वारा हस्ताक्षरित —</div>
                    <div>साक्षी का नाम: <span class="mirror-out" data-mirror-out="swname">&nbsp;</span></div>
                    <div>पता: <span class="mirror-out" data-mirror-out="swaddr">&nbsp;</span> &nbsp; व्यवसाय: <span class="mirror-out" data-mirror-out="swocc">&nbsp;</span></div>
                </div>

                <p class="sb-body" style="text-indent:0; margin-top:1.4rem;">
                    मैं, प्रतिभू, जिसके हस्ताक्षर उपर्युक्त करार में दिए गए हैं, एतद्द्वारा घोषणा करता/करती हूँ कि
                    <span class="d-block mt-1">(क) मैं ______ सरकार की स्थायी सेवा में हूँ; <em>अथवा</em></span>
                    <span class="d-block">(ख) मैं साधारणतया भारत का निवासी हूँ और मेरे पास ऐसे साधन हैं जिनसे करार के निबंधनों के अनुसार राशि की मांग किए जाने पर मैं केन्द्र सरकार को वह राशि चुका सकता/सकती हूँ।</span>
                    <em>(जो लागू न हो उसे काट दें।)</em>
                </p>
            </div>
        </div>

        {{-- Signature uploads (Probationer + Surety) --}}
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
                    <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i>To be executed on Non-Judicial Stamp Paper of ₹100. Optional upload — you may also print the generated PDF and sign it physically.</small>
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
    // Mirror the witness fields (no dedicated data-mirror on them → wire by name).
    var pairs = [['prob_witness_name','pwname'],['prob_witness_address','pwaddr'],['prob_witness_occupation','pwocc'],
                 ['surety_witness_name','swname'],['surety_witness_address','swaddr'],['surety_witness_occupation','swocc']];
    pairs.forEach(function(p){
        var src = document.querySelector('[name="'+p[0]+'"]');
        if(!src) return;
        function s(){ document.querySelectorAll('[data-mirror-out="'+p[1]+'"]').forEach(function(o){ o.textContent = src.value || ' '; }); }
        src.addEventListener('input', s); src.addEventListener('change', s); s();
    });
})();
</script>
@endpush
@endsection
