@include('admin.partials.choices-bootstrap5')

@php
    $sectors = $sectors ?? collect();
    $ministries = $ministries ?? collect();
@endphp

<!-- Filter Card Partial -->
<div class="card filter-card mb-4 choices-bs-scope" id="cruFilterCard">
    <div class="card-body p-4">
        <form method="GET" action="{{ $route }}" id="filterForm" novalidate>
            <div class="row g-2 g-md-3 align-items-center cru-filter-row">
            <div class="col-6 col-md-4 col-lg cru-filter-col">
                <label for="filter_date" class="visually-hidden">Date</label>
                <div class="input-group input-group-sm cru-input-group">
                    <input type="date"
                           class="form-control"
                           id="filter_date"
                           name="date"
                           value="{{ $filters['date'] ?? '' }}"
                           placeholder="Date">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-calendar3 text-muted" aria-hidden="true"></i>
                    </span>
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg cru-filter-col">
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

            <div class="col-6 col-md-4 col-lg cru-filter-col">
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

            <div class="col-6 col-md-4 col-lg cru-filter-col">
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

            <div class="col-6 col-md-4 col-lg cru-filter-col">
                <label for="filter_faculty" class="visually-hidden">Faculty</label>
                <select class="form-select form-select-sm js-cru-filter-choice" id="filter_faculty" name="faculty" data-placeholder="Faculty">
                    <option value="">Faculty</option>
                    @foreach($faculties as $faculty)
                        <option value="{{ $faculty->pk }}" {{ ($filters['faculty'] ?? '') == $faculty->pk ? 'selected' : '' }}>
                            {{ $faculty->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-4 col-lg cru-filter-col">
                <label for="filter_sector" class="visually-hidden">Sector (required)</label>
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

            <div class="col-6 col-md-4 col-lg cru-filter-col">
                <label for="filter_ministry" class="visually-hidden">Ministry (required)</label>
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

            <div class="col-6 col-md-auto cru-filter-col">
                <button type="button"
                        class="btn btn-outline-danger btn-sm w-100 fw-normal px-3"
                        id="cruFilterReset">
                    Reset Filters
                </button>
            </div>

            <div class="col-6 col-md-auto ms-md-auto cru-filter-col d-none" aria-hidden="true">
                <button type="submit" class="btn btn-light border btn-sm cru-btn-search-icon w-100" tabindex="-1">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </form>
    </div>
</div>

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

    function applyCruFilters() {
        if (suppressAutoApply) return;
        var form = document.getElementById('filterForm');
        if (!form) return;

        clearTimeout(applyTimer);
        applyTimer = setTimeout(function () {
            if (typeof form.requestSubmit === 'function') {
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

        preservedMinistry = '';
        form.querySelectorAll('select.js-cru-filter-choice').forEach(function (el) {
            if (el.id === 'filter_ministry') return;
            setSelectValue(el, '');
        });
        setMinistryOptions([], '', false);

        suppressAutoApply = false;
        form.dispatchEvent(new CustomEvent('cru:filters-reset', { bubbles: true }));
        applyCruFilters();
    }

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

            form.querySelectorAll('select.js-cru-filter-choice').forEach(function (el) {
                if (el.id === 'filter_sector' || el.id === 'filter_ministry') return;
                el.addEventListener('change', applyCruFilters);
            });

            if (ministryEl && !ministryEl.dataset.cruAutoApplyBound) {
                ministryEl.dataset.cruAutoApplyBound = '1';
                ministryEl.addEventListener('change', applyCruFilters);
            }
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
