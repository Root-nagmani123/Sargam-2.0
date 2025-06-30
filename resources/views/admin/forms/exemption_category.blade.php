@extends('admin.layouts.master')

@section('title', 'Exemption Master | Lal Bahadur Shastri National Academy of Administration')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet" />

<div class="container-fluid">
     <x-breadcrum title="Exemption Master" />
    <x-session_message />
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold">Exemption Master</h4>
                <a href="{{ route('exemptionCreate') }}" class="btn btn-primary">+ Add Exemption</a>
            </div>

            {{-- <div class="card card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif --}}

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
        <h5 class="mb-3">Exemptions</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>S.No</th>
                        <th>Exemption Name</th>
                        <th>Description</th>
                        <th>Created Date</th>
                        <th>Created By</th>
                        <th>Modified By</th>
                        <th>Action</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($headings as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->Exemption_name }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('Y-m-d') }}</td>
                        <td>{{ $item->creator->name ?? 'N/A' }}</td>
                        <td>{{ $item->updater->name ?? 'N/A' }}</td>

                        <td>
                            <a href="{{ route('exemptionEdit', $item->pk) }}" class="btn btn-sm btn-info">Edit</a>
                        </td>
                        <td>
                            <div class='form-check form-switch d-inline-block'>
                                <input class='form-check-input status-toggle' type='checkbox' role='switch'
                                    data-table='fc_exemption_master' data-column='visible' data-id='{{ $item->pk }}'
                                    {{ $item->visible == 1 ? 'checked' : '' }}>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">No exemptions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
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

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
$(document).ready(function() {
    $('.summernote').summernote({
        height: 200,
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link']],
            ['view', ['fullscreen', 'codeview']]
        ]
    });
});
</script>
@endsection