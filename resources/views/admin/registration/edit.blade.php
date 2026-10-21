@extends('admin.layouts.master')

@section('title', 'Edit Form - Sargam | Lal Bahadur')

@section('setup_content')
    @include('admin.partials.choices-bootstrap5')

    <div class="container-fluid choices-bs-scope">
        <x-breadcrum title="Edit Form"/>

        <!-- Start Edit Form Card -->
      <div class="card">
    <div class="card-body">
        <form action="{{ route('forms.update', $form->id) }}" method="POST" id="editForm">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <!-- Form Name -->
                <div class="col-md-6">
                    <label for="name" class="form-label">Course Name:</label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="{{ old('name', $form->name) }}" required>
                </div>

                <!-- Short Name -->
                <div class="col-md-6">
                    <label for="shortname" class="form-label">Form Name:</label>
                    <input type="text" class="form-control" id="shortname" name="shortname"
                           value="{{ old('shortname', $form->shortname) }}" required>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label for="description" class="form-label">Description:</label>
                    <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $form->description) }}</textarea>
                </div>

                <!-- Parent Form -->
                <div class="col-md-6">
                    <label for="parent_id" class="form-label">Parent Form:</label>
                    <select name="parent_id" id="parent_id" class="form-select">
                        <option value="">None (Top-level form)</option>
                        @foreach ($forms as $item)
                            <option value="{{ $item->id }}"
                                {{ old('parent_id', $form->parent_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Course Start Date -->
                <div class="col-md-6">
                    <label for="course_sdate" class="form-label">Course Start Date:</label>
                    <input type="date" class="form-control" id="course_sdate" name="course_sdate"
                           value="{{ old('course_sdate', $form->course_sdate) }}" required>
                </div>

                <!-- Course End Date -->
                <div class="col-md-6">
                    <label for="course_edate" class="form-label">Course End Date:</label>
                    <input type="date" class="form-control" id="course_edate" name="course_edate"
                           value="{{ old('course_edate', $form->course_edate) }}" required>
                </div>

                <!-- Visibility -->
                <div class="col-md-6 d-flex align-items-center pt-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="visible" name="visible"
                               {{ $form->visible ? 'checked' : '' }}>
                        <label class="form-check-label ms-2" for="visible">Show on Main Page</label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <hr class="mt-4">
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-primary" type="submit">Update</button>
                <button class="btn btn-outline-primary"><a href="{{ route('forms.index') }}">Cancel</a></button>
            </div>
        </form>
    </div>
</div>


        <!-- End Edit Form Card -->
    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var startDateInput = document.getElementById('course_sdate');
        var endDateInput = document.getElementById('course_edate');
        var form = document.getElementById('editForm');
        if (!startDateInput || !endDateInput || !form) return;

        startDateInput.addEventListener('change', function () {
            endDateInput.setAttribute('min', this.value);
        });
        if (startDateInput.value) {
            endDateInput.setAttribute('min', startDateInput.value);
        }

        form.addEventListener('submit', function (e) {
            var startDate = new Date(startDateInput.value);
            var endDate = new Date(endDateInput.value);
            if (startDate > endDate) {
                e.preventDefault();
                alert('Course Start Date cannot be later than Course End Date.');
            }
        });
    });
</script>
@endpush
