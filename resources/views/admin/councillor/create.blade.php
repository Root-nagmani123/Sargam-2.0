@extends('admin.layouts.master')

@section('title', 'Councillor Group - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Councillor Group - Create" />
    <x-session_message />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">Create Councillor Group</h4>
            <hr>
            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Course Name</label>
                            <input type="text" class="form-control" name="" id="" value="">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Counsellor Code</label>
                            <input type="text" class="form-control" name="" id="" value="">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Faculty Name</label>
                            <input type="text" class="form-control" name="" id="" value="">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Status</label>
                            <select class="form-select" name="" id="">
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="mb-3">

                    <button class="btn btn-primary hstack gap-2 float-end btn-sm" type="submit">
                        Submit
                    </button>
                    <a href="" class="btn btn-secondary hstack gap-6 float-end me-2 btn-sm">
                                
                                Back
                            </a>
                    
                </div>
            </form>


        </div>
    </div>
    @endsection