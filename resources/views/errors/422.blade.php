@php
    $code = 422;
    $title = 'Unprocessable Content';
    $message = 'The request is well-formed but was unable to be processed. Please check your data and try again.';
@endphp
@extends('errors.layout')
@section('buttons')
    <a href="{{ url('/') }}" class="btn btn-error-home btn-lg px-4 py-3 fw-semibold">Go Home</a>
    <button type="button" onclick="history.back()" class="btn btn-error-secondary btn-lg px-4 py-3 fw-semibold">Go Back</button>
@endsection
