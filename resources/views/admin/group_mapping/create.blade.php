@extends('admin.layouts.master')

@section('title', 'Group Mapping')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Group Mapping" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($groupMapping) ? 'Edit Group Mapping' : 'Add Group Mapping' }}
            </h4>
            <hr>
            <form action="{{ route('group.mapping.store') }}" method="POST" id="classSessionForm">
                @csrf
                @if(!empty($groupMapping)) 
                    <input type="hidden" name="id" value="{{ encrypt($groupMapping->pk) }}">
                @endif
                <div class="row">
                    
                    

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-select 
                                name="course_id" 
                                label="Course name :" 
                                placeholder="Course name" 
                                formLabelClass="form-label"
                                formSelectClass="select2"
                                required="true"
                                :options="$courses" 
                                :value="old('course_id', $groupMapping->course_name ?? '')"
                                />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-select 
                                name="type_id" 
                                label="Group Type :" 
                                placeholder="Group Type" 
                                formLabelClass="form-label"
                                formSelectClass="select2"
                                required="true"
                                :options="$courseGroupTypeMaster" 
                                :value="old('type_name', $groupMapping->type_name ?? '')"
                                />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input 
                                name="group_name" 
                                label="Group Name :" 
                                placeholder="Group Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('group_name', $groupMapping->group_name ?? '') }}"
                                />
                        </div>
                    </div>
                    
                </div>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                        <i class="material-icons menu-icon">save</i>
                        Save
                    </button>
                    <a href="{{ route('group.mapping.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
                        <i class="material-icons menu-icon">arrow_back</i>
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection