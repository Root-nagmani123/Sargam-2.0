<div class="row g-3 mw-step-grid">
    @php
        $userRoleOptions = App\Models\UserRoleMaster::getUserRoleList();
    @endphp
    <div class="col-12">
        <div class="mb-3 mw-role-field">
            <label class="form-label" for="role">Role Option <span class="text-danger">*</span></label>
            <div class="controls">
                <div class="mw-role-grid">
                    @foreach ($userRoleOptions as $key => $value)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="userrole[]" id="userrole{{ $key }}" value="{{ $key }}" {{ in_array($key, old('userrole', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="userrole{{ $key }}">{{ $value }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
