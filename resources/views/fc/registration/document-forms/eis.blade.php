@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php
    $val = fn ($name) => old($name, $data[$name] ?? '');
    $splitHeading = function ($h) {
        $parts = array_map('trim', explode('/', $h, 2));
        return [$parts[0] ?? $h, $parts[1] ?? ''];
    };
@endphp

@push('styles')
<style>
    .eis-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px;
        box-shadow:0 6px 22px rgba(0,40,90,.06); padding:1.8rem 1.9rem; margin-bottom:1.25rem; }
    @media (max-width:575.98px){ .eis-paper{ padding:1rem .7rem; } }
    .eis-head{ text-align:center; margin-bottom:.6rem; }
    .eis-head__title{ font-family:'Times New Roman', Georgia, serif; font-weight:700; font-size:1.2rem; text-decoration:underline; margin:0; }
    .eis-head__sub{ font-size:.8rem; color:#444; margin:.25rem 0 0; font-weight:600; }
    .eis-head__hi{ font-family:'Times New Roman', Georgia, serif; font-weight:700; font-size:1.02rem; margin:.3rem 0 0; }
    .eis-formtag{ text-align:right; font-size:.78rem; font-weight:700; color:#444; margin-bottom:.2rem; }
    .eis-ddo{ display:flex; gap:1.5rem; font-size:.82rem; font-weight:600; margin:.4rem 0 .8rem; }
    .eis-ddo span{ flex:1; border-bottom:1px solid #94a3b8; padding:.1rem .3rem; }
    table.eis{ width:100%; border-collapse:collapse; }
    table.eis th, table.eis td{ border:1px solid #6b7280; padding:5px 7px; font-size:.82rem; vertical-align:middle; }
    table.eis thead th{ background:#eef3fb; color:var(--fc-navy); text-align:center; font-weight:700; }
    .eis-sec{ width:34px; background:#f4f8fd; text-align:center; padding:2px !important; }
    .eis-sec > span{ writing-mode:vertical-rl; transform:rotate(180deg); display:inline-block;
        font-weight:700; font-size:.74rem; color:var(--fc-navy); letter-spacing:.3px; white-space:nowrap; }
    .eis-sec small{ display:block; font-weight:600; color:#5a6b85; }
    .eis-sno{ width:42px; text-align:center; color:#555; }
    .eis-part{ width:44%; }
    .eis-part small{ color:#64748b; }
    /* NPS-style boxed "comb" input (one character per cell). */
    .eis-comb{ width:100%; height:26px; border:1px solid #64748b; padding-left:6px; color:#0b3d91;
        font:700 13px 'Courier New', monospace; letter-spacing:7px; background-color:#fff;
        background-image:linear-gradient(90deg,#cbd5e1 1px,transparent 1px); background-size:14px 100%; background-position:6px 0; }
    .eis-comb:focus{ outline:none; border-color:var(--fc-blue); box-shadow:0 0 0 2px rgba(0,74,147,.12); }
    .eis-plain{ width:100%; height:26px; border:1px solid #64748b; padding:0 6px; color:#0b3d91; font-weight:600; font-size:.82rem; }
    .eis-plain:focus{ outline:none; border-color:var(--fc-blue); box-shadow:0 0 0 2px rgba(0,74,147,.12); }
    .eis-ticks{ display:flex; flex-wrap:wrap; gap:.1rem .9rem; }
    .eis-tick{ font-size:.82rem; display:inline-flex; align-items:center; gap:.25rem; }
    .eis-tick input{ transform:scale(1.02); }
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

        <div class="eis-paper">
            <div class="eis-formtag">Form: EIS/B/1</div>
            <div class="eis-head">
                <h2 class="eis-head__title">{{ $template['title'] }}</h2>
                @if(! empty($template['subtitle']))<div class="eis-head__sub">{{ $template['subtitle'] }}</div>@endif
                @if(! empty($template['title_hi']))<div class="eis-head__hi">{{ $template['title_hi'] }}</div>@endif
            </div>
            <div class="eis-ddo">
                <span>DDO Code &amp; Name / डीडीओ कोड एवं नाम:</span>
                <span>Date / दिनांक:</span>
            </div>

            @php $sno = 0; @endphp
            <table class="eis">
                <thead>
                    <tr>
                        <th style="width:34px;">A</th>
                        <th class="eis-sno">SNo</th>
                        <th class="eis-part">Particulars / विवरण</th>
                        <th>Details / ब्योरा</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($template['sections'] as $section)
                        @php [$secEn, $secHi] = $splitHeading($section['heading']); $rows = $section['fields']; @endphp
                        @foreach($rows as $idx => $f)
                            @php $sno++; $type = $f['type'] ?? 'text'; $v = $val($f['name']); @endphp
                            <tr>
                                @if($idx === 0)
                                    <td class="eis-sec" rowspan="{{ count($rows) }}">
                                        <span>{!! $secEn !!}@if($secHi)<small>{!! $secHi !!}</small>@endif</span>
                                    </td>
                                @endif
                                <td class="eis-sno">{{ $sno }}</td>
                                <td class="eis-part">{!! $f['label'] !!}</td>
                                <td>
                                    @if($type === 'select')
                                        <div class="eis-ticks">
                                            @foreach($f['options'] ?? [] as $opt)
                                                <label class="eis-tick"><input type="radio" name="{{ $f['name'] }}" value="{{ $opt }}" {{ (string)$v===(string)$opt?'checked':'' }}> {{ $opt }}</label>
                                            @endforeach
                                        </div>
                                    @elseif($type === 'date')
                                        <input type="date" name="{{ $f['name'] }}" value="{{ $v }}" class="eis-plain">
                                    @elseif($type === 'email')
                                        <input type="email" name="{{ $f['name'] }}" value="{{ $v }}" class="eis-plain">
                                    @elseif($type === 'textarea')
                                        <input type="text" name="{{ $f['name'] }}" value="{{ $v }}" class="eis-comb">
                                    @else
                                        <input type="text" name="{{ $f['name'] }}" value="{{ $v }}" class="eis-comb">
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach

                    {{-- Place & Date footer --}}
                    @foreach($template['sections_footer'] ?? [] as $section)
                        @foreach($section['fields'] as $idx => $f)
                            @php $sno++; $type = $f['type'] ?? 'text'; $v = $val($f['name']); @endphp
                            <tr>
                                @if($idx === 0)
                                    <td class="eis-sec" rowspan="{{ count($section['fields']) }}">
                                        <span>Place &amp; Date<small>स्थान व तिथि</small></span>
                                    </td>
                                @endif
                                <td class="eis-sno">{{ $sno }}</td>
                                <td class="eis-part">{!! $f['label'] !!}</td>
                                <td>
                                    @if($type === 'date')
                                        <input type="date" name="{{ $f['name'] }}" value="{{ $v }}" class="eis-plain">
                                    @else
                                        <input type="text" name="{{ $f['name'] }}" value="{{ $v }}" class="eis-comb">
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Signature upload (unchanged behaviour) --}}
        @if(! empty($template['signatures']))
            <div class="card fc-card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 text-uppercase small fw-bold text-muted">Signatures / हस्ताक्षर</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($template['signatures'] as $i => $sig)
                            @php $existingSig = $data['_signatures'][$i] ?? null; @endphp
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">{!! $sig !!}</label>
                                <input type="file" name="signature[{{ $i }}]"
                                       class="form-control form-control-sm @error('signature.'.$i) is-invalid @enderror"
                                       accept="image/*">
                                @error('signature.'.$i)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                @if($existingSig)
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/'.$existingSig) }}" alt="signature"
                                             style="max-height:48px;border:1px solid #ddd;padding:2px;background:#fff;">
                                        <small class="text-muted d-block">Uploaded — choose a file to replace it.</small>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="bi bi-info-circle me-1"></i>Upload a scanned/photographed signature image (PNG/JPG, max 2&nbsp;MB). Optional — you may also print the generated PDF and sign it physically.
                    </small>
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-between mb-5">
            <a href="{{ route('fc-reg.forms.step', [$form, $step]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-circle me-1"></i>Save &amp; Generate PDF
            </button>
        </div>
    </form>
</div>
</div>
@endsection
