@extends('admin.layouts.master')

@section('title', 'Exemption Master | Lal Bahadur Shastri National Academy of Administration')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="container-fluid exemption-master-page py-3">
    <x-breadcrum title="Exemption Master">
        <a href="{{ route('exemptionCreate') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Exemption</span>
        </a>
    </x-breadcrum>

    <div id="status-msg" class="mb-3"></div>
    <x-session_message />

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Laravel paginates this list, so the global DataTables enhancer must not
         take over the hand-written footer (opt-out is honoured on any ancestor). --}}
    <div class="card overflow-hidden rounded-3" data-sargam-dt-ui="false">
        <div class="card-body p-3 p-md-4">

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap align-middle mb-0 w-100 programme-dt-table" id="exemptionMasterTable">
                        <thead>
                            <tr>
                                <th style="width:70px;" class="text-center">S.No</th>
                                <th>Exemption Name</th>
                                <th>Description</th>
                                <th>Created Date</th>
                                <th>Created By</th>
                                <th>Modified By</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:90px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($headings as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $headings->firstItem() + $index }}</td>
                                    <td>{{ $item->Exemption_name }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d-m-Y') }}</td>
                                    <td>{{ $item->creator->name ?? 'N/A' }}</td>
                                    <td>{{ $item->updater->name ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <div class="form-check form-switch programme-action-switch d-inline-block mb-0">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                   data-table="fc_exemption_master" data-column="visible" data-id="{{ $item->pk }}"
                                                   {{ $item->visible == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center programme-action-group"
                                             role="group" aria-label="Row actions">
                                            <a href="{{ route('exemptionEdit', $item->pk) }}" class="programme-action-btn"
                                               title="Edit" aria-label="Edit exemption">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No exemptions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Footer variant B: Laravel paginator reusing the DataTables class
                 names so the programme-dt footer styling applies unchanged. --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                <div class="programme-dt-pagination">
                    {{ $headings->withQueryString()->links('vendor.pagination.custom') }}
                </div>
                <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <div class="dataTables_info">
                        Showing {{ $headings->firstItem() ?? 0 }}&ndash;{{ $headings->lastItem() ?? 0 }}
                        of {{ number_format($headings->total()) }} items
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="card overflow-hidden rounded-3 mt-4">
        <div class="card-body p-3 p-md-4">
            <h5 class="mb-3 fw-semibold">Important Notice</h5>
            <form action="{{ route('exemptionUpdateNotice') }}" method="POST">
                @csrf
                <textarea class="form-control summernote" name="important_notice" rows="6">{{ old('important_notice', $notice?->description ?? '') }}</textarea>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary rounded-1 px-4">Update Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
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
