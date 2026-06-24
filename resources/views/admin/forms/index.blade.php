@extends('admin.layouts.master')
@section('title', 'Manage Forms')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<style>
.fc-forms-choices .choices__inner.form-select {
    min-height: calc(1.5em + 0.75rem + var(--bs-border-width) * 2);
    background-image: none !important;
}
#formsGridWrapper.is-loading {
    opacity: 0.45;
    pointer-events: none;
}
</style>
@endpush

@section('setup_content')
<div class="container-fluid py-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <section class="row align-items-center mb-4">
                <div class="col-md-4 col-lg-3">
                    <h4 class="h4 fw-bold mb-2 mb-md-0">
                        <i class="bi bi-collection me-2"></i>Dynamic Forms
                    </h4>
                </div>
                <div class="col-md-8 col-lg-9">
                    <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-3">
                        <div class="btn-group shadow-sm rounded-1" role="group" aria-label="Filter forms by programme status">
                            <button type="button" class="btn btn-success px-4 fw-semibold active" id="fcFilterActive" aria-pressed="true">
                                <i class="bi bi-check-circle me-1"></i> Active
                            </button>
                            <button type="button" class="btn btn-outline-secondary px-4 fw-semibold" id="fcFilterArchive" aria-pressed="false">
                                <i class="bi bi-archive me-1"></i> Archived
                            </button>
                        </div>
                        <a href="{{ route('fc-reg.admin.forms.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Create New Form
                        </a>
                    </div>
                </div>
            </section>

            <div class="alert alert-info border-0 shadow-sm mb-4 py-2">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Active</strong> = linked course not ended (or no course link). <strong>Archived</strong> = linked course end date has passed.
            </div>

            <div class="row g-3 mb-4 fc-forms-choices align-items-end">
                <div class="col-md-4">
                    <label for="fcFormSearch" class="form-label mb-1">Search</label>
                    <input type="search" id="fcFormSearch" class="form-control rounded-1"
                           placeholder="Form name, slug, description, course…" autocomplete="off">
                </div>
                <div class="col-md-4">
                    <label for="fcCourseFilter" class="form-label mb-1">Linked Course</label>
                    <select id="fcCourseFilter" class="form-select rounded-1">
                        <option value="">All Courses</option>
                        @foreach($courses ?? [] as $pk => $name)
                            <option value="{{ $pk }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-secondary" id="fcResetFilters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                    </button>
                    <span class="text-muted small ms-2" id="fcFormsCount"></span>
                </div>
            </div>

            <div id="formsGridWrapper">
                <div class="row g-4" id="formsGrid">
                    <div class="col-12 text-center py-5 text-muted" id="formsGridLoading">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 mb-0">Loading forms…</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(function () {
    var currentFilter = 'active';
    var searchTimer = null;
    var courseChoices = null;
    var listUrl = @json(route('fc-reg.admin.forms.list'));
    var coursesUrl = @json(route('fc-reg.admin.forms.filter.courses'));

    function setActiveButton($activeBtn) {
        $('#fcFilterActive')
            .removeClass('btn-success active text-white')
            .addClass('btn-outline-success')
            .attr('aria-pressed', 'false');
        $('#fcFilterArchive')
            .removeClass('btn-secondary active text-white')
            .addClass('btn-outline-secondary')
            .attr('aria-pressed', 'false');

        if ($activeBtn.attr('id') === 'fcFilterActive') {
            $activeBtn.removeClass('btn-outline-success')
                .addClass('btn-success text-white active')
                .attr('aria-pressed', 'true');
        } else {
            $activeBtn.removeClass('btn-outline-secondary')
                .addClass('btn-secondary text-white active')
                .attr('aria-pressed', 'true');
        }
    }

    function initCourseChoices() {
        if (typeof Choices === 'undefined') {
            return;
        }
        var el = document.getElementById('fcCourseFilter');
        if (!el || el.dataset.choicesInitialized === 'true') {
            return;
        }
        courseChoices = new Choices(el, {
            searchEnabled: true,
            shouldSort: false,
            itemSelectText: '',
            classNames: {
                containerOuter: ['choices', 'w-100'],
                containerInner: ['choices__inner', 'form-select', 'shadow-sm'],
                listDropdown: ['choices__list--dropdown', 'dropdown-menu', 'mt-1', 'p-0', 'shadow-sm', 'w-100'],
            }
        });
        el.dataset.choicesInitialized = 'true';
        el._choicesInstance = courseChoices;
    }

    function rebuildCourseFilterOptions(courses) {
        var $sel = $('#fcCourseFilter');
        var current = $sel.val();
        $sel.find('option:not(:first)').remove();
        $.each(courses || {}, function (pk, name) {
            $sel.append($('<option>', { value: pk, text: name }));
        });

        if (courseChoices) {
            courseChoices.destroy();
            $sel[0].dataset.choicesInitialized = 'false';
            courseChoices = null;
        }
        initCourseChoices();
        $sel.val('');
        if (courseChoices) {
            courseChoices.setChoiceByValue('');
        }
    }

    function loadCourseFilterOptions() {
        return $.get(coursesUrl, { status_filter: currentFilter }).done(function (res) {
            if (res.success) {
                rebuildCourseFilterOptions(res.courses);
            }
        });
    }

    function loadForms() {
        var $wrapper = $('#formsGridWrapper');
        var $grid = $('#formsGrid');
        $wrapper.addClass('is-loading');

        $.ajax({
            url: listUrl,
            type: 'GET',
            data: {
                status_filter: currentFilter,
                course_filter: $('#fcCourseFilter').val() || '',
                search: $('#fcFormSearch').val() || ''
            },
            success: function (res) {
                if (res.success) {
                    $grid.html(res.html);
                    $('#fcFormsCount').text(res.count ? '(' + res.count + ' forms)' : '(0 forms)');
                }
            },
            error: function () {
                $grid.html(
                    '<div class="col-12"><div class="alert alert-danger mb-0">Could not load forms. Please try again.</div></div>'
                );
            },
            complete: function () {
                $wrapper.removeClass('is-loading');
            }
        });
    }

    function reloadAll() {
        $.when(loadCourseFilterOptions()).always(loadForms);
    }

    initCourseChoices();
    loadForms();

    $('#fcFilterActive').on('click', function () {
        setActiveButton($(this));
        currentFilter = 'active';
        reloadAll();
    });

    $('#fcFilterArchive').on('click', function () {
        setActiveButton($(this));
        currentFilter = 'archive';
        reloadAll();
    });

    $('#fcCourseFilter').on('change', loadForms);

    $('#fcFormSearch').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadForms, 350);
    });

    $('#fcResetFilters').on('click', function () {
        $('#fcFormSearch').val('');
        currentFilter = 'active';
        setActiveButton($('#fcFilterActive'));
        reloadAll();
    });
});
</script>
@endpush
