@extends('admin.layouts.master')

@section('title', 'Edit Faculty')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Faculty" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card" id="facultyForm" data-store-url="{{ route('faculty.update') }}" data-index-url="{{ route('faculty.index') }}">
        <div class="card-body">
            <h4 class="card-title mb-3">Edit Faculty</h4>
            <hr>
            <form>
                <input type="hidden" name="faculty_id" value="{{ $faculty->pk }}">
                @include('admin.faculty.components.basicInfo')
                @include('admin.faculty.components.degree')
                @include('admin.faculty.components.experienceDetails')
                @include('admin.faculty.components.bankDetails')
                @include('admin.faculty.components.researchPublication')
                <hr>
                <div class="row">
                    <div class="col-12">
                        <label for="sector" class="form-label">Current Sector :</label>
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input success" type="radio" name="current_sector"
                                    id="success-radio" value="1" {{ $faculty->faculty_sector == 1 ? 'checked' : '' }}>
                                <label class="form-check-label" for="success-radio">Government Sector</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input success" type="radio" name="current_sector"
                                    id="success2-radio" value="2" {{ $faculty->faculty_sector == 2 ? 'checked' : '' }}>
                                <label class="form-check-label" for="success2-radio">Private Sector</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">

                        <label for="expertise" class="form-label">Area of Expertise :</label>
                        <div class="mb-3">
                            @if(!empty($faculties))
                                <fieldset>
                                    @foreach ($faculties as $key => $option)
                                    <div class="form-check py-2">
                                        <input type="checkbox" name="faculties[]" value="{{ $key }}" class="form-check-input" id="{{ $loop->index }}" {{ in_array($key, $facultExpertise) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $loop->index }}">{{ $option }}</label>
                                    </div>
                                    @endforeach
                                    @error('faculties[]')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </fieldset>
                            @endif
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="button" id="saveFacultyForm">
                        <i class="material-icons menu-icon">save</i>
                        Save
                    </button>
                    <a href="{{ route('faculty.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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

@section('scripts')
<script>
    
    
</script>
@endsection