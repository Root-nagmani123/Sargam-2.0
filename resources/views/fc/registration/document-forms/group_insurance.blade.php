@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php
    $val = fn ($name) => old($name, $data[$name] ?? '');
    $cols = array_column($template['tables'][0]['columns'], 'name');
    $colDefs = $template['tables'][0]['columns'];
    $oldRows = old('nominees');
    if (is_array($oldRows)) {
        $cnt = 0; foreach ($cols as $c) { $cnt = max($cnt, is_array($oldRows[$c] ?? null) ? count($oldRows[$c]) : 0); }
        $rows = [];
        for ($i = 0; $i < max($cnt,1); $i++) { $r = []; foreach ($cols as $c) { $r[$c] = $oldRows[$c][$i] ?? ''; } $rows[] = $r; }
    } else {
        $rows = $data['_tables']['nominees'] ?? [];
        if (! $rows) { $rows = [array_fill_keys($cols, '')]; }
    }
@endphp

@push('styles')
<style>
    .gi-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px;
        box-shadow:0 6px 22px rgba(0,40,90,.06); padding:2.2rem 2.4rem; margin-bottom:1.25rem;
        font-family:'Times New Roman', Georgia, serif; color:#111; }
    @media (max-width:575.98px){ .gi-paper{ padding:1.4rem 1rem; } }
    .gi-variant-box{ float:right; border:1px solid #888; padding:.35rem .6rem; font-family:system-ui,sans-serif;
        font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#14315e; }
    .gi-formno{ text-align:center; font-weight:700; font-size:.95rem; margin:0; }
    .gi-title{ text-align:center; font-weight:700; font-size:1.05rem; margin:.7rem 0 0; text-transform:uppercase; }
    .gi-sub{ text-align:center; font-style:italic; font-size:.9rem; margin:.4rem 0 0; }
    .gi-cat{ font-family:system-ui,sans-serif; background:#f4f8fd; border:1px solid var(--fc-line);
        border-radius:8px; padding:.7rem .9rem; margin:1.1rem 0; }
    .gi-body{ font-size:1rem; line-height:2; text-align:justify; margin:1.2rem 0 0; }
    .gi-table{ width:100%; border-collapse:collapse; margin:1rem 0; font-family:system-ui,sans-serif; }
    .gi-table th, .gi-table td{ border:1px solid #555; padding:5px 6px; font-size:.76rem; vertical-align:top; }
    .gi-table th{ background:#eef3fb; text-align:center; font-weight:700; color:var(--fc-navy); }
    .gi-table .cno{ font-weight:400; color:#666; }
    .gi-in{ border:0; border-bottom:1px dotted #94a3b8; background:transparent; width:100%;
        padding:.1rem .2rem; color:#0b3d91; font-weight:600; font-size:.8rem; outline:none; font-family:system-ui,sans-serif; }
    .gi-in:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; font-family:inherit;
        font-size:inherit; padding:0 .3rem 1px; color:#0b3d91; font-weight:600; outline:none; min-width:70px; }
    .blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .blank--name{ min-width:280px; }
    .gi-lines{ margin-top:1.4rem; font-size:.98rem; line-height:2.1; }
    .gi-lbl{ font-weight:600; }
    .gi-note{ font-size:.86rem; margin-top:1.2rem; line-height:1.7; }
    .mirror-out{ font-weight:600; color:#0b3d91; }
</style>
@endpush

<div class="fc-form-page">
<div class="fc-shell">
    <div class="fc-band">
        <div class="fc-band__row">
            <div class="fc-band__ico"><i class="bi bi-pencil-square"></i></div>
            <div>
                <h4>{{ $template['title'] }}</h4>
                <p>{{ $template['title_hi'] ?? $template['subtitle'] ?? '' }}</p>
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

        {{-- ═══════════ PAGE 1 · ACCOUNT SECTION (LBSNAA cover) ═══════════ --}}
        <div class="gi-paper">
            <div style="text-align:center; font-family:'Times New Roman',Georgia,serif;">
                <div style="font-weight:700; font-size:1.02rem;">Government of India / भारत सरकार</div>
                <div style="font-weight:600; font-size:.9rem;">Ministry of Personnel, Public Grievances &amp; Pensions (Department of Personnel &amp; Training)</div>
                <div style="font-weight:600; font-size:.86rem;">कार्मिक, लोक शिकायत एवं पेंशन मंत्रालय (कार्मिक एवं प्रशिक्षण विभाग)</div>
                <div style="font-weight:700; font-size:.96rem; margin-top:.3rem;">Lal Bahadur Shastri National Academy of Administration, Mussoorie-248179</div>
                <div style="font-weight:700; font-size:.9rem;">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी-248179</div>
            </div>
            <div style="text-align:center; margin:1rem 0 1.3rem;">
                <span style="display:inline-block; background:#14315e; color:#fff; font-weight:700; letter-spacing:1px; padding:.35rem 1.4rem; border-radius:4px;">ACCOUNT SECTION / लेखा अनुभाग</span>
            </div>

            <div class="gi-lines" style="font-size:1rem;">
                <div class="mb-2"><span class="gi-lbl">1. Name in full (Block Letters) / पूरा नाम (बड़े अक्षरों में):</span>
                    <input type="text" name="officer_name" class="blank blank--name" required value="{{ $val('officer_name') }}" data-mirror="name" autocomplete="off"></div>
                <div class="mb-2"><span class="gi-lbl">2. Service to which you belong / जिस सेवा से आप संबंधित हैं:</span>
                    <input type="text" name="service" class="blank" style="min-width:260px;" value="{{ $val('service') }}"></div>
                <div class="mb-2"><span class="gi-lbl">3. Date of Joining / कार्यग्रहण की तिथि:</span>
                    <input type="date" name="date_of_joining" class="blank" value="{{ $val('date_of_joining') }}">
                    &nbsp;
                    <span class="ms-2">
                        <label class="gi-lbl d-inline">( </label>
                        <label style="font-weight:400;"><input type="radio" name="joining_time" value="Forenoon" {{ $val('joining_time')==='Forenoon'?'checked':'' }}> Forenoon / पूर्वाह्न</label>
                        &nbsp;
                        <label style="font-weight:400;"><input type="radio" name="joining_time" value="Afternoon" {{ $val('joining_time')==='Afternoon'?'checked':'' }}> Afternoon / अपराह्न</label>
                        <label class="gi-lbl d-inline"> )</label>
                    </span></div>
                <div class="mb-2"><span class="gi-lbl">4. Whether a fresh Trainee or a Departmental candidate who will receive salary from the parent department / नया प्रशिक्षु अथवा विभागीय अभ्यर्थी जो मूल विभाग से वेतन प्राप्त करेगा:</span>
                    <div class="mt-1" style="font-weight:400;">
                        <label class="me-3"><input type="radio" name="trainee_type" value="Fresh Trainee" {{ $val('trainee_type')==='Fresh Trainee'?'checked':'' }}> Fresh Trainee / नया प्रशिक्षु</label>
                        <label><input type="radio" name="trainee_type" value="Departmental Candidate" {{ $val('trainee_type')==='Departmental Candidate'?'checked':'' }}> Departmental Candidate / विभागीय अभ्यर्थी</label>
                    </div></div>
                <div class="mb-2"><span class="gi-lbl">5. If a Departmental candidate, name &amp; address of the Department paying your salary during the Foundation Course / यदि विभागीय अभ्यर्थी हैं, तो आधार पाठ्यक्रम के दौरान वेतन देने वाले विभाग का नाम एवं पता:</span>
                    <textarea name="department_details" rows="2" class="form-control mt-1" style="border:1px solid #cbd5e1;border-radius:6px;">{{ $val('department_details') }}</textarea></div>
                <div class="mb-2"><span class="gi-lbl">6. Were you earlier a member of the CGEGIS? If so, the monthly subscription and the name &amp; address of the office maintaining the account / क्या आप पहले सीजीईजीआईएस के सदस्य थे? यदि हाँ, तो मासिक अंशदान तथा खाता रखने वाले कार्यालय का नाम एवं पता:</span>
                    <textarea name="earlier_member" rows="2" class="form-control mt-1" style="border:1px solid #cbd5e1;border-radius:6px;">{{ $val('earlier_member') }}</textarea></div>
                <div class="d-flex justify-content-between mt-3">
                    <div>Date / दिनांक: <input type="date" name="declaration_date" class="blank" value="{{ $val('declaration_date') }}"></div>
                    <div>Signature of Trainee / प्रशिक्षु के हस्ताक्षर</div>
                </div>
            </div>
        </div>

        {{-- ═══════════ PAGE 2 · FORM No. 7 / 8 NOMINATION ═══════════ --}}
        <div class="gi-paper">
            <div class="gi-variant-box" id="giVariantTag">Form No. 7 / 8</div>
            <p class="gi-formno">Form No. 7 / Form No. 8 &nbsp; {See Para 19.7}<br>प्रपत्र सं. 7 / प्रपत्र सं. 8</p>
            <h2 class="gi-title">Nomination for Benefits under the Central Government Employees Group Insurance Scheme, 1980</h2>
            <div style="text-align:center; font-weight:700; font-size:.98rem; margin-top:.3rem;">केंद्रीय सरकार कर्मचारी समूह बीमा योजना, 1980 के अंतर्गत लाभों हेतु नामांकन</div>
            <p class="gi-sub">(Form 7 — when the Government servant has <strong>no family</strong>; Form 8 — when the Government servant <strong>has a family</strong>) <br>(प्रपत्र 7 — जब सरकारी कर्मचारी का <strong>कोई परिवार नहीं</strong> है; प्रपत्र 8 — जब सरकारी कर्मचारी का <strong>परिवार है</strong>)</p>

            <div class="gi-cat">
                <label class="form-label small fw-bold mb-1">Applicable Form / लागू प्रपत्र <span class="text-danger">*</span></label>
                <select name="form_variant" id="giVariant" class="form-select form-select-sm" required style="max-width:420px;">
                    <option value="">— Select —</option>
                    @foreach(($template['sections'][0]['fields'][2]['options'] ?? []) as $opt)
                        <option value="{{ $opt }}" {{ (string) $val('form_variant') === (string) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            <p class="gi-body">
                I, <span class="blank blank--name mirror-out" data-mirror-out="name" style="display:inline-block;">&nbsp;</span>
                having <strong>no family / a family</strong>, hereby nominate the person/persons mentioned below and confer
                on him/them the right to receive, to the extent specified below, any amount that may be sanctioned by the
                Central Government under the Central Government Employees Group Insurance Scheme, 1980, in the event of my
                death while in service or which, having become payable on my attaining the age of superannuation, may
                remain unpaid at my death.
                <br><span style="font-size:.92rem;">मैं, <span class="mirror-out" data-mirror-out="name">&nbsp;</span> जिसका <strong>कोई परिवार नहीं है / परिवार है</strong>, एतद्द्वारा नीचे उल्लिखित व्यक्ति/व्यक्तियों को नामांकित करता/करती हूँ तथा उन्हें केंद्रीय सरकार कर्मचारी समूह बीमा योजना, 1980 के अंतर्गत मेरी सेवाकाल में मृत्यु की स्थिति में स्वीकृत होने वाली राशि नीचे दर्शाई गई सीमा तक प्राप्त करने का अधिकार प्रदान करता/करती हूँ।</span>
            </p>

            <div class="d-flex justify-content-end mb-1">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="giAddRow()"><i class="bi bi-plus-circle me-1"></i>Add Row</button>
            </div>
            <table class="gi-table">
                <thead>
                    <tr>
                        @foreach($colDefs as $i => $c)<th>{!! $c['label'] !!}<div class="cno">({{ $i + 1 }})</div></th>@endforeach
                        <th style="width:36px;">—</th>
                    </tr>
                </thead>
                <tbody id="giBody">
                    @foreach($rows as $row)
                        <tr class="gi-row">
                            @foreach($colDefs as $c)
                                <td><input type="text" name="nominees[{{ $c['name'] }}][]" class="gi-in" value="{{ $row[$c['name']] ?? '' }}"></td>
                            @endforeach
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="giRemoveRow(this)"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="gi-lines">
                <div>Dated this <span class="blank mirror-out" data-mirror-out="date" style="display:inline-block; min-width:130px;">&nbsp;</span> at
                    <input type="text" name="place" class="blank" value="{{ $val('place') }}" placeholder="place / स्थान"> / दिनांक ......... स्थान .........</div>
                <div class="mt-2"><span class="gi-lbl">Two witnesses to signature / हस्ताक्षर के दो साक्षी:</span></div>
                <div>1. <span class="blank" style="min-width:320px;">&nbsp;</span></div>
                <div>2. <span class="blank" style="min-width:320px;">&nbsp;</span></div>
                <div class="mt-3" style="text-align:right;">Signature of the Government Servant / सरकारी कर्मचारी के हस्ताक्षर</div>
                <div>Name / नाम: <span class="blank blank--name mirror-out" data-mirror-out="name" style="display:inline-block;">&nbsp;</span></div>
                <div>Designation / पद नाम: <input type="text" name="designation" class="blank" value="{{ $val('designation') }}"></div>
            </div>

            <div class="gi-note">
                <strong>N.B. / टिप्पणी:</strong>
                <ol class="mb-0 ps-3 mt-1">@foreach($template['notes'] ?? [] as $n)<li>{!! $n !!}</li>@endforeach</ol>
            </div>
        </div>

        {{-- Signature uploads (Government Servant + two witnesses) --}}
        @if(! empty($template['signatures']))
            <div class="card fc-card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3"><h6 class="mb-0 text-uppercase small fw-bold text-muted">Signatures / हस्ताक्षर</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($template['signatures'] as $i => $sig)
                            @php $existingSig = $data['_signatures'][$i] ?? null; @endphp
                            <div class="col-md-4">
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
    function syncName() {
        var src = document.querySelector('[name="officer_name"]');
        var v = src ? src.value : '';
        document.querySelectorAll('[data-mirror-out="name"]').forEach(function (o){ o.textContent = v || ' '; });
    }
    function syncDate() {
        var src = document.querySelector('[name="declaration_date"]');
        var v = src ? fmtDate(src.value) : '';
        document.querySelectorAll('[data-mirror-out="date"]').forEach(function (o){ o.textContent = v || ' '; });
    }
    var nm = document.querySelector('[name="officer_name"]');
    if (nm) { nm.addEventListener('input', syncName); nm.addEventListener('change', syncName); }
    var dt = document.querySelector('[name="declaration_date"]');
    if (dt) { dt.addEventListener('input', syncDate); dt.addEventListener('change', syncDate); }
    syncName(); syncDate();

    var sel = document.getElementById('giVariant'), tag = document.getElementById('giVariantTag');
    function syncVariant(){ if(!sel||!tag) return; var v=sel.value; tag.textContent = v.indexOf('Form 7')===0 ? 'UNMARRIED / परिवार रहित' : (v.indexOf('Form 8')===0 ? 'MARRIED / परिवार सहित' : 'Form No. 7 / 8'); }
    if (sel) sel.addEventListener('change', syncVariant);
    syncVariant();

    window.giAddRow = function(){
        var body = document.getElementById('giBody');
        var r = body.querySelector('.gi-row').cloneNode(true);
        r.querySelectorAll('input').forEach(function(e){ e.value=''; });
        body.appendChild(r);
    };
    window.giRemoveRow = function(btn){
        var body = document.getElementById('giBody');
        if (body.querySelectorAll('.gi-row').length > 1) btn.closest('.gi-row').remove();
        else btn.closest('.gi-row').querySelectorAll('input').forEach(function(e){ e.value=''; });
    };
})();
</script>
@endpush
@endsection
