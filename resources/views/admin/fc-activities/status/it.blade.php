@extends('admin.layouts.master')
@section('title', 'Activity Status - IT')
@section('setup_content')
<div class="container-fluid px-3">
    <x-breadcrum title="FC Activities - IT Status"></x-breadcrum>
    <h4 class="fw-bold mb-3" style="color:#1a3c6e;">IT Status - Biometrics</h4>
    <p class="small text-muted mb-2">Done: {{ $count }} | Pending: {{ $total - $count }}</p>
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table class="table table-sm table-hover mb-0 js-fc-datatable" data-export-title="FC Activities - IT Status"><thead><tr><th>#</th><th>Name</th><th>OT Code</th><th>Biometric</th></tr></thead><tbody>
        @foreach($rows as $i => $row)
            <tr><td>{{ $i + 1 }}</td><td>{{ $row['otname'] }}</td><td>{{ $row['otcode'] }}</td><td>{{ $row['done'] ? 'Done' : '' }}</td></tr>
        @endforeach
        </tbody></table>
    </div></div>
</div>
@endsection

@include('admin.fc-activities.partials.datatable-tools')
