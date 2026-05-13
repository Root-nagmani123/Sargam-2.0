{{--
  Icon + label picker (Bootstrap dropdown). Submits the same `name` as before (hidden input).
  @param string $selectedIcon
  @param string $name
  @param string|null $selectId       Unique id for the toggle button (required if multiple pickers)
  @param string|null $formSelect      If it contains form-select-sm, the trigger button uses btn-sm
  @param string|null $label
  @param string|null $labelClass
  @param bool $showHelp          When false, hides the hint under the picker
  @param string|null $wrapperClass Extra classes on the root div
  @param string|null $toggleClass   Extra classes on the dropdown trigger button
--}}
@php
    $name = $name ?? 'icon';
    $selectedIcon = $selectedIcon ?? 'bi-file-text';
    $selectId = $selectId ?? 'fcFormIconPicker';
    $formSelect = $formSelect ?? '';
    $label = $label ?? 'Form icon';
    $labelClass = $labelClass ?? 'form-label fw-semibold';
    $btnSm = str_contains($formSelect, 'form-select-sm') ? 'btn-sm' : '';
    $showHelp = $showHelp ?? true;
    $wrapperClass = $wrapperClass ?? '';
    $toggleClass = $toggleClass ?? '';

    $fcIconChoices = [
        'bi-file-text' => 'Document — generic',
        'bi-ui-checks-grid' => 'Form / fields grid',
        'bi-card-checklist' => 'Checklist',
        'bi-journal-text' => 'Notes / journal',
        'bi-clipboard-data' => 'Survey or data entry',
        'bi-pencil-square' => 'Edit / write',
        'bi-chat-left-text' => 'Feedback / comments',
        'bi-person-badge' => 'Profile / ID',
        'bi-person-fill' => 'Person / basic information',
        'bi-people' => 'People / group',
        'bi-mortarboard' => 'Education / training',
        'bi-building' => 'Organization / office',
        'bi-hospital' => 'Medical',
        'bi-heart-pulse' => 'Health',
        'bi-airplane' => 'Travel',
        'bi-bank' => 'Bank / finance',
        'bi-folder2-open' => 'Documents folder',
        'bi-file-earmark-pdf' => 'PDF',
        'bi-shield-check' => 'Compliance / verified',
        'bi-clock-history' => 'History / timeline',
        'bi-graph-up-arrow' => 'Reports / progress',
        'bi-calendar-event' => 'Schedule / event',
        'bi-envelope' => 'Email / message',
        'bi-telephone' => 'Phone',
        'bi-geo-alt' => 'Location',
        'bi-list-check' => 'Tasks / steps',
        'bi-award' => 'Certificate / award',
        'bi-megaphone' => 'Announcement',
        'bi-house-door' => 'Home / address',
        'bi-passport' => 'Registration / ID document',
        'bi-truck' => 'Logistics / arrival',
        'bi-wrench-adjustable' => 'Setup / tools',
    ];

    $selectedLabel = $fcIconChoices[$selectedIcon] ?? ('Saved icon: ' . $selectedIcon);
    $searchBlob = strtolower($selectedLabel . ' ' . $selectedIcon);
@endphp

