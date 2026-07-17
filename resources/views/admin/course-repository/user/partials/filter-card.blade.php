@include('admin.partials.choices-bootstrap5')

@php
    $sectors = $sectors ?? collect();
    $ministries = $ministries ?? collect();
    // Optional: when a documents table is present, its column show/hide control
    // is rendered inline in this toolbar. Hidden entirely when no table exists.
    $columnToggle = $columnToggle ?? null;
@endphp

<!-- Filter Card Partial -->
<div class="card filter-card border-0 shadow-sm rounded-4 mb-4 choices-bs-scope" id="cruFilterCard">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="{{ $route }}" id="filterForm" novalidate>
            @php
                $cruHasMoreFilters = !empty($filters['faculty']) || !empty($filters['sector']) || !empty($filters['ministry']);
                $cruHasSearch = !empty($filters['search']);
            @endphp

            <div class="cru-filter-row cru-filter-toolbar d-flex flex-wrap align-items-center gap-2">
                <span class="cru-filter-lead text-secondary d-none d-md-inline-flex align-items-center gap-1 me-1">
                    <span>Filters</span>
                </span>

                {{-- Date --}}
                <div class="cru-filter-col cru-filter-pill cru-filter-pill-date">
                    <label for="filter_date" class="visually-hidden">Date</label>
                    <div class="input-group input-group-sm cru-input-group">
                        <input type="date"
                               class="form-control"
                               id="filter_date"
                               name="date"
                               value="{{ $filters['date'] ?? '' }}"
                               placeholder="Date">
                    </div>
                </div>

                {{-- Course --}}
                <div class="cru-filter-col cru-filter-pill">
                    <label for="filter_course" class="visually-hidden">Course</label>
                    <select class="form-select form-select-sm js-cru-filter-choice" id="filter_course" name="course" data-placeholder="Course">
                        <option value="">Course</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->pk }}" {{ ($filters['course'] ?? '') == $course->pk ? 'selected' : '' }}>
                                {{ $course->course_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Subject --}}
                <div class="cru-filter-col cru-filter-pill cru-filter-pill-wide">
                    <label for="filter_subject" class="visually-hidden">Subject</label>
                    <select class="form-select form-select-sm js-cru-filter-choice" id="filter_subject" name="subject" data-placeholder="Subject">
                        <option value="">Subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->pk }}" {{ ($filters['subject'] ?? '') == $subject->pk ? 'selected' : '' }}>
                                {{ $subject->subject_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Week --}}
                <div class="cru-filter-col cru-filter-pill cru-filter-pill-sm">
                    <label for="filter_week" class="visually-hidden">Week</label>
                    <select class="form-select form-select-sm js-cru-filter-choice" id="filter_week" name="week" data-placeholder="Week">
                        <option value="">Week</option>
                        @for($i = 1; $i <= 52; $i++)
                            <option value="{{ $i }}" {{ ($filters['week'] ?? '') == $i ? 'selected' : '' }}>
                                Week {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>

                {{-- Reset --}}
                <button type="button"
                        class="btn btn-outline-danger btn-sm fw-semibold px-3 cru-reset-btn"
                        id="cruFilterReset">
                    Reset Filters
                </button>

                {{-- Overflow filters (Faculty / Sector / Ministry) --}}
                <div class="cru-more-filters position-relative">
                    <button type="button"
                            class="btn btn-link btn-sm text-decoration-none fw-semibold p-0 cru-more-filters-toggle"
                            data-bs-toggle="collapse"
                            data-bs-target="#cruMoreFilters"
                            aria-expanded="{{ $cruHasMoreFilters ? 'true' : 'false' }}"
                            aria-controls="cruMoreFilters">
                        +3 Filters
                    </button>
                    <div class="collapse cru-more-filters-panel {{ $cruHasMoreFilters ? 'show' : '' }}" id="cruMoreFilters">
                        <div class="card cru-more-filters-card border-0 shadow rounded-4">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="fw-semibold text-dark">Filters</span>
                                </div>
                                <hr class="cru-colvis-divider my-2">

                                {{-- Faculty --}}
                                <div class="cru-filter-col mb-2">
                                    <select class="form-select form-select-sm js-cru-filter-choice" id="filter_faculty" name="faculty" data-placeholder="Faculty">
                                        <option value="">Faculty</option>
                                        @foreach($faculties as $faculty)
                                            <option value="{{ $faculty->pk }}" {{ ($filters['faculty'] ?? '') == $faculty->pk ? 'selected' : '' }}>
                                                {{ $faculty->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Sector --}}
                                <div class="cru-filter-col mb-2">
                                    <select class="form-select form-select-sm js-cru-filter-choice js-cru-filter-sector"
                                            id="filter_sector"
                                            name="sector"
                                            required
                                            aria-required="true"
                                            data-placeholder="Sector *">
                                        <option value="">Sector *</option>
                                        @foreach($sectors as $sector)
                                            <option value="{{ $sector->pk }}" {{ ($filters['sector'] ?? '') == $sector->pk ? 'selected' : '' }}>
                                                {{ $sector->sector_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Ministry --}}
                                <div class="cru-filter-col mb-0">
                                    <select class="form-select form-select-sm js-cru-filter-choice js-cru-filter-ministry"
                                            id="filter_ministry"
                                            name="ministry"
                                            required
                                            aria-required="true"
                                            data-placeholder="Ministry *"
                                            @if(empty($filters['sector'])) disabled @endif>
                                        <option value="">Ministry *</option>
                                        @foreach($ministries as $ministry)
                                            <option value="{{ $ministry->pk }}" {{ ($filters['ministry'] ?? '') == $ministry->pk ? 'selected' : '' }}>
                                                {{ $ministry->ministry_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right side: column show/hide (only when a table is present) + free-text search --}}
                <div class="cru-toolbar-right d-flex align-items-center gap-2 ms-md-auto">
                    @if(!empty($columnToggle) && !empty($columnToggle['columns']))
                        @include('admin.course-repository.user.partials.table-column-toggle', [
                            'cruTableId' => $columnToggle['tableId'],
                            'cruColumnStorageKey' => $columnToggle['storageKey'],
                            'cruColumns' => $columnToggle['columns'],
                        ])
                    @endif
                    <div class="collapse collapse-horizontal cru-search-collapse {{ $cruHasSearch ? 'show' : '' }}" id="cruSearchCollapse">
                        <label for="filter_search" class="visually-hidden">Search documents</label>
                        <div class="input-group input-group-sm cru-search-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </span>
                            <input type="search"
                                   class="form-control border-start-0 ps-0"
                                   id="filter_search"
                                   name="search"
                                   value="{{ $filters['search'] ?? '' }}"
                                   placeholder="Search by keyword, topic, author…"
                                   autocomplete="off"
                                   aria-label="Search documents">
                        </div>
                    </div>
                    <button type="button"
                            class="btn btn-light border btn-sm cru-btn-search-icon"
                            data-bs-toggle="collapse"
                            data-bs-target="#cruSearchCollapse"
                            aria-expanded="{{ $cruHasSearch ? 'true' : 'false' }}"
                            aria-controls="cruSearchCollapse"
                            aria-label="Toggle search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
    </form>
    </div>
</div>

@push('styles')
<style>
    #cruFilterCard.filter-card .cru-filter-row {
        --cru-filter-control-h: 2rem;
    }

    #cruFilterCard.filter-card .cru-filter-col {
        min-width: 0;
        max-width: 100%;
    }

    #cruFilterCard.filter-card .cru-filter-col .choices,
    #cruFilterCard.filter-card .cru-filter-col .input-group,
    #cruFilterCard.filter-card .cru-filter-col .form-control,
    #cruFilterCard.filter-card .cru-filter-col .form-select {
        width: 100%;
        max-width: 100%;
    }

    /* Lock the whole Choices control to one row so it never grows after a
       value is selected (placeholder, selected, focused and open states all
       resolve to the same height). */
    #cruFilterCard.filter-card .choices,
    #cruFilterCard.filter-card .choices[data-type*="select-one"] {
        height: var(--cru-filter-control-h) !important;
        min-height: var(--cru-filter-control-h) !important;
        margin-bottom: 0 !important;
    }

    #cruFilterCard.filter-card .choices .choices__inner {
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        width: 100% !important;
        min-height: var(--cru-filter-control-h) !important;
        height: var(--cru-filter-control-h) !important;
        max-height: var(--cru-filter-control-h) !important;
        padding: 0 2rem 0 0.5rem !important;
        font-size: 0.875rem !important;
        line-height: 1.25rem !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
    }

    #cruFilterCard.filter-card .choices__list--single {
        display: flex !important;
        align-items: center !important;
        flex: 1 1 auto !important;
        width: 100% !important;
        min-width: 0 !important;
        height: 100% !important;
        padding: 0 !important;
    }

    #cruFilterCard.filter-card .choices__list--single .choices__item,
    #cruFilterCard.filter-card .choices__list--single .choices__placeholder {
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        line-height: 1.25rem !important;
    }

    /* The inline search field Choices injects for single-selects must not add a
       second line (the usual cause of the height jump after selection). */
    #cruFilterCard.filter-card .choices[data-type*="select-one"] .choices__inner .choices__input {
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        line-height: 1.25rem !important;
    }

    /* Open dropdown menu keeps the field width; long option names (e.g. course
       names longer than the field) wrap to a second line instead of clipping. */
    #cruFilterCard.filter-card .choices__list--dropdown,
    #cruFilterCard.filter-card .choices__list[aria-expanded] {
        width: 100% !important;
    }

    #cruFilterCard.filter-card .choices__list--dropdown .choices__item,
    #cruFilterCard.filter-card .choices__list[aria-expanded] .choices__item {
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-break: break-word !important;
        line-height: 1.25rem !important;
    }

    #cruFilterCard.filter-card .input-group .form-control {
        min-height: var(--cru-filter-control-h);
        height: var(--cru-filter-control-h);
    }

    @media (min-width: 992px) {
        #cruFilterCard.filter-card .cru-filter-row .cru-filter-col.col-lg {
            flex: 1 1 0;
            min-width: 0;
            max-width: 100%;
        }
    }

    /* ---- Compact pill toolbar (reference design) ---- */
    #cruFilterCard.filter-card .cru-filter-toolbar {
        row-gap: 0.5rem;
    }

    #cruFilterCard.filter-card .cru-filter-lead {
        font-size: 0.875rem;
    }

    #cruFilterCard.filter-card .cru-filter-lead .bi {
        color: var(--cru-primary);
    }

    #cruFilterCard.filter-card .cru-filter-toolbar .cru-filter-pill {
        flex: 0 0 auto;
        width: 9.5rem;
    }

    #cruFilterCard.filter-card .cru-filter-toolbar .cru-filter-pill-date {
        width: 9.5rem;
    }

    #cruFilterCard.filter-card .cru-filter-toolbar .cru-filter-pill-wide {
        width: 11rem;
    }

    #cruFilterCard.filter-card .cru-filter-toolbar .cru-filter-pill-sm {
        width: 7.5rem;
    }

    #cruFilterCard.filter-card .cru-reset-btn {
        flex: 0 0 auto;
        border-radius: var(--cru-radius);
        white-space: nowrap;
    }

    #cruFilterCard.filter-card .cru-more-filters-toggle {
        flex: 0 0 auto;
        color: var(--cru-primary);
        white-space: nowrap;
    }

    #cruFilterCard.filter-card .cru-more-filters-toggle:hover {
        color: var(--cru-primary-hover);
        text-decoration: underline !important;
    }

    /* Floating overflow panel (image 2) */
    #cruFilterCard.filter-card .cru-more-filters-panel {
        position: absolute;
        top: calc(100% + 0.5rem);
        left: 0;
        z-index: 1056;
        min-width: 17rem;
    }

    #cruFilterCard.filter-card .cru-more-filters-card {
        border: 1px solid var(--cru-border) !important;
    }

    /* Right-aligned search */
    #cruFilterCard.filter-card .cru-toolbar-right {
        flex: 0 0 auto;
    }

    #cruFilterCard.filter-card .cru-search-collapse .cru-search-group {
        width: 16rem;
        max-width: 60vw;
        border: 1px solid var(--cru-border);
        border-radius: var(--cru-radius);
        overflow: hidden;
    }

    #cruFilterCard.filter-card .cru-search-collapse .cru-search-group .input-group-text,
    #cruFilterCard.filter-card .cru-search-collapse .cru-search-group .form-control {
        border: 0;
    }

    #cruFilterCard.filter-card .cru-btn-search-icon {
        flex: 0 0 auto;
        min-width: 2.25rem;
        border-radius: var(--cru-radius) !important;
        color: #495057;
    }

    @media (max-width: 575.98px) {
        #cruFilterCard.filter-card .cru-filter-toolbar .cru-filter-pill,
        #cruFilterCard.filter-card .cru-filter-toolbar .cru-filter-pill-date,
        #cruFilterCard.filter-card .cru-filter-toolbar .cru-filter-pill-wide,
        #cruFilterCard.filter-card .cru-filter-toolbar .cru-filter-pill-sm {
            flex: 1 1 calc(50% - 0.5rem);
            width: auto;
            min-width: 0;
        }

        #cruFilterCard.filter-card .cru-more-filters-panel {
            left: auto;
            right: 0;
        }

        #cruFilterCard.filter-card .cru-toolbar-right {
            flex: 1 1 100%;
        }

        #cruFilterCard.filter-card .cru-search-collapse .cru-search-group {
            width: 100%;
            max-width: none;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    var ministriesUrl = @json(route('course-repository.ministries-by-sector'));
    var preservedMinistry = @json($filters['ministry'] ?? '');
    var applyTimer = null;
    var suppressAutoApply = false;
    var autoApplyDelayMs = 350;

    function cruFilterChoiceOptions(el) {
        return {
            searchEnabled: true,
            shouldSort: false,
            allowHTML: false,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: el.getAttribute('data-placeholder') || 'Choose…',
            searchPlaceholderValue: 'Search…',
            position: 'bottom'
        };
    }

    function initCruFilterChoiceEl(el) {
        if (!el || typeof Choices === 'undefined') return;
        if (el._choicesBs) {
            try { el._choicesBs.destroy(); } catch (e) { /* ignore */ }
            el._choicesBs = null;
        }
        if (el.parentElement && el.parentElement.classList.contains('choices')) {
            var parent = el.parentElement;
            parent.parentNode.insertBefore(el, parent);
            parent.remove();
        }
        try {
            el._choicesBs = new Choices(el, cruFilterChoiceOptions(el));
        } catch (e) {
            console.warn('Course repository filter Choices init failed', e);
        }
    }

    function getSelectValue(el) {
        if (!el) return '';
        if (el._choicesBs && typeof el._choicesBs.getValue === 'function') {
            var choice = el._choicesBs.getValue(true);
            if (choice && choice.value) return String(choice.value);
        }
        return el.value ? String(el.value) : '';
    }

    function setSelectValue(el, value) {
        if (!el) return;
        var val = value == null ? '' : String(value);
        el.value = val;
        if (el._choicesBs) {
            try {
                el._choicesBs.removeActiveItems();
                if (val) {
                    el._choicesBs.setChoiceByValue(val);
                }
            } catch (e) {
                /* native value already set */
            }
        }
    }

    // AJAX filtering is enabled only on the card-listing pages (which expose
    // #courseCardsGrid). Document-table pages fall back to a normal submit so
    // their table-specific scripts keep working.
    function cruAjaxEnabled() {
        return !!document.getElementById('courseCardsGrid')
            && typeof window.fetch === 'function'
            && typeof DOMParser !== 'undefined';
    }

    function buildFilterUrl() {
        var form = document.getElementById('filterForm');
        var action = (form && form.getAttribute('action')) || window.location.pathname;
        if (!form) return action;
        var params = new URLSearchParams();
        new FormData(form).forEach(function (value, key) {
            if (value !== '' && value != null) params.append(key, value);
        });
        var qs = params.toString();
        return qs ? action + '?' + qs : action;
    }

    // Swap only the results region — filter controls (and Choices instances) stay untouched.
    function swapFilterResults(html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var curResults = document.getElementById('cruFilterResults');
        var newResults = doc.getElementById('cruFilterResults');
        if (curResults && newResults) {
            curResults.innerHTML = newResults.innerHTML;
            return true;
        }

        var newMain = doc.getElementById('cru-user-main') || doc.getElementById('main-content');
        var curMain = document.getElementById('cru-user-main') || document.getElementById('main-content');
        if (!newMain || !curMain) return false;

        var curCard = curMain.querySelector('#cruFilterCard');
        var newCard = newMain.querySelector('#cruFilterCard');
        if (!curCard || !newCard) return false;

        var newNodes = [];
        for (var n = newCard.nextSibling; n; n = n.nextSibling) newNodes.push(n);

        while (curCard.nextSibling) {
            curCard.parentNode.removeChild(curCard.nextSibling);
        }
        newNodes.forEach(function (node) {
            curCard.parentNode.appendChild(document.importNode(node, true));
        });
        return true;
    }

    function cruLoadResults(url, push) {
        var main = document.getElementById('cru-user-main') || document.getElementById('main-content');
        if (main) main.classList.add('cru-ajax-loading');

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin'
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Request failed');
                return res.text();
            })
            .then(function (html) {
                if (!swapFilterResults(html)) {
                    window.location.assign(url);
                    return;
                }
                if (push && window.history && typeof window.history.pushState === 'function') {
                    try { window.history.pushState({ cruFilter: true }, '', url); } catch (e) { /* ignore */ }
                }
                document.dispatchEvent(new CustomEvent('cru:results-updated'));
            })
            .catch(function () {
                window.location.assign(url);
            })
            .finally(function () {
                if (main) main.classList.remove('cru-ajax-loading');
            });
    }

    function applyCruFilters() {
        if (suppressAutoApply) return;
        var form = document.getElementById('filterForm');
        if (!form) return;

        clearTimeout(applyTimer);
        applyTimer = setTimeout(function () {
            if (cruAjaxEnabled()) {
                cruLoadResults(buildFilterUrl(), true);
            } else if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }, autoApplyDelayMs);
    }

    function resetCruFilters(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        var form = document.getElementById('filterForm');
        if (!form) return;

        suppressAutoApply = true;
        clearTimeout(applyTimer);

        var dateEl = document.getElementById('filter_date');
        if (dateEl) dateEl.value = '';

        var searchEl = document.getElementById('filter_search');
        if (searchEl) searchEl.value = '';

        preservedMinistry = '';
        form.querySelectorAll('select.js-cru-filter-choice').forEach(function (el) {
            if (el.id === 'filter_ministry') return;
            setSelectValue(el, '');
        });
        setMinistryOptions([], '', false);

        suppressAutoApply = false;
        form.dispatchEvent(new CustomEvent('cru:filters-reset', { bubbles: true }));

        var baseUrl = form.getAttribute('action') || window.location.pathname;
        if (cruAjaxEnabled()) {
            // Clear filters AND refresh to the unfiltered list — without a page reload.
            cruLoadResults(baseUrl, true);
        } else if (window.history && typeof window.history.replaceState === 'function') {
            // No-AJAX pages: clear the controls and keep the URL in sync, no reload.
            window.history.replaceState({}, '', baseUrl);
        }
    }

    // Back/forward navigation between AJAX filter states — re-fetch without a full reload.
    window.addEventListener('popstate', function () {
        if (cruAjaxEnabled()) {
            cruLoadResults(window.location.href, false);
        }
    });

    function setMinistryOptions(ministries, selectedPk, enabled) {
        var ministryEl = document.getElementById('filter_ministry');
        if (!ministryEl) return;

        if (ministryEl._choicesBs) {
            try { ministryEl._choicesBs.destroy(); } catch (e) { /* ignore */ }
            ministryEl._choicesBs = null;
        }
        if (ministryEl.parentElement && ministryEl.parentElement.classList.contains('choices')) {
            var parent = ministryEl.parentElement;
            parent.parentNode.insertBefore(ministryEl, parent);
            parent.remove();
        }

        ministryEl.innerHTML = '<option value="">Ministry *</option>';
        (ministries || []).forEach(function (m) {
            var opt = document.createElement('option');
            opt.value = String(m.pk);
            opt.textContent = m.ministry_name;
            if (selectedPk && String(m.pk) === String(selectedPk)) {
                opt.selected = true;
            }
            ministryEl.appendChild(opt);
        });

        if (enabled) {
            ministryEl.removeAttribute('disabled');
        } else {
            ministryEl.setAttribute('disabled', 'disabled');
        }

        initCruFilterChoiceEl(ministryEl);
        if (!ministryEl.dataset.cruAutoApplyBound) {
            ministryEl.dataset.cruAutoApplyBound = '1';
            ministryEl.addEventListener('change', applyCruFilters);
        }
    }

    function loadMinistriesForSector(sectorPk, selectedMinistry) {
        if (!sectorPk) {
            setMinistryOptions([], '', false);
            return;
        }
        fetch(ministriesUrl + '?sector_pk=' + encodeURIComponent(sectorPk), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (res) {
                if (!res.ok) throw new Error('Failed to load ministries');
                return res.json();
            })
            .then(function (json) {
                if (json && json.success && Array.isArray(json.data)) {
                    setMinistryOptions(json.data, selectedMinistry || '', true);
                    if (selectedMinistry) {
                        applyCruFilters();
                    }
                } else {
                    setMinistryOptions([], '', true);
                }
            })
            .catch(function () {
                setMinistryOptions([], '', true);
            });
    }

    function onSectorChange() {
        var sectorEl = document.getElementById('filter_sector');
        var sectorPk = getSelectValue(sectorEl);
        preservedMinistry = '';
        if (!sectorPk) {
            setMinistryOptions([], '', false);
            applyCruFilters();
            return;
        }
        loadMinistriesForSector(sectorPk, '');
    }

    function initCruFilterChoices() {
        var root = document.getElementById('cruFilterCard') || document.getElementById('filterForm');
        if (!root) return;

        root.querySelectorAll('select.js-cru-filter-choice').forEach(initCruFilterChoiceEl);

        var sectorEl = document.getElementById('filter_sector');
        var ministryEl = document.getElementById('filter_ministry');
        var form = document.getElementById('filterForm');

        if (sectorEl && !sectorEl.dataset.cruAutoApplyBound) {
            sectorEl.dataset.cruAutoApplyBound = '1';
            sectorEl.addEventListener('change', onSectorChange);
            var sectorValue = getSelectValue(sectorEl);
            if (sectorValue && ministryEl && ministryEl.options.length <= 1) {
                loadMinistriesForSector(sectorValue, preservedMinistry);
            }
        }

        if (form && !form.dataset.cruAutoApplyBound) {
            form.dataset.cruAutoApplyBound = '1';

            var dateEl = document.getElementById('filter_date');
            if (dateEl) {
                dateEl.addEventListener('change', applyCruFilters);
            }

            var searchEl = document.getElementById('filter_search');
            if (searchEl) {
                // applyCruFilters is debounced, so typing won't spam requests.
                searchEl.addEventListener('input', applyCruFilters);
            }

            form.querySelectorAll('select.js-cru-filter-choice').forEach(function (el) {
                if (el.id === 'filter_sector' || el.id === 'filter_ministry') return;
                el.addEventListener('change', applyCruFilters);
            });

            if (ministryEl && !ministryEl.dataset.cruAutoApplyBound) {
                ministryEl.dataset.cruAutoApplyBound = '1';
                ministryEl.addEventListener('change', applyCruFilters);
            }

            // Native submit (Enter / hidden search button) → AJAX too, no reload.
            form.addEventListener('submit', function (e) {
                if (cruAjaxEnabled()) {
                    e.preventDefault();
                    clearTimeout(applyTimer);
                    cruLoadResults(buildFilterUrl(), true);
                }
            });
        }

        var resetBtn = document.getElementById('cruFilterReset');
        if (resetBtn) {
            resetBtn.addEventListener('click', resetCruFilters);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCruFilterChoices);
    } else {
        initCruFilterChoices();
    }
})();
</script>
@endpush
