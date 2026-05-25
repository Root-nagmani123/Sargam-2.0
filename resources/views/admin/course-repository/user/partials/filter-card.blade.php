@include('admin.partials.choices-bootstrap5')

@php
    $sectors = $sectors ?? collect();
    $ministries = $ministries ?? collect();
@endphp

<!-- Filter Card Partial -->
<div class="filter-card choices-bs-scope mb-3 mb-md-4 overflow-visible" id="cruFilterCard">
    <form method="GET" action="{{ $route }}" id="filterForm" novalidate>
        <div class="row g-2 g-md-3 align-items-center">
            <div class="col-12 col-lg-auto">
                <span class="cru-filter-label text-muted small fw-normal mb-0">Filters</span>
            </div>

            <div class="col-6 col-md-4 col-lg">
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

            <div class="col-6 col-md-4 col-lg">
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

            <div class="col-6 col-md-4 col-lg">
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

            <div class="col-6 col-md-4 col-lg">
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

            <div class="col-6 col-md-4 col-lg">
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
            <div class="col-6 col-md-4 col-lg">
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

            <div class="col-6 col-md-4 col-lg">
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

            <div class="col-6 col-md-auto">
                <a href="{{ $route }}" class="btn btn-outline-danger btn-sm w-100 fw-normal px-3">
                    Reset Filters
                </a>
            </div>

            <div class="col-6 col-md-auto ms-md-auto">
                <button type="submit" class="btn btn-light border btn-sm cru-btn-search-icon w-100" title="Apply filters" aria-label="Apply filters">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    var ministriesUrl = @json(route('course-repository.ministries-by-sector'));
    var preservedMinistry = @json($filters['ministry'] ?? '');

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
            el._choicesBs = new Choices(el, {
                searchEnabled: true,
                shouldSort: false,
                allowHTML: false,
                itemSelectText: '',
                placeholder: true,
                placeholderValue: el.getAttribute('data-placeholder') || 'Choose…',
                searchPlaceholderValue: 'Search…',
                position: 'bottom'
            });
        } catch (e) {
            console.warn('Course repository filter Choices init failed', e);
        }
    }

    function setMinistryOptions(ministries, selectedPk) {
        var ministryEl = document.getElementById('filter_ministry');
        if (!ministryEl) return;

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

        ministryEl.disabled = !(ministries && ministries.length);
        initCruFilterChoiceEl(ministryEl);
    }

    function loadMinistriesForSector(sectorPk, selectedMinistry) {
        if (!sectorPk) {
            setMinistryOptions([], '');
            return;
        }
        fetch(ministriesUrl + '?sector_pk=' + encodeURIComponent(sectorPk), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (json && json.success && Array.isArray(json.data)) {
                    setMinistryOptions(json.data, selectedMinistry || '');
                } else {
                    setMinistryOptions([], '');
                }
            })
            .catch(function () {
                setMinistryOptions([], '');
            });
    }

    function initCruFilterChoices() {
        var root = document.getElementById('cruFilterCard');
        if (!root) return;

        if (typeof window.initChoicesBootstrap5In === 'function') {
            window.initChoicesBootstrap5In(root);
        } else {
            root.querySelectorAll('select.js-cru-filter-choice').forEach(initCruFilterChoiceEl);
        }

        var sectorEl = document.getElementById('filter_sector');
        var ministryEl = document.getElementById('filter_ministry');
        var form = document.getElementById('filterForm');

        if (sectorEl) {
            sectorEl.addEventListener('change', function () {
                preservedMinistry = '';
                loadMinistriesForSector(this.value, '');
            });
            if (sectorEl.value && ministryEl && ministryEl.options.length <= 1) {
                loadMinistriesForSector(sectorEl.value, preservedMinistry);
            }
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                var sector = sectorEl ? sectorEl.value : '';
                var ministry = ministryEl ? ministryEl.value : '';
                if (!sector || !ministry) {
                    e.preventDefault();
                    window.alert('Please select both Sector and Ministry before applying filters.');
                    if (!sector && sectorEl) sectorEl.focus();
                    else if (!ministry && ministryEl) ministryEl.focus();
                }
            });
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
