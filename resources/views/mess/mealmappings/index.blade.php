@extends('admin.layouts.master')
@section('title', 'Mess Meal Mappings')
@section('setup_content')
<div class="container-fluid">
    <h4>Mess Meal Mappings</h4>
    <a href="{{ route('mess.mealmappings.create') }}" class="btn btn-primary mb-3">Add Meal Mapping</a>
    <table class="table table-bordered">
        <thead><tr><th>Meal</th><th>Item</th><th>Date</th></tr></thead>
        <tbody>
        @foreach($mealmappings as $mapping)
            <tr>
                <td>{{ $mapping->meal_name }}</td>
                <td>{{ $mapping->item_name }}</td>
                <td>{{ $mapping->date }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
