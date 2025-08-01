@extends('admin.layouts.master')

@section('title', 'Subject module - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Subject module" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">Subject module</h4>
            <hr>
            <form action="{{ route('subject-module.store') }}" method="POST">
    @csrf

    <div class="row my-2">
        <div class="col-sm-6">
            <label for="module_name" class="form-label">Module Name <span class="text-danger">*</span></label>
            <input type="text" name="module_name" class="form-control @error('module_name') is-invalid @enderror" placeholder="Enter module name" value="{{ old('module_name') }}" required>
            @error('module_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-sm-6">
            <label for="active_inactive" class="form-label">Status <span class="text-danger">*</span></label>
            <select name="active_inactive" class="form-control @error('active_inactive') is-invalid @enderror" required>
                <option value="">-- Select Status --</option>
                <option value="1" {{ old('active_inactive') == '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ old('active_inactive') == '0' ? 'selected' : '' }}>Inactive</option>
            </select>
            @error('active_inactive')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <hr>
    <div class="mb-3 text-end">
        <button class="btn btn-primary" type="submit">Submit</button>
        <a href="{{ route('subject-module.index') }}" class="btn btn-secondary">Back</a>
    </div>
</form>


        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection
<script>
function addModuleField() {
    const newRow = `
        <div class="row my-2 module-row">
            <div class="col-sm-5">
                <input type="text" name="module_name[]" class="form-control" placeholder="Module Name" required>
            </div>
            <div class="col-sm-3">
                <select name="active_inactive[]" class="form-control" required>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="col-sm-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger remove-btn">
                    <i class="material-icons menu-icon">delete</i>
                </button>
            </div>
        </div>
    `;
    document.getElementById('module_fields').insertAdjacentHTML('beforeend', newRow);
}

document.getElementById('module_fields').addEventListener('click', function(e) {
    if (e.target.closest('.remove-btn')) {
        e.target.closest('.module-row').remove();
    }
});
</script>