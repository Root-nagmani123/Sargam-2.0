@extends('admin.layouts.master')

@section('title', isset($floor) ? 'Edit Floor' : 'Add Floor')

@section('setup_content')
<div class="container-fluid">

    <x-breadcrum title="Exam Floor Master" />
    <x-session_message />

    <div class="card">
        <div class="card-body">

            <h4 class="card-title mb-3">
                {{ isset($floor) ? 'Edit' : 'Add' }} Floor
            </h4>
            <hr>

            <form method="POST" action="{{ route('master.exam_floor.store') }}">
                @csrf

                @if(isset($floor))
                    <input type="hidden" name="id" value="{{ encrypt($floor->pk) }}">
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                Floor Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="floor_name"
                                   class="form-control"
                                   value="{{ old('floor_name', $floor->floor_name ?? '') }}"
                                   placeholder="eg. Ground Floor"
                                   required>
                            @error('floor_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number"
                                   min="0"
                                   name="display_order"
                                   class="form-control"
                                   value="{{ old('display_order', $floor->display_order ?? '') }}"
                                   placeholder="eg. 1">
                            @error('display_order')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select name="active_inactive" class="form-select" required>
                                <option value="1"
                                    {{ old('active_inactive', $floor->active_inactive ?? 1) == 1 ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="2"
                                    {{ old('active_inactive', $floor->active_inactive ?? 2) == 2 ? 'selected' : '' }}>
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
                        {{ isset($floor) ? 'Update' : 'Submit' }}
                    </button>
                    <a href="{{ route('master.exam_floor.index') }}" class="btn btn-secondary">
                        Back
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection
