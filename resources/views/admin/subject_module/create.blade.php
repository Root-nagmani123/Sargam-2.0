@extends('admin.layouts.master')

@section('title', 'Subject Module - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid py-5">
    <p class="text-secondary mb-0">Opening Add Module…</p>
</div>
<script>
    window.location.replace(@json(route('subject-module.index', ['open_add_module' => 1])));
</script>
@endsection
