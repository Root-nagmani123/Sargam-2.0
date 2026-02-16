<form action="{{ route('master.mdo_duty_type.store') }}" method="POST" id="dutyTypeForm">
    @csrf
    @if(!empty($mdoDutyType))
        <input type="hidden" name="id" value="{{ encrypt($mdoDutyType->pk) }}">
    @endif
    <div class="row">
        <div class="col-md-6">
        <div class="mb-3">
        <x-input 
            name="mdo_duty_type_name" 
            label="Name :" 
            placeholder="Enter Duty Type Name" 
            formLabelClass="form-label"
            required="true"
            value="{{ old('mdo_duty_type_name', $mdoDutyType->mdo_duty_type_name ?? '') }}"
        />
        @error('mdo_duty_type_name')
            <div class="text-danger small">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="col-md-6">
<div class="mb-3">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="active_inactive" class="form-select" required>
            <option value="">-- Select Status --</option>
            <option value="1" {{ old('active_inactive', $mdoDutyType->active_inactive ?? '') == 1 ? 'selected' : '' }}>Active</option>
            <option value="0" {{ old('active_inactive', $mdoDutyType->active_inactive ?? '') == 0 ? 'selected' : '' }}>Inactive</option>
        </select>
        @error('active_inactive')
            <div class="text-danger small">{{ $message }}</div>
        @enderror
    </div>
</div>
    </div>
    <div class="d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary">{{ !empty($mdoDutyType) ? 'Update' : 'Save' }}
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> Close
        </button>
    </div>
</form>