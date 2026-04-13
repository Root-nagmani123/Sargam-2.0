@php
    /** @var \App\Models\WordOfTheDay|null $word */
    $isEdit = isset($word) && $word;
@endphp

<form method="POST"
    action="{{ $isEdit ? route('admin.word-of-day.update', $word->id) : route('admin.word-of-day.store') }}"
    id="wordOfDayForm">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif
    {{-- Used to reopen the correct modal after validation errors --}}
    <input type="hidden" name="_wod_context" value="{{ $isEdit ? 'edit:'.$word->id : 'create' }}">

    <div class="mb-3">
        <label class="form-label fw-semibold">Hindi <span class="text-danger">*</span></label>
        <input type="text" name="hindi_text" class="form-control @error('hindi_text') is-invalid @enderror"
            value="{{ old('hindi_text', $word->hindi_text ?? '') }}" required maxlength="255"
            placeholder="e.g. अर्हक अंक">
        @error('hindi_text')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">English <span class="text-danger">*</span></label>
        <input type="text" name="english_text" class="form-control @error('english_text') is-invalid @enderror"
            value="{{ old('english_text', $word->english_text ?? '') }}" required maxlength="255"
            placeholder="e.g. Qualifying marks">
        @error('english_text')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">Sort order</label>
        <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
            value="{{ old('sort_order', $isEdit ? $word->sort_order : 0) }}" min="0" max="99999">
        @error('sort_order')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <small class="text-body-secondary">Lower numbers appear earlier in the daily rotation sequence.</small>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">Active</label>
        <select name="active_inactive" class="form-select @error('active_inactive') is-invalid @enderror" required>
            @php
                $activeVal = old('active_inactive', $isEdit ? ($word->active_inactive ? '1' : '0') : '1');
            @endphp
            <option value="1" {{ (string) $activeVal === '1' ? 'selected' : '' }}>Yes</option>
            <option value="0" {{ (string) $activeVal === '0' ? 'selected' : '' }}>No</option>
        </select>
        @error('active_inactive')
            <div class="invalid-feedback d-block">{{ $message }}</div>
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
