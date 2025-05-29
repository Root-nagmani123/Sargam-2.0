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
                            <label for="type" class="form-label">Course Student Attendance</label>
                            <select name="type" class="form-control" id="">
                                <option value="">Select Type</option>
                                <option value="memo">Memo</option>
                                <option value="notice">Notice</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Student Master <span style="color:#af2910;">*</span></label>
                            <select name="student_master_id" class="form-control" id="">
                                <option value="">Select Student</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">OT Code</label>
                            <input type="text" class="form-control" name="ot_code" id="" placeholder="Enter OT Code" readonly>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="type" class="form-label">Type <span style="color:#af2910;">*</span></label>
                            <select name="type" class="form-control" id="">
                                <option value="">Select Type</option>
                                <option value="memo">Memo</option>
                                <option value="notice">Notice</option>
                            </select>
                        </div>
                    </div>
                     <div class="col-6">
                        <div class="mb-3">
                            <label for="type" class="form-label">Course Master <span style="color:#af2910;">*</span></label>
                            <select name="type" class="form-control" id="">
                                <option value="">Select Type</option>
                                <option value="memo">Memo</option>
                                <option value="notice">Notice</option>
                            </select>
                        </div>
                    </div>
                     <div class="col-6">
                        <div class="mb-3">
                            <label for="type" class="form-label">Type <span style="color:#af2910;">*</span></label>
                            <select name="type" class="form-control" id="">
                                <option value="">Select Type</option>
                                <option value="memo">Memo</option>
                                <option value="notice">Notice</option>
                            </select>
                        </div>
                    </div>
                     <div class="col-12">
                        <div class="mb-3">
                            <label for="type" class="form-label">Message <span style="color:#af2910;">*</span></label>
                            <textarea name="message" id="message" class="form-control" rows="5"></textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="" class="form-label">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Select Status</option>
                                <option value="0">Active</option>
                                <option value="1">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                </div>
                
                <hr>

                <div class="text-end gap-3">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('master.memo.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </form>

        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>
@endsection