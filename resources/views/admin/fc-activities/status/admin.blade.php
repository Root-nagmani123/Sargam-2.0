@extends('admin.layouts.master')
@section('title', 'Activity Status - Admin')
@section('setup_content')
<div class="container-fluid px-3">
    <h4 class="fw-bold mb-3" style="color:#1a3c6e;">Admin Status - Joined</h4>
    <p class="small text-muted mb-2">Joined: {{ $count }} | Not Joined: {{ $total - $count }}</p>
    <div class="card border-0 shadow-sm"><div class="table-responsive">
        <table class="table table-sm table-hover mb-0"><thead><tr><th>#</th><th>Name</th><th>OT Code</th><th>Joined</th></tr></thead><tbody>
        @foreach($rows as $i => $row)
            <tr><td>{{ $i + 1 }}</td><td>{{ $row['otname'] }}</td><td>{{ $row['otcode'] }}</td><td>{{ $row['done'] ? 'Joined' : '' }}</td></tr>
        @endforeach
        </tbody></table>
    </div></div>
</div>
@endsection
