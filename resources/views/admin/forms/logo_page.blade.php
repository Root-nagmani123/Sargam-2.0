@extends('admin.layouts.master')

@section('title', 'Registration Page - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Registration Page</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Registration Page
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
            <h4 class="card-title mb-3">Create Registration Page</h4>
            <hr>
            <form>
                <div class="row">
                    <div class="col-6">
                        <label for="Schoolname" class="form-label">Logo1 :</label>

                        <div class="mb-3">
                            <input type="file" class="form-control" id="logo1" name="logo1" placeholder="Logo1">
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="Schoolname" class="form-label">Logo2 :</label>

                        <div class="mb-3">
                            <input type="file" class="form-control" id="logo2" name="logo2" placeholder="Logo2">
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="Schoolname" class="form-label">Logo3 :</label>

                        <div class="mb-3">
                            <input type="file" class="form-control" id="logo3" name="logo3" placeholder="Logo3">
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="Schoolname" class="form-label">Logo4 :</label>

                        <div class="mb-3">
                            <input type="file" class="form-control" id="logo4" name="logo4" placeholder="Logo4">
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="Schoolname" class="form-label">Main Heading :</label>

                        <div class="mb-3">
                            <input type="text" class="form-control" id="heading" name="heading" placeholder="Main heading">
                        </div>
                    </div>
                     <div class="col-6">
                        <label for="Schoolname" class="form-label">Sub Heading :</label>

                        <div class="mb-3">
                            <input type="text" class="form-control" id="sub_heading" name="sub_heading" placeholder="Sub heading">
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                        <i class="material-icons menu-icon">send</i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>
@endsection