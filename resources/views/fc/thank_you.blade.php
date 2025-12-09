@extends('fc.layouts.master')

@section('title', 'Thank You - Foundation Course')

@section('setup_content')
<div class="container mt-5 h-100 " style="flex: 1;">
    <div class="card shadow p-4 text-center">
        <h2 class="text-success fw-bold">Thank you!</h2>
        <p class="mt-3">Your exemption application has been submitted successfully.</p>
        <div class="mb-3 d-flex justify-content-center">
         <a href="{{ route('fc.choose.path') }}" class="btn btn-primary mt-4 text-center" style="background-color: #004a93; border-color: #004a93;">Go to Home</a>
       </div>
    </div>
</div>
@endsection
