@extends('admin.layouts.master')

@section('title', 'My Session Feedback - Sargam | Lal Bahadur')

@section('setup_content')
<style>
:root {
    --primary: #af2910;
    --secondary: #f4f6f9;
    --border: #d0d7de;
    --text-dark: #1f2937;
}

body {
    background: var(--secondary);
    color: var(--text-dark);
    font-size: 14px;
}

.page-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--primary);
}

.filter-card {
    border: 1px solid var(--border);
    border-radius: 8px;
    background: #fff;
}

.filter-card .card-header {
    background: var(--primary);
    color: #fff;
    font-weight: 600;
}

.content-card {
    border: 1px solid var(--border);
    border-radius: 8px;
    background: #fff;
}

.content-card .card-header {
    background: #eef4fb;
    font-weight: 600;
}

.remarks-title {
    background: var(--primary);
    color: #fff;
    padding: 0.5rem 0.75rem;
    font-weight: 600;
    border-radius: 4px 4px 0 0;
}

.remarks-list {
    border-top: 0;
    border-radius: 0 0 4px 4px;
    padding: 1rem;
}

.rating-header {
    color: #af2910 !important;
    font-weight: 600;
}

.percentage-cell {
    font-weight: 600;
    color: var(--primary);
}

.loading-spinner {
    display: none;
    text-align: center;
    padding: 20px;
}

.faculty-type-badge {
    font-size: 0.75rem;
    padding: 2px 6px;
    border-radius: 10px;
    background: #e9ecef;
    color: #495057;
}

.pagination-info {
    font-size: 0.875rem;
}