<div class="fc-form-icon-picker {{ $wrapperClass }}" data-fc-icon-picker>
    <label class="{{ $labelClass }}" for="{{ $selectId }}">{{ $label }}</label>

    <input type="hidden"
           name="{{ $name }}"
           id="{{ $selectId }}_input"
           value="{{ $selectedIcon }}"
           data-fc-icon-input>

    <div class="dropdown w-100">
        <button type="button"
                class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center justify-content-between gap-2 w-100 text-start {{ $btnSm }} {{ $toggleClass }} @error($name) is-invalid @enderror"
                id="{{ $selectId }}"
                data-bs-toggle="dropdown"
                data-bs-display="static"
                aria-expanded="false"
                aria-haspopup="listbox"
                data-fc-icon-toggle>
            <span class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                <i class="bi {{ $selectedIcon }} fs-5 text-primary flex-shrink-0" data-fc-icon-toggle-icon></i>
                <span class="text-truncate" data-fc-icon-current-label>{{ $selectedLabel }}</span>
            </span>
        </button>

        <div class="dropdown-menu shadow p-0 mt-1 w-100"
             style="max-height: min(22rem, 70vh);"
             role="listbox"
             aria-labelledby="{{ $selectId }}">
            <div class="p-2 border-bottom bg-body position-sticky top-0" style="z-index: 2;">
                <input type="search"
                       class="form-control form-control-sm"
                       placeholder="Search by name…"
                       data-fc-icon-filter
                       autocomplete="off"
                       aria-label="Filter icons by name">
            </div>
            <ul class="list-unstyled mb-0 overflow-auto py-1" style="max-height: min(18rem, 55vh);">
                @if ($selectedIcon && ! array_key_exists($selectedIcon, $fcIconChoices))
                    <li data-fc-icon-option-row data-fc-icon-search="{{ e($searchBlob) }}">
                        <button type="button"
                                class="dropdown-item d-flex align-items-center gap-2 py-2"
                                data-fc-icon-option
                                data-value="{{ $selectedIcon }}"
                                data-label="{{ e('Saved: ' . $selectedIcon) }}"
                                role="option">
                            <i class="bi {{ $selectedIcon }} fs-5 text-primary flex-shrink-0" style="width: 1.75rem; text-align: center;"></i>
                            <span class="text-break">Saved: <code class="small">{{ $selectedIcon }}</code></span>
                        </button>
                    </li>
                @endif
                @foreach ($fcIconChoices as $class => $choiceLabel)
                    @php $rowSearch = strtolower($choiceLabel . ' ' . $class); @endphp
                    <li data-fc-icon-option-row data-fc-icon-search="{{ e($rowSearch) }}">
                        <button type="button"
                                class="dropdown-item d-flex align-items-center gap-2 py-2 {{ $selectedIcon === $class ? 'active' : '' }}"
                                data-fc-icon-option
                                data-value="{{ $class }}"
                                data-label="{{ e($choiceLabel) }}"
                                role="option"
                                aria-selected="{{ $selectedIcon === $class ? 'true' : 'false' }}">
                            <i class="bi {{ $class }} fs-5 text-primary flex-shrink-0" style="width: 1.75rem; text-align: center;"></i>
                            <span>{{ $choiceLabel }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    @if ($showHelp)
        <small class="text-muted d-block mt-1">Open the list to see each icon next to its name. You can search to narrow the list.</small>
    @endif
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

@once
    @push('scripts')
        <script>
            (function () {
                function setPickerValue(wrap, value, label) {
                    const input = wrap.querySelector('[data-fc-icon-input]');
                    const iconEl = wrap.querySelector('[data-fc-icon-toggle-icon]');
                    const labelEl = wrap.querySelector('[data-fc-icon-current-label]');
                    if (input) input.value = value;
                    if (iconEl) {
                        iconEl.className = 'bi ' + value + ' fs-5 text-primary flex-shrink-0';
                    }
                    if (labelEl) labelEl.textContent = label;
                    wrap.querySelectorAll('[data-fc-icon-option]').forEach(function (btn) {
                        const sel = btn.getAttribute('data-value') === value;
                        btn.classList.toggle('active', sel);
                        btn.setAttribute('aria-selected', sel ? 'true' : 'false');
                    });
                }

                function closeDropdown(toggleBtn) {
                    if (typeof bootstrap === 'undefined' || !bootstrap.Dropdown) return;
                    const dd = bootstrap.Dropdown.getInstance(toggleBtn);
                    if (dd) dd.hide();
                }

                document.addEventListener('click', function (e) {
                    const opt = e.target.closest('[data-fc-icon-option]');
                    if (!opt) return;
                    const wrap = opt.closest('[data-fc-icon-picker]');
                    const toggle = wrap && wrap.querySelector('[data-fc-icon-toggle]');
                    if (!wrap || !toggle) return;
                    e.preventDefault();
                    setPickerValue(wrap, opt.getAttribute('data-value'), opt.getAttribute('data-label') || opt.getAttribute('data-value'));
                    closeDropdown(toggle);
                    const filter = wrap.querySelector('[data-fc-icon-filter]');
                    if (filter) filter.value = '';
                    wrap.querySelectorAll('[data-fc-icon-option-row]').forEach(function (row) {
                        row.classList.remove('d-none');
                    });
                });

                document.addEventListener('input', function (e) {
                    if (!e.target.matches('[data-fc-icon-filter]')) return;
                    const wrap = e.target.closest('[data-fc-icon-picker]');
                    if (!wrap) return;
                    var q = (e.target.value || '').toLowerCase().trim();
                    wrap.querySelectorAll('[data-fc-icon-option-row]').forEach(function (row) {
                        var hay = (row.getAttribute('data-fc-icon-search') || '').toLowerCase();
                        row.classList.toggle('d-none', q.length > 0 && hay.indexOf(q) === -1);
                    });
                });
            })();
        </script>
    @endpush
@endonce
