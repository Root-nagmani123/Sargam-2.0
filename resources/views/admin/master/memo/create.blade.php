@extends('admin.layouts.master')

@section('title', 'Create Memo Management - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<link rel="stylesheet" href="{{asset('admin_assets/css/dual-listbox.css')}}">
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
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Session</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Faculty Name</label>
                            <select name="" class="form-control" id="">
                                <option value="">Select Faculty</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="" class="form-label">Faculty Name</label>
                            <select name="" class="form-control" id="">
                                <option value="">Select Faculty</option>
                                <!-- Options will be populated dynamically -->
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="selected_student_list" class="form-label">Select Students</label>
                        <select id="select" class="select1 form-control" name="selected_student_list[]" multiple>

                        </select>
                        @error('selected_student_list')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label for="textarea" class="form-label">Message (If Any) </label>
                        <textarea class="form-control" id="textarea" rows="3" placeholder="Enter remarks..."
                            name="Remark"></textarea>
                        @error('Remark')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
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
    <div class="bg-white p-4 rounded shadow-sm">
    <h5 class="text-center fw-bold mb-3">88th Foundation Course</h5>
    <p class="text-center mb-0">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
    <hr>

    <p class="mb-1">SHOW CAUSE NOTICE</p>
    <p><strong>Date:</strong> 22/11/2013</p>

    <p>It has been brought to the notice of the undersigned that you were absent without prior authorization from
        following session(s)...</p>

    <div class="table-responsive mb-3">
        <table class="table table-bordered text-center">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>No. of Session(s)</th>
                    <th>Topics</th>
                    <th>
                        Venue
                    </th>
                    <th>Session(s)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>22-11-2013</td>
                    <td>1</td>
                    <td>Lorem ipsum dolor sit amet.</td>
                    <td>Lorem, ipsum.</td>
                    <td>06:00-07:00</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mb-4">
        <p class="fw-bold">You are advised to do the following:</p>
        <ul>
            <li>Reply to this Memo online through this <a href="#">conversation</a></li>
            <li>Appear <a href="#">in person before the undersigned at 1800 hrs on next working day</a></li>
        </ul>
        <p>In absence of online explanation and your personal appearance, unilateral decision may be taken.</p>
    </div>

    <p><strong>ALBY VARGHESE, A42</strong><br>
        Remarks: Show Cause Notice for 22.11.13</p>

    <p class="text-end"><strong>Rajesh Arya</strong><br>Deputy Director Sr. & I/C Discipline 88th F.C.</p>

</div>
    <!-- end Vertical Steps Example -->
</div>

@endsection