@extends('admin.layouts.master')

@section('title', 'Create Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Create Memo/Notice Management" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Create Memo/Notice Management</h4>
            <hr>
            <form action="" method="POST">
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="type" class="form-label">Course</label>
                            <select name="type" class="form-control" id="">
                                <option value="">Select Course</option>

                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Subject <span style="color:#af2910;">*</span></label>
                            <select name="student_master_id" class="form-control" id="">
                                <option value="">Select Subject</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                            <small>For student who have absent or late in that session</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Topic</label>
                            <select name="student_master_id" class="form-control" id="">
                                <option value="">Select Topic</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="type" class="form-label">Venue <span style="color:#af2910;">*</span></label>
                            <input type="text" name="" id="" class="form-control">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Session</label>
                            <select name="" class="form-control" id="">
                                <option value="">Select Session</option>
                                <!-- Options will be populated dynamically -->
                        </div>
                    </div>
                    

                </div>

                <hr>

                <div class="row">
                    <div class="col-10">
                        <div class="text-center gap-3">
                            <button type="submit" class="btn btn-danger">Notice</button>
                            <button type="submit" class="btn btn-warning" style="margin-left: 5%;">Memo</button>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="text-end gap-3">
                            <button type="reset" class="btn btn-primary">Preview</button>
                            <a href="{{ route('master.memo.index') }}" class="btn btn-secondary">Back</a>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>
@endsection