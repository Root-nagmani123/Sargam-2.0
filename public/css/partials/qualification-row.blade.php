{{-- resources/views/partials/qualification-row.blade.php --}}
<div class="dynamic-row border rounded p-3 mb-2 bg-light position-relative" style="border-radius:8px!important;">
    <button type="button" class="btn-close position-absolute top-0 end-0 m-2" onclick="removeRow(this)" title="Remove"></button>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Degree/Qualification <span class="text-danger">*</span></label>
            <select name="qualifications[{{ $i }}][qualification_id]" class="form-select form-select-sm" required>
                <option value="">Select…</option>
                @foreach($qualificationMasters as $qm)
                    <option value="{{ $qm->id }}" {{ ($q?->qualification_id == $qm->id) ? 'selected' : '' }}>
                        {{ $qm->qualification_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Degree Name / Specialisation <span class="text-danger">*</span></label>
            <input type="text" name="qualifications[{{ $i }}][degree_name]" class="form-control form-control-sm"
                   value="{{ $q?->degree_name }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Board / University <span class="text-danger">*</span></label>
            <select name="qualifications[{{ $i }}][board_id]" class="form-select form-select-sm">
                <option value="">Select board…</option>
                @foreach($boardMasters as $bm)
                    <option value="{{ $bm->id }}" {{ ($q?->board_id == $bm->id) ? 'selected' : '' }}>{{ $bm->board_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Institution Name <span class="text-danger">*</span></label>
            <input type="text" name="qualifications[{{ $i }}][institution_name]" class="form-control form-control-sm"
                   value="{{ $q?->institution_name }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Year of Passing <span class="text-danger">*</span></label>
            <input type="text" name="qualifications[{{ $i }}][year_of_passing]" class="form-control form-control-sm"
                   maxlength="4" placeholder="YYYY" value="{{ $q?->year_of_passing }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">% / CGPA <span class="text-danger">*</span></label>
            <input type="text" name="qualifications[{{ $i }}][percentage_cgpa]" class="form-control form-control-sm"
                   value="{{ $q?->percentage_cgpa }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Stream</label>
            <select name="qualifications[{{ $i }}][stream_id]" class="form-select form-select-sm">
                <option value="">Select…</option>
                @foreach($streamMasters as $sm)
                    <option value="{{ $sm->id }}" {{ ($q?->stream_id == $sm->id) ? 'selected' : '' }}>{{ $sm->stream_name }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
