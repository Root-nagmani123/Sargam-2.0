@extends('admin.layouts.master')

@section('title', 'Exemption Master | Lal Bahadur Shastri National Academy of Administration')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet" />
@endpush

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Exemption Master" />
    <div id="status-msg" class="mb-3"></div>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Exemption Master</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-end mb-3">
                        <div class="d-flex align-items-center gap-2">

                            <!-- Add New Button -->
                            <a href="{{ route('exemptionCreate') }}"
                                class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 20px; vertical-align: middle;">add</i>
                                Add Exemption
                            </a>

                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <!--display errors if any -->
            @if ($errors->any())
            <div class="alert alert-danger mb-3">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table text-nowrap w-100']) !!}
            </div>
        </div>
    </div>
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <h5 class="mb-3">Important Notice</h5>
            <form action="{{ route('exemptionUpdateNotice') }}" method="POST">
                @csrf
                <textarea class="form-control summernote" name="important_notice" rows="6">
        {{ old('important_notice', $notice?->description ?? '') }}
             </textarea>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-success px-4">Update Notice</button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
$(document).ready(function() {
    $('.summernote').summernote({
        height: 200,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript',
                'subscript', 'clear'
            ]],
            ['fontname', ['fontname']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['color', ['forecolor']], // Changed from 'color' to 'forecolor'
            ['para', ['ul', 'ol', 'paragraph', 'align']],
            ['height', ['height']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video', 'pdf']],
            ['view', ['fullscreen', 'codeview', 'help']],
            ['misc', ['undo', 'redo']]
        ]
    });
});
</script>
@endpush