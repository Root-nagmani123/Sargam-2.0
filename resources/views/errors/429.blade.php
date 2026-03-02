@php
    $code = 429;
    $title = 'Too Many Requests';
    $message = "You're making too many requests. Please slow down and try again later.";
@endphp
@extends('errors.layout')
@section('buttons')
    <a href="{{ url('/') }}" class="btn btn-error-home btn-lg px-4 py-3 fw-semibold">Go Home</a>
    <button type="button" onclick="history.back()" class="btn btn-error-secondary btn-lg px-4 py-3 fw-semibold">Go Back</button>
@endsection
