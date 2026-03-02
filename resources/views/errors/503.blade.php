@php
    $code = 503;
    $title = 'Service Unavailable';
    $message = "We're performing maintenance. Please try again in a few moments.";
@endphp
@extends('errors.layout')
@section('buttons')
    <button type="button" onclick="location.reload()" class="btn btn-error-home btn-lg px-4 py-3 fw-semibold">Refresh Page</button>
    <a href="{{ url('/') }}" class="btn btn-error-contact btn-lg px-4 py-3 fw-semibold">Go Home</a>
@endsection
