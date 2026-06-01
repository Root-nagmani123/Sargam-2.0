@extends('admin.layouts.master')

@section('title', 'Class Session Master')

@section('setup_content')
<script>
    window.location.replace(@json(route('master.class.session.index', ['open_csm_modal' => 'add'])));
</script>
@endsection
