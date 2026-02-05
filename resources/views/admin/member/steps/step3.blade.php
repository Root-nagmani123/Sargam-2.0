@php
    $userRoleOptions = App\Models\UserRoleMaster::getUserRoleList();
@endphp
<div class="row g-3">
    <div class="col-12">
        <label class="form-label fw-medium" for="role">Role Options <span class="text-danger">*</span></label>
        <p class="text-body-secondary small mb-2">Select one or more roles for this member.</p>
        <div class="controls">
            <div class="row g-2">
                @foreach ($userRoleOptions as $key => $value)
                    <div class="col-md-4 col-sm-6">
                        <div class="form-check border rounded-2 px-3 py-2 bg-body-secondary bg-opacity-25">
                            <input class="form-check-input" type="checkbox" name="userrole[]" id="userrole{{ $key }}" value="{{ $key }}" {{ in_array($key, old('userrole', [])) ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium" for="userrole{{ $key }}">{{ $value }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>