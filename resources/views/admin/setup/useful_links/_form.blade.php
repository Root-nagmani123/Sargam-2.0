@php
    /** @var \App\Models\UsefulLink|null $usefulLink */
    $isEdit = isset($usefulLink) && $usefulLink;
@endphp

<form method="POST"
    action="{{ $isEdit ? route('admin.setup.useful_links.update', encrypt($usefulLink->id)) : route('admin.setup.useful_links.store') }}"
    id="usefulLinkForm" enctype="multipart/form-data">
    @csrf

    <div class="mb-3">
        <label class="form-label fw-semibold">
            Label <span class="text-danger">*</span>
        </label>
        <input type="text" name="label" class="form-control" placeholder="e.g. Employee Handbook"
            value="{{ old('label', $usefulLink->label ?? '') }}" required maxlength="255">
        @error('label')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">
            URL
        </label>
        <input type="url" name="url" class="form-control" placeholder="https://example.com"
            value="{{ old('url', $usefulLink->url ?? '') }}" maxlength="2048">
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

        @if ($isEdit && !empty($usefulLink->file_path))
            <div class="mt-2">
                <a href="{{ asset('storage/' . $usefulLink->file_path) }}" target="_blank"
                    class="btn btn-sm btn-outline-primary">
                    View Current File
                </a>
            </div>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="remove_file" id="removeFile" value="1">
                <label class="form-check-label" for="removeFile">
                    Remove current file
                </label>
            </div>
        @endif
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">
            Open In <span class="text-danger">*</span>
        </label>
        <select name="target_blank" class="form-select" required>
            @php
                $defaultTargetBlank = $usefulLink->target_blank ?? true;
            @endphp
            <option value="1" {{ (string) old('target_blank', $defaultTargetBlank ? '1' : '0') === '1' ? 'selected' : '' }}>
                New Tab
            </option>
            <option value="0" {{ (string) old('target_blank', $defaultTargetBlank ? '1' : '0') === '0' ? 'selected' : '' }}>
                Same Tab
            </option>
        </select>
        @error('target_blank')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            Close
        </button>
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? 'Update' : 'Save' }}
        </button>
    </div>
</form>

