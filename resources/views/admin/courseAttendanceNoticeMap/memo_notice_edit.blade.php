@extends('admin.layouts.master')

@section('title', 'Edit Memo / Notice - Sargam | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<div class="container-fluid py-5">
    <p class="text-secondary mb-0">Opening Edit Template…</p>
</div>
<script>
    window.location.replace(@json(route('admin.memo-notice.index', ['open_edit_template' => $template->pk ?? request()->route('pk')])));
</script>
@endsection
