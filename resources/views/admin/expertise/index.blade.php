@extends('admin.layouts.master')

@section('title', 'Area of Expertise - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Area of Expertise</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Area of Expertise
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="widget-content searchable-container list">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-4 col-xl-3">
                    <form class="position-relative">
                        <input type="text" class="form-control product-search ps-5" id="input-search">
                        <i
                            class="ri-search-line position-absolute top-50 start-0 translate-middle-y fs-6 text-dark ms-3"></i>
                    </form>
                </div>
                <div
                    class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                    <a href="{{ route('expertise.create') }}" id="btn-add-contact"
                        class="btn btn-primary d-flex align-items-center">
                        <i class="ri-add-line text-white me-1 fs-5"></i> Add Expertise
                    </a>
                </div>
            </div>
        </div>
        <div class="card card-body">
            <div class="table-responsive">
                <table class="table search-table align-middle text-nowrap">
                    <thead class="header-item">
                        <tr>
                            <th>
                                <div class="n-chk align-self-center text-center">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input primary" id="contact-check-all">
                                        <label class="form-check-label" for="contact-check-all"></label>
                                        <span class="new-control-indicator"></span>
                                    </div>
                                </div>
                            </th>
                            <th>Area of Expertise</th>
                            <th>Action</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- start row -->
                        <tr class="search-items">
                            <td>
                                <div class="n-chk align-self-center text-center">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input contact-chkbox primary"
                                            id="checkbox1">
                                        <label class="form-check-label" for="checkbox1"></label>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="ms-3">
                                        <div class="user-meta-info">
                                            <h6 class="user-name mb-0" data-name=" Adams"> Adams</h6>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="action-btn">
                                    <a href="{{ route('expertise.edit', 1) }}" class="text-primary edit">
                                        <i class="ri-eye-line fs-5"></i>
                                    </a>
                                    <a href="javascript:void(0)" class="text-dark delete ms-2">
                                        <i class="ri-delete-bin-line fs-5"></i>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                        data-table="news" data-column="status" data-id="21" checked="">
                                </div>
                            </td>
                        </tr>
                        <!-- end row -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


@endsection