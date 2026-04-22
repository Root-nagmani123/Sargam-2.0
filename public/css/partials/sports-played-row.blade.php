{{-- sports-played-row.blade.php --}}
<div class="dynamic-row border rounded p-2 mb-2 bg-light position-relative">
    <button type="button" class="btn-close position-absolute top-0 end-0 m-1" onclick="removeRow(this)"></button>
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Sport <span class="text-danger">*</span></label>
            <select name="sports_played[{{ $i }}][sport_id]" class="form-select form-select-sm" required>
                <option value="">Select sport…</option>
                @foreach($sportsMasters as $sm)
                    <option value="{{ $sm->id }}" {{ ($sp?->sport_id == $sm->id) ? 'selected' : '' }}>{{ $sm->sport_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Level</label>
            <select name="sports_played[{{ $i }}][level]" class="form-select form-select-sm">
                <option value="">Select…</option>
                @foreach(['National','State','District','University','School'] as $lv)
                    <option value="{{ $lv }}" {{ ($sp?->level == $lv) ? 'selected' : '' }}>{{ $lv }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Role</label>
            <select name="sports_played[{{ $i }}][role]" class="form-select form-select-sm">
                <option value="">Select…</option>
                @foreach(['Player','Captain','Coach','Manager'] as $rl)
                    <option value="{{ $rl }}" {{ ($sp?->role == $rl) ? 'selected' : '' }}>{{ $rl }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Year</label>
            <input type="text" name="sports_played[{{ $i }}][year]" class="form-control form-control-sm"
                   maxlength="4" placeholder="YYYY" value="{{ $sp?->year }}">
        </div>
    </div>
</div>
