@php
    $code = 500;
    $title = 'Server Error';
    $message = "Oops! Something went wrong on our end. Our team has been notified and is working to fix it.";
@endphp
@extends('errors.layout')
@section('buttons')
    <a href="{{ url('/') }}" class="btn btn-error-home btn-lg px-4 py-3 fw-semibold">Go Home</a>
    <a href="{{ url('/') }}#contact" class="btn btn-error-contact btn-lg px-4 py-3 fw-semibold">Contact Us</a>
@endsection
