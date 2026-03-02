<form action="{{ route('master.designation.store') }}" method="POST" id="designationForm">
    @csrf
    @if(!empty($designationMaster)) 
        <input type="hidden" name="pk" value="{{ encrypt($designationMaster->pk) }}">
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <x-input
                    name="designation_name"
                    label="Name :" 
                    placeholder="Name" 
                    formLabelClass="form-label fw-semibold"
                    required="true"
                    value="{{ old('designation_name', $designationMaster->designation_name ?? '') }}"
                    />
            </div>
        </div>
    </div>
</form>
