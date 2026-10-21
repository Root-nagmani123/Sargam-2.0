@extends('admin.layouts.master')

@section('title', 'Create Course - Programme - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid programme-create-page">
    <x-breadcrum title="Create Course"></x-breadcrum>
    <x-session_message />

    @include('admin.programme.partials.form', ['isEdit' => false])
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="{{ asset('js/programme.js') }}"></script>
@endpush
