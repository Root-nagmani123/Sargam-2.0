@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php
    $val = fn ($name) => old($name, $data[$name] ?? '');
@endphp

@push('styles')
<style>
    /* Government document-faithful, fill-in-the-blank layout (visual only). */
    .oath-paper{ background:#fff; border:1px solid var(--fc-line); border-radius:14px;
        box-shadow:0 6px 22px rgba(0,40,90,.06); padding:2.4rem 2.6rem; margin-bottom:1.25rem; }
    @media (max-width:575.98px){ .oath-paper{ padding:1.6rem 1.1rem; } }
    .oath-doc{ font-family:'Times New Roman', Georgia, serif; color:#111; max-width:820px; margin:0 auto; }
    .oath-doc + .oath-doc{ border-top:2px dashed #cbd5e1; margin-top:2.4rem; padding-top:2.4rem; }
    .oath-title{ text-align:center; font-weight:700; font-size:1.15rem; text-decoration:underline;
        letter-spacing:.3px; margin:0; }
    .oath-subtitle{ text-align:center; font-weight:700; font-size:.8rem; margin:.35rem 0 0; }
    .oath-body{ font-size:1.02rem; line-height:2.15; text-align:justify; margin:1.9rem 0 0; }
    .oath-hindi .oath-body{ line-height:2.3; }
    .oath-god{ text-align:center; font-weight:600; margin:1.4rem 0 0; }
    .oath-lines{ margin:1.4rem 0 0; }
    .oath-line{ display:flex; align-items:flex-end; gap:.5rem; margin:.55rem 0; }
    .oath-line__lbl{ flex:0 0 auto; font-weight:600; min-width:118px; }
    .oath-hindi .oath-line__lbl{ min-width:90px; }
    .oath-note{ font-size:.92rem; margin:1.6rem 0 0; }
    .oath-accepted{ text-align:center; font-weight:700; text-decoration:underline; margin:1.6rem 0 .2rem; }
    .oath-place{ margin:1.5rem 0 0; }
    /* Inline fillable blank — looks like a ruled blank, types like an input. */
    .blank{ border:0; border-bottom:1px dotted #64748b; background:transparent; font-family:inherit;
        font-size:inherit; padding:0 .35rem 1px; color:#0b3d91; font-weight:600; outline:none; min-width:60px; }
    .blank:focus{ border-bottom-color:var(--fc-blue); background:#eef6ff; }
    .blank--name{ min-width:280px; }
    .blank--flex{ flex:1 1 auto; }
    .blank--sm{ min-width:150px; }
    .oath-hint{ display:block; font-size:.72rem; color:#7a8699; font-weight:400; }
    .lang-tag{ display:inline-block; font-size:.68rem; font-weight:700; letter-spacing:.6px;
        color:#64748b; border:1px solid var(--fc-line); border-radius:20px; padding:.1rem .6rem;
        margin-bottom:.4rem; text-transform:uppercase; }
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
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('fc-reg.forms.doc-form.save', [$form, $step, $field->field_name]) }}" enctype="multipart/form-data">
        @csrf

        <div class="oath-paper">
            {{-- ─────────────── ENGLISH ─────────────── --}}
            <div class="oath-doc" lang="en">
                <span class="lang-tag">English</span>
                <h2 class="oath-title">FORM OF OATH/AFFIRMATION</h2>
                <p class="oath-subtitle">[MHA OM No. 31/3/65-Estt.(A) dated 23-3-1964- as amended from time to time]</p>

                <p class="oath-body">
                    &ldquo;I,
                    <input type="text" name="officer_name" class="blank blank--name" required
                           value="{{ $val('officer_name') }}" data-mirror="name"
                           autocomplete="off" placeholder="&nbsp;">
                    (Name of the Probationer) do swear/solemnly affirm that I will be
                    faithful and bear true allegiance to India and to the Constitution of India as by
                    law established, that I will uphold the sovereignty and integrity of India, and that
                    I will carry out the duties of my office loyally, honestly, and with impartiality.
                </p>
                <p class="oath-god">(SO HELP ME GOD)&rdquo;</p>

                <div class="oath-lines">
                    <div class="oath-line">
                        <span class="oath-line__lbl">SIGNATURE</span>
                        <span class="blank blank--flex">&nbsp;</span>
                    </div>
                    <div class="oath-line">
                        <span class="oath-line__lbl">NAME</span>
                        <span class="blank blank--flex" data-mirror-out="name">&nbsp;</span>
                    </div>
                    <div class="oath-line" style="justify-content:flex-end;">
                        <em>(In capital letters)</em>
                    </div>
                    <div class="oath-line">
                        <span class="oath-line__lbl">SERVICE</span>
                        <input type="text" name="service" class="blank blank--flex"
                               value="{{ $val('service') }}" data-mirror="service"
                               autocomplete="off" placeholder="&nbsp;">
                    </div>
                </div>

                <p class="oath-place">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
                <div class="oath-line">
                    <span class="oath-line__lbl" style="min-width:60px;">Dated</span>
                    <input type="date" name="declaration_date" class="blank blank--sm"
                           value="{{ $val('declaration_date') }}" data-mirror="date">
                </div>

                <p class="oath-note">(Conscientious objectors of Oath-taking may make a solemn affirmation in the form indicated above).</p>
                <p class="oath-accepted">ACCEPTED</p>
            </div>

            {{-- ─────────────── HINDI (candidate types their own Hindi; name mirrors within Hindi) ─────────────── --}}
            <div class="oath-doc oath-hindi" lang="hi">
                <span class="lang-tag">हिन्दी</span>
                <div style="font-size:.72rem; color:#64748b; font-style:italic; margin:.15rem 0 .6rem;">हिन्दी विवरण यहाँ भरें (वैकल्पिक) — या रिक्त छोड़कर मुद्रित प्रति पर हाथ से भरें। / Enter the Hindi details here (optional), or leave blank to hand-fill on the print.</div>
                <h2 class="oath-title">शपथ / पुष्टि प्रपत्र</h2>

                <p class="oath-body">
                    &ldquo;मैं
                    <input type="text" name="hi[name]" class="blank blank--name" value="{{ $data['_hi']['name'] ?? '' }}" data-mirror="hi_name" autocomplete="off">
                    (परिवीक्षाधीन का नाम) शपथ लेता/लेती हूँ/सत्यनिष्ठा से प्रतिज्ञा करता/करती हूँ कि मैं भारत,
                    तथा विधि द्वारा यथास्थापित भारत के संविधान के प्रति वफादार एवं सत्यनिष्ठ रहूँगा/रहूँगी, मैं
                    भारत की प्रभुसत्ता एवं अखण्डता बनाये रखूँगा/रखूँगी तथा अपने पद के कर्तव्यों को निष्ठा,
                    ईमानदारी एवं निष्पक्षता के साथ निभाऊँगा/निभाऊँगी।
                </p>
                <p class="oath-god">(भगवान मेरी सहायता करे)&rdquo;</p>

                <div class="oath-lines">
                    <div class="oath-line">
                        <span class="oath-line__lbl">हस्ताक्षर</span>
                        <span class="blank blank--flex">&nbsp;</span>
                    </div>
                    <div class="oath-line">
                        <span class="oath-line__lbl">नाम</span>
                        <span class="blank blank--flex" data-mirror-out="hi_name">&nbsp;</span>
                    </div>
                    <div class="oath-line">
                        <span class="oath-line__lbl">सेवा</span>
                        <input type="text" name="hi[service]" class="blank blank--flex" value="{{ $data['_hi']['service'] ?? '' }}" autocomplete="off">
                    </div>
                </div>

                <p class="oath-place">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी</p>
                <div class="oath-line">
                    <span class="oath-line__lbl" style="min-width:60px;">दिनांक</span>
                    <input type="text" name="hi[date]" class="blank blank--sm" value="{{ $data['_hi']['date'] ?? '' }}" autocomplete="off">
                </div>

                <p class="oath-note">(शपथ ग्रहण के लिए नैतिक आपत्तिकर्ता उपर्युक्त प्रपत्र में सत्यनिष्ठ बनने हेतु प्रतिज्ञा करें)</p>
                <p class="oath-accepted">स्वीकृत</p>
            </div>
        </div>

        {{-- Signature upload (optional — same behaviour as every other doc form) --}}
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

@push('scripts')
<script>
(function () {
    // Live-mirror each typed value into every read-only blank (Hindi block + NAME line).
    function fmtDate(v) {
        if (!v) return '';
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v);
        return m ? (m[3] + '-' + m[2] + '-' + m[1]) : v;
    }
    function sync(el) {
        var key = el.getAttribute('data-mirror');
        var raw = el.value || '';
        var text = (el.type === 'date') ? fmtDate(raw) : raw;
        document.querySelectorAll('[data-mirror-out="' + key + '"]').forEach(function (out) {
            out.textContent = text || ' ';
        });
    }
    document.querySelectorAll('[data-mirror]').forEach(function (el) {
        el.addEventListener('input', function () { sync(el); });
        el.addEventListener('change', function () { sync(el); });
        sync(el); // initialise from saved/old values
    });
})();
</script>
@endpush
@endsection
