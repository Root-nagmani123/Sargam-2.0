@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php
    $val  = fn ($name) => old($name, $data[$name] ?? '');
    $tbl  = $template['tables'][0];
    $cols = array_column($tbl['columns'], 'name');
    $oldRows = old($tbl['key']);
    if (is_array($oldRows)) {
        $cnt = 0; foreach ($cols as $c) { $cnt = max($cnt, is_array($oldRows[$c] ?? null) ? count($oldRows[$c]) : 0); }
        $rows = []; for ($i=0;$i<max($cnt,1);$i++){ $r=[]; foreach($cols as $c){ $r[$c]=$oldRows[$c][$i] ?? ''; } $rows[]=$r; }
    } else { $rows = $data['_tables'][$tbl['key']] ?? []; if(!$rows) $rows=[array_fill_keys($cols,'')]; }
@endphp

@push('styles')
<style>
    .mp-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px; box-shadow:0 6px 22px rgba(0,40,90,.06);
        padding:2rem 2.2rem; margin-bottom:1.25rem; font-family:'Times New Roman', Georgia, serif; color:#111; }
    @media (max-width:575.98px){ .mp-paper{ padding:1.2rem .8rem; } }
    .mp-docno{ text-align:right; font-weight:700; font-size:.85rem; }
    .mp-title-hi{ text-align:center; font-weight:700; font-size:1.05rem; margin:.2rem 0 0; }
    .mp-title{ text-align:center; font-weight:700; font-size:1.05rem; text-decoration:underline; margin:.15rem 0 0; }
    .mp-brackets{ text-align:center; font-size:.8rem; color:#333; margin:.35rem 0 0; line-height:1.4; }
    .mp-year{ text-align:right; font-weight:700; margin:.8rem 0 0; }
    .mp-item{ font-size:1rem; margin:.5rem 0 0; }
    .mp-item .lbl{ font-weight:600; }
    .mp-blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; font-family:inherit; font-size:inherit;
        padding:0 .35rem 1px; color:#0b3d91; font-weight:600; outline:none; min-width:120px; }
    .mp-blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    table.mp{ width:100%; border-collapse:collapse; margin:1.1rem 0; font-family:system-ui,sans-serif; }
    table.mp th, table.mp td{ border:1px solid #555; padding:5px 6px; font-size:.74rem; vertical-align:top; }
    table.mp th{ background:#eef3fb; text-align:center; font-weight:700; color:var(--fc-navy); }
    .mp-cno{ font-weight:400; color:#666; }
    .mp-in{ border:0; border-bottom:1px dotted #94a3b8; background:transparent; width:100%; padding:.1rem .2rem;
        color:#0b3d91; font-weight:600; font-size:.8rem; outline:none; font-family:system-ui,sans-serif; }
    .mp-in:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .mp-footer{ margin-top:1.2rem; font-size:1rem; }
    .mp-notes{ font-size:.82rem; margin-top:1.4rem; line-height:1.6; font-family:system-ui,sans-serif; border-top:1px solid #d5deeb; padding-top:.8rem; }
    .mp-notes b{ color:#14315e; }
</style>
@endpush

<div class="fc-form-page">
<div class="fc-shell">
    <div class="fc-band">
        <div class="fc-band__row">
            <div class="fc-band__ico"><i class="bi bi-pencil-square"></i></div>
            <div><h4>{{ $template['title'] }}</h4><p>{{ $template['title_hi'] ?? $template['subtitle'] ?? '' }}</p></div>
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

        <div class="mp-paper">
            <div class="mp-docno">Document-6-B</div>
            <div class="mp-title-hi">प्रथम नियुक्ति के समय भरा जाने वाला चल संपत्ति के विवरण का फार्म</div>
            <div class="mp-title">STATEMENT OF MOVABLE PROPERTY ON FIRST APPOINTMENT</div>
            <div class="mp-brackets">
                [b. Shares, debentures, postal Cumulative Time Deposits and cash including bank deposits inherited by him or similarly owned, acquired or held by him]<br>
                [c. Other movable property owned, acquired or held by him]
            </div>

            <div class="mp-year">वर्ष / YEAR: <input type="text" name="year" class="mp-blank" value="{{ $val('year') }}" style="min-width:110px;"></div>

            <div class="mp-item"><span class="lbl">1.</span> अधिकारी का पूरा नाम, तथा सेवा जिससे वह संबंधित है / Name of the Officer (in full) and service to which the officer belongs:
                <input type="text" name="officer_name" class="mp-blank" required value="{{ $val('officer_name') }}" style="min-width:260px;"></div>
            <div class="mp-item">
                <span class="lbl">2.</span> वर्तमान पद / Present Post:
                <input type="text" name="present_post" class="mp-blank" value="{{ $val('present_post') }}" style="min-width:180px;">
                &nbsp;&nbsp;<span class="lbl">3.</span> वर्तमान वेतन / Present Pay (₹):
                <input type="text" name="present_pay" class="mp-blank" value="{{ $val('present_pay') }}" style="min-width:140px;">
            </div>

            <div class="d-flex justify-content-end mb-1 mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="mpAddRow()"><i class="bi bi-plus-circle me-1"></i>Add Row</button>
            </div>
            <table class="mp">
                <thead>
                    <tr>
                        <th style="width:20%;">चल संपत्ति का नाम तथा विवरण<br>Name and details of Movable Property<div class="mp-cno">(1)</div></th>
                        <th style="width:12%;">*वर्तमान मूल्य<br>Present Value<div class="mp-cno">(2)</div></th>
                        <th style="width:22%;">यदि अपने नाम पर नहीं है तो बताएं किसके नाम पर है, तथा उसका कर्मचारी के साथ संबंध<br>If not in own name, state in whose name held and his/her relationship to the Govt. Servant<div class="mp-cno">(3)</div></th>
                        <th>कैसे अर्जित की — खरीदी है, विरासत में मिली, उपहार में मिली या अन्य तरह से; अर्जन की तारीख तथा जिन व्यक्तियों से प्राप्त की उनके नाम एवं विवरण<br>How acquired — purchase/inheritance/gift, etc., with date of acquisition and name &amp; details of persons from whom acquired<div class="mp-cno">(4)</div></th>
                        <th style="width:14%;">अभ्युक्तियां<br>Remarks<div class="mp-cno">(5)</div></th>
                        <th style="width:34px;">—</th>
                    </tr>
                </thead>
                <tbody id="mpBody">
                    @foreach($rows as $row)
                        <tr class="mp-row">
                            <td><input type="text" name="{{ $tbl['key'] }}[description][]" class="mp-in" value="{{ $row['description'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[present_value][]" class="mp-in" value="{{ $row['present_value'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[in_whose_name][]" class="mp-in" value="{{ $row['in_whose_name'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[how_acquired][]" class="mp-in" value="{{ $row['how_acquired'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[remarks][]" class="mp-in" value="{{ $row['remarks'] ?? '' }}"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="mpRemoveRow(this)"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mp-footer row">
                <div class="col-md-6">दिनांक / Dated:
                    <input type="date" name="declaration_date" class="mp-blank" value="{{ $val('declaration_date') }}" style="min-width:150px;"></div>
                <div class="col-md-6 text-md-end">हस्ताक्षर / Signature: ______________________</div>
            </div>

            <div class="mp-notes">
                <div class="mb-1"><b>Note / टिप्पणी:</b> The declaration form is required to be filled in and submitted by every member of IAS/IPS and Class-I and Class-II Service, giving particulars of all movable property held by him either in his own name or in the name of any member of his family or in the name of any other person. / भा.प्र.सेवा/भा.पु.सेवा तथा प्रथम एवं द्वितीय श्रेणी सेवा के प्रत्येक सदस्य को इस घोषणा फार्म में अपनी उस समस्त चल संपत्ति का विवरण देना होता है जो चाहे उसके अपने नाम पर हो या परिवार के किसी सदस्य अथवा किसी अन्य व्यक्ति के नाम पर हो।</div>
                <div><b>*</b> In cases where it is not possible to assess the value accurately, the approximate value in relation to present conditions may be indicated. / जहाँ सही-सही मूल्य निर्धारण संभव न हो, वहाँ वर्तमान स्थिति के अनुसार अनुमानित मूल्य दर्शाएँ।</div>
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
window.mpAddRow = function(){ var b=document.getElementById('mpBody'); if(!b) return; var r=b.querySelector('.mp-row').cloneNode(true); r.querySelectorAll('input').forEach(function(e){e.value='';}); b.appendChild(r); };
window.mpRemoveRow = function(btn){ var b=document.getElementById('mpBody'); if(b.querySelectorAll('.mp-row').length>1) btn.closest('.mp-row').remove(); else btn.closest('.mp-row').querySelectorAll('input').forEach(function(e){e.value='';}); };
</script>
@endpush
@endsection
