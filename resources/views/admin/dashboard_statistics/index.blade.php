@extends('admin.layouts.master')

@section('title', 'Batch Profile & Statistics - Sargam | LBSNAA')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Batch Profile & Statistics" />

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span>{{ $errors->first() }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Quick: Generate snapshot from course --}}
    <section class="mb-4" aria-labelledby="generate-heading">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden" style="border-left: 4px solid #198754;">
            <div class="card-body py-3">
                <button class="btn btn-link btn-sm p-0 text-success text-decoration-none fw-semibold d-flex align-items-center gap-1" type="button" data-bs-toggle="collapse" data-bs-target="#generateFromCourse" aria-expanded="false" aria-controls="generateFromCourse" id="generate-heading">
                    <i class="bi bi-chevron-down collapse-icon" style="transition: transform 0.2s;"></i>
                    <i class="bi bi-download"></i>
                    Generate snapshot from course
                </button>
                <div class="collapse mt-3" id="generateFromCourse">
                    <p class="text-muted small mb-3">Create a snapshot from current <strong>enrolled students</strong> for a course—no need to open the charts page.</p>
                    <form action="{{ route('admin.dashboard-statistics.save-from-course') }}" method="POST" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                            <label for="gen_course" class="form-label small mb-0 fw-medium">Course <span class="text-danger">*</span></label>
                            <select name="course_master_pk" id="gen_course" class="form-select form-select-sm" required>
                                <option value="">Select course</option>
                                @foreach($courses ?? [] as $c)
                                <option value="{{ $c->pk }}" {{ old('course_master_pk') == $c->pk ? 'selected' : '' }}>{{ $c->course_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                            <label for="gen_date" class="form-label small mb-0 fw-medium">Snapshot date <span class="text-danger">*</span></label>
                            <input type="date" name="snapshot_date" id="gen_date" value="{{ old('snapshot_date', date('Y-m-d')) }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                            <label for="gen_title" class="form-label small mb-0 fw-medium">Title</label>
                            <input type="text" name="title" id="gen_title" class="form-control form-control-sm" placeholder="e.g. Batch 2026" value="{{ old('title') }}">
                        </div>
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-flex align-items-center pb-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="gen_default" {{ old('is_default') ? 'checked' : '' }}>
                                <label class="form-check-label small" for="gen_default">Set as default</label>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                            <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-download me-1"></i> Generate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- Snapshots list --}}
    <section aria-labelledby="snapshots-heading">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden" style="border-left: 4px solid #004a93;">
            <div class="card-header bg-transparent border-bottom py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <h2 id="snapshots-heading" class="h5 mb-0 fw-semibold d-flex align-items-center gap-2">
                        <i class="bi bi-collection text-primary"></i>
                        Saved snapshots
                    </h2>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <form method="get" action="{{ route('admin.dashboard-statistics.charts') }}" class="d-inline-flex gap-2 align-items-center flex-wrap">
                            <select name="course_master_pk" class="form-select form-select-sm" style="min-width: 180px;" aria-label="Select course to view charts">
                                <option value="">View by course</option>
                                @foreach($courses ?? [] as $c)
                                <option value="{{ $c->pk }}">{{ Str::limit($c->course_name, 35) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-pie-chart me-1"></i> View charts</button>
                        </form>
                        <a href="{{ route('admin.dashboard-statistics.charts') }}" class="btn btn-outline-secondary btn-sm">Default snapshot</a>
                        <a href="{{ route('admin.dashboard-statistics.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Add snapshot (manual)
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-nowrap">#</th>
                                <th scope="col">Date</th>
                                <th scope="col">Title</th>
                                <th scope="col">Items</th>
                                <th scope="col">Default</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($snapshots as $snapshot)
                            <tr>
                                <td>{{ $snapshots->firstItem() + $loop->index }}</td>
                                <td>{{ $snapshot->snapshot_date->format('d M Y') }}</td>
                                <td>{{ $snapshot->title ?? '—' }}</td>
                                <td><span class="badge bg-secondary rounded-pill">{{ $snapshot->items_count }}</span></td>
                                <td>
                                    @if($snapshot->is_default)
                                        <span class="badge bg-success rounded-pill">Default</span>
                                    @else
                                        <form action="{{ route('admin.dashboard-statistics.set-default', $snapshot) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">Set default</button>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.dashboard-statistics.charts', ['snapshot_id' => $snapshot->id]) }}" class="btn btn-outline-info" title="View charts" aria-label="View charts"><i class="bi bi-graph-up"></i></a>
                                        <a href="{{ route('admin.dashboard-statistics.edit', $snapshot) }}" class="btn btn-outline-primary" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                        <form action="{{ route('admin.dashboard-statistics.destroy', $snapshot) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this snapshot?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-inbox text-muted d-block mb-2" style="font-size: 2.5rem;"></i>
                                    <p class="text-muted mb-2">No snapshots yet.</p>
                                    <p class="small mb-0">
                                        <a href="#generateFromCourse" data-bs-toggle="collapse" class="me-2">Generate from course</a> or
                                        <a href="{{ route('admin.dashboard-statistics.create') }}">add one manually</a>.
                                    </p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($snapshots->hasPages())
            <div class="card-footer bg-transparent border-0 py-2">
                {{ $snapshots->links() }}
            </div>
            @endif
        </div>
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('generateFromCourse');
    var btn = document.querySelector('[data-bs-target="#generateFromCourse"]');
    if (el && btn) {
        el.addEventListener('show.bs.collapse', function() { if (btn.querySelector('.collapse-icon')) btn.querySelector('.collapse-icon').style.transform = 'rotate(0deg)'; });
        el.addEventListener('hide.bs.collapse', function() { if (btn.querySelector('.collapse-icon')) btn.querySelector('.collapse-icon').style.transform = 'rotate(-90deg)'; });
        if (!el.classList.contains('show')) { var icon = btn.querySelector('.collapse-icon'); if (icon) icon.style.transform = 'rotate(-90deg)'; }
    }
});
</script>
@endpush
@endsection
