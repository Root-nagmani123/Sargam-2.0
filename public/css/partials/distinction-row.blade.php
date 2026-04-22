{{-- distinction-row.blade.php --}}
<div class="dynamic-row border rounded p-3 mb-2 bg-light position-relative">
    <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="removeRow(this)"></button>
    <div class="row g-2">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Type <span class="text-danger">*</span></label>
            <input type="text" name="distinctions[{{ $i }}][distinction_type]" class="form-control form-control-sm"
                   value="{{ $d?->distinction_type }}" placeholder="Gold Medal, Rank, Prize…" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Description</label>
            <input type="text" name="distinctions[{{ $i }}][description]" class="form-control form-control-sm"
                   value="{{ $d?->description }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Awarding Body</label>
            <input type="text" name="distinctions[{{ $i }}][awarding_body]" class="form-control form-control-sm"
                   value="{{ $d?->awarding_body }}">
        </div>
        <div class="col-md-1">
            <label class="form-label small fw-semibold">Year</label>
            <input type="text" name="distinctions[{{ $i }}][year]" class="form-control form-control-sm"
                   maxlength="4" value="{{ $d?->year }}" placeholder="YYYY">
        </div>
    </div>
</div>
