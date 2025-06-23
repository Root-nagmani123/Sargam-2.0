@extends('admin.layouts.master')

@section('title', 'Area of Expertise - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Area of Expertise" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Area of Expertise</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#vertical-center-scroll-modal">+ Area of Expertise</a>
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
                                    <th class="col">Area of Expertise</th>
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
                                            <h6 class="mb-0">Adams</h6>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            <a href="{{ route('expertise.edit', 1) }}"
                                        data-bs-toggle="modal" data-bs-target="#vertical-center-scroll-modal" class="btn btn-success text-white btn-sm">
                                                Edit
                                            </a>
                                            <form action="" method="POST"
                                                class="m-0">
                                                <button type="submit" class="btn btn-danger text-white btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                    data-table="states" data-column="status" data-id="">
                                            </div>
                                        </td>
                                </tr>
                                
                            </tbody>

                        </table>


                    </div>
                </div>
            </div>
            <!-- end Zero Configuration -->
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