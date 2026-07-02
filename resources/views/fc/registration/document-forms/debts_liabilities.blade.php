@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php
    $val = fn ($name) => old($name, $data[$name] ?? '');
    $tbl  = $template['tables'][0];
    $cols = array_column($tbl['columns'], 'name');
    $oldRows = old($tbl['key']);
    if (is_array($oldRows)) {
        $cnt = 0; foreach ($cols as $c) { $cnt = max($cnt, is_array($oldRows[$c] ?? null) ? count($oldRows[$c]) : 0); }
        $rows = []; for ($i=0;$i<max($cnt,1);$i++){ $r=[]; foreach($cols as $c){ $r[$c]=$oldRows[$c][$i] ?? ''; } $rows[]=$r; }
    } else { $rows = $data['_tables'][$tbl['key']] ?? []; if(!$rows) $rows=[array_fill_keys($cols,'')]; }
    // Official 6-C notes (bilingual pairs).
    $notes = [
        ['Individual items of loans not exceeding three months\' emoluments or Rs. 1,000, whichever is less, need not be included.',
         'यदि एक ऋणराशि तीन महीनों की परिलब्धियों अथवा 1000 रुपए से अधिक न हो, तो उसे शामिल करने की आवश्यकता नहीं है।'],
        ['In Column 6, information regarding permission, if any, obtained from or report made to the competent authority may also be given.',
         'कॉलम 6 में, यदि सक्षम अधिकारी से अनुमति ली गई हो या उसकी सूचना दी गई हो, तो उसका विवरण भी दें।'],
        ['The term "emoluments" means pay and allowances received by the Government servant.',
         'परिलब्धियों का तात्पर्य सरकारी कर्मचारी द्वारा प्राप्त वेतन और भत्तों से है।'],
        ['The statement should also include various loans and advances available to Government servants, such as advance for purchase of conveyance, house building advance, etc. (other than advances of pay and travelling allowance, advances from the GP Fund, and loans on Life Insurance Policies and fixed deposits).',
         'विवरण में सरकारी कर्मचारियों को उपलब्ध विभिन्न ऋण, यथा आवागमन हेतु उपलब्ध अग्रिम, गृह निर्माण अग्रिम आदि (अन्य अग्रिमों, यथा यात्रा अग्रिम, भविष्य निधि अग्रिम तथा जीवन बीमा पालिसी और आवधिक जमा से लिए गए ऋणों के अतिरिक्त) को भी शामिल किया जाना चाहिए।'],
    ];
@endphp

