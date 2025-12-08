@extends('admin.layouts.master')

@section('title', 'Edit Faculty')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Faculty" />

    <!-- start Vertical Steps Example -->
    {{-- id="facultyForm" data-store-url="{{ route('faculty.update') }}"
        data-index-url="{{ route('faculty.index') }} --}}

        <form class="facultyForm">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Personal Information</h4>
                    <hr>
                    <input type="hidden" name="faculty_id" value="{{ $faculty->pk }}">
                    @include('admin.faculty.components.basicInfo')
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Qualification Details</h4>
                    <hr>
                    @include('admin.faculty.components.degree')
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Experience Details</h4>
                    <hr>
                    @include('admin.faculty.components.experienceDetails')
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Bank Details</h4>
                    <hr>
                    @include('admin.faculty.components.bankDetails')
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Other information</h4>
                    <hr>
                    @include('admin.faculty.components.researchPublication')
                </div>
            </div>

            <div class="card">
                <div class="card-body">
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
                                        <div class="row">
                                            @foreach ($faculties as $key => $option)
                                            <div class="col-3">
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="faculties[]" value="{{ $key }}"
                                                        class="form-check-input" id="{{ $loop->index }}"
                                                        {{ in_array($key, $facultExpertise) ? 'checked' : '' }}>
                                                    <label class="form-check-label"
                                                        for="{{ $loop->index }}">{{ $option }}</label>
                                                </div>
                                            </div>
                                            @endforeach
                                            @error('faculties[]')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </fieldset>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <button class="btn btn-primary hstack gap-6 float-end" type="button" id="saveFacultyForm">
                                <i class="material-icons menu-icon">save</i>
                                Update
                            </button>
                            <a href="{{ route('faculty.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
                                <i class="material-icons menu-icon">arrow_back</i>
                                Back
                            </a>
                        </div>
                </div>
            </div>
        </form>
    <!-- end Vertical Steps Example -->
</div>


@endsection

@section('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '#saveFacultyForm', function (e) {
        e.preventDefault();
        var form = $('.facultyForm')[0];
        var formData = new FormData(form);

        // Validate required fields before submit
        var requiredFields = [
            'facultytype', 'firstName', 'middlename', 'lastname', 'fullname', 'gender', 'landline', 'mobile',
            'country', 'state', 'district', 'city', 'email', 'residence_address', 'permanent_address', 'bankname',
            'accountnumber', 'ifsccode', 'pannumber', 'joiningdate', 'current_sector'
        ];
        var missing = [];
        requiredFields.forEach(function(field) {
            if (!formData.get(field) || formData.get(field) === 'undefined') {
                missing.push(field);
            }
        });
        if (missing.length > 0) {
            alert('Please fill all required fields: ' + missing.join(', '));
            return;
        }

        $.ajax({
            url: "{{ route('faculty.update') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status) {
                    window.location.href = "{{ route('faculty.index') }}";
                } else {
                    if (window.toastr) {
                        toastr.error(response.message || 'Error updating faculty.');
                    } else {
                        alert(response.message || 'Error updating faculty.');
                    }
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unknown error');
                if (window.toastr) {
                    toastr.error('Error: ' + msg);
                } else {
                    alert('Error: ' + msg);
                }
            }
        });
    });
});
</script>
@endsection
