@extends('admin.layouts.master')
@section('title','Bank Details Report')
@section('setup_content')
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;"><i class="bi bi-bank me-2"></i>Bank Details Report</h4>
        <div class="d-flex gap-2">
            @include('fc.report.partials.scoped-form-back', ['scopedForm' => $scopedForm ?? null])
            <a href="{{ route('admin.reports.export','bank') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
            </a>
        </div>
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-3 px-3 py-2">
        <div class="row g-2 align-items-end">
            @include('fc.report.partials.form-filter-select', ['forms' => $forms])
            <div class="col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="bank_status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="filled"  {{ request('bank_status')=='filled'?'selected':'' }}>Filled</option>
                    <option value="missing" {{ request('bank_status')=='missing'?'selected':'' }}>Not Filled</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Name / Username"
                           value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                    <a href="{{ route('admin.reports.bank') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x"></i></a>
                </div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="card-header bg-white border-bottom py-2 px-3 small fw-semibold">
            {{ $students->total() }} students
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:12px;">
                <thead class="table-dark">
                    <tr><th class="px-3">#</th><th>User ID</th><th>Full Name</th><th>Service</th><th>Bank Name</th><th>IFSC</th><th>Account No</th><th>Holder Name</th></tr>
                </thead>
                <tbody>
                @forelse($students as $idx => $s)
                    <tr>
                        <td class="px-3">{{ $students->firstItem() + $idx }}</td>
                        <td><code style="font-size:10px">{{ $s->user_id }}</code></td>
                        <td>{{ $s->full_name }}</td>
                        <td><span class="badge bg-primary-subtle text-primary" style="font-size:10px;">{{ $s->service_code ?? '—' }}</span></td>
                        <td>{{ $s->bank_name ?? '—' }}</td>
                        <td><code style="font-size:10px">{{ $s->ifsc_code ?? '—' }}</code></td>
                        <td><code style="font-size:10px">{{ $s->account_no ?? '—' }}</code></td>
                        <td>{{ $s->account_holder_name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">No records found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2 px-3">{{ $students->links() }}</div>
    </div>
</div>
@endsection
