@extends('admin.layouts.master')

@section('title', 'Edit Stream')

@section('setup_content')
<script>
    window.location.replace(@json(route('stream.index', ['open_stm_modal' => 'edit', 'stm_id' => $stream->pk ?? request()->route('stream')])));
</script>
@endsection
