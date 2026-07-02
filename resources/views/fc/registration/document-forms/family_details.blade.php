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
    .fm-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px; box-shadow:0 6px 22px rgba(0,40,90,.06);
        padding:2rem 2.2rem; margin-bottom:1.25rem; font-family:'Times New Roman', Georgia, serif; color:#111; }
    @media (max-width:575.98px){ .fm-paper{ padding:1.2rem .8rem; } }
    .fm-docno{ text-align:right; font-weight:700; font-size:.85rem; }
    .fm-title{ text-align:center; font-weight:700; font-size:1.12rem; text-decoration:underline; margin:.2rem 0 0; }
    .fm-sub{ text-align:center; font-weight:700; font-size:.82rem; margin:.2rem 0 0; }
    .fm-title-hi{ text-align:center; font-weight:700; font-size:1rem; margin:.35rem 0 0; }
    .fm-item{ font-size:1rem; margin:.5rem 0 0; }
    .fm-item .lbl{ font-weight:600; }
    .fm-blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; font-family:inherit; font-size:inherit;
        padding:0 .35rem 1px; color:#0b3d91; font-weight:600; outline:none; min-width:130px; }
    .fm-blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    table.fm{ width:100%; border-collapse:collapse; margin:1rem 0; font-family:system-ui,sans-serif; }
    table.fm th, table.fm td{ border:1px solid #555; padding:5px 6px; font-size:.76rem; vertical-align:middle; }
    table.fm th{ background:#eef3fb; text-align:center; font-weight:700; color:var(--fc-navy); }
    .fm-cno{ font-weight:400; color:#666; }
    .fm-office{ background:#f6f7f9; color:#9aa6b5; text-align:center; font-size:.68rem; }
    .fm-in{ border:0; border-bottom:1px dotted #94a3b8; background:transparent; width:100%; padding:.1rem .2rem;
        color:#0b3d91; font-weight:600; font-size:.8rem; outline:none; }
    .fm-in:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .fm-decl{ font-size:.95rem; margin-top:1.2rem; }
    .fm-sign{ margin-top:1.4rem; font-size:1rem; line-height:2.1; }
    .fm-notes{ font-size:.82rem; margin-top:1.4rem; line-height:1.55; font-family:system-ui,sans-serif; border-top:1px solid #d5deeb; padding-top:.8rem; }
    .fm-notes b{ color:#14315e; }
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

        <div class="fm-paper">
            <div class="fm-docno">Document-1</div>
            <div class="fm-title">Form No. 3: Details of Family</div>
            <div class="fm-sub">[See Rule 54(12) of CCS (Pension) Rules, 1972]</div>
            <div class="fm-title-hi">फॉर्म सं. 3 : परिवार का विवरण</div>

            <div class="fm-item"><span class="lbl">Name of the Government Servant / सरकारी कर्मचारी का नाम:</span>
                <input type="text" name="officer_name" class="fm-blank" required value="{{ $val('officer_name') }}" style="min-width:240px;"></div>
            <div class="fm-item"><span class="lbl">Designation / पद नाम:</span>
                <input type="text" name="designation" class="fm-blank" value="{{ $val('designation') }}" style="min-width:220px;"></div>
            <div class="fm-item"><span class="lbl">Date of Birth / जन्म तिथि:</span>
                <input type="date" name="date_of_birth" class="fm-blank" value="{{ $val('date_of_birth') }}"></div>
            <div class="fm-item"><span class="lbl">Details of the members of my family* as on / परिवार के सदस्यों का विवरण, तदनांक:</span>
                <input type="date" name="details_as_on" class="fm-blank" value="{{ $val('details_as_on') }}"></div>

            <div class="d-flex justify-content-end mb-1 mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="fmAddRow()"><i class="bi bi-plus-circle me-1"></i>Add Row</button>
            </div>
            <table class="fm">
                <thead>
                    <tr>
                        <th style="width:38px;">S.No.<div class="fm-cno">(1)</div></th>
                        <th>Name of the members of family* / परिवार के सदस्य का नाम<div class="fm-cno">(2)</div></th>
                        <th style="width:13%;">Date of Birth** / जन्म तिथि<div class="fm-cno">(3)</div></th>
                        <th style="width:15%;">Relationship with the officer / अधिकारी के साथ संबंध<div class="fm-cno">(4)</div></th>
                        <th style="width:13%;">Marital Status / वैवाहिक स्थिति<div class="fm-cno">(5)</div></th>
                        <th style="width:15%;">Remarks / टिप्पणी<div class="fm-cno">(6)</div></th>
                        <th style="width:12%;">Dated signature of Head of Office / कार्यालय प्रमुख के दिनांकित हस्ताक्षर<div class="fm-cno">(7)</div></th>
                        <th style="width:34px;">—</th>
                    </tr>
                </thead>
                <tbody id="fmBody">
                    @foreach($rows as $ri => $row)
                        <tr class="fm-row">
                            <td class="text-center fm-sno">{{ $ri + 1 }}</td>
                            <td><input type="text" name="{{ $tbl['key'] }}[name][]" class="fm-in" value="{{ $row['name'] ?? '' }}"></td>
                            <td><input type="date" name="{{ $tbl['key'] }}[dob][]" class="fm-in" value="{{ $row['dob'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[relationship][]" class="fm-in" value="{{ $row['relationship'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[marital_status][]" class="fm-in" value="{{ $row['marital_status'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[remarks][]" class="fm-in" value="{{ $row['remarks'] ?? '' }}"></td>
                            <td class="fm-office">(for office use)</td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="fmRemoveRow(this)"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="fm-decl">I hereby undertake to keep the above particulars up-to-date by notifying to the Head of the Office any addition or alteration. / मैं एतद्द्वारा किसी भी परिवर्धन या परिवर्तन के बारे में कार्यालय प्रमुख को सूचित करके उपर्युक्त विवरणों को अद्यतन रखने का वचन देता/देती हूँ।</div>

            <div class="fm-sign row">
                <div class="col-md-6">
                    <div>Place / स्थान: <input type="text" name="place" class="fm-blank" value="{{ $val('place') }}"></div>
                    <div>Date / दिनांक: <input type="date" name="declaration_date" class="fm-blank" value="{{ $val('declaration_date') }}"></div>
                </div>
                <div class="col-md-6 text-md-end">Signature of the Government Servant / सरकारी कर्मचारी के हस्ताक्षर<br>______________________</div>
            </div>

            <div class="fm-notes">
                <div><b>* Family</b> means family as defined in clause (b) of sub-rule (14) of Rule 54 of the CCS (Pension) Rules, 1972 — wife/husband (incl. judicially separated), unmarried son under 25 & unmarried/widowed/divorced daughter (incl. legally adopted), dependent parents, and dependent disabled siblings. / <b>* परिवार</b> का अर्थ केन्द्रीय सिविल सेवा (पेंशन) नियमावली, 1972 के नियम 54 के उप-नियम (14) के खंड (ख) में परिभाषित परिवार से है।</div>
                <div class="mt-1"><b>**</b> Please attach an ID proof of date of birth (in each case, except your own). / <b>**</b> जन्म तिथि का पहचान प्रमाण संलग्न करें (अपने को छोड़कर प्रत्येक मामले में)।</div>
                <div class="mt-1"><b>Note:</b> Additions/alterations are recorded in Column (7) under the Head of Office's signature; details of spouse, all children and parents, and disabled siblings may be given. / <b>नोट:</b> परिवर्धन/परिवर्तन कॉलम (7) में कार्यालय प्रमुख के हस्ताक्षर के तहत दर्ज किए जाते हैं। (Full instructions are reproduced on the generated PDF.)</div>
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
                    <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i>Column (7) and the Head of Office signature are filled by the office. Optional — you may also print the generated PDF and sign it physically.</small>
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
window.fmAddRow = function(){ var b=document.getElementById('fmBody'); if(!b) return; var r=b.querySelector('.fm-row').cloneNode(true); r.querySelectorAll('input').forEach(function(e){e.value='';}); b.appendChild(r); fmRenum(); };
window.fmRemoveRow = function(btn){ var b=document.getElementById('fmBody'); if(b.querySelectorAll('.fm-row').length>1) btn.closest('.fm-row').remove(); else btn.closest('.fm-row').querySelectorAll('input').forEach(function(e){e.value='';}); fmRenum(); };
function fmRenum(){ document.querySelectorAll('#fmBody .fm-row').forEach(function(r,i){ r.querySelector('.fm-sno').textContent=i+1; }); }
</script>
@endpush
@endsection
