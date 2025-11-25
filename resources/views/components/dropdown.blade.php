@props([
    'label' => 'Select Option',
    'items' => [],
    'id' => 'dd_'.uniqid(),
])

<div class="modern-bottom-dd mb-3">

    <button
        id="{{ $id }}"
        class="dd-trigger w-100 d-flex justify-content-between align-items-center"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        aria-haspopup="true"
        type="button"
    >
        <span class="dd-text">{{ $label }}</span>

        <span class="dd-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M7 10l5 5 5-5" stroke="#004a93" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round"/>
            </svg>
        </span>
    </button>

    <ul class="dropdown-menu w-100 dd-menu" aria-labelledby="{{ $id }}">
        @foreach ($items as $item)
        <li>
            <button class="dropdown-item dd-menu-item" type="button">{{ $item }}</button>
        </li>
        @endforeach
    </ul>
</div>
