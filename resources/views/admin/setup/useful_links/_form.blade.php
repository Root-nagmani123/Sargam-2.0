@php
    /** @var \App\Models\UsefulLink|null $usefulLink */
    $isEdit = isset($usefulLink) && $usefulLink;
@endphp

<form method="POST"
    action="{{ $isEdit ? route('admin.setup.useful_links.update', encrypt($usefulLink->id)) : route('admin.setup.useful_links.store') }}"
    id="usefulLinkForm" enctype="multipart/form-data"
    data-has-existing-file="{{ $isEdit && !empty($usefulLink->file_path) ? '1' : '0' }}">
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
        <input type="url" name="url" id="usefulLinkUrl" class="form-control" placeholder="https://example.com"
            value="{{ old('url', $usefulLink->url ?? '') }}" maxlength="2048">
        @error('url')
            <small class="text-danger d-block">{{ $message }}</small>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">File Upload</label>
        <input type="file" name="file" id="usefulLinkFile" class="form-control"
            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
        <small class="text-muted">Allowed: PDF, Image, DOC, XLS, PPT (max 10 MB)</small>
        @error('file')
            <small class="text-danger d-block">{{ $message }}</small>
        @enderror
        @error('url_or_file')
            <small class="text-danger d-block">{{ $message }}</small>
        @enderror
        <small id="urlFileValidationError" class="text-danger d-block mt-1"></small>

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

<script>
    (function() {
        const form = document.getElementById('usefulLinkForm');
        const urlInput = document.getElementById('usefulLinkUrl');
        const fileInput = document.getElementById('usefulLinkFile');
        const removeFileCheckbox = document.getElementById('removeFile');
        const errorEl = document.getElementById('urlFileValidationError');
        const hasExistingFile = form.dataset.hasExistingFile === '1';

        if (!form || !urlInput || !fileInput || !errorEl) {
            return;
        }

        const clearValidationError = () => {
            urlInput.classList.remove('is-invalid');
            fileInput.classList.remove('is-invalid');
            errorEl.textContent = '';
        };

        const hasNewFile = () => fileInput.files && fileInput.files.length > 0;
        const hasUrl = () => urlInput.value.trim() !== '';
        const keepsExistingFile = () => hasExistingFile && (!removeFileCheckbox || !removeFileCheckbox.checked);

        const isUrlOrFileProvided = () => hasUrl() || hasNewFile() || keepsExistingFile();

        const validateUrlOrFile = () => {
            clearValidationError();

            if (isUrlOrFileProvided()) {
                return true;
            }

            urlInput.classList.add('is-invalid');
            fileInput.classList.add('is-invalid');
            errorEl.textContent = 'Please provide at least one: URL or File Upload.';
            return false;
        };

        form.addEventListener('submit', function(event) {
            if (!validateUrlOrFile()) {
                event.preventDefault();
            }
        });

        [urlInput, fileInput, removeFileCheckbox].forEach((input) => {
            if (!input) return;
            input.addEventListener('change', clearValidationError);
            input.addEventListener('input', clearValidationError);
        });
    })();
</script>

