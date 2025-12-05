@extends('admin.layouts.master')

@section('title', 'Exemption Medical Speciality Master')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Exemption Medical Speciality Master" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ isset($speciality) ? 'Edit' : 'Add' }} Exemption Medical Speciality
            </h4>
            <hr>
            <form method="POST" action="{{ route('master.exemption.medical.speciality.store') }}">
                @csrf
                @if(isset($speciality))
                <input type="hidden" name="id" value="{{ encrypt($speciality->pk) }}">
                @endif
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="medical_speciality_name" class="form-label">Speciality Name</label>
                            <input type="text" name="medical_speciality_name" class="form-control"
                                value="{{ old('medical_speciality_name', $speciality->speciality_name ?? '') }}"
                                required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="active_inactive" class="form-label">Status</label>
                            <select name="active_inactive" class="form-select">
                                <option value="1"
                                    {{ (old('active_inactive', $speciality->active_inactive ?? 1) == 1) ? 'selected' : '' }}>
                                    Active</option>
                                <option value="0"
                                    {{ (old('active_inactive', $speciality->active_inactive ?? 1) == 0) ? 'selected' : '' }}>
                                    Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                    {{ isset($speciality) ? 'Update' : 'Submit' }}
                </button>
                <a href="{{ route('master.exemption.medical.speciality.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>

@endsection