</style>

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid py-3">
    <x-breadcrum title="My Session Feedback"></x-breadcrum>

    @if ($facultyMissing ?? false)
        <div class="alert alert-warning">
            <strong>Faculty profile not found.</strong>
            Your login could not be matched to a faculty record (employee link, faculty ID, or timetable assignment). Please contact the administrator.
        </div>
    @else
        <div class="row g-3">
            <aside class="col-lg-3 col-md-4">
                <div class="card filter-card">
                        <div class="card-header">Options</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('feedback.portal.data') }}" id="filterForm">
                            @csrf
                            <input type="hidden" name="page" id="pageInput" value="1">

                            <fieldset class="mb-3">
                                <legend class="fs-6 fw-semibold">Course Status</legend>
                                <div class="form-check">
                                    <input class="form-check-input course-type-radio" type="radio" name="course_type"
                                        value="current" id="course_current"
                                        {{ ($courseType ?? 'current') === 'current' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="course_current">Current Courses</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input course-type-radio" type="radio" name="course_type"
                                        value="archived" id="course_archived"
                                        {{ ($courseType ?? '') === 'archived' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="course_archived">Archived Courses</label>
                                </div>
                            </fieldset>

                            <div class="mb-3">
                                <label class="form-label" for="programSelect">Program Name</label>
                                <select class="form-select" name="program_id" id="programSelect">
                                    <option value="">All Programs</option>
                                    @foreach ($programs ?? [] as $id => $name)
                                        <option value="{{ $id }}" {{ ($currentProgram ?? '') == $id ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="mb-3">
                                <label class="form-label" for="from_date">From Date</label>
                                <input type="date" name="from_date" id="from_date" class="form-control" value="">
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="to_date">To Date</label>
                                <input type="date" name="to_date" id="to_date" class="form-control" value="">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-50">Apply</button>
                                <button type="button" class="btn btn-outline-secondary w-50" id="resetButton">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </aside>

            <main class="col-lg-9 col-md-8">
                <div class="card content-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="page-title">Average Rating - Course / Topic wise</span>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <div class="btn-group" role="group" aria-label="Export options">
                                <button type="button" class="btn btn-sm btn-success" onclick="exportToExcel()">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="exportToPDF()">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="printReport()">
                                    <i class="bi bi-printer me-1"></i> Print
                                </button>
                            </div>
                            <small class="text-muted" id="refreshTime">Data refreshed: {{ now()->format('d-M-Y H:i') }}</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="loading-spinner" id="loadingSpinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 mb-0">Loading feedback data...</p>
                        </div>
                        <div id="contentContainer"></div>
                    </div>
                </div>
            </main>
        </div>
    @endif
</div>

@if (!($facultyMissing ?? false))
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const programSelect = document.getElementById('programSelect');
    const resetButton = document.getElementById('resetButton');
    const pageInput = document.getElementById('pageInput');
    const contentContainer = document.getElementById('contentContainer');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const refreshTimeEl = document.getElementById('refreshTime');
    const dataUrl = @json(route('feedback.portal.data'));
    const exportUrl = @json(route('feedback.portal.export'));
    const printUrl = @json(route('feedback.portal.print'));
    const defaultLoadingText = 'Loading feedback data...';

    function updatePrograms(programs, selectedId) {
        const current = selectedId || programSelect.value;
        programSelect.innerHTML = '<option value="">All Programs</option>';
        Object.entries(programs || {}).forEach(([id, name]) => {
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = name;
            if (String(id) === String(current)) {
                opt.selected = true;
            }
            programSelect.appendChild(opt);
        });
    }

    function bindPagination() {
        contentContainer.querySelectorAll('.portal-page-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                loadFeedbackData(parseInt(this.dataset.page, 10) || 1);
            });
        });
    }

    window.loadFeedbackData = function(page = 1) {
        pageInput.value = page;
        loadingSpinner.style.display = 'block';
        contentContainer.style.display = 'none';

        const formData = new FormData(filterForm);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrf) {
            formData.set('_token', csrf);
        }

        fetch(dataUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Request failed');
            }
            return res.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to load data');
            }
            contentContainer.innerHTML = data.html || '';
            pageInput.value = data.currentPage || 1;
            updatePrograms(data.programs, programSelect.value);
            if (refreshTimeEl && data.refreshTime) {
                refreshTimeEl.textContent = 'Data refreshed: ' + data.refreshTime;
            }
            bindPagination();
        })
        .catch(() => {
            contentContainer.innerHTML =
                '<div class="alert alert-danger text-center mb-0">Error loading data. Please try again.</div>';
        })
        .finally(() => {
            loadingSpinner.style.display = 'none';
            contentContainer.style.display = 'block';
        });
    };

    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        loadFeedbackData(1);
    });

    resetButton.addEventListener('click', function() {
        filterForm.reset();
        document.getElementById('course_current').checked = true;
        pageInput.value = 1;
        loadFeedbackData(1);
    });

    programSelect.addEventListener('change', () => loadFeedbackData(1));

    document.querySelectorAll('.course-type-radio').forEach(radio => {
        radio.addEventListener('change', () => {
            pageInput.value = 1;
            loadFeedbackData(1);
        });
    });

    document.querySelectorAll('#filterForm input[type="date"]').forEach(el => {
        el.addEventListener('change', () => loadFeedbackData(1));
    });

    loadFeedbackData(1);

    window.exportToExcel = function() {
        const formData = new FormData(filterForm);
        formData.append('export_type', 'excel');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrf) {
            formData.set('_token', csrf);
        }

        loadingSpinner.style.display = 'block';
        loadingSpinner.querySelector('p').textContent = 'Generating Excel report...';

        fetch(exportUrl, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Export failed');
            }
            return res.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `my_session_feedback_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(() => alert('Error exporting to Excel. Please try again.'))
        .finally(() => {
            loadingSpinner.style.display = 'none';
            loadingSpinner.querySelector('p').textContent = defaultLoadingText;
        });
    };

    window.exportToPDF = function() {
        const formData = new FormData(filterForm);
        formData.append('export_type', 'pdf');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrf) {
            formData.set('_token', csrf);
        }

        loadingSpinner.style.display = 'block';
        loadingSpinner.querySelector('p').textContent = 'Generating PDF report...';

        fetch(exportUrl, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Export failed');
            }
            return res.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `my_session_feedback_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(() => alert('Error exporting to PDF. Please try again.'))
        .finally(() => {
            loadingSpinner.style.display = 'none';
            loadingSpinner.querySelector('p').textContent = defaultLoadingText;
        });
    };

    window.printReport = function() {
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            params.append(key, value);
        }
        window.open(`${printUrl}?${params.toString()}`, '_blank');
    };
});
</script>
@endif
@endsection
