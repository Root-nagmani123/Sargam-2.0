@extends('admin.layouts.master')

@section('title', isset($building) ? 'Edit Building' : 'Add Building')

@section('setup_content')
<div class="container-fluid">

    <x-breadcrum title="Exam Building Master" />
    <x-session_message />

    <div class="card">
        <div class="card-body">

            <h4 class="card-title mb-3">
                {{ isset($building) ? 'Edit' : 'Add' }} Building
            </h4>
            <hr>

            <form method="POST" action="{{ route('master.exam_building.store') }}">
                @csrf

                @if(isset($building))
                    <input type="hidden" name="id" value="{{ encrypt($building->pk) }}">
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                Building Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="building_name"
                                   class="form-control"
                                   value="{{ old('building_name', $building->building_name ?? '') }}"
                                   placeholder="eg. Adhar Shila"
                                   required>
                            @error('building_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Short Name</label>
                            <input type="text"
                                   name="building_short_name"
                                   class="form-control"
                                   value="{{ old('building_short_name', $building->building_short_name ?? '') }}"
                                   placeholder="eg. AS">
                            @error('building_short_name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text"
                                   name="description"
                                   class="form-control"
                                   value="{{ old('description', $building->description ?? '') }}"
                                   placeholder="Enter description">
                            @error('description')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select name="active_inactive" class="form-select" required>
                                <option value="1"
                                    {{ old('active_inactive', $building->active_inactive ?? 1) == 1 ? 'selected' : '' }}>
                                    Active
                                </option>
                                <option value="2"
                                    {{ old('active_inactive', $building->active_inactive ?? 2) == 2 ? 'selected' : '' }}>
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
                        {{ isset($building) ? 'Update' : 'Submit' }}
                    </button>
                    <a href="{{ route('master.exam_building.index') }}" class="btn btn-secondary">
                        Back
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection
