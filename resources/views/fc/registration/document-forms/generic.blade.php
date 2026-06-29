@extends('fc.layouts.master')
@section('title', $template['title'] . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
@php
    // Build current values, preferring old() input (after a validation error) then saved data.
    $val = function ($name) use ($data) {
        return old($name, $data[$name] ?? '');
    };
    // Rows for a repeatable table: prefer old() input, else saved rows, else one blank row.
    $tableRows = function ($tableKey, $cols) use ($data) {
        $old = old($tableKey);
        if (is_array($old)) {
            $cnt = 0;
            foreach ($cols as $c) { $cnt = max($cnt, is_array($old[$c] ?? null) ? count($old[$c]) : 0); }
            $rows = [];
            for ($i = 0; $i < $cnt; $i++) {
                $r = [];
                foreach ($cols as $c) { $r[$c] = $old[$c][$i] ?? ''; }
                $rows[] = $r;
            }
            return $rows ?: [array_fill_keys($cols, '')];
        }
        $saved = $data['_tables'][$tableKey] ?? [];
        return $saved ?: [array_fill_keys($cols, '')];
    };
@endphp
<div class="fc-form-page">
<div class="fc-shell">
    <div class="fc-band">
        <div class="fc-band__row">
            <div class="fc-band__ico"><i class="bi bi-pencil-square"></i></div>
            <div>
                <h4>{{ $template['title'] }}</h4>
                <p>{{ $template['subtitle'] ?? ($template['title_hi'] ?? '') }}</p>
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

        {{-- Optional intro / preamble text --}}
        @if(! empty($template['intro']))
            <div class="alert alert-light border small mb-3">{!! $template['intro'] !!}</div>
        @endif

        {{-- Header sections --}}
        @foreach($template['sections'] ?? [] as $section)
            <div class="card fc-card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 text-uppercase small fw-bold text-muted">{!! $section['heading'] !!}</h6>
                </div>
                <div class="card-body">
                    @if(! empty($section['intro']))<p class="small text-muted mb-3">{!! $section['intro'] !!}</p>@endif
                    <div class="row g-3">
                        @foreach($section['fields'] as $f)
                            @include('fc.registration.document-forms._field', ['f' => $f, 'value' => $val($f['name'])])
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Repeatable tables --}}
        @foreach($template['tables'] ?? [] as $tbl)
            @php $cols = array_column($tbl['columns'], 'name'); $rows = $tableRows($tbl['key'], $cols); @endphp
            <div class="card fc-card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-uppercase small fw-bold text-muted">{!! $tbl['heading'] !!}</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="fcDocAddRow('{{ $tbl['key'] }}')">
                        <i class="bi bi-plus-circle me-1"></i>Add Row
                    </button>
                </div>
                <div class="card-body p-0">
                    @if(! empty($tbl['intro']))<div class="px-3 py-2 small text-muted border-bottom">{!! $tbl['intro'] !!}</div>@endif
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width:46px;">#</th>
                                    @foreach($tbl['columns'] as $c)<th>{!! $c['label'] !!}</th>@endforeach
                                    <th style="width:54px;">—</th>
                                </tr>
                            </thead>
                            <tbody id="tbl-{{ $tbl['key'] }}-body">
                                @foreach($rows as $row)
                                    <tr class="fc-repeat-row">
                                        <td class="text-center fc-row-no"></td>
                                        @foreach($tbl['columns'] as $c)
                                            @php $cv = $row[$c['name']] ?? ''; $ct = $c['type'] ?? 'text'; @endphp
                                            <td>
                                                @if($ct === 'select')
                                                    <select name="{{ $tbl['key'] }}[{{ $c['name'] }}][]" class="form-select form-select-sm">
                                                        <option value="">—</option>
                                                        @foreach($c['options'] ?? [] as $opt)
                                                            <option value="{{ $opt }}" {{ (string) $cv === (string) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input type="{{ in_array($ct, ['date','number','email']) ? $ct : 'text' }}"
                                                           name="{{ $tbl['key'] }}[{{ $c['name'] }}][]"
                                                           class="form-control form-control-sm" value="{{ $cv }}">
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="fcDocRemoveRow(this,'{{ $tbl['key'] }}')"><i class="bi bi-x-lg"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Declaration + footer fields --}}
        @if(! empty($template['declaration']) || ! empty($template['sections_footer']))
            <div class="card fc-card border-0 shadow-sm mb-3">
                <div class="card-body">
                    @if(! empty($template['declaration']))
                        <div class="alert alert-light border small mb-3">
                            <strong>Declaration / घोषणा:</strong><br>{!! $template['declaration'] !!}
                        </div>
                    @endif
                    @foreach($template['sections_footer'] ?? [] as $section)
                        <div class="row g-3">
                            @foreach($section['fields'] as $f)
                                @include('fc.registration.document-forms._field', ['f' => $f, 'value' => $val($f['name'])])
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Signature uploads --}}
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

        {{-- Optional footnotes / instructions --}}
        @if(! empty($template['notes']))
            <div class="card fc-card border-0 shadow-sm mb-3">
                <div class="card-body small text-muted">
                    <strong>Notes / नोट:</strong>
                    <ol class="mb-0 ps-3 mt-1">@foreach($template['notes'] as $n)<li>{!! $n !!}</li>@endforeach</ol>
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
function fcDocRenumber(key) {
    document.querySelectorAll('#tbl-' + key + '-body .fc-repeat-row').forEach(function (r, i) {
        var c = r.querySelector('.fc-row-no'); if (c) c.textContent = i + 1;
    });
}
function fcDocAddRow(key) {
    var body = document.getElementById('tbl-' + key + '-body');
    var tpl = body.querySelector('.fc-repeat-row');
    var row = tpl.cloneNode(true);
    row.querySelectorAll('input,select,textarea').forEach(function (e) {
        if (e.tagName === 'SELECT') { e.selectedIndex = 0; } else { e.value = ''; }
    });
    body.appendChild(row);
    fcDocRenumber(key);
}
function fcDocRemoveRow(btn, key) {
    var body = document.getElementById('tbl-' + key + '-body');
    var rows = body.querySelectorAll('.fc-repeat-row');
    if (rows.length > 1) {
        btn.closest('.fc-repeat-row').remove();
    } else {
        btn.closest('.fc-repeat-row').querySelectorAll('input,select,textarea').forEach(function (e) {
            if (e.tagName === 'SELECT') { e.selectedIndex = 0; } else { e.value = ''; }
        });
    }
    fcDocRenumber(key);
}
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[id^="tbl-"][id$="-body"]').forEach(function (b) {
        fcDocRenumber(b.id.replace(/^tbl-/, '').replace(/-body$/, ''));
    });
});
</script>
@endpush
@endsection
