@extends('admin.layouts.master')
@section('title', 'Mess Store Allocations')
@section('setup_content')
<div class="container-fluid">
    <h4>Mess Store Allocations</h4>
    <a href="{{ route('admin.mess.storeallocations.create') }}" class="btn btn-primary mb-3">Add Store Allocation</a>
    <table class="table table-bordered">
        <thead><tr><th>Store Name</th><th>Allocated To</th><th>Date</th></tr></thead>
        <tbody>
        @foreach($storeallocations as $allocation)
            <tr>
                <td>{{ $allocation->store_name }}</td>
                <td>{{ $allocation->allocated_to }}</td>
                <td>{{ $allocation->allocation_date }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
