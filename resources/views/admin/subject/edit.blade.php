@extends('admin.layouts.master')

@section('title', 'Edit Subject - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid py-5">
    <p class="text-secondary mb-0">Opening Edit Subject…</p>
</div>
<script>
    window.location.replace(@json(route('subject.index', ['open_edit_subject' => request()->route('subject')])));
</script>
@endsection
