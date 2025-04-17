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
                    <!-- Button trigger modal -->
                    <button type="button" class="btn mb-1 btn-primary px-4 fs-4 " data-bs-toggle="modal"
                        data-bs-target="#vertical-center-scroll-modal">
                        <i class="ri-add-line text-white me-1 fs-5"></i> Add Expertise
                    </button>
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
                                    <a href="{{ route('expertise.edit', 1) }}" class="text-primary edit"
                                        data-bs-toggle="modal" data-bs-target="#vertical-center-scroll-modal">
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
<!-- Vertically centered modal -->
<div class="modal fade" id="vertical-center-scroll-modal" tabindex="-1" aria-labelledby="vertical-center-modal"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                <h4 class="modal-title" id="myLargeModalLabel">
                    Vertically centered scrollable Modal
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4>Overflowing text to show scroll behavior</h4>
                <p>
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Vivamus sagittis lacus vel
                    augue laoreet rutrum faucibus dolor auctor.
                </p>
                <p>
                    Aenean lacinia bibendum nulla sed consectetur.
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Donec sed odio dui. Donec
                    ullamcorper nulla non metus auctor fringilla.
                </p>
                <p>
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Vivamus sagittis lacus vel
                    augue laoreet rutrum faucibus dolor auctor.
                </p>
                <p>
                    Aenean lacinia bibendum nulla sed consectetur.
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Donec sed odio dui. Donec
                    ullamcorper nulla non metus auctor fringilla.
                </p>
                <p>
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Vivamus sagittis lacus vel
                    augue laoreet rutrum faucibus dolor auctor.
                </p>
                <p>
                    Aenean lacinia bibendum nulla sed consectetur.
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Donec sed odio dui. Donec
                    ullamcorper nulla non metus auctor fringilla.
                </p>
                <p>
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Vivamus sagittis lacus vel
                    augue laoreet rutrum faucibus dolor auctor.
                </p>
                <p>
                    Aenean lacinia bibendum nulla sed consectetur.
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Donec sed odio dui. Donec
                    ullamcorper nulla non metus auctor fringilla.
                </p>
                <p>
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Vivamus sagittis lacus vel
                    augue laoreet rutrum faucibus dolor auctor.
                </p>
                <p>
                    Aenean lacinia bibendum nulla sed consectetur.
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Donec sed odio dui. Donec
                    ullamcorper nulla non metus auctor fringilla.
                </p>
                <p>
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Vivamus sagittis lacus vel
                    augue laoreet rutrum faucibus dolor auctor.
                </p>
                <p>
                    Aenean lacinia bibendum nulla sed consectetur.
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Donec sed odio dui. Donec
                    ullamcorper nulla non metus auctor fringilla.
                </p>
                <p>
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Vivamus sagittis lacus vel
                    augue laoreet rutrum faucibus dolor auctor.
                </p>
                <p>
                    Aenean lacinia bibendum nulla sed consectetur.
                    Praesent commodo cursus magna, vel scelerisque
                    nisl consectetur et. Donec sed odio dui. Donec
                    ullamcorper nulla non metus auctor fringilla.
                </p>
            </div>
            <div class="modal-footer gap-2">
                <button type="submit" class="btn bg-success-subtle text-success  waves-effect text-start">
                    Submit
                </button>
                <button type="button" class="btn bg-danger-subtle text-danger  waves-effect text-start"
                    data-bs-dismiss="modal">
                    Close
                </button>

            </div>
        </div>
    </div>
</div>

@endsection