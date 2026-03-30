@extends('admin.layouts.master')
@section('title', 'Create Useful Link - Sargam | Lal Bahadur Shastri')
@section('setup_content')
    <div class="container-fluid">
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h4 class="mb-0">Create Useful Link</h4>
                    <a href="{{ route('admin.setup.useful_links.index') }}" class="btn btn-outline-secondary">
                        Back
                    </a>
                </div>

                <form method="POST" action="{{ route('admin.setup.useful_links.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Label <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="label" class="form-control"
                            placeholder="e.g. Employee Handbook" value="{{ old('label') }}" required maxlength="255">
                        @error('label')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">URL</label>
                        <input type="url" name="url" class="form-control"
                            placeholder="https://example.com" value="{{ old('url') }}" maxlength="2048">
                        <small class="text-muted">URL ya File me se kam se kam ek dena zaroori hai.</small>
                        @error('url')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">File Upload</label>
                        <input type="file" name="file" class="form-control"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                        <small class="text-muted">Allowed: PDF, Image, DOC, XLS, PPT (max 10 MB)</small>
                        @error('file')
                            <small class="text-danger d-block">{{ $message }}</small>
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

