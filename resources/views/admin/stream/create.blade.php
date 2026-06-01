@extends('admin.layouts.master')

@section('title', 'Stream')

@section('setup_content')
<script>
    window.location.replace(@json(route('stream.index', ['open_stm_modal' => 'add'])));
</script>
@endsection
