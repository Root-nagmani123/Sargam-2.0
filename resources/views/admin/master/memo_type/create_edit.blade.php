@extends('admin.layouts.master')

@section('title', isset($memoType) ? 'Edit Memo Type' : 'Add Memo Type')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // One value drives the Status select (Active unless the record says otherwise).
    $memoTypeStatus = old_string('active_inactive', $memoType->active_inactive ?? 1);
@endphp
<div class="container-fluid mst-page">

    <x-breadcrum title="{{ isset($memoType) ? 'Edit Memo Type' : 'Add Memo Type' }}" />
    <x-session_message />

    <form method="POST" action="{{ route('master.memo.type.master.store') }}" enctype="multipart/form-data">
        @csrf
        @if(isset($memoType))
            <input type="hidden" name="pk" value="{{ encrypt($memoType->pk) }}">
        @endif

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Memo Type Details</h2>

                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <label for="memoTypeName" class="mst-form-label d-block">
                            Memo Type Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="text" name="memo_type_name" id="memoTypeName"
                               class="form-control mst-control @error('memo_type_name') is-invalid @enderror"
                               value="{{ old('memo_type_name', $memoType->memo_type_name ?? '') }}"
                               placeholder="Enter memo type name" maxlength="100" required aria-required="true"
                               @error('memo_type_name') aria-describedby="memoTypeNameError" @enderror>
                        @error('memo_type_name')
                            <span class="mst-field-error" id="memoTypeNameError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label for="memoTypeDoc" class="mst-form-label d-block">Upload Document</label>
                        <input type="file" name="memo_doc_upload" id="memoTypeDoc"
                               class="form-control mst-control @error('memo_doc_upload') is-invalid @enderror"
                               accept=".pdf,.doc,.docx" aria-describedby="memoTypeDocHint">
                        <small class="text-muted d-block mt-1" id="memoTypeDocHint">PDF or Word (.doc, .docx), up to 2 MB.</small>
                        @if(isset($memoType) && $memoType->memo_doc_upload)
                            <a href="{{ asset('storage/' . $memoType->memo_doc_upload) }}" target="_blank" rel="noopener"
                               class="d-inline-flex align-items-center gap-1 mt-2 text-primary fw-medium">
                                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                                <span>Existing file: {{ basename($memoType->memo_doc_upload) }}</span>
                            </a>
                        @endif
                        @error('memo_doc_upload')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label for="memoTypeStatus" class="mst-form-label d-block">
                            Status <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="active_inactive" id="memoTypeStatus"
                                class="form-select mst-control mst-searchable @error('active_inactive') is-invalid @enderror"
                                data-placeholder="Select Status" required aria-required="true"
                                @error('active_inactive') aria-describedby="memoTypeStatusError" @enderror>
                            <option value="1" @selected($memoTypeStatus === '1')>Active</option>
                            <option value="2" @selected($memoTypeStatus !== '1')>Inactive</option>
                        </select>
                        @error('active_inactive')
                            <span class="mst-field-error" id="memoTypeStatusError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('master.memo.type.master.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4">
                        {{ isset($memoType) ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
@endpush
