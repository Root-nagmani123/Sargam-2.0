@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid py-5">
    <p class="text-secondary mb-0">Opening Add Subject…</p>
</div>
<script>
    window.location.replace(@json(route('subject.index', ['open_add_subject' => 1])));
</script>
@endsection
