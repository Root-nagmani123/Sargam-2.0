@props(['forms', 'selectId' => null, 'colClass' => 'col-md-3'])

<div class="{{ $colClass }}">
    <label class="form-label small mb-1">Form</label>
    <select name="form_id" @if($selectId) id="{{ $selectId }}" @endif class="form-select form-select-sm" data-placeholder="All Forms">
        <option value="">All Forms</option>
        @foreach($forms as $form)
            <option value="{{ $form->id }}" {{ (string) request('form_id') === (string) $form->id ? 'selected' : '' }}>
                {{ $form->form_name }}@if($form->courseMaster?->course_name) — {{ $form->courseMaster->course_name }}@endif
            </option>
        @endforeach
    </select>
</div>
