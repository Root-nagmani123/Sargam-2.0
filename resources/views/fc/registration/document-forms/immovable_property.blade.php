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
    .im-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px; box-shadow:0 6px 22px rgba(0,40,90,.06);
        padding:2rem 2.2rem; margin-bottom:1.25rem; font-family:'Times New Roman', Georgia, serif; color:#111; }
    @media (max-width:575.98px){ .im-paper{ padding:1.2rem .8rem; } }
    .im-docno{ text-align:right; font-weight:700; font-size:.85rem; }
    .im-formline{ text-align:center; font-weight:700; font-size:.82rem; margin:.2rem 0 0; }
    .im-title-hi{ text-align:center; font-weight:700; font-size:1.05rem; margin:.4rem 0 0; }
    .im-title{ text-align:center; font-weight:700; font-size:1.05rem; text-decoration:underline; margin:.15rem 0 0; }
    .im-item{ font-size:1rem; margin:.5rem 0 0; }
    .im-item .lbl{ font-weight:600; }
    .im-blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; font-family:inherit; font-size:inherit;
        padding:0 .35rem 1px; color:#0b3d91; font-weight:600; outline:none; min-width:120px; }
    .im-blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    table.im{ width:100%; border-collapse:collapse; margin:1.1rem 0; font-family:system-ui,sans-serif; }
    table.im th, table.im td{ border:1px solid #555; padding:5px 6px; font-size:.72rem; vertical-align:top; }
    table.im th{ background:#eef3fb; text-align:center; font-weight:700; color:var(--fc-navy); }
    .im-cno{ font-weight:400; color:#666; }
    .im-in{ border:0; border-bottom:1px dotted #94a3b8; background:transparent; width:100%; padding:.1rem .2rem;
        color:#0b3d91; font-weight:600; font-size:.8rem; outline:none; font-family:system-ui,sans-serif; }
    .im-in:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .im-footer{ margin-top:1.2rem; font-size:1rem; }
    .im-notes{ font-size:.8rem; margin-top:1.4rem; line-height:1.6; font-family:system-ui,sans-serif; border-top:1px solid #d5deeb; padding-top:.8rem; }
    .im-notes b{ color:#14315e; }
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

        <div class="im-paper">
            <div class="im-docno">Document-6(a)</div>
            <div class="im-formline">[Form 1 — See Government of India's Instruction (1) and (2) below Rule 16]</div>
            <div class="im-title-hi">प्रथम नियुक्ति के समय भरा जाने वाला अचल संपत्ति के विवरण का फार्म</div>
            <div class="im-title">STATEMENT OF IMMOVABLE PROPERTY ON FIRST APPOINTMENT</div>
            <div style="text-align:center; font-weight:600; margin:.5rem 0 .2rem;">as on date / जिस तिथि तक:
                <input type="date" name="as_on_date" class="im-blank" value="{{ $val('as_on_date') }}" style="min-width:150px;"></div>

            <div class="im-item"><span class="lbl">1.</span> अधिकारी का पूरा नाम, तथा सेवा जिससे वह संबंधित है / Name of the Officer (in full) and service to which the officer belongs:
                <input type="text" name="officer_name" class="im-blank" required value="{{ $val('officer_name') }}" style="min-width:260px;"></div>
            <div class="im-item"><span class="lbl">2.</span> वर्तमान पद / Present Post held:
                <input type="text" name="present_post" class="im-blank" value="{{ $val('present_post') }}" style="min-width:180px;"></div>
            <div class="im-item">
                <span class="lbl">3.</span> राज्य संवर्ग जिससे संबंधित है / Cadre of the State on which borne:
                <input type="text" name="cadre" class="im-blank" value="{{ $val('cadre') }}" style="min-width:150px;">
                &nbsp;&nbsp;<span class="lbl">4.</span> वर्तमान वेतन / Present Pay (₹):
                <input type="text" name="present_pay" class="im-blank" value="{{ $val('present_pay') }}" style="min-width:130px;">
            </div>

            <div class="d-flex justify-content-end mb-1 mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="imAddRow()"><i class="bi bi-plus-circle me-1"></i>Add Row</button>
            </div>
            <table class="im">
                <thead>
                    <tr>
                        <th style="width:15%;">जिला, उपखण्ड, तालुका एवं गांव का नाम जहां संपत्ति है<br>Name of District, Sub-Division, Taluk and Village in which property is situated<div class="im-cno">(1)</div></th>
                        <th style="width:15%;">संपत्ति का नाम तथा विवरण (मकान/अन्य भवन एवं भूमि)<br>Name and details of Property (Housing / other building and Land)<div class="im-cno">(2)</div></th>
                        <th style="width:10%;">*वर्तमान मूल्य<br>Present Value<div class="im-cno">(3)</div></th>
                        <th style="width:16%;">**यदि अपने नाम पर नहीं है तो किसके नाम पर, तथा कर्मचारी के साथ संबंध<br>If not in own name, in whose name held and his/her relationship to the member of the Service<div class="im-cno">(4)</div></th>
                        <th>कैसे अर्जित की — खरीद/पट्टा***/बंधक/विरासत/उपहार आदि; अर्जन की तारीख तथा जिनसे प्राप्त की उनके नाम एवं विवरण<br>How acquired — purchase/lease***/mortgage/inheritance/gift, etc., with date of acquisition and details of persons from whom acquired<div class="im-cno">(5)</div></th>
                        <th style="width:10%;">संपत्ति से वार्षिक आय<br>Annual Income from the Property<div class="im-cno">(6)</div></th>
                        <th style="width:10%;">अभ्युक्तियां<br>Remarks<div class="im-cno">(7)</div></th>
                        <th style="width:34px;">—</th>
                    </tr>
                </thead>
                <tbody id="imBody">
                    @foreach($rows as $row)
                        <tr class="im-row">
                            <td><input type="text" name="{{ $tbl['key'] }}[location][]" class="im-in" value="{{ $row['location'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[property_details][]" class="im-in" value="{{ $row['property_details'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[present_value][]" class="im-in" value="{{ $row['present_value'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[in_whose_name][]" class="im-in" value="{{ $row['in_whose_name'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[how_acquired][]" class="im-in" value="{{ $row['how_acquired'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[annual_income][]" class="im-in" value="{{ $row['annual_income'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[remarks][]" class="im-in" value="{{ $row['remarks'] ?? '' }}"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="imRemoveRow(this)"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="im-footer row">
                <div class="col-md-6">दिनांक / Dated:
                    <input type="date" name="declaration_date" class="im-blank" value="{{ $val('declaration_date') }}" style="min-width:150px;"></div>
                <div class="col-md-6 text-md-end">हस्ताक्षर / Signature: ______________________</div>
            </div>

            <div class="im-notes">
                <div class="mb-2"><b>Note / टिप्पणी:</b> The declaration form is required to be filled in and submitted by every member of IAS/IPS under Rule 16(5) of the All India Services (Conduct) Rules, 1968 on first appointment, and Class-I and Class-II Service under Rule 18(3) of the Central Civil Services (Conduct) Rules, 1965, and thereafter at an interval of every twelve months — giving particulars of all immovable property owned, acquired or inherited by him/her, or held by him/her on lease or mortgage, either in his/her own name or in the name of any member of his/her family or any other person. / यह घोषणा प्रपत्र भा.प्र.सेवा/भा.पु.सेवा के प्रत्येक सदस्य को (अखिल भारतीय सेवा (आचरण) नियमावली 1968 के नियम 16(5) के अंतर्गत) तथा प्रथम एवं द्वितीय श्रेणी सेवा के सदस्यों को (केन्द्रीय सिविल सेवा (आचरण) नियमावली 1965 के नियम 18(3) के अंतर्गत) प्रथम नियुक्ति के समय भरकर प्रस्तुत करना होता है तथा तत्पश्चात प्रत्येक 12 माह के अंतराल पर।</div>
                <div class="mb-1"><b>*</b> In cases where it is not possible to assess the value accurately, the approximate value in relation to present conditions may be indicated. / जहाँ सही-सही मूल्य निर्धारण संभव न हो, वहाँ वर्तमान स्थिति के अनुसार अनुमानित मूल्य दर्शाएँ।</div>
                <div class="mb-1"><b>**</b> Inapplicable clause to be struck out. / जो लागू न हो, काट दें।</div>
                <div><b>***</b> Includes short-term lease also. / अल्पावधि का पट्टा भी सम्मिलित है।</div>
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
window.imAddRow = function(){ var b=document.getElementById('imBody'); if(!b) return; var r=b.querySelector('.im-row').cloneNode(true); r.querySelectorAll('input').forEach(function(e){e.value='';}); b.appendChild(r); };
window.imRemoveRow = function(btn){ var b=document.getElementById('imBody'); if(b.querySelectorAll('.im-row').length>1) btn.closest('.im-row').remove(); else btn.closest('.im-row').querySelectorAll('input').forEach(function(e){e.value='';}); };
</script>
@endpush
@endsection
