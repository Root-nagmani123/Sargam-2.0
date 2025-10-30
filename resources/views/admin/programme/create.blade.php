@extends('admin.layouts.master')

@section('title', 'Programme - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Programme" />
    <x-session_message />

    <!-- start Vertical Steps Example -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($courseMasterObj) && $courseMasterObj->pk ? 'Edit Programme' : 'Create Programme' }}
            </h4>
            <hr>
            <form action="{{ route('programme.store') }}" method="POST">
                @csrf
                
                @if(!empty($courseMasterObj) && $courseMasterObj->pk)
                    <input type="hidden" name="course_id" value="{{ encrypt($courseMasterObj->pk) }}">
                @endif


                <div class="row">
                    <div class="row" id="course_fields">
                        <div class="col-md-6">
                            <x-input 
                                name="coursename" 
                                label="Course Name" 
                                placeholder="Course Name" 
                                formLabelClass="form-label"
                                value="{{ $courseMasterObj->course_name ?? '' }}"
                                required="true"
                                />
                        </div>
                        <div class="col-md-6">
                            <x-input 
                                name="courseshortname" 
                                label="Course Short Name" 
                                placeholder="Course Short Name" 
                                value="{{ $courseMasterObj->couse_short_name ?? '' }}"
                                formLabelClass="form-label" />
                        </div>
                        <div class="col-md-6 mt-4">
                            <x-input 
                                type="text" 
                                name="courseyear" 
                                label="Course Year" 
                                placeholder="Course Year" 
                                value="{{ $courseMasterObj->course_year ?? '' }}"
                                formLabelClass="form-label"
                                min="1900"
                                max="2100" 
                                required="true"/>
                        </div>

                        <div class="col-md-6 mt-4">
                            <x-input 
                                type="date" 
                                name="startdate" 
                                label="Start Date" 
                                placeholder="Start Date" 
                                value="{{ $courseMasterObj->start_year ?? '' }}"
                                formLabelClass="form-label" />
                        </div>
                        
                        <div class="col-md-6 mt-4">
                            <x-input 
                                type="date" 
                                name="enddate" 
                                label="End Date" 
                                placeholder="End Date" 
                                value="{{ $courseMasterObj->end_date ?? '' }}"
                                formLabelClass="form-label" />
                        </div>

                        <div class="col-md-6 mt-4">

                            <x-select 
                                name="coursecoordinator" 
                                label="Course Coordinator" 
                                placeholder="Course Coordinator" 
                                formLabelClass="form-label" 
                                value="{{ $coordinator_name ?? '' }}"
                                :options="$facultyList" />

                        </div>
                        <div class="col-md-12 mt-4">
                            <label class="form-label">Assistant Course Coordinators</label>
                            <div id="assistant-coordinators-container">
                                @if(!empty($assistant_coordinator_name) && is_array($assistant_coordinator_name))
                                    @foreach($assistant_coordinator_name as $index => $coordinator)
                                        <div class="assistant-coordinator-row row mb-3" data-index="{{ $index }}">
                                            <div class="col-md-6">
                                                <label class="form-label">Assistant Coordinator</label>
                                                <select name="assistantcoursecoordinator[]" class="form-select @error('assistantcoursecoordinator') is-invalid @enderror" required>
                                                    <option value="">Select Assistant Coordinator</option>
                                                    @foreach($facultyList as $key => $name)
                                                        <option value="{{ $name }}" {{ $coordinator == $name ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('assistantcoursecoordinator')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label">Role</label>
                                                <input type="text" name="assistant_coordinator_role[]" class="form-control @error('assistant_coordinator_role') is-invalid @enderror" 
                                                       placeholder="e.g., Discipline In-Charge, Co-Coordinator" 
                                                       value="{{ $assistant_coordinator_roles[$index] ?? '' }}" required>
                                                @error('assistant_coordinator_role')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-coordinator" style="margin-bottom: 0;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="assistant-coordinator-row row mb-3" data-index="0">
                                        <div class="col-md-6">
                                            <label class="form-label">Assistant Coordinator</label>
                                            <select name="assistantcoursecoordinator[]" class="form-select @error('assistantcoursecoordinator') is-invalid @enderror" required>
                                                <option value="">Select Assistant Coordinator</option>
                                                @foreach($facultyList as $key => $name)
                                                    <option value="{{ $name }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            @error('assistantcoursecoordinator')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label">Role</label>
                                            <input type="text" name="assistant_coordinator_role[]" class="form-control @error('assistant_coordinator_role') is-invalid @enderror" 
                                                   placeholder="e.g., Discipline In-Charge, Co-Coordinator" required>
                                            @error('assistant_coordinator_role')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-coordinator" style="margin-bottom: 0;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add-coordinator">
                                    <i class="fas fa-plus"></i> Add Another Assistant Coordinator
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
                <hr>
                <div class="mb-3 mt-4 text-end gap-2">
                    <button class="btn btn-primary" type="submit">
                        Submit
                    </button>
                    <a href="{{ route('programme.index') }}" class="btn btn-secondary">
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let coordinatorIndex = {{ !empty($assistant_coordinator_name) && is_array($assistant_coordinator_name) ? count($assistant_coordinator_name) : 1 }};
    
    // Add coordinator functionality
    document.getElementById('add-coordinator').addEventListener('click', function() {
        const container = document.getElementById('assistant-coordinators-container');
        const newRow = document.createElement('div');
        newRow.className = 'assistant-coordinator-row row mb-3';
        newRow.setAttribute('data-index', coordinatorIndex);
        
        newRow.innerHTML = `
            <div class="col-md-6">
                <label class="form-label">Assistant Coordinator</label>
                <select name="assistantcoursecoordinator[]" class="form-select" required>
                    <option value="">Select Assistant Coordinator</option>
                    @foreach($facultyList as $key => $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Role</label>
                <input type="text" name="assistant_coordinator_role[]" class="form-control" 
                       placeholder="e.g., Discipline In-Charge, Co-Coordinator" required>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger btn-sm remove-coordinator" style="margin-bottom: 0;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        container.appendChild(newRow);
        coordinatorIndex++;
    });
    
    // Remove coordinator functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-coordinator')) {
            const row = e.target.closest('.assistant-coordinator-row');
            const container = document.getElementById('assistant-coordinators-container');
            
            // Don't allow removing the last coordinator
            if (container.children.length > 1) {
                row.remove();
            } else {
                alert('At least one assistant coordinator is required.');
            }
        }
    });
});
</script>
@endpush