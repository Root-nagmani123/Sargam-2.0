@extends('admin.layouts.master')

@section('title', 'Estate Request for Others - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Estate Request for Others</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="card shadow-sm" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row align-items-center mb-4">
                <div class="col-12 col-md-6">
                    <h2 class="mb-0">Estate Request for Others</h2>
                </div>
                <div class="col-12 col-md-6 mt-3 mt-md-0">
                    <div class="d-flex justify-content-md-end justify-content-start">
                        <a href="{{ route('admin.estate.add-other-estate-request') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Add Other Estate
                        </a>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <hr>
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="estateRequestTable">
                    <thead>
                        <tr>
                            <th class="w-auto pe-2">
                                <input type="checkbox" class="form-check-input" id="select_all" aria-label="Select all">
                            </th>
                            <th>S.No.</th>
                            <th>Request ID</th>
                            <th>Employee Name</th>
                            <th>Father's Name</th>
                            <th>Section</th>
                            <th>Date of Joining in Academy</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="pe-2">
                                <input type="checkbox" class="form-check-input" aria-label="Select row">
                            </td>
                            <td>1</td>
                            <td><span class="fw-medium">Oth-req-1</span></td>
                            <td>Karan Pillee</td>
                            <td>Karan Pillee</td>
                            <td>Karan Pillee</td>
                            <td>Karan Pillee</td>
                            <td>
                                <div class="d-inline-flex gap-1">
                                    <a href="javascript:void(0)" class="text-primary p-1" title="Edit" aria-label="Edit">
                                        <i class="material-symbols-rounded">edit</i>
                                    </a>
                                    <a href="javascript:void(0)" class="text-primary p-1" title="Delete" aria-label="Delete">
                                        <i class="material-symbols-rounded">delete</i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush
