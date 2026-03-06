@extends('admin.layouts.master')

@section('title', 'Section master - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <div class="card card-body py-3" style="border-left:4px solid #004a93;">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Section master</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Section master
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="widget-content searchable-container list">
        <div class="datatables">
            
            <!-- start Zero Configuration -->
            <div class="card" style="border-left:4px solid #004a93;">
                <div class="card-body">
                    <div class="row">
                <div class="col-md-4 col-xl-3">
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="mb-0 card-title">Section master</h4>
                    </div>
                </div>
                <div
                    class="col-md-8 col-xl-9 text-end d-flex justify-content-md-end justify-content-center mt-3 mt-md-0 gap-3">
                    <a href="#" id="btn-add-contact" class="btn btn-primary d-flex align-items-center">Add Section master
                    </a>
                    <a href="javascript:void(0)" id="btn-add-contact" class="btn btn-success d-flex align-items-center" data-bs-toggle="modal"
                    data-bs-target="#vertical-center-scroll-modal">Bulk Upload
                    </a>
                </div>
            </div>
                    <div class="table-responsive">
                        <!-- Vertically centered modal -->
                        <div class="modal fade" id="vertical-center-scroll-modal" tabindex="-1"
                            aria-labelledby="vertical-center-modal" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header d-flex align-items-center">
                                        <h4 class="modal-title" id="myLargeModalLabel">
                                            Bulk Upload for Faculty
                                        </h4>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <hr>
                                    <div class="modal-body">
                                        <form action="" method="POST">
                                            <label for="" class="form-label">Upload CSV</label>
                                            <input type="file" name="file" id="file" class="form-control">
                                        </form>
                                    </div>
                                    <hr>
                                    <div class="modal-footer">
                                        <button type="submit"
                                            class="btn bg-success-subtle text-success  waves-effect text-start">
                                            Submit
                                        </button>
                                        <button type="button"
                                            class="btn bg-danger-subtle text-danger  waves-effect text-start"
                                            data-bs-dismiss="modal">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div id="zero_config_wrapper" class="dataTables_wrapper">
                            <table id="zero_config"
                                class="table table-striped table-bordered text-nowrap align-middle dataTable"
                                aria-describedby="zero_config_info">
                                <thead>
                                    <!-- start row -->
                                    <tr>
                                        <th class="col">S.No.</th>
                                        <th class="col">Section Name</th>
                                        <th class="col">Employee Role</th>
                                        <th class="col">Employee Name</th>
                                        <th class="col">Action</th>
                                        <th class="col">Status</th>
                                    </tr>
                                    <!-- end row -->
                                </thead>
                                <tbody>
                                    <tr class="odd">
                                        <td>1</td>
                                        <td class="sorting_1">
                                            <div class="d-flex align-items-center gap-6">
                                                <img src="../assets/images/profile/user-5.jpg" width="45"
                                                    class="rounded-circle">
                                                <h6 class="mb-0"> Airi Satou</h6>
                                            </div>
                                        </td>
                                        <td>Accountant</td>
                                        <td>Accountant</td>
                                        <td>
                                            <div class="d-flex justify-content-start align-items-start gap-2">
                                                <a href="#" class="btn btn-success text-white btn-sm">
                                                    Edit
                                                </a>
                                                <form action="#" method="POST" class="m-0">
                                                    <input type="hidden" name="_token"
                                                        value="7m53OwU7KaFp1PPyJcyUuVMXW7xvrGr12yL6QycA"> <input
                                                        type="hidden" name="_method" value="DELETE"> <button
                                                        type="submit" class="btn btn-danger text-white btn-sm"
                                                        onclick="return confirm('Are you sure you want to delete?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input status-toggle" type="checkbox"
                                                    role="switch" data-table="news" data-column="status" data-id="21"
                                                    checked="">
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end Zero Configuration -->
        </div>
    </div>

</div>


@endsection