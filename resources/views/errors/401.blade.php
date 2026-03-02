@php
    $code = 401;
    $title = 'Unauthorized';
    $message = 'You need to be authenticated to access this resource. Please log in to continue.';
@endphp
@extends('errors.layout')
@section('buttons')
    <a href="{{ url('/') }}" class="btn btn-error-home btn-lg px-4 py-3 fw-semibold">Log In</a>
    <a href="{{ url('/') }}" class="btn btn-error-contact btn-lg px-4 py-3 fw-semibold">Go Home</a>
@endsection
