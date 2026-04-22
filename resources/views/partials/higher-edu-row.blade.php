{{-- higher-edu-row.blade.php --}}
{{-- Usage: @include('partials.higher-edu-row', ['h'=>$h, 'i'=>$i]) --}}
<div class="dynamic-row border rounded p-3 mb-2 bg-light position-relative">
    <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="removeRow(this)"></button>
    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Degree Type</label>
            <select name="higher_edus[{{ $i }}][degree_type]" class="form-select form-select-sm" required>
                <option value="">Select…</option>
                @foreach($degreeMasters ?? [] as $deg)
                    <option value="{{ $deg->pk }}" {{ (string) ($h?->degree_type ?? '') === (string) $deg->pk ? 'selected' : '' }}>{{ $deg->degree_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Subject / Specialisation</label>
            <input type="text" name="higher_edus[{{ $i }}][subject_name]" class="form-control form-control-sm"
                   value="{{ $h?->subject_name }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">University <span class="text-danger">*</span></label>
            <input type="text" name="higher_edus[{{ $i }}][university_name]" class="form-control form-control-sm"
                   value="{{ $h?->university_name }}" required>
        </div>
        <div class="col-md-1">
            <label class="form-label small fw-semibold">Year <span class="text-danger">*</span></label>
            <input type="text" name="higher_edus[{{ $i }}][year_of_passing]" class="form-control form-control-sm"
                   maxlength="4" value="{{ $h?->year_of_passing }}" required placeholder="YYYY">
        </div>
        <div class="col-md-1">
            <label class="form-label small fw-semibold">% / CGPA</label>
            <input type="text" name="higher_edus[{{ $i }}][percentage_cgpa]" class="form-control form-control-sm"
                   value="{{ $h?->percentage_cgpa }}">
        </div>
    </div>
</div>
