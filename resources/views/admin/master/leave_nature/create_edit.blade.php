@extends('admin.layouts.master')

@section('title', isset($leaveNature) ? 'Edit Leave Nature' : 'Add Leave Nature')

@section('setup_content')
<div class="container-fluid">

    <x-breadcrum title="Leave Nature Master" />
    <x-session_message />

    <div class="card">
        <div class="card-body">

            <h4 class="card-title mb-3">
                {{ isset($leaveNature) ? 'Edit' : 'Add' }} Leave Nature
            </h4>
            <hr>

            <form method="POST" action="{{ route('master.leave-nature.store') }}">
                @csrf

                @if(isset($leaveNature))
                    <input type="hidden" name="id" value="{{ encrypt($leaveNature->pk) }}">
                @endif

                <div class="row">

                    <!-- Leave Type -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                Leave Type <span class="text-danger">*</span>
                            </label>
                            <select name="leave_type" class="form-select" required>
                                <option value="">Select Leave Type</option>
                                <option value="PT_EXEMPTION"
                                    {{ old('leave_type', $leaveNature->leave_type ?? '') === 'PT_EXEMPTION' ? 'selected' : '' }}>
                                    PT Exemption
                                </option>
                                <option value="STATIONED_LEAVE"
                                    {{ old('leave_type', $leaveNature->leave_type ?? '') === 'STATIONED_LEAVE' ? 'selected' : '' }}>
                                    Stationed Leave
                                </option>
                            </select>
                            @error('leave_type')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Nature Name -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                Nature Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="nature_name"
                                   class="form-control"
                                   value="{{ old('nature_name', $leaveNature->nature_name ?? '') }}"
                                   placeholder="Enter nature name (e.g. Medical, Injury)"
                                   maxlength="150"
                                   required>
                            @error('nature_name')
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
                                    {{ old('active_inactive', $leaveNature->active_inactive ?? 1) == 1 ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="2"
                                    {{ old('active_inactive', $leaveNature->active_inactive ?? 2) == 2 ? 'selected' : '' }}>
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
                        {{ isset($leaveNature) ? 'Update' : 'Submit' }}
                    </button>
                    <a href="{{ route('master.leave-nature.index') }}" class="btn btn-secondary">
                        Back
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection
