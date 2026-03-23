@extends('admin.layouts.master')

@section('title', 'OT Directory - Sargam')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="OT Directory"></x-breadcrum>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.directory.ot') }}" class="mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label mb-1 fw-semibold">Program Name*</label>
                        <select name="course_id" class="form-select" id="otCourseSelect">
                            @foreach($activeCourses as $course)
                                <option value="{{ $course->pk }}" {{ (int) $selectedCourseId === (int) $course->pk ? 'selected' : '' }}>
                                    {{ $course->couse_short_name ?: $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1 fw-semibold">Search</label>
                        <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control" placeholder="Name, OT code, email, cadre">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1 fw-semibold">Sort by</label>
                        <select name="sort" class="form-select">
                            <option value="name_asc" {{ ($sort ?? 'name_asc') === 'name_asc' ? 'selected' : '' }}>Name (A-Z)</option>
                            <option value="name_desc" {{ ($sort ?? 'name_asc') === 'name_desc' ? 'selected' : '' }}>Name (Z-A)</option>
                            <option value="ot_code_asc" {{ ($sort ?? 'name_asc') === 'ot_code_asc' ? 'selected' : '' }}>OT Code (A-Z)</option>
                            <option value="ot_code_desc" {{ ($sort ?? 'name_asc') === 'ot_code_desc' ? 'selected' : '' }}>OT Code (Z-A)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1 fw-semibold">Per page</label>
                        <select name="per_page" class="form-select">
                            <option value="25" {{ (int) ($perPage ?? 50) === 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ (int) ($perPage ?? 50) === 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ (int) ($perPage ?? 50) === 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2 justify-content-md-end">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <a href="{{ route('admin.directory.ot', ['course_id' => $selectedCourseId]) }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button type="submit" name="export" value="csv" class="btn btn-outline-success btn-sm">Export CSV</button>
                    <button type="submit" name="export" value="excel" class="btn btn-outline-success btn-sm">Export Excel</button>
                </div>
            </form>
            @if($students->total() > 0)
                <p class="mb-2 text-muted small">
                    Showing {{ $students->firstItem() }} to {{ $students->lastItem() }} of {{ $students->total() }} records
                </p>
            @endif

            <div class="table-responsive ot-directory-scroll">
                <table class="table align-middle" id="otDirectoryTable">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Name</th>
                            <th>OT Code</th>
                            <th>Room No.</th>
                            <th>Room Extension No.</th>
                            <th>Email ID</th>
                            <th>Course Name</th>
                            <th>Cadre Name</th>
                            <th>Photo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            <tr>
                                <td>{{ ($students->firstItem() ?? 0) + $index }}</td>
                                <td>{{ $student->display_name ?: '-' }}</td>
                                <td>{{ $student->generated_OT_code ?: '-' }}</td>
                                <td>-</td>
                                <td>-</td>
                                <td>{{ $student->email ?: '-' }}</td>
                                <td>{{ $student->course_name ?: '-' }}</td>
                                <td>{{ $student->cadre_name ?: '-' }}</td>
                                <td>
                                    @if(!empty($student->photo_path))
                                        <img src="{{ asset('storage/' . $student->photo_path) }}" alt="photo" class="directory-photo" loading="lazy" decoding="async">
                                    @else
                                        <img src="{{ asset('images/dummypic.jpeg') }}" alt="photo" class="directory-photo" loading="lazy" decoding="async">
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $students->links() }}
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    #otDirectoryTable thead th {
        background: #2f7fc0;
        color: #fff;
        font-size: 12px;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    #otDirectoryTable tbody td {
        font-size: 12px;
        vertical-align: middle;
    }
    #otDirectoryTable tbody tr:nth-child(odd) {
        background: #ecebff;
    }
    .directory-photo {
        width: 56px;
        height: 56px;
        object-fit: cover;
        border-radius: 6px;
    }
    @media (max-width: 768px) {
        .ot-directory-scroll {
            max-height: 65vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
    }
    /* Choices.js + Bootstrap look */
    .choices[data-type*="select-one"] .choices__inner {
        min-height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        background-color: #fff;
        font-size: 0.95rem;
    }
    .choices.is-focused .choices__inner,
    .choices.is-open .choices__inner {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .choices__list--dropdown,
    .choices__list[aria-expanded] {
        z-index: 20;
        border-radius: 0.375rem;
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const cssHref = 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css';
        const jsSrc = 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js';

        function ensureCss() {
            const exists = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
                .some((el) => (el.getAttribute('href') || '').includes('choices.min.css'));
            if (!exists) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = cssHref;
                document.head.appendChild(link);
            }
        }

        function initChoices() {
            const el = document.getElementById('otCourseSelect');
            if (!el || typeof window.Choices === 'undefined' || el.dataset.choicesInit === '1') return;

            new Choices(el, {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: '',
                placeholder: true,
                searchPlaceholderValue: 'Search course...'
            });
            el.dataset.choicesInit = '1';
        }

        function ensureScriptAndInit() {
            if (typeof window.Choices !== 'undefined') {
                initChoices();
                return;
            }

            const existing = document.querySelector('script[data-choices-loader="1"]');
            if (existing) {
                existing.addEventListener('load', initChoices, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = jsSrc;
            script.dataset.choicesLoader = '1';
            script.onload = initChoices;
            document.body.appendChild(script);
        }

        document.addEventListener('DOMContentLoaded', function () {
            ensureCss();
            ensureScriptAndInit();
        });
    })();
</script>
@endpush

@endsection

