@extends('admin.layouts.master')

@section('title', 'Course Curriculum - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <div class="card card-body py-3" style="border-left:4px solid #004a93;">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Course Curriculum</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="../main/index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Course Curriculum
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
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Course Curriculum</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('curriculum.create') }}" class="btn btn-primary">+ Add Curriculum</a>
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
                                    <th class="col">Course Name</th>
                                    <th class="col">Batch Name</th>
                                    <th class="col">Major Subject Name</th>
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
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection