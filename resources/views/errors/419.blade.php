@php
    $code = 419;
    $title = 'Session Expired';
    $message = 'Your session has expired. Please log in again to continue.';
@endphp
@extends('errors.layout')
@section('buttons')
    <a href="{{ url('/') }}" class="btn btn-error-home btn-lg px-4 py-3 fw-semibold">Log In</a>
    <a href="{{ url('/') }}" class="btn btn-error-contact btn-lg px-4 py-3 fw-semibold">Go Home</a>
@endsection
