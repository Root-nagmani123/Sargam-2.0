@extends('admin.layouts.master')
@section('title', 'Activity Reports Summary')
@section('setup_content')
<div class="container-fluid px-3">
    <x-breadcrum title="FC Activities - Report Summary"></x-breadcrum>
    <h4 class="fw-bold mb-3" style="color:#1a3c6e;">Activity completion (distinct OTs)</h4>
    @forelse($departments as $dept)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 bg-white fw-semibold">{{ $dept->name }}</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 js-fc-datatable" data-export-title="FC Activities - {{ $dept->name }}">
                    <thead class="table-light">
                        <tr><th>Activity</th><th>Code</th><th>OTs completed</th></tr>
                    </thead>
                    <tbody>
                    @forelse($dept->masters as $m)
                        <tr>
                            <td>{{ $m->menun }}</td>
                            <td><code>{{ $m->menuid }}</code></td>
                            <td>
                                <a href="{{ route('fc-reg.admin.activities.reports.by-activity', $m->menuid) }}">
                                    {{ $counts[$m->menuid] ?? 0 }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted text-center py-2">No active activities.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <p class="text-muted">No departments in scope.</p>
    @endforelse
</div>
@endsection

@include('admin.fc-activities.partials.datatable-tools')
