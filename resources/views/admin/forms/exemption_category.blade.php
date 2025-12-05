@extends('admin.layouts.master')

@section('title', 'Exemption Master | Lal Bahadur Shastri National Academy of Administration')

@section('setup_content')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet" />

<div class="container-fluid">
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
                <table class="table text-nowrap w-100">
                    <thead>
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
                            <td>{{ $headings->firstItem() + $index }}</td>
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

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                    <div class="text-muted small mb-2">
                        Showing {{ $headings->firstItem() }}
                        to {{ $headings->lastItem() }}
                        of {{ $headings->total() }} items
                    </div>

                    <div>
                        {{ $headings->links('vendor.pagination.custom') }}
                    </div>

                </div>
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
@endsection