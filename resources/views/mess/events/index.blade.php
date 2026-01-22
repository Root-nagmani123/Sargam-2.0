@extends('admin.layouts.master')
@section('title', 'Mess Events')
@section('setup_content')
<div class="container-fluid">
    <h4>Mess Events</h4>
    <a href="{{ route('mess.events.create') }}" class="btn btn-primary mb-3">Add Event</a>
    <table class="table table-bordered">
        <thead><tr><th>Name</th><th>Description</th><th>Date</th></tr></thead>
        <tbody>
        @foreach($events as $event)
            <tr>
                <td>{{ $event->name }}</td>
                <td>{{ $event->description }}</td>
                <td>{{ $event->event_date }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
