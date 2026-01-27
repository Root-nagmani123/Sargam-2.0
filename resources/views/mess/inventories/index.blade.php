@extends('admin.layouts.master')
@section('title', 'Mess Inventory')
@section('setup_content')
<div class="container-fluid">
    <h4>Mess Inventory</h4>
    <a href="{{ route('admin.mess.inventories.create') }}" class="btn btn-primary mb-3">Add Inventory</a>
    <table class="table table-bordered">
        <thead><tr><th>Item Name</th><th>Category</th><th>Quantity</th><th>Unit</th><th>Expiry Date</th></tr></thead>
        <tbody>
        @foreach($inventories as $item)
            <tr>
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->category }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->unit }}</td>
                <td>{{ $item->expiry_date }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
