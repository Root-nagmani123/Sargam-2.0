{{-- AJAX partial (create()/edit() when request()->ajax()), loaded into the
     .mst-modal #dutyTypeModal body on the index page. store() requires
     active_inactive, so the Status field is part of the form. --}}
@php
    $mdoFormStatus = (string) old('active_inactive', $mdoDutyType->active_inactive ?? 1);
@endphp
<form action="{{ route('master.mdo_duty_type.store') }}" method="POST" id="dutyTypeForm">
    @csrf
    @if(!empty($mdoDutyType))
        <input type="hidden" name="id" value="{{ encrypt($mdoDutyType->pk) }}">
    @endif
    <div class="mst-field-card">
        <div class="mb-3">
            <label for="dutyTypeFormName" class="mst-form-label d-block">
                Duty Type Name <span class="mst-req" aria-hidden="true">*</span>
            </label>
            <input type="text" name="mdo_duty_type_name" id="dutyTypeFormName"
                   class="form-control mst-control @error('mdo_duty_type_name') is-invalid @enderror"
                   value="{{ old('mdo_duty_type_name', $mdoDutyType->mdo_duty_type_name ?? '') }}"
                   placeholder="Enter duty type name" maxlength="255" required aria-required="true">
            @error('mdo_duty_type_name')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-0">
            <label for="dutyTypeFormStatus" class="mst-form-label d-block">
                Status <span class="mst-req" aria-hidden="true">*</span>
            </label>
            <select name="active_inactive" id="dutyTypeFormStatus" class="form-select mst-control" required aria-required="true">
                <option value="1" @selected($mdoFormStatus === '1')>Active</option>
                <option value="0" @selected($mdoFormStatus !== '1')>Inactive</option>
            </select>
        </div>
    </div>
    <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
        <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn mst-btn-submit px-4">
            {{ !empty($mdoDutyType) ? 'Update' : 'Save' }}
        </button>
    </div>
</form>
