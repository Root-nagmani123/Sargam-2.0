@extends('admin.layouts.master')

@section('title', 'Create District')

@section('setup_content')
<script>
    window.location.replace(@json(route('master.district.index', ['open_dst_modal' => 'add'])));
</script>
@endsection
