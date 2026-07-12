@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php
    $val = fn ($name) => old($name, $data[$name] ?? '');
    $elig = $val('surety_eligibility');
    $svcOpts = $template['sections'][0]['fields'][1]['options'] ?? ['IAS', 'IPS', 'IFoS'];
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
    .sb-subtitle{ text-align:center; font-weight:700; font-size:.92rem; margin:.3rem 0 0; }
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
    select.blank{ border-bottom:1px dotted #64748b; }
    .mirror-out{ border-bottom:1px dotted #94a3b8; padding:0 .3rem; display:inline-block; min-width:80px; }
    .sb-handfill{ font-size:.72rem; color:#64748b; font-style:italic; margin:.15rem 0 .5rem; }
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
                <div class="sb-docno">Document-7-A</div>
                <div class="sb-title">
                    This bond to be typed and signed on India Non-Judicial Paper (Rs. 100/-) by Probationers of<br>
                    ALL INDIA SERVICES (IAS / IPS / IFoS)
                </div>
                <div class="sb-subtitle">Format to be used by IAS / IPS / IFoS Probationers</div>

                <div class="sb-to">To,<br>&emsp;&emsp;The President of India,</div>

                <p class="sb-body">
                    Whereas I,
                    <textarea name="officer_name" rows="2" class="blank" required data-mirror="pname" placeholder="full name &amp; address of the Probationer">{{ $val('officer_name') }}</textarea>
                    a probationer in the
                    <select name="service" class="blank blank--sm" data-mirror="svc">
                        <option value="">—</option>
                        @foreach($svcOpts as $opt)<option value="{{ $opt }}" {{ (string)$val('service')===(string)$opt?'selected':'' }}>{{ $opt }}</option>@endforeach
                    </select>
                    (Indian Administrative Service / Indian Police Service / Indian Forest Service) (hereinafter referred to as
                    &lsquo;the Probationer&rsquo;), being entitled — subject to compliance with the
                    <input type="text" name="service_rule" class="blank blank--mid" value="{{ $val('service_rule') }}" data-mirror="srule" placeholder="applicable Service (Probation) Rules">
                    [Indian Administrative Service (Probation) Rules, 1954 / Indian Police Service (Probation) Rules, 1954 /
                    Indian Forest Service (Probation) Rules, 1968] — to receive from the President of India (hereinafter referred to
                    as the Central Government) or from the Government of the State to which I may be posted, pay and allowances during
                    the period in which I am under training:
                </p>

                <p class="sb-body">
                    Now, we, the Probationer, and
                    <input type="text" name="surety_name" class="blank blank--mid" value="{{ $val('surety_name') }}" data-mirror="sname" placeholder="name of the Surety">
                    residing at
                    <textarea name="surety_address" rows="2" class="blank" data-mirror="saddr" placeholder="address of the Surety">{{ $val('surety_address') }}</textarea>
                    working as
                    <input type="text" name="surety_occupation" class="blank blank--mid" value="{{ $val('surety_occupation') }}" data-mirror="socc" placeholder="occupation">
                    (hereinafter referred to as &lsquo;the Surety&rsquo;), jointly and severally, do hereby, in pursuance of the said
                    rules, promise and agree that in the event of the failure of the Probationer to complete probation to the
                    satisfaction of the Central Government, to refund to the Central Government on demand any moneys paid to him/her,
                    including the pay and travelling expenses to join appointment.
                </p>

                <p class="sb-body">
                    The Surety hereby agrees that his/her liability hereunder shall not be affected by the Central Government
                    extending the period of probation, or giving the Probationer an extension of time for payment of, or compounding,
                    the amount payable hereunder.
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
                    I, the Surety whose signature is appended to the above agreement, do hereby declare that —
                    <span class="sb-elig d-block">
                        <label class="d-block"><input type="radio" name="surety_eligibility" value="In the permanent service of Government" {{ $elig==='In the permanent service of Government'?'checked':'' }}>
                            (a) I am in the permanent service of the Government of <span style="border-bottom:1px dotted #64748b; min-width:160px; display:inline-block;">&nbsp;</span>; <em>or</em></label>
                        <label class="d-block"><input type="radio" name="surety_eligibility" value="Ordinarily resident in India" {{ $elig==='Ordinarily resident in India'?'checked':'' }}>
                            (b) I am ordinarily resident in India and possess means which will enable me to repay to the Central Government the sums of money referred to, in the event of my being called upon to do so in accordance with the terms of the agreement.</label>
                    </span>
                    <em>(Strike out whichever is not applicable.)</em>
                </p>
            </div>

            {{-- ─────────────── HINDI (candidate types their own Hindi; blank by default) ─────────────── --}}
            <div class="sb-doc" lang="hi">
                <span class="lang-tag">हिन्दी</span>
                <div class="sb-handfill">हिन्दी विवरण यहाँ भरें (वैकल्पिक) — या रिक्त छोड़कर मुद्रित बंधपत्र पर हाथ से भरें। / Enter the Hindi details here (optional), or leave blank to hand-fill on the printed bond.</div>
                <div class="sb-docno">दस्तावेज़-7-ए</div>
                <div class="sb-title">अखिल भारतीय सेवाओं (आई.ए.एस. / आई.पी.एस. / आई.एफ़.ओ.एस.) के परिवीक्षाधीन अधिकारियों द्वारा भरा जाना है</div>

                <div class="sb-to">सेवा में,<br>&emsp;&emsp;भारत के राष्ट्रपति,</div>

                <p class="sb-body">
                    मैं, <textarea name="hi[pname]" rows="2" class="blank" placeholder="परिवीक्षाधीन का नाम और पता">{{ $data['_hi']['pname'] ?? '' }}</textarea> (परिवीक्षाधीन का नाम और पता),
                    <input type="text" name="hi[svc]" class="blank blank--mid" value="{{ $data['_hi']['svc'] ?? '' }}" autocomplete="off"> सेवा में परिवीक्षाधीन व्यक्ति (जिसे इसमें आगे
                    &lsquo;परिवीक्षाधीन&rsquo; कहा गया है), राष्ट्रपति (जिन्हें इसमें आगे &lsquo;केन्द्र सरकार&rsquo; कहा गया है) अथवा जिस राज्य
                    में मुझे नियुक्त किया जाए, उस राज्य सरकार से प्रशिक्षण की अवधि के लिए (भारतीय प्रशासनिक सेवा (परिवीक्षा) नियमावली,
                    1954 अथवा भारतीय पुलिस सेवा (परिवीक्षा) नियमावली, 1954 अथवा भारतीय वन सेवा (परिवीक्षा) नियमावली, 1968 के
                    अध्यधीन) वेतन और भत्ते पाने का हकदार हूँ।
                </p>

                <p class="sb-body">
                    अब, परिवीक्षाधीन और मैं, <input type="text" name="hi[sname]" class="blank blank--mid" value="{{ $data['_hi']['sname'] ?? '' }}" autocomplete="off"> निवासी
                    <textarea name="hi[saddr]" rows="2" class="blank" placeholder="प्रतिभू का पता">{{ $data['_hi']['saddr'] ?? '' }}</textarea>, <input type="text" name="hi[socc]" class="blank blank--mid" value="{{ $data['_hi']['socc'] ?? '' }}" autocomplete="off">
                    (प्रतिभू का नाम और पता, जिसे इसमें आगे &lsquo;प्रतिभू&rsquo; कहा गया है), उक्त नियमों के अनुसरण में संयुक्त रूप से और
                    पृथक-पृथक एतद्द्वारा वचन देते हैं और करार करते हैं कि यदि परिवीक्षाधीन व्यक्ति केन्द्र सरकार की संतुष्टि के अनुसार परिवीक्षा
                    की अवधि पूरी नहीं कर पाता है, तो वेतन और नियुक्ति पर जाने के लिए यात्रा व्यय सहित परिवीक्षाधीन व्यक्ति को भुगतान की गई
                    किसी भी राशि की मांग किए जाने पर हम उसका भुगतान करेंगे।
                </p>

                <p class="sb-body">
                    प्रतिभू एतद्द्वारा यह करार करता है कि यहां नीचे दी गई उसकी देयता केन्द्र सरकार द्वारा उसकी परिवीक्षा की अवधि बढ़ाए जाने से
                    अथवा परिवीक्षाधीन व्यक्ति को भुगतान के लिए अधिक समय देने या यहां नीचे दी गई देय राशि को माफ करने से किसी प्रकार
                    प्रभावित नहीं होगी।
                </p>

                <div class="sb-lines">
                    दिनांक <input type="text" name="hi[ddate]" class="blank blank--sm" value="{{ $data['_hi']['ddate'] ?? '' }}" autocomplete="off">
                    <div class="sb-sign mt-3">परिवीक्षाधीन के हस्ताक्षर ______________________</div>
                    <div>साक्षी की उपस्थिति में परिवीक्षाधीन द्वारा हस्ताक्षरित — साक्षी का नाम: <input type="text" name="hi[pwname]" class="blank blank--mid" value="{{ $data['_hi']['pwname'] ?? '' }}" autocomplete="off"></div>
                    <div>पता: <input type="text" name="hi[pwaddr]" class="blank blank--wide" value="{{ $data['_hi']['pwaddr'] ?? '' }}" autocomplete="off"> &nbsp; व्यवसाय: <input type="text" name="hi[pwocc]" class="blank blank--mid" value="{{ $data['_hi']['pwocc'] ?? '' }}" autocomplete="off"></div>

                    <div class="sb-sign mt-3">प्रतिभू के हस्ताक्षर ______________________</div>
                    <div>साक्षी की उपस्थिति में प्रतिभू द्वारा हस्ताक्षरित — साक्षी का नाम: <input type="text" name="hi[swname]" class="blank blank--mid" value="{{ $data['_hi']['swname'] ?? '' }}" autocomplete="off"></div>
                    <div>पता: <input type="text" name="hi[swaddr]" class="blank blank--wide" value="{{ $data['_hi']['swaddr'] ?? '' }}" autocomplete="off"> &nbsp; व्यवसाय: <input type="text" name="hi[swocc]" class="blank blank--mid" value="{{ $data['_hi']['swocc'] ?? '' }}" autocomplete="off"></div>
                </div>

                <p class="sb-body" style="text-indent:0; margin-top:1.4rem;">
                    मैं, प्रतिभू, जिसके हस्ताक्षर उपर्युक्त करार में दिए गए हैं, एतद्द्वारा घोषणा करता/करती हूँ कि —
                    <span class="d-block mt-1">(क) मैं ______ सरकार की स्थायी सेवा में हूँ; <b>अथवा</b></span>
                    <span class="d-block">(ख) मैं साधारणतया भारत का निवासी हूँ और मेरे पास ऐसे साधन हैं जिनसे करार के निबंधनों के अनुसार राशि की मांग किए जाने पर मैं केन्द्र सरकार को वह राशि चुका सकता/सकती हूँ।</span>
                    (जो लागू न हो उसे काट दें।)
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

{{-- Hindi entries are intentionally NOT auto-filled from English. The Hindi copy is
     filled by hand on the printed bond, so its slots stay blank (dotted lines). --}}
@endsection
