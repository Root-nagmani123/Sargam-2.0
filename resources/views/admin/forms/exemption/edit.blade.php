@extends('admin.layouts.master')

@section('title', 'Edit Exemption Master - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">Edit Exemption</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.fc_exemption.index') }}" class="text-muted">Exemption Master</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Edit</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <form action="{{ route('admin.fc_exemption.update', $exemption->Pk) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="Exemption_name" class="form-label">Exemption Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="Exemption_name" name="Exemption_name" value="{{ old('Exemption_name', $exemption->Exemption_name) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="Exemption_short_name" class="form-label">Short Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="Exemption_short_name" name="Exemption_short_name" value="{{ old('Exemption_short_name', $exemption->Exemption_short_name) }}" required>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <a href="{{ route('admin.fc_exemption.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