@push('styles')
<style>
    .dc-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px; box-shadow:0 6px 22px rgba(0,40,90,.06);
        padding:2rem 2.2rem; margin-bottom:1.25rem; font-family:'Times New Roman', Georgia, serif; color:#111; }
    @media (max-width:575.98px){ .dc-paper{ padding:1.2rem .8rem; } }
    .dc-docno{ text-align:right; font-weight:700; font-size:.85rem; }
    .dc-title{ text-align:center; font-weight:700; font-size:1.12rem; text-decoration:underline; margin:.2rem 0 0; }
    .dc-form{ text-align:center; font-size:.82rem; color:#444; font-weight:600; margin-top:.2rem; }
    .dc-title-hi{ text-align:center; font-weight:700; font-size:1rem; margin:.35rem 0 0; }
    .dc-sub{ text-align:center; font-style:italic; font-size:.85rem; margin:.3rem 0 0; }
    .dc-ason{ text-align:center; font-size:1rem; margin:.9rem 0 0; }
    .dc-blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; font-family:inherit; font-size:inherit;
        padding:0 .35rem 1px; color:#0b3d91; font-weight:600; outline:none; min-width:120px; }
    .dc-blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    table.dc{ width:100%; border-collapse:collapse; margin:1.1rem 0; font-family:system-ui,sans-serif; }
    table.dc th, table.dc td{ border:1px solid #555; padding:5px 6px; font-size:.78rem; vertical-align:top; }
    table.dc th{ background:#eef3fb; text-align:center; font-weight:700; color:var(--fc-navy); }
    .dc-cno{ font-weight:400; color:#666; }
    .dc-in{ border:0; border-bottom:1px dotted #94a3b8; background:transparent; width:100%; padding:.1rem .2rem;
        color:#0b3d91; font-weight:600; font-size:.8rem; outline:none; font-family:system-ui,sans-serif; }
    .dc-in:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .dc-footer{ margin-top:1.4rem; font-size:1rem; line-height:2.1; }
    .dc-footer .lbl{ font-weight:600; }
    .dc-notes{ font-size:.82rem; margin-top:1.4rem; line-height:1.6; font-family:system-ui,sans-serif; }
    .dc-notes b{ color:#14315e; }
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

        <div class="dc-paper">
            <div class="dc-docno">Document-6-C</div>
            <div class="dc-title">Statement of Debts and Other Liabilities on First Appointment</div>
            <div class="dc-form">Form No. 6-C</div>
            <div class="dc-title-hi">प्रथम नियुक्ति पर ऋणों तथा अन्य देयताओं का विवरण</div>
            <div class="dc-sub">[debts and other liabilities incurred by him/her directly or indirectly]</div>
            <div class="dc-ason">
                as on date / जिस तिथि तक:
                <input type="date" name="as_on_date" class="dc-blank" value="{{ $val('as_on_date') }}" style="min-width:160px;">
            </div>

            <div class="d-flex justify-content-end mb-1">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="dcAddRow()"><i class="bi bi-plus-circle me-1"></i>Add Row</button>
            </div>
            <table class="dc">
                <thead>
                    <tr>
                        <th style="width:42px;">Sl.No.<div class="dc-cno">(1)</div></th>
                        <th>Amount / धनराशि<div class="dc-cno">(2)</div></th>
                        <th>Name and address of Creditor / ऋणदाता का नाम और पता<div class="dc-cno">(3)</div></th>
                        <th>Date of incurring Liability / दायित्व की तिथि<div class="dc-cno">(4)</div></th>
                        <th>Details of Transaction / लेन-देन का विवरण<div class="dc-cno">(5)</div></th>
                        <th>Remarks / टिप्पणी<div class="dc-cno">(6)</div></th>
                        <th style="width:34px;">—</th>
                    </tr>
                </thead>
                <tbody id="dcBody">
                    @foreach($rows as $ri => $row)
                        <tr class="dc-row">
                            <td class="text-center dc-sno">{{ $ri + 1 }}</td>
                            <td><input type="text" name="{{ $tbl['key'] }}[amount][]" class="dc-in" value="{{ $row['amount'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[creditor][]" class="dc-in" value="{{ $row['creditor'] ?? '' }}"></td>
                            <td><input type="date" name="{{ $tbl['key'] }}[date_incurred][]" class="dc-in" value="{{ $row['date_incurred'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[details][]" class="dc-in" value="{{ $row['details'] ?? '' }}"></td>
                            <td><input type="text" name="{{ $tbl['key'] }}[remarks][]" class="dc-in" value="{{ $row['remarks'] ?? '' }}"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="dcRemoveRow(this)"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="dc-footer">
                <div class="row">
                    <div class="col-md-6">Dated / दिनांक:
                        <input type="date" name="declaration_date" class="dc-blank" value="{{ $val('declaration_date') }}" style="min-width:150px;">
                    </div>
                    <div class="col-md-6">
                        <div>Signature / हस्ताक्षर: ______________________</div>
                        <div>Name (in Block Letters) / नाम (स्पष्ट अक्षरों में):
                            <input type="text" name="officer_name" class="dc-blank" required value="{{ $val('officer_name') }}" style="min-width:200px;"></div>
                        <div>Service / सेवा:
                            <input type="text" name="service" class="dc-blank" value="{{ $val('service') }}" style="min-width:200px;"></div>
                    </div>
                </div>
            </div>

            <div class="dc-notes">
                @foreach($notes as $i => $n)
                    <div class="mb-1"><b>NOTE {{ $i + 1 }}.</b> {{ $n[0] }} / {{ $n[1] }}</div>
                @endforeach
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
window.dcAddRow = function(){ var b=document.getElementById('dcBody'); if(!b) return; var r=b.querySelector('.dc-row').cloneNode(true); r.querySelectorAll('input').forEach(function(e){e.value='';}); b.appendChild(r); dcRenum(); };
window.dcRemoveRow = function(btn){ var b=document.getElementById('dcBody'); if(b.querySelectorAll('.dc-row').length>1) btn.closest('.dc-row').remove(); else btn.closest('.dc-row').querySelectorAll('input').forEach(function(e){e.value='';}); dcRenum(); };
function dcRenum(){ document.querySelectorAll('#dcBody .dc-row').forEach(function(r,i){ r.querySelector('.dc-sno').textContent=i+1; }); }
</script>
@endpush
@endsection
