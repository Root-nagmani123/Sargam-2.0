@php
    $code = 404;
    $title = 'Page Not Found';
    $message = "Sorry! The page you're looking for doesn't exist. It might have been moved or deleted.";
@endphp
@extends('errors.layout')
@section('buttons')
    <a href="{{ url('/') }}" class="btn btn-error-home btn-lg px-4 py-3 fw-semibold">Go Home</a>
    <button type="button" onclick="history.back()" class="btn btn-error-secondary btn-lg px-4 py-3 fw-semibold">Go Back</button>
@endsection
