@extends('layouts.app')
@section('title', 'Medical Details – {{ $username }}')
@section('content')
<div class="row justify-content-center">
<div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white py-3 px-4">
            <h5 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-heart-pulse me-2"></i>Medical Details
            </h5>
            <small class="text-muted">Officer Trainee: <strong>{{ $student?->full_name ?? $username }}</strong> ({{ $username }})</small>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('fc-reg.admin.joining.medical.save', $username) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Height (cm)</label>
                        <input type="number" name="height_cm" class="form-control"
                               value="{{ old('height_cm', $medical?->height_cm) }}" min="50" max="300">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Weight (kg)</label>
                        <input type="number" name="weight_kg" class="form-control"
                               value="{{ old('weight_kg', $medical?->weight_kg) }}" min="20" max="300">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Blood Group</label>
                        <select name="blood_group" class="form-select">
                            <option value="">Select…</option>
                            @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                                <option value="{{ $bg }}" {{ old('blood_group', $medical?->blood_group) == $bg ? 'selected' : '' }}>{{ $bg }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Blood Pressure</label>
                        <input type="text" name="blood_pressure" class="form-control" placeholder="e.g. 120/80"
                               value="{{ old('blood_pressure', $medical?->blood_pressure) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Fitness Status <span class="text-danger">*</span></label>
                        <select name="is_fit" class="form-select" required>
                            <option value="1" {{ old('is_fit', $medical?->is_fit ?? 1) == 1 ? 'selected' : '' }}>Fit</option>
                            <option value="0" {{ old('is_fit', $medical?->is_fit ?? 1) == 0 ? 'selected' : '' }}>Not Fit</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Examined Date</label>
                        <input type="date" name="examined_date" class="form-control"
                               value="{{ old('examined_date', $medical?->examined_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Examined By (Doctor's Name)</label>
                        <input type="text" name="examined_by" class="form-control"
                               value="{{ old('examined_by', $medical?->examined_by) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Medical Remarks</label>
                        <textarea name="medical_remarks" class="form-control" rows="3">{{ old('medical_remarks', $medical?->medical_remarks) }}</textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-between pt-3 mt-3 border-top">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Save Medical Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
@endsection
