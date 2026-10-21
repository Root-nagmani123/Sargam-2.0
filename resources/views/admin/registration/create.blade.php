@extends('admin.layouts.master')

@section('title', 'Create Form - Sargam | Lal Bahadur')

@section('setup_content')
    @include('admin.partials.choices-bootstrap5')

    <div class="container-fluid choices-bs-scope">
        <x-breadcrum title="Create Form" />

        <!-- Form -->
        <div class="card mt-3">
            <div class="card-body">
                <form action="{{ route('forms.store') }}" method="POST"  id="createForm">
                    @csrf
                    <div class="row g-3">
                        <!-- Form Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label">Course Name:</label>
                            <input type="text" class="form-control" id="name" name="name"
                                placeholder="Enter Form Name" required>
                        </div>

                        <!-- Short Name -->
                        <div class="col-md-6">
                            <label for="shortname" class="form-label">Form Name:</label>
                            <input type="text" class="form-control" id="shortname" name="shortname"
                                placeholder="Enter Short Name" required>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label">Description:</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter Description"></textarea>
                        </div>

                        <!-- Parent Form -->
                        <div class="col-md-6">
                            <label for="parent_id" class="form-label">Parent Form:</label>
                            <select name="parent_id" id="parent_id" class="form-select">
                                <option value="">Choose Option</option>
                                @foreach ($forms as $item)
                                    <option value="{{ $item->id }}"
                                        {{ old('parent_id') == $item->id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Course Start Date -->
                        <div class="col-md-6">
                            <label for="course_sdate" class="form-label">Course Start Date:</label>
                            <input type="date" class="form-control" id="course_sdate" name="course_sdate" required>
                        </div>

                        <!-- Course End Date -->
                        <div class="col-md-6">
                            <label for="course_edate" class="form-label">Course End Date:</label>
                            <input type="date" class="form-control" id="course_edate" name="course_edate" required>
                        </div>

                        <!-- Visibility Toggle -->
                        <div class="col-md-6 d-flex align-items-center pt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="visible" name="visible">
                                <label class="form-check-label ms-2" for="visible">Visible</label>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <hr class="mt-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('forms.index') }}" class="btn btn-secondary">
                            <i class="material-icons menu-icon">cancel</i> Cancel
                        </a>
                        <button class="btn btn-primary" type="submit">
                            <i class="material-icons menu-icon">send</i> Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var startDateInput = document.getElementById('course_sdate');
        var endDateInput = document.getElementById('course_edate');
        var form = document.getElementById('createForm');
        if (!startDateInput || !endDateInput || !form) return;

        var today = new Date().toISOString().split('T')[0];
        startDateInput.setAttribute('min', today);
        endDateInput.setAttribute('min', today);

        form.addEventListener('submit', function (e) {
            var startDate = new Date(startDateInput.value);
            var endDate = new Date(endDateInput.value);
            if (startDate > endDate) {
                e.preventDefault();
                alert('Course Start Date cannot be later than Course End Date.');
            }
        });

        startDateInput.addEventListener('change', function () {
            endDateInput.setAttribute('min', this.value);
        });
    });
</script>
@endpush
