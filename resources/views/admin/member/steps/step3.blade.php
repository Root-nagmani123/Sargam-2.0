<div class="row">
    {{-- <div class="col-md-12">
        <div class="mb-3">

            @php
                $userRoleOptions = [
                    
                ];
            @endphp

            <x-select name="userrole" label="User Role :" :options="$userRoleOptions" :value="old('userrole')" formLabelClass="form-label" formSelectClass="form-select" />

            
        </div>
    </div> --}}
    @php
        $userRoleOptions = App\Models\UserRoleMaster::getUserRoleList();

    @endphp
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label" for="role">Role Options :</label>
            <div class="controls">
                <div class="row">
                    @foreach ($userRoleOptions as $key => $value)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="userrole[]" id="userrole{{ $key }}" value="{{ $key }}" {{ in_array($key, old('userrole', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="userrole{{ $key }}">{{ $value }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>