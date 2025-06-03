<div class="row">
    <div class="col-md-12">
        <div class="mb-3">

            @php
                $userRoleOptions = [
                    
                ];
            @endphp

            <x-select name="userrole" label="User Role :" :options="$userRoleOptions" :value="old('userrole')" formLabelClass="form-label" formSelectClass="form-select" />

            
        </div>
    </div>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label" for="role">Role Options :</label>
            <div class="controls">
                <div class="row">
                    <div class="col-md-4">

                        @php
                            $checkboxOptions = [
                                'Academy Staff',
                                'Academy Staff1',
                                'Academy Staff2',
                            ];
                        @endphp

                        <x-checkbox name="styled_max_checkbox[]" id="customCheck4" :options="$checkboxOptions" />

                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>