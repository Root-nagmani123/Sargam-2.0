@extends('admin.layouts.master')

@section('title', 'Faculty Topic Mapping - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Faculty Topic Mapping</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Faculty Topic Mapping
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Faculty Topic Mapping</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="#" class="btn btn-primary">+ Add Faculty Topic Mapping</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <div class="dataTables_length" id="zero_config_length"><label>Show <select
                                    name="zero_config_length" aria-controls="zero_config" class="">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select> entries</label></div>
                        <div id="zero_config_filter" class="dataTables_filter"><label>Search:<input type="search"
                                    class="" placeholder="" aria-controls="zero_config"></label></div>
                        <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="sorting sorting_asc" tabindex="0" aria-controls="zero_config" rowspan="1"
                                        colspan="1" aria-sort="ascending"
                                        aria-label="Name: activate to sort column descending" style="width: 224.625px;">
                                        S.No.</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Position: activate to sort column ascending"
                                        style="width: 225.875px;">Programme Name</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Position: activate to sort column ascending"
                                        style="width: 225.875px;">Major Subject</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Position: activate to sort column ascending"
                                        style="width: 225.875px;">Topic</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Position: activate to sort column ascending"
                                        style="width: 225.875px;">Faculties</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Position: activate to sort column ascending"
                                        style="width: 225.875px;">Minor Subject</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Salary: activate to sort column ascending"
                                        style="width: 85.8906px;">Action</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Salary: activate to sort column ascending"
                                        style="width: 85.8906px;">Status</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                <tr class="odd">
                                    <td>1</td>
                                    <td class="sorting_1">
                                        <div class="d-flex align-items-center gap-6">
                                            <h6 class="mb-0"> Airi Satou</h6>
                                        </div>
                                    </td>
                                    <td class="sorting_1">
                                        <div class="d-flex align-items-center gap-6">
                                            <h6 class="mb-0"> Airi Satou</h6>
                                        </div>
                                    </td>
                                    <td class="sorting_1">
                                        <div class="d-flex align-items-center gap-6">
                                            <h6 class="mb-0"> Airi Satou</h6>
                                        </div>
                                    </td>
                                    <td class="sorting_1">
                                        <div class="d-flex align-items-center gap-6">
                                            <h6 class="mb-0"> Airi Satou</h6>
                                        </div>
                                    </td>
                                    <td class="sorting_1">
                                        <div class="d-flex align-items-center gap-6">
                                            <h6 class="mb-0"> Airi Satou</h6>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            <a href="#" class="btn btn-success text-white btn-sm">
                                                Edit
                                            </a>
                                            <form action="#" method="POST" class="m-0">
                                                <input type="hidden" name="_token"
                                                    value="7m53OwU7KaFp1PPyJcyUuVMXW7xvrGr12yL6QycA"> <input
                                                    type="hidden" name="_method" value="DELETE"> <button type="submit"
                                                    class="btn btn-danger text-white btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="news" data-column="status" data-id="21" checked="">
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="dataTables_info" id="zero_config_info" role="status" aria-live="polite">Showing 1 to
                            10 of 57 entries</div>
                        <div class="dataTables_paginate paging_simple_numbers" id="zero_config_paginate"><a
                                class="paginate_button previous disabled" aria-controls="zero_config"
                                aria-disabled="true" role="link" data-dt-idx="previous" tabindex="-1"
                                id="zero_config_previous">Previous</a><span><a class="paginate_button current"
                                    aria-controls="zero_config" role="link" aria-current="page" data-dt-idx="0"
                                    tabindex="0">1</a><a class="paginate_button " aria-controls="zero_config"
                                    role="link" data-dt-idx="1" tabindex="0">2</a><a class="paginate_button "
                                    aria-controls="zero_config" role="link" data-dt-idx="2" tabindex="0">3</a><a
                                    class="paginate_button " aria-controls="zero_config" role="link" data-dt-idx="3"
                                    tabindex="0">4</a><a class="paginate_button " aria-controls="zero_config"
                                    role="link" data-dt-idx="4" tabindex="0">5</a><a class="paginate_button "
                                    aria-controls="zero_config" role="link" data-dt-idx="5" tabindex="0">6</a></span><a
                                class="paginate_button next" aria-controls="zero_config" role="link" data-dt-idx="next"
                                tabindex="0" id="zero_config_next">Next</a></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection