@extends('admin.layouts.master')

@section('title', 'Counsellor Group - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Counsellor Group" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($counsellorGroup) ? 'Edit Counsellor Group' : 'Add Counsellor Group' }}
            </h4>
            <hr>
            <form action="{{ route('counsellor.group.store') }}" method="POST" id="counsellorGroupForm">
                @csrf
                @if(!empty($counsellorGroup))
                <input type="hidden" name="pk" value="{{ encrypt($counsellorGroup->pk) }}">
                @endif
                <div class="row">
                    <div class="col-4">
                        <div class="mb-3">
                            <x-select name="course_master_pk" label="Course Name :" placeholder="Course Name"
                                formLabelClass="form-label" formSelectClass="select2" required="true"
                                :options="$courses" :value="old('course_master_pk', $counsellorGroup->course_master_pk ?? '')" />
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <x-input name="counsellor_group_name" label="Counsellor Group Name :" placeholder="Counsellor Group Name"
                                formLabelClass="form-label" required="true"
                                value="{{ old('counsellor_group_name', $counsellorGroup->counsellor_group_name ?? '') }}" />
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mb-3">
                            <x-select name="faculty_master_pk" label="Faculty Name :" placeholder="Faculty Name (Optional)"
                                formLabelClass="form-label" formSelectClass="select2" required="false"
                                :options="$faculties" :value="old('faculty_master_pk', $counsellorGroup->faculty_master_pk ?? '')" />
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3 gap-2 float-end">
                    <button class="btn btn-primary " type="submit" id="saveCounsellorGroupForm">
                        Save
                    </button>
                    <a href="{{ route('counsellor.group.index') }}" class="btn btn-secondary">
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>

@endsection

