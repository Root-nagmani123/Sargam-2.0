{{-- employment-row.blade.php --}}
<div class="dynamic-row border rounded p-3 mb-2 bg-light position-relative">
    <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="removeRow(this)"></button>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Organisation Name <span class="text-danger">*</span></label>
            <input type="text" name="employments[{{ $i }}][organisation_name]" class="form-control form-control-sm"
                   value="{{ $e?->organisation_name }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Designation <span class="text-danger">*</span></label>
            <input type="text" name="employments[{{ $i }}][designation]" class="form-control form-control-sm"
                   value="{{ $e?->designation }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Job Type</label>
            <select name="employments[{{ $i }}][job_type_id]" class="form-select form-select-sm">
                <option value="">Type…</option>
                @foreach($jobTypes as $jt)
                    <option value="{{ $jt->id }}" {{ ($e?->job_type_id == $jt->id) ? 'selected' : '' }}>{{ $jt->job_type_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1">
            <label class="form-label small fw-semibold">From <span class="text-danger">*</span></label>
            <input type="date" name="employments[{{ $i }}][from_date]" class="form-control form-control-sm"
                   value="{{ $e?->from_date?->format('Y-m-d') }}" required>
        </div>
        <div class="col-md-1">
            <label class="form-label small fw-semibold">To</label>
            <input type="date" name="employments[{{ $i }}][to_date]" class="form-control form-control-sm"
                   value="{{ $e?->to_date?->format('Y-m-d') }}">
        </div>
        <div class="col-md-1 d-flex align-items-end pb-1">
            <div class="form-check">
                <input type="checkbox" name="employments[{{ $i }}][is_current]" value="1" class="form-check-input"
                       {{ $e?->is_current ? 'checked' : '' }}>
                <label class="form-check-label small">Current</label>
            </div>
        </div>
    </div>
</div>
