<div class="row">
    {{-- <div class="col-md-12">
        <div class="mb-3">

            @php
            $userRoleOptions = [

            ];
            @endphp

            <x-select name="userrole" label="User Role :" :options="$userRoleOptions" :value="old('userrole')"
                formLabelClass="form-label" formSelectClass="form-select" />


        </div>
    </div> --}}
    @php
        $userRoleOptions = App\Models\UserRoleMaster::getUserRoleList();
    @endphp

    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label fw-bold fs-5" for="role">Role Options:</label>
            <div class="controls">
                <div class="row g-2">
                    @foreach ($userRoleOptions as $key => $value)
                        <div class="col-md-4">
                            <input type="checkbox" class="btn-check" id="userrole{{ $key }}" name="userrole[]"
                                value="{{ $key }}" {{ in_array($key, old('userrole', [])) ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary w-100 rounded-pill py-2" for="userrole{{ $key }}">
                                {{ $value }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>