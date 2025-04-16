@extends('admin.layouts.master')

@section('title', 'Programme - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Create Course</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Course
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
            <h4 class="card-title mb-3">Create Course</h4>
            <hr>
            <form>
                <div class="row">
                    <div class="row" id="course_fields">
                        <div class="col-md-6">
                            <label for="coursename" class="form-label">Course Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="coursename" name="coursename"
                                    placeholder="Course Name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="courseyear" class="form-label">Course Year :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="courseyear" name="courseyear" placeholder="Course Year">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="startdate" class="form-label">Start Date :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="startdate" name="startdate" placeholder="Start Date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="enddate" class="form-label">End Date :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="enddate" name="enddate" placeholder="End Date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="coursecoordinator" class="form-label">Course coordinator :</label>
                            <div class="mb-3">
                            <select name="coursecoordinator" id="coursecoordinator" class="form-control">
                                <option value="0">Select Coordinator</option>
                            </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="assistantcoursecoordinator" class="form-label">Assistant Course Coordinator :</label>
                            <div class="mb-3">
                            <select name="assistantcoursecoordinator" id="assistantcoursecoordinator" class="form-control">
                                <option value="0">Select Assistant Course Coordinator</option>
                                <option value="0">1</option>
                                <option value="0">2</option>
                                <option value="0">3</option>
                            </select>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3 mt-4">
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