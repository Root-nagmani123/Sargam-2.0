@extends('admin.layouts.master')
@section('title', 'Create Quick Link - Sargam | Lal Bahadur Shastri')
@section('setup_content')
    <div class="container-fluid">
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h4 class="mb-0">Create Quick Link</h4>
                    <a href="{{ route('admin.setup.quick_links.index') }}" class="btn btn-outline-secondary">
                        Back
                    </a>
                </div>

                <form method="POST" action="{{ route('admin.setup.quick_links.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Label <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="label" class="form-control"
                            placeholder="e.g. E-Office" value="{{ old('label') }}" required maxlength="255">
                        @error('label')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            URL <span class="text-danger">*</span>
                        </label>
                        <input type="url" name="url" class="form-control"
                            placeholder="https://example.com" value="{{ old('url') }}" required maxlength="2048">
                        @error('url')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Open In <span class="text-danger">*</span>
                        </label>
                        <select name="target_blank" class="form-select" required>
                            <option value="1" {{ old('target_blank', '1') == '1' ? 'selected' : '' }}>
                                New Tab
                            </option>
                            <option value="0" {{ old('target_blank', '1') == '0' ? 'selected' : '' }}>
                                Same Tab
                            </option>
                        </select>
                        @error('target_blank')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

