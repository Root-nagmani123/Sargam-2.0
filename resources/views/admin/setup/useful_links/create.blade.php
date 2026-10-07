@extends('admin.layouts.master')

@section('title', 'Create Useful Link - Sargam | Lal Bahadur Shastri')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/useful-links-admin.css') }}?v={{ @filemtime(public_path('css/useful-links-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
{{-- The listing loads this same form into a modal over AJAX; this page is the
     no-JS / direct-link fallback. Both render _form.blade.php so the two can't
     drift apart — only $inModal changes, which swaps the footer's Cancel for a
     link back to the listing. --}}
<div class="container-fluid ul-page">
    <x-breadcrum title="Create Useful Link" :showBack="true"
                 :backUrl="route('admin.setup.useful_links.index')" />

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            @include('admin.setup.useful_links._form', ['inModal' => false])
        </div>
    </div>
</div>
@endsection
