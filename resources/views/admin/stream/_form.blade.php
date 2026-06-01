@php
    /** @var \App\Models\Stream|null $stream */
    $isEdit = isset($stream) && $stream;
    $statusValue = old('status', $stream->status ?? 1);
@endphp

<form action="{{ $isEdit ? route('stream.update', $stream->pk) : route('stream.store') }}"
    method="POST"
    id="streamForm"
    class="stm-modal-form"
    novalidate>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="mb-3">
        <label for="stream_name" class="form-label cgt-field-label mb-2">
            Stream Name <span class="text-danger">*</span>
        </label>
        <input type="text"
            name="stream_name"
            id="stream_name"
            class="form-control rounded-2"
            placeholder="eg. Human Resources"
            value="{{ old('stream_name', $isEdit ? $stream->stream_name : '') }}"
            maxlength="100"
            autocomplete="off"
            required>
        <small class="text-danger d-none stm-field-error" data-field="stream_name"></small>
    </div>

    <div class="mb-4">
        <label for="status" class="form-label cgt-field-label mb-2">
            Status <span class="text-danger">*</span>
        </label>
        <select name="status" id="status" class="form-select rounded-2" required>
            <option value="" disabled {{ $statusValue === '' || $statusValue === null ? 'selected' : '' }}>Select Status</option>
            <option value="1" {{ (string) $statusValue === '1' ? 'selected' : '' }}>Active</option>
            <option value="0" {{ (string) $statusValue === '0' ? 'selected' : '' }}>Inactive</option>
        </select>
        <small class="text-danger d-none stm-field-error" data-field="status"></small>
    </div>

    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
        <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">
            Cancel
        </button>
        <button type="submit" class="btn btn-primary rounded-2 px-4" id="saveStreamForm">
            {{ $isEdit ? 'Update Stream' : 'Add Stream' }}
        </button>
    </div>
</form>
