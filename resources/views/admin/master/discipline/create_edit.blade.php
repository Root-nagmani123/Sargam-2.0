@extends('admin.layouts.master')

@section('title', isset($discipline) ? 'Edit Discipline' : 'Add Discipline')

@section('setup_content')
<div class="container-fluid">

    <x-breadcrum title="Discipline Master" />
    <x-session_message />

    <div class="card">
        <div class="card-body">

            <h4 class="card-title mb-3">
                {{ isset($discipline) ? 'Edit' : 'Add' }} Discipline
            </h4>
            <hr>

            <form method="POST" action="{{ route('master.discipline.store') }}">
                @csrf

                @if(isset($discipline))
                    <input type="hidden" name="id" value="{{ encrypt($discipline->pk) }}">
                @endif

                <div class="row">

                    <!-- Course -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                Course <span class="text-danger">*</span>
                            </label>
                            <select name="course_master_pk" class="form-select" required>
                                <option value="">Select Course</option>
                                @foreach($courses as $c)
                                    <option value="{{ $c->pk }}"
                                        {{ old('course_master_pk', $discipline->course_master_pk ?? '') == $c->pk ? 'selected' : '' }}>
                                        {{ $c->course_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_master_pk')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Discipline Name -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                Discipline Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="discipline_name"
                                   class="form-control"
                                   value="{{ old('discipline_name', $discipline->discipline_name ?? '') }}"
                                   placeholder="Enter discipline name"
                                   required>
                            @error('discipline_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                </div>

                <div class="row">

                    <!-- Mark Deduction -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                Mark Deduction <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   step="0.01"
                                   name="mark_diduction"
                                   class="form-control"
                                   value="{{ old('mark_diduction', $discipline->mark_diduction ?? '') }}"
                                   placeholder="Enter mark deduction"
                                   required>
                            @error('mark_diduction')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select name="active_inactive" class="form-select" required>
                                <option value="1"
                                    {{ old('active_inactive', $discipline->active_inactive ?? 1) == 1 ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="2"
                                    {{ old('active_inactive', $discipline->active_inactive ?? 2) == 2 ? 'selected' : '' }}>
                                    Inactive
                                </option>
                            </select>
                            @error('active_inactive')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                </div>

                <hr>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        {{ isset($discipline) ? 'Update' : 'Submit' }}
                    </button>
                    <a href="{{ route('master.discipline.index') }}" class="btn btn-secondary">
                        Back
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection
