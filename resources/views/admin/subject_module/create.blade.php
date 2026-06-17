@extends('admin.layouts.master')

@section('title', 'Subject Module - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Subject module" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card" >
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
<script>
    window.location.replace(@json(route('subject-module.index', ['open_add_module' => 1])));
</script>
@endsection
