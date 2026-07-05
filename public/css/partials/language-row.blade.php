{{-- resources/views/partials/language-row.blade.php --}}
<div class="dynamic-row border rounded p-2 mb-2 bg-light position-relative">
    <button type="button" class="btn-close position-absolute top-0 end-0 m-1" onclick="removeRow(this)"></button>
    <div class="row g-2 align-items-center">
        <div class="col-md-3">
            <select name="languages[{{ $i }}][language_id]" class="form-select form-select-sm" required>
                <option value="">Language…</option>
                @foreach($languageMasters as $lm)
                    <option value="{{ $lm->id }}" {{ ($l?->language_id == $lm->id) ? 'selected' : '' }}>{{ $lm->language_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="languages[{{ $i }}][proficiency]" class="form-select form-select-sm">
                <option value="">Proficiency…</option>
                @foreach(['Basic','Intermediate','Fluent'] as $p)
                    <option value="{{ $p }}" {{ ($l?->proficiency == $p) ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5 d-flex gap-3">
            @foreach(['Read','Write','Speak'] as $skill)
                <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input"
                           name="languages[{{ $i }}][can_{{ strtolower($skill) }}]" value="1"
                           {{ ($l && $l->{'can_'.strtolower($skill)}) ? 'checked' : '' }}>
                    <label class="form-check-label small">{{ $skill }}</label>
                </div>
            @endforeach
        </div>
    </div>
</div>
