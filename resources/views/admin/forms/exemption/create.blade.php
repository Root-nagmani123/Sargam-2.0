@extends('admin.layouts.master')

@section('title', 'Exemption Master - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Exemption Master</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Exemption Master
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- start Vertical Steps Example -->
   <div class="card">
    <div class="card-body">
        <h4 class="card-title mb-3">Create Exemption Master</h4>
        <hr>
        <form action="{{ route('admin.fc_exemption.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-6">
                    <label for="Exemption_name" class="form-label">Exemption Name :</label>
                    <div class="mb-3">
                        <input type="text" class="form-control" id="Exemption_name" name="Exemption_name"
                            placeholder="Enter Exemption Name" required>
                    </div>
                </div>
                <div class="col-6">
                    <label for="Exemption_short_name" class="form-label">Exemption Short Name :</label>
                    <div class="mb-3">
                        <input type="text" class="form-control" id="Exemption_short_name" name="Exemption_short_name"
                            placeholder="Enter Short Name" required>
                    </div>
                </div>
            </div>
            <hr>
            <div class="mb-3">
                <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                    <i class="material-icons menu-icon">send</i>
                    Submit
                </button>
            </div>
        </form>
    </div>
</div>

    <!-- end Vertical Steps Example -->
</div>
@endsection