@php
    /** @var \App\Models\UsefulLink|null $usefulLink */
    $isEdit = isset($usefulLink) && $usefulLink;
    // The listing loads this over AJAX into a modal and supplies its own footer
    // buttons context; create.blade.php / edit.blade.php render it in-page.
    $inModal = $inModal ?? true;
@endphp

{{-- ⚠️ Shared by the modal AND the standalone create/edit pages, so every class
     used here is scoped to BOTH .ul-modal and .ul-page in
     useful-links-admin.css — see the trap in docs/new-design-index-page.md §3c. --}}
<form method="POST"
      action="{{ $isEdit ? route('admin.setup.useful_links.update', encrypt($usefulLink->id)) : route('admin.setup.useful_links.store') }}"
      id="usefulLinkForm" enctype="multipart/form-data" novalidate
      data-has-existing-file="{{ $isEdit && !empty($usefulLink->file_path) ? '1' : '0' }}">
    @csrf

    <div class="ul-field-card ul-form-grid">
        <div class="form-group ul-form-grid--full">
            <label class="ul-form-label" for="usefulLinkLabel">Label<span class="ul-req">*</span></label>
            <input type="text" name="label" id="usefulLinkLabel" class="form-control ul-control"
                   placeholder="e.g. Employee Handbook" maxlength="255" required
                   value="{{ old('label', $usefulLink->label ?? '') }}">
            @error('label')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        {{-- URL and File are a pair: the controller requires one of the two, so
             they sit side by side with a single shared error line under them. --}}
        <div class="form-group">
            <label class="ul-form-label" for="usefulLinkUrl">URL</label>
            <input type="url" name="url" id="usefulLinkUrl" class="form-control ul-control"
                   placeholder="https://example.com" maxlength="2048"
                   value="{{ old('url', $usefulLink->url ?? '') }}">
            @error('url')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="ul-form-label" for="usefulLinkFile">File upload</label>
            <input type="file" name="file" id="usefulLinkFile" class="form-control ul-control"
                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
            <p class="ul-form-help">PDF, image, DOC, XLS or PPT — up to 10 MB.</p>
            @error('file')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group ul-form-grid--full">
            <span class="ul-pair-error">@error('url_or_file'){{ $message }}@enderror</span>
            <p class="ul-form-help mt-0">Give a URL, a file, or both — at least one is required.</p>

            @if ($isEdit && !empty($usefulLink->file_path))
                <a href="{{ asset('storage/' . $usefulLink->file_path) }}" class="ul-current-file"
                   target="_blank" rel="noopener">
                    <i class="bi bi-paperclip" aria-hidden="true"></i>{{ basename($usefulLink->file_path) }}
                </a>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_file" id="removeFile" value="1">
                    <label class="form-check-label" for="removeFile">Remove the current file</label>
                </div>
            @endif
        </div>

        <div class="form-group">
            <label class="ul-form-label" for="usefulLinkTarget">Opens in<span class="ul-req">*</span></label>
            @php $defaultTargetBlank = $usefulLink->target_blank ?? true; @endphp
            <select name="target_blank" id="usefulLinkTarget" class="form-select ul-control" required>
                <option value="1" @selected((string) old('target_blank', $defaultTargetBlank ? '1' : '0') === '1')>New Tab</option>
                <option value="0" @selected((string) old('target_blank', $defaultTargetBlank ? '1' : '0') === '0')>Same Tab</option>
            </select>
            @error('target_blank')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="{{ $inModal ? 'ul-modal-footer' : 'ul-form-footer' }}">
        @if ($inModal)
            <button type="button" class="btn ul-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        @else
            <a href="{{ route('admin.setup.useful_links.index') }}" class="btn ul-btn-cancel">Cancel</a>
        @endif
        <button type="submit" class="btn ul-btn-submit">{{ $isEdit ? 'Update Link' : 'Save Link' }}</button>
    </div>
</form>

{{-- No <script> here on purpose. The listing owns submit handling for the modal
     — it has to close the modal and reload the grid, and its handler is
     delegated on document so it also survives the form being re-loaded. The
     standalone create/edit pages post normally and are validated server-side. --}}
