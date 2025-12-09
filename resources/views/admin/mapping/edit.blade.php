@extends('admin.layouts.master')

@section('title', 'Faculty Topic Mapping - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Edit Faculty Topic Mapping</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Edit Faculty Topic Mapping
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <!-- start Person Info -->
            <div class="card">
                <form action="#">
                    <div>
                        <div class="card-body">
                            <!--/row-->
                            <h4 class="card-title">Edit Faculty Topic Mapping</h4>
                            <small class="form-control-feedback">Please add topic wise faculty details.</small>
                            <hr>
                            <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Programme Name</label>
                                    <select class="form-select">
                                        <option>--Select Course Name--</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Major Subject</label>
                                    <select class="form-select">
                                        <option>--Select Batch Name--</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Minor Subject</label>
                                    <select class="form-select">
                                        <option>--Select Major Subject Name--</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Topic</label>
                                    <select class="form-select">
                                        <option>--Select Minor Subject Number--</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Faculty</label>
                                    <input type="text" name="" id="" class="form-control" placeholder="Minor Subject Name">
                                </div>
                            </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <div class="card-body border-top">
                                <button type="submit" class="btn btn-primary">
                                    Submit
                                </button>
                                <button type="button" class="btn bg-danger-subtle text-danger ms-6">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <!-- end Person Info -->
        </div>
    </div>
</div>


@endsection