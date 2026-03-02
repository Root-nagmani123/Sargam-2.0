@php
    $code = 400;
    $title = 'Bad Request';
    $message = 'The request could not be understood or was invalid. Please check your input and try again.';
@endphp
@extends('errors.layout')
@section('buttons')
    <a href="{{ url('/') }}" class="btn btn-error-home btn-lg px-4 py-3 fw-semibold">Go Home</a>
    <button type="button" onclick="history.back()" class="btn btn-error-secondary btn-lg px-4 py-3 fw-semibold">Go Back</button>
@endsection
