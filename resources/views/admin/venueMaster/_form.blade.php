@php
    /** @var \App\Models\VenueMaster|null $venue */
    $isEdit = isset($venue) && $venue;
@endphp

<form method="POST"
    action="{{ $isEdit ? route('Venue-Master.update', $venue->venue_id) : route('Venue-Master.store') }}"
    id="venueMasterForm">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="mb-3">
        <label for="venue_name" class="form-label cgt-field-label mb-2">
            Venue Name <span class="text-danger">*</span>
        </label>
        <input type="text"
            name="venue_name"
            id="venue_name"
            class="form-control rounded-2"
            placeholder="eg. George Everest Bunglow"
            value="{{ old('venue_name', $venue->venue_name ?? '') }}"
            required
            maxlength="255">
        <small class="text-danger d-none vm-field-error" data-field="venue_name"></small>
    </div>

    <div class="mb-3">
        <label for="venue_short_name" class="form-label cgt-field-label mb-2">
            Short Name <span class="text-danger">*</span>
        </label>
        <input type="text"
            name="venue_short_name"
            id="venue_short_name"
            class="form-control rounded-2"
            placeholder="eg. GEB"
            value="{{ old('venue_short_name', $venue->venue_short_name ?? '') }}"
            required
            maxlength="100">
        <small class="text-danger d-none vm-field-error" data-field="venue_short_name"></small>
    </div>

    <div class="mb-4">
        <label for="description" class="form-label cgt-field-label mb-2">
            Description <span class="text-danger">*</span>
        </label>
        <textarea name="description"
            id="description"
            class="form-control rounded-2"
            rows="3"
            placeholder="eg. Lorem ipsum dolor sit amet">{{ old('description', $venue->description ?? '') }}</textarea>
        <small class="text-danger d-none vm-field-error" data-field="description"></small>
    </div>

    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
        <button type="button" class="btn btn-outline-primary rounded-2 px-4" data-bs-dismiss="modal">
            Cancel
        </button>
        <button type="submit" class="btn btn-primary rounded-2 px-4">
            {{ $isEdit ? 'Update Venue' : 'Create Venue' }}
        </button>
    </div>
</form>
