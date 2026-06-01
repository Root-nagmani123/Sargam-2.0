@extends('admin.layouts.master')

@section('title', 'Edit District')

@section('setup_content')
<script>
    window.location.replace(@json(route('master.district.index', ['open_dst_modal' => 'edit', 'dst_id' => $district->pk ?? request()->route('id')])));
</script>
@endsection
