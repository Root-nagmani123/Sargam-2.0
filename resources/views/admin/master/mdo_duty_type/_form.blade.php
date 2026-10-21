<form action="{{ route('master.mdo_duty_type.store') }}" method="POST" id="dutyTypeForm">
    @csrf
    @if(!empty($mdoDutyType))
        <input type="hidden" name="id" value="{{ encrypt($mdoDutyType->pk) }}">
    @endif
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
    <div class="d-flex justify-content-end gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="material-icons menu-icon">save</i> {{ !empty($mdoDutyType) ? 'Update' : 'Save' }}
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="material-icons menu-icon">close</i> Close
        </button>
    </div>
</form>