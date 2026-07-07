@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php
    $val = fn ($name) => old($name, $data[$name] ?? '');
    // Fixed relation rows (i–vii): [canonical value, bilingual label]
    $relations = [
        ['Father', 'पिता / Father'],
        ['Mother', 'माता / Mother'],
        ['Wife / Husband', 'पत्नी / पति / Wife / Husband'],
        ['Son(s)', 'पुत्र / Son(s)'],
        ['Daughter(s)', 'पुत्री / पुत्रियाँ / Daughter(s)'],
        ['Brother(s)', 'भाई / Brother(s)'],
        ['Sister(s)', 'बहिन / बहिनें / Sister(s)'],
    ];
    $rn = ['i','ii','iii','iv','v','vi','vii'];
    $fields = ['name','nationality','present_address','place_of_birth','occupation'];
    // Saved rows keyed by relation, per table.
    $lookup = function ($key) use ($data) {
        $m = [];
        foreach ($data['_tables'][$key] ?? [] as $r) { $m[$r['relation'] ?? ''] = $r; }
        return $m;
    };
    // Value for a cell: prefer old() (validation error), then saved (matched by relation), else ''.
    $cell = function ($key, $i, $col, $canonical) use ($lookup) {
        $old = old($key.'.'.$col.'.'.$i);
        if ($old !== null) return $old;
        return $lookup($key)[$canonical][$col] ?? '';
    };
@endphp

@push('styles')
<style>
    .cr-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px; box-shadow:0 6px 22px rgba(0,40,90,.06);
        padding:2rem 2.2rem; margin-bottom:1.25rem; font-family:'Times New Roman', Georgia, serif; color:#111; }
    @media (max-width:575.98px){ .cr-paper{ padding:1.2rem .8rem; } }
    .cr-docno{ text-align:right; font-weight:700; font-size:.85rem; }
    .cr-title-hi{ text-align:center; font-weight:700; font-size:1.05rem; margin:.2rem 0 0; }
    .cr-title{ text-align:center; font-weight:700; font-size:1.02rem; text-decoration:underline; margin:.15rem 0 0; }
    .cr-sub{ text-align:center; font-size:.82rem; color:#444; margin:.2rem 0 0; }
    .cr-secttl{ font-weight:700; color:#b02a37; font-size:.9rem; margin:1.2rem 0 .3rem; }
    .cr-secttl .en{ color:#111; }
    table.cr{ width:100%; border-collapse:collapse; font-family:system-ui,sans-serif; }
    table.cr th, table.cr td{ border:1px solid #555; padding:5px 6px; font-size:.76rem; vertical-align:middle; }
    table.cr th{ background:#eef3fb; text-align:center; font-weight:700; color:var(--fc-navy); }
    .cr-rn{ width:30px; text-align:center; color:#555; }
    .cr-rel{ width:16%; font-weight:600; }
    .cr-in{ border:0; border-bottom:1px dotted #94a3b8; background:transparent; width:100%; padding:.1rem .2rem;
        color:#0b3d91; font-weight:600; font-size:.8rem; outline:none; }
    .cr-in:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .cr-cert{ font-size:.95rem; margin-top:1.2rem; }
    .cr-sign{ margin-top:1.4rem; font-size:1rem; line-height:2.1; }
    .cr-notes{ font-size:.82rem; margin-top:1.2rem; line-height:1.55; font-family:system-ui,sans-serif; border-top:1px solid #d5deeb; padding-top:.8rem; }
    .cr-notes b{ color:#14315e; }
    .blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; color:#0b3d91; font-weight:600; padding:0 .3rem 1px; outline:none; min-width:130px; }
    .blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
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

        <div class="cr-paper">
            <div class="cr-docno">Document-2</div>
            <div class="cr-title-hi">सरकारी कर्मचारी द्वारा प्रथम नियुक्ति पर भरा जाने वाला फार्म</div>
            <div class="cr-title">FORM TO BE FILLED BY GOVERNMENT EMPLOYEES ON FIRST APPOINTMENT</div>
            <div class="cr-sub">[MHA OM No. F.3/12(S)/64-Ests.(B), dated 12-10-1965]</div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">Name / नाम <span class="text-danger">*</span>:
                    <input type="text" name="officer_name" class="blank" required value="{{ $val('officer_name') }}" style="min-width:220px;"></div>
            </div>

            @foreach($template['tables'] as $ti => $tbl)
                @php [$secHi, $secEn] = array_pad(array_map('trim', explode('/', preg_replace('/^[A-B]\.\s*/','',$tbl['heading']), 2)), 2, ''); @endphp
                <div class="cr-secttl">
                    {{ $ti + 1 }}.
                    @if($ti === 0)
                        विदेशों में निवास कर रहे या विदेशी राष्ट्रीयता-प्राप्त निकट संबंधी <span class="en">/ Close relations who are Nationals of, or are domiciled in, other countries</span>
                    @else
                        भारत में निवास कर रहे निकट संबंधी जो भारतीय मूल के नहीं हैं <span class="en">/ Close relations residing in India who are of non-Indian origin</span>
                    @endif
                </div>
                <table class="cr">
                    <thead>
                        <tr>
                            <th class="cr-rn">#</th>
                            <th class="cr-rel">संबंध / Relation</th>
                            <th>नाम / Name</th>
                            <th>राष्ट्रीयता / Nationality</th>
                            <th>वर्तमान पता / Present Address</th>
                            <th>जन्म स्थान / Place of Birth</th>
                            <th>व्यवसाय / Occupation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($relations as $i => $r)
                            <tr>
                                <td class="cr-rn">{{ $rn[$i] }}</td>
                                <td class="cr-rel">{{ $r[1] }}<input type="hidden" name="{{ $tbl['key'] }}[relation][{{ $i }}]" value="{{ $r[0] }}"></td>
                                @foreach($fields as $col)
                                    <td><input type="text" name="{{ $tbl['key'] }}[{{ $col }}][{{ $i }}]" class="cr-in" value="{{ $cell($tbl['key'], $i, $col, $r[0]) }}"></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach

            <div class="cr-cert">मैं प्रमाणित करता/करती हूँ कि जहाँ तक मेरी जानकारी और विश्वास है, पूर्वोक्त सूचना सही और पूर्ण है। I certify that the foregoing information is correct and complete to the best of my knowledge and belief.</div>

            <div class="cr-sign row">
                <div class="col-md-6">तारीख / Date:
                    <input type="date" name="declaration_date" class="blank" value="{{ $val('declaration_date') }}" style="min-width:150px;"></div>
                <div class="col-md-6 text-md-end">
                    <div>हस्ताक्षर / Signature: ______________________</div>
                    <div>पदनाम / Designation: <input type="text" name="designation" class="blank" value="{{ $val('designation') }}" style="min-width:200px;"></div>
                </div>
            </div>

            <div class="cr-notes">
                <div class="mb-1"><b>टिप्पणी / Note 1:</b> इस प्रपत्र में दी जाने वाली सूचना का छिपाया जाना विभागीय अपराध समझा जाएगा, जिसके लिए सेवा से बरखास्त किये जाने तक का दण्ड दिया जा सकता है। Suppression of information in this form will be considered a major departmental offence, for which the punishment may extend to dismissal from service.</div>
                <div><b>Note 2:</b> उपर्युक्त तारीख के बाद यदि कोई परिवर्तन होता है तो इसकी सूचना विभागाध्यक्ष / कार्यालयाध्यक्ष को प्रत्येक वर्ष के अंत में दें। Subsequent changes, if any, in the above particulars should be reported to the Head of Office / Department at the end of each year.</div>
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
@endsection
