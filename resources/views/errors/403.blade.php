@php
    $code = 403;
    $title = 'Access Forbidden';
    $message = "You don't have permission to access this resource. If you believe this is a mistake, please contact the administrator.";
@endphp
@extends('errors.layout')
@section('buttons')
    <a href="{{ url('/') }}" class="btn btn-error-home btn-lg px-4 py-3 fw-semibold">Go Home</a>
    <button type="button" onclick="history.back()" class="btn btn-error-secondary btn-lg px-4 py-3 fw-semibold">Go Back</button>
@endsection
