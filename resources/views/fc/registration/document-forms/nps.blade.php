@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php
    $val = fn ($name) => old($name, $data[$name] ?? '');
    // Options pulled from schema so they stay in sync.
    $flat = [];
    foreach (($template['sections'] ?? []) as $sec) { foreach ($sec['fields'] as $f) { $flat[$f['name']] = $f; } }
    $opt = fn ($name) => $flat[$name]['options'] ?? [];
    // nominees rows
    $tbl = $template['tables'][0] ?? null;
    $cols = $tbl ? array_column($tbl['columns'], 'name') : [];
    if ($tbl) {
        $oldRows = old($tbl['key']);
        if (is_array($oldRows)) {
            $cnt = 0; foreach ($cols as $c) { $cnt = max($cnt, is_array($oldRows[$c] ?? null) ? count($oldRows[$c]) : 0); }
            $rows = []; for ($i=0;$i<max($cnt,1);$i++){ $r=[]; foreach($cols as $c){ $r[$c]=$oldRows[$c][$i] ?? ''; } $rows[]=$r; }
        } else { $rows = $data['_tables'][$tbl['key']] ?? []; if(!$rows) $rows=[array_fill_keys($cols,'')]; }
    }
@endphp

@push('styles')
<style>
    .nps-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px; box-shadow:0 6px 22px rgba(0,40,90,.06);
        padding:1.6rem 1.8rem; margin-bottom:1.25rem; font-family:Arial, Helvetica, sans-serif; color:#111; }
    @media (max-width:575.98px){ .nps-paper{ padding:1rem .7rem; } }
    .nps-top{ border:2px solid #000; display:flex; align-items:stretch; }
    .nps-top__main{ flex:1 1 auto; padding:.7rem .9rem; text-align:center; border-right:2px solid #000; }
    .nps-top__t{ font-weight:800; font-size:1.08rem; color:#111; margin:0; }
    .nps-top__cra{ font-size:.72rem; color:#333; margin-top:.2rem; }
    .nps-top__hi{ font-weight:700; font-size:.92rem; margin-top:.25rem; }
    .nps-top__photo{ flex:0 0 96px; display:flex; align-items:center; justify-content:center; text-align:center;
        font-size:.62rem; color:#667; padding:4px; }
    .nps-catbox{ border:1px solid #000; border-top:0; padding:.6rem .9rem; }
    .nps-catbox__h{ font-weight:700; font-size:.82rem; margin-bottom:.4rem; }
    .nps-catgrid{ display:grid; grid-template-columns:repeat(3,1fr); gap:.3rem .9rem; font-size:.82rem; }
    @media (max-width:575.98px){ .nps-catgrid{ grid-template-columns:1fr 1fr; } }
    .nps-to{ font-size:.82rem; margin:.8rem 0; line-height:1.5; }
    .nps-note{ font-size:.72rem; color:#444; font-style:italic; }
    .nps-band{ background:#14315e; color:#fff; padding:.4rem .8rem; font-weight:700; font-size:.82rem;
        text-transform:uppercase; letter-spacing:.3px; margin-top:1rem; }
    .nps-sec{ border:1px solid #b9c7de; border-top:0; padding:.85rem .8rem; }
    .nps-fld{ margin-bottom:.7rem; }
    .nps-lbl{ font-size:.76rem; font-weight:700; color:#222; display:block; margin-bottom:.15rem; }
    .nps-lbl .req{ color:#c0392b; }
    .nps-comb{ width:100%; height:30px; border:1px solid #64748b; padding-left:8px; color:#0b3d91;
        font:700 15px 'Courier New', monospace; letter-spacing:9px; background-color:#fff;
        background-image:linear-gradient(90deg,#cbd5e1 1px,transparent 1px); background-size:18px 100%; background-position:8px 0; }
    .nps-comb:focus{ outline:none; border-color:var(--fc-blue); box-shadow:0 0 0 2px rgba(0,74,147,.12); }
    textarea.nps-comb{ height:auto; background-image:none; letter-spacing:normal; font-family:inherit; padding:6px 8px; }
    select.nps-comb{ background-image:none; letter-spacing:normal; font-family:inherit; }
    .nps-ticks{ display:flex; flex-wrap:wrap; gap:.2rem 1.1rem; align-items:center; padding-top:.15rem; }
    .nps-tick{ font-size:.84rem; display:inline-flex; align-items:center; gap:.3rem; }
    .nps-tick input{ transform:scale(1.05); }
    table.nps-tbl{ width:100%; border-collapse:collapse; }
    table.nps-tbl th, table.nps-tbl td{ border:1px solid #6b7280; padding:5px 6px; font-size:.8rem; }
    table.nps-tbl th{ background:#eef3fb; color:var(--fc-navy); text-align:center; font-weight:700; }
    .nps-tin{ border:0; border-bottom:1px dotted #94a3b8; background:transparent; width:100%; color:#0b3d91; font-weight:600; font-size:.82rem; outline:none; }
    .nps-decl{ border:1px solid #cbd5e1; background:#fafcff; border-radius:8px; padding:.9rem 1rem; font-size:.84rem; line-height:1.65; }
</style>
@endpush

@php
    // Reusable renderers.
    $comb = function ($name, $value, $req = false) {
        $r = $req ? 'required' : '';
        return '<input type="text" name="'.$name.'" value="'.e($value).'" class="nps-comb" '.$r.'>';
    };
@endphp

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
        <div class="alert alert-danger shadow-sm mb-3"><strong class="d-block mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix the following:</strong>
            <ul class="mb-0 ps-3">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('fc-reg.forms.doc-form.save', [$form, $step, $field->field_name]) }}" enctype="multipart/form-data">
        @csrf

        <div class="nps-paper">
            {{-- Header --}}
            <div class="nps-top">
                <div class="nps-top__main">
                    <div class="nps-top__t">NATIONAL PENSION SYSTEM (NPS) – SUBSCRIBER REGISTRATION FORM</div>
                    <div class="nps-top__cra">Central Recordkeeping Agency (CRA) — Protean eGov Technologies Limited <em>(formerly NSDL e-Governance Infrastructure Limited)</em></div>
                    <div class="nps-top__hi">राष्ट्रीय पेंशन प्रणाली (एनपीएस) — अंशदाता पंजीकरण प्रपत्र</div>
                </div>
                <div class="nps-top__photo">Affix recent passport-size photograph<br>(3.5 × 2.5 cm)</div>
            </div>

            {{-- Category tick-grid --}}
            <div class="nps-catbox">
                <div class="nps-catbox__h">Please select your category / कृपया अपनी श्रेणी चुनें <span style="color:#c0392b;">*</span></div>
                <div class="nps-catgrid">
                    @foreach($opt('category') as $o)
                        <label class="nps-tick"><input type="radio" name="category" value="{{ $o }}" {{ (string)$val('category')===(string)$o?'checked':'' }}> {{ $o }}</label>
                    @endforeach
                </div>
            </div>

            <div class="nps-to">
                To,<br><strong>The National Pension System Trust.</strong><br>Dear Sir/Madam,<br>
                I hereby request that an NPS account be opened in my name as per the particulars given below:
                <div class="nps-note">* indicates mandatory fields. Please fill the form in English and in BLOCK letters with black ink. / * अनिवार्य फ़ील्ड दर्शाता है। कृपया प्रपत्र अंग्रेज़ी में बड़े अक्षरों में काली स्याही से भरें।</div>
            </div>

            {{-- 1. PERSONAL DETAILS --}}
            <div class="nps-band">1. PERSONAL DETAILS / व्यक्तिगत विवरण</div>
            <div class="nps-sec">
                <div class="nps-fld">
                    <span class="nps-lbl">Name of Applicant in full / आवेदक का पूरा नाम</span>
                    <div class="nps-ticks">
                        <span class="nps-tick"><input type="radio" name="salutation" value="Shri" {{ $val('salutation')==='Shri'?'checked':'' }}> Shri</span>
                        <span class="nps-tick"><input type="radio" name="salutation" value="Smt" {{ $val('salutation')==='Smt'?'checked':'' }}> Smt.</span>
                        <span class="nps-tick"><input type="radio" name="salutation" value="Kumari" {{ $val('salutation')==='Kumari'?'checked':'' }}> Kumari</span>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-4 nps-fld"><span class="nps-lbl">First Name <span class="req">*</span> / नाम</span>{!! $comb('first_name', $val('first_name'), true) !!}</div>
                    <div class="col-md-4 nps-fld"><span class="nps-lbl">Middle Name / मध्य नाम</span>{!! $comb('middle_name', $val('middle_name')) !!}</div>
                    <div class="col-md-4 nps-fld"><span class="nps-lbl">Last Name / उपनाम</span>{!! $comb('last_name', $val('last_name')) !!}</div>
                    <div class="col-md-6 nps-fld"><span class="nps-lbl">Father's Name / पिता का नाम</span>{!! $comb('father_name', $val('father_name')) !!}</div>
                    <div class="col-md-6 nps-fld"><span class="nps-lbl">Mother's Name / माता का नाम</span>{!! $comb('mother_name', $val('mother_name')) !!}</div>
                    <div class="col-md-4 nps-fld"><span class="nps-lbl">Date of Birth / जन्म तिथि</span><input type="date" name="date_of_birth" value="{{ $val('date_of_birth') }}" class="nps-comb" style="letter-spacing:normal;background-image:none;font-family:inherit;"></div>
                    <div class="col-md-4 nps-fld"><span class="nps-lbl">Place of Birth (City &amp; Country) / जन्म स्थान</span>{!! $comb('place_of_birth', $val('place_of_birth')) !!}</div>
                    <div class="col-md-4 nps-fld"><span class="nps-lbl">Nationality / राष्ट्रीयता</span>{!! $comb('nationality', $val('nationality')) !!}</div>
                    <div class="col-md-6 nps-fld"><span class="nps-lbl">Gender / लिंग</span>
                        <div class="nps-ticks">@foreach($opt('gender') as $o)<span class="nps-tick"><input type="radio" name="gender" value="{{ $o }}" {{ (string)$val('gender')===(string)$o?'checked':'' }}> {{ $o }}</span>@endforeach</div>
                    </div>
                    <div class="col-md-6 nps-fld"><span class="nps-lbl">Marital Status / वैवाहिक स्थिति</span>
                        <div class="nps-ticks">@foreach($opt('marital_status') as $o)<span class="nps-tick"><input type="radio" name="marital_status" value="{{ $o }}" {{ (string)$val('marital_status')===(string)$o?'checked':'' }}> {{ $o }}</span>@endforeach</div>
                    </div>
                    <div class="col-md-6 nps-fld"><span class="nps-lbl">Spouse's Name (if married) / जीवनसाथी का नाम</span>{!! $comb('spouse_name', $val('spouse_name')) !!}</div>
                </div>
            </div>

            {{-- 2. PROOF OF IDENTITY --}}
            <div class="nps-band">2. PROOF OF IDENTITY (PoI) / पहचान का प्रमाण</div>
            <div class="nps-sec"><div class="row g-3">
                <div class="col-md-6 nps-fld"><span class="nps-lbl">PAN / पैन</span>{!! $comb('pan', $val('pan')) !!}</div>
                <div class="col-md-6 nps-fld"><span class="nps-lbl">Aadhaar No. / आधार संख्या</span>{!! $comb('aadhaar', $val('aadhaar')) !!}</div>
            </div></div>

            {{-- 4.1 CORRESPONDENCE ADDRESS --}}
            <div class="nps-band">3. CORRESPONDENCE ADDRESS DETAILS / पत्राचार का पता</div>
            <div class="nps-sec"><div class="row g-3">
                <div class="col-12 nps-fld"><span class="nps-lbl">Address / पता</span><textarea name="corr_address" rows="2" class="nps-comb">{{ $val('corr_address') }}</textarea></div>
                <div class="col-md-4 nps-fld"><span class="nps-lbl">City / शहर</span>{!! $comb('corr_city', $val('corr_city')) !!}</div>
                <div class="col-md-4 nps-fld"><span class="nps-lbl">State / राज्य</span>{!! $comb('corr_state', $val('corr_state')) !!}</div>
                <div class="col-md-4 nps-fld"><span class="nps-lbl">Pincode / पिन कोड</span>{!! $comb('corr_pincode', $val('corr_pincode')) !!}</div>
            </div></div>

            {{-- 4.2 PERMANENT ADDRESS --}}
            <div class="nps-band">4. PERMANENT ADDRESS DETAILS / स्थायी पता</div>
            <div class="nps-sec"><div class="row g-3">
                <div class="col-12 nps-fld"><span class="nps-lbl">Address / पता</span><textarea name="perm_address" rows="2" class="nps-comb">{{ $val('perm_address') }}</textarea></div>
                <div class="col-md-4 nps-fld"><span class="nps-lbl">City / शहर</span>{!! $comb('perm_city', $val('perm_city')) !!}</div>
                <div class="col-md-4 nps-fld"><span class="nps-lbl">State / राज्य</span>{!! $comb('perm_state', $val('perm_state')) !!}</div>
                <div class="col-md-4 nps-fld"><span class="nps-lbl">Pincode / पिन कोड</span>{!! $comb('perm_pincode', $val('perm_pincode')) !!}</div>
            </div></div>

            {{-- 5. CONTACT DETAILS --}}
            <div class="nps-band">5. CONTACT DETAILS / संपर्क विवरण</div>
            <div class="nps-sec"><div class="row g-3">
                <div class="col-md-6 nps-fld"><span class="nps-lbl">Mobile No. / मोबाइल संख्या</span>{!! $comb('mobile', $val('mobile')) !!}</div>
                <div class="col-md-6 nps-fld"><span class="nps-lbl">Email / ईमेल</span><input type="email" name="email" value="{{ $val('email') }}" class="nps-comb" style="letter-spacing:2px;"></div>
            </div></div>

            {{-- 7. SUBSCRIBER BANK DETAILS --}}
            <div class="nps-band">7. SUBSCRIBER BANK DETAILS / अंशदाता बैंक विवरण</div>
            <div class="nps-sec"><div class="row g-3">
                <div class="col-md-6 nps-fld"><span class="nps-lbl">Account Type / खाता प्रकार</span>
                    <div class="nps-ticks">@foreach($opt('account_type') as $o)<span class="nps-tick"><input type="radio" name="account_type" value="{{ $o }}" {{ (string)$val('account_type')===(string)$o?'checked':'' }}> {{ $o }}</span>@endforeach</div>
                </div>
                <div class="col-md-6 nps-fld"><span class="nps-lbl">Bank A/c Number / बैंक खाता संख्या</span>{!! $comb('account_number', $val('account_number')) !!}</div>
                <div class="col-md-6 nps-fld"><span class="nps-lbl">Bank Name / बैंक का नाम</span>{!! $comb('bank_name', $val('bank_name')) !!}</div>
                <div class="col-md-6 nps-fld"><span class="nps-lbl">Branch Name / शाखा का नाम</span>{!! $comb('branch_name', $val('branch_name')) !!}</div>
                <div class="col-12 nps-fld"><span class="nps-lbl">Branch Address / शाखा का पता</span><textarea name="branch_address" rows="2" class="nps-comb">{{ $val('branch_address') }}</textarea></div>
                <div class="col-md-6 nps-fld"><span class="nps-lbl">IFSC Code / आईएफएससी कोड</span>{!! $comb('ifsc', $val('ifsc')) !!}</div>
                <div class="col-md-6 nps-fld"><span class="nps-lbl">MICR Code / एमआईसीआर कोड</span>{!! $comb('micr', $val('micr')) !!}</div>
            </div></div>

            {{-- 8. NOMINATION --}}
            @if($tbl)
                <div class="nps-band d-flex justify-content-between align-items-center"><span>8. SUBSCRIBERS NOMINATION DETAILS / नामांकन विवरण</span>
                    <button type="button" class="btn btn-sm btn-light py-0" onclick="npsAddRow()"><i class="bi bi-plus-circle me-1"></i>Add</button></div>
                <div class="nps-sec p-0">
                    <table class="nps-tbl">
                        <thead><tr><th style="width:40px;">#</th>@foreach($tbl['columns'] as $c)<th>{!! $c['label'] !!}</th>@endforeach<th style="width:34px;">—</th></tr></thead>
                        <tbody id="npsBody">
                            @foreach($rows as $ri => $row)
                                <tr class="nps-row"><td class="text-center">{{ $ri+1 }}</td>
                                    @foreach($tbl['columns'] as $c)<td><input type="text" name="{{ $tbl['key'] }}[{{ $c['name'] }}][]" class="nps-tin" value="{{ $row[$c['name']] ?? '' }}"></td>@endforeach
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="npsRemoveRow(this)"><i class="bi bi-x-lg"></i></button></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- 10. PENSION FUND & INVESTMENT OPTION --}}
            <div class="nps-band">10. PENSION FUND (PF) SELECTION &amp; INVESTMENT OPTION / पेंशन निधि चयन एवं निवेश विकल्प</div>
            <div class="nps-sec"><div class="row g-3">
                <div class="col-md-6 nps-fld"><span class="nps-lbl">Pension Fund Manager (PFM) / पेंशन निधि प्रबंधक</span>{!! $comb('pension_fund', $val('pension_fund')) !!}</div>
                <div class="col-md-6 nps-fld"><span class="nps-lbl">Investment Option / निवेश विकल्प</span>
                    <div class="nps-ticks">@foreach($opt('investment_option') as $o)<span class="nps-tick"><input type="radio" name="investment_option" value="{{ $o }}" {{ (string)$val('investment_option')===(string)$o?'checked':'' }}> {{ $o }}</span>@endforeach</div>
                </div>
                <div class="col-md-6 nps-fld"><span class="nps-lbl">Tier II Account? / टियर II खाता?</span>
                    <div class="nps-ticks">@foreach($opt('tier_ii') as $o)<span class="nps-tick"><input type="radio" name="tier_ii" value="{{ $o }}" {{ (string)$val('tier_ii')===(string)$o?'checked':'' }}> {{ $o }}</span>@endforeach</div>
                </div>
            </div></div>

            {{-- 11. FATCA --}}
            <div class="nps-band">11. DECLARATION ON FATCA COMPLIANCE / एफएटीसीए अनुपालन घोषणा</div>
            <div class="nps-sec">
                <span class="nps-lbl">Are you a tax resident of any country other than India? / क्या आप भारत के अतिरिक्त किसी अन्य देश के कर निवासी हैं?</span>
                <div class="nps-ticks">@foreach($opt('tax_resident_outside') as $o)<span class="nps-tick"><input type="radio" name="tax_resident_outside" value="{{ $o }}" {{ (string)$val('tax_resident_outside')===(string)$o?'checked':'' }}> {{ $o }}</span>@endforeach</div>
            </div>

            {{-- 12. DECLARATION --}}
            <div class="nps-band">12. DECLARATION BY SUBSCRIBER / अंशदाता द्वारा घोषणा</div>
            <div class="nps-sec">
                <div class="nps-decl mb-3">{!! $template['declaration'] !!}</div>
                <div class="row g-3">
                    @foreach(($template['sections_footer'][0]['fields'] ?? []) as $f)
                        <div class="{{ $f['width'] ?? 'col-md-6' }} nps-fld">
                            <span class="nps-lbl">{!! $f['label'] !!}</span>
                            <input type="{{ ($f['type']??'text')==='date'?'date':'text' }}" name="{{ $f['name'] }}" value="{{ $val($f['name']) }}" class="nps-comb" style="letter-spacing:normal;background-image:none;font-family:inherit;">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Signature --}}
        @if(!empty($template['signatures']))
            <div class="card fc-card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3"><h6 class="mb-0 text-uppercase small fw-bold text-muted">Signatures / हस्ताक्षर</h6></div>
                <div class="card-body"><div class="row g-3">
                    @foreach($template['signatures'] as $i => $sig)
                        @php $existingSig = $data['_signatures'][$i] ?? null; @endphp
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">{!! $sig !!}</label>
                            <input type="file" name="signature[{{ $i }}]" class="form-control form-control-sm @error('signature.'.$i) is-invalid @enderror" accept="image/*">
                            @error('signature.'.$i)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            @if($existingSig)<div class="mt-2"><img src="{{ asset('storage/'.$existingSig) }}" style="max-height:48px;border:1px solid #ddd;padding:2px;background:#fff;"></div>@endif
                        </div>
                    @endforeach
                </div><small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i>Optional — you may also print the generated PDF and sign it physically.</small></div>
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
window.npsAddRow = function(){ var b=document.getElementById('npsBody'); if(!b) return; var r=b.querySelector('.nps-row').cloneNode(true); r.querySelectorAll('input').forEach(function(e){e.value='';}); b.appendChild(r); npsRenum(); };
window.npsRemoveRow = function(btn){ var b=document.getElementById('npsBody'); if(b.querySelectorAll('.nps-row').length>1) btn.closest('.nps-row').remove(); else btn.closest('.nps-row').querySelectorAll('input').forEach(function(e){e.value='';}); npsRenum(); };
function npsRenum(){ document.querySelectorAll('#npsBody .nps-row').forEach(function(r,i){ r.querySelector('td').textContent=i+1; }); }
</script>
@endpush
@endsection
