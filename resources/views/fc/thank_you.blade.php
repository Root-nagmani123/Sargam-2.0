@extends('fc.layouts.master')

@section('title', 'Thank You - Foundation Course')

@section('content')
<div class="container mt-5 h-100 " style="flex: 1;">
    <div class="card shadow p-4 text-center">
        <h2 class="text-success fw-bold">Thank you!</h2>
        <p class="mt-3">Your exemption application has been submitted successfully.</p>
        <a href="{{ route('fc.choose.path') }}" class="btn btn-primary mt-4" style="background-color: #004a93; border-color: #004a93;">Go to Home</a>
    </div>
</div>
@endsection
