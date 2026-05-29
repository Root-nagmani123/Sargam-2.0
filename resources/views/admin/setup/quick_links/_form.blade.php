@php
    /** @var \App\Models\QuickLink|null $quickLink */
    $isEdit = isset($quickLink) && $quickLink;
@endphp

<form method="POST"
    action="{{ $isEdit ? route('admin.setup.quick_links.update', encrypt($quickLink->id)) : route('admin.setup.quick_links.store') }}"
    id="quickLinkForm">
    @csrf

    <div class="mb-3">
        <label class="form-label cgt-field-label mb-2">
            Link Label <span class="text-danger">*</span>
        </label>
        <input type="text" name="label" class="form-control rounded-2" placeholder="eg. E-Office"
            value="{{ old('label', $quickLink->label ?? '') }}" required maxlength="255">
        @error('label')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label cgt-field-label mb-2">
            Link <span class="text-danger">*</span>
        </label>
        <input type="url" name="url" class="form-control rounded-2" placeholder="eg. https://eoffice.lbsnaa.gov.in/"
            value="{{ old('url', $quickLink->url ?? '') }}" required maxlength="2048">
        @error('url')
            <small class="text-danger d-block">{{ $message }}</small>
        @enderror
    </div>

    <div class="mb-4">
        <label class="form-label cgt-field-label mb-2">
            Open in <span class="text-danger">*</span>
        </label>
        <select name="target_blank" class="form-select rounded-2" required>
            @php
                $defaultTargetBlank = $quickLink->target_blank ?? true;
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

    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
        <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">
            Cancel
        </button>
        <button type="submit" class="btn btn-primary rounded-2 px-4">
            {{ $isEdit ? 'Update Link' : 'Create Link' }}
        </button>
    </div>
</form>
