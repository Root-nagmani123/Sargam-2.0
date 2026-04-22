{{-- resources/views/fc/report/by-service.blade.php --}}
@extends('admin.layouts.master')
@section('title','Service-wise Report')
@section('setup_content')
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;"><i class="bi bi-briefcase me-2"></i>Service-wise Report</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
            <a href="{{ route('admin.reports.export','service') }}" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV</a>
        </div>
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-3 px-3 py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $s)<option value="{{ $s->id }}" {{ request('session_id')==$s->id?'selected':'' }}>{{ $s->session_name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Service</label>
                <select name="service_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($services as $sv)<option value="{{ $sv->id }}" {{ request('service_id')==$sv->id?'selected':'' }}>{{ $sv->service_name }}</option>@endforeach
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary px-3">Filter</button></div>
        </div>
    </form>

    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:12px;">
                <thead class="table-dark">
                    <tr><th>#</th><th>Service</th><th>Code</th><th class="text-center">Total</th><th class="text-center">Male</th><th class="text-center">Female</th><th class="text-center">Submitted</th><th class="text-center">Docs Done</th><th class="text-center">% Done</th></tr>
                </thead>
                <tbody>
                @php $grand = ['total'=>0,'male'=>0,'female'=>0,'submitted'=>0,'docs'=>0]; @endphp
                @forelse($data as $i => $row)
                    @php
                        $grand['total']     += $row->total;
                        $grand['male']      += $row->male;
                        $grand['female']    += $row->female;
                        $grand['submitted'] += $row->submitted;
                        $grand['docs']      += $row->docs_done;
                        $pct = $row->total > 0 ? round(($row->submitted/$row->total)*100) : 0;
                    @endphp
                    <tr>
                        <td class="px-3">{{ $i+1 }}</td>
                        <td>{{ $row->service_name }}</td>
                        <td><span class="badge bg-primary-subtle text-primary" style="font-size:10px;">{{ $row->service_code }}</span></td>
                        <td class="text-center fw-bold">{{ $row->total }}</td>
                        <td class="text-center text-primary">{{ $row->male }}</td>
                        <td class="text-center text-danger">{{ $row->female }}</td>
                        <td class="text-center text-success">{{ $row->submitted }}</td>
                        <td class="text-center">{{ $row->docs_done }}</td>
                        <td class="text-center">
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress flex-grow-1" style="height:5px;min-width:40px;">
                                    <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                                </div>
                                <span style="font-size:10px;">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-3">No data found.</td></tr>
                @endforelse
                </tbody>
                @if($data->count())
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td colspan="3" class="px-3 text-end small">TOTAL</td>
                        <td class="text-center">{{ $grand['total'] }}</td>
                        <td class="text-center">{{ $grand['male'] }}</td>
                        <td class="text-center">{{ $grand['female'] }}</td>
                        <td class="text-center">{{ $grand['submitted'] }}</td>
                        <td class="text-center">{{ $grand['docs'] }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
