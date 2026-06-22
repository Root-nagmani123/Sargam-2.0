@extends('admin.layouts.master')

@section('title', 'Edit Module - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid py-5">
    <p class="text-secondary mb-0">Opening Edit Module…</p>
</div>
<script>
    window.location.replace(@json(route('subject-module.index', ['open_edit_module' => request()->route('subject_module')])));
</script>
@endsection
