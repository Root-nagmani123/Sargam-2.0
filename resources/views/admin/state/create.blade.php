@extends('admin.layouts.master')

@section('title', 'State')

@section('setup_content')
<script>
    window.location.replace(@json(route('master.state.index', ['open_stt_modal' => 'add'])));
</script>
@endsection
