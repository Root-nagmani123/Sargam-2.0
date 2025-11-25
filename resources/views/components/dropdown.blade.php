@props([
    'label' => 'Select Option',
    'items' => [],
    'id' => 'dropdown_'.uniqid(),
])

<div class="dropdown w-100 position-relative">
    <label class="form-label fw-semibold" for="{{ $id }}">{{ $label }}</label>

    <button 
        class="btn w-100 shadow-none text-start fw-semibold dropdown-toggle custom-dd d-flex justify-content-between align-items-center"
        type="button"
        id="{{ $id }}"
        data-bs-toggle="dropdown"
        aria-expanded="false" style="border-bottom: 2px solid #004a93;"
    >
        <span>{{ $label }}</span>
        <span class="ms-2">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:end;"><path d="M7 10l5 5 5-5" stroke="#004a93" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </span>
    </button>

    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="{{ $id }}" style="min-width:200px;">
        @foreach ($items as $item)
            <li>
                <button class="dropdown-item" type="button">
                    {{ $item }}
                </button>
            </li>
        @endforeach
    </ul>
</div>
