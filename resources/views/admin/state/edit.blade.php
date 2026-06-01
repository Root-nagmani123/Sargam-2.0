@extends('admin.layouts.master')

@section('title', 'Edit State')

@section('setup_content')
<script>
    window.location.replace(@json(route('master.state.index', ['open_stt_modal' => 'edit', 'stt_id' => $state->pk ?? request()->route('id')])));
</script>
@endsection
