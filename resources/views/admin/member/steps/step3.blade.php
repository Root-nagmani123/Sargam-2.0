@php
    $userRoleOptions = App\Models\UserRoleMaster::getUserRoleList();
    $selectedRoles = old('userrole', []);
@endphp
<div class="row">
    <div class="col-12">
        <div class="mb-3">
            <label class="form-label" for="role">Role Option <span class="text-danger">*</span></label>
            <div class="controls">
                <div class="mbrw-role-grid">
                    @foreach ($userRoleOptions as $key => $value)
                        <label class="mbrw-role-chip" for="userrole{{ $key }}">
                            <input class="form-check-input" type="checkbox" name="userrole[]"
                                id="userrole{{ $key }}" value="{{ $key }}"
                                {{ in_array($key, $selectedRoles) ? 'checked' : '' }}>
                            <span>{{ $value }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
