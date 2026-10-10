@extends('admin.layouts.master')

@section('title', isset($conclusion) ? 'Edit Memo Conclusion' : 'Add Memo Conclusion')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // One value drives the Status select. The old markup defaulted the two
    // options separately (?? 1 and ?? 2), so both were "selected" on Add and the
    // browser kept the last one (Inactive). Inactive posts 0: store() saves
    // `active_inactive ? 1 : 0`, so the old "2" was saved as Active.
    $conclusionStatus = old_string('active_inactive', $conclusion->active_inactive ?? 1);
@endphp
<div class="container-fluid mst-page">

    <x-breadcrum title="{{ isset($conclusion) ? 'Edit Memo Conclusion' : 'Add Memo Conclusion' }}" />
    <x-session_message />

    <form method="POST" action="{{ route('master.memo.conclusion.master.store') }}">
        @csrf
        @if(isset($conclusion))
            <input type="hidden" name="id" value="{{ encrypt($conclusion->pk) }}">
        @endif

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Memo Conclusion Details</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="conclusionName" class="mst-form-label d-block">
                            Conclusion Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="text" name="discussion_name" id="conclusionName"
                               class="form-control mst-control @error('discussion_name') is-invalid @enderror"
                               value="{{ old('discussion_name', $conclusion->discussion_name ?? '') }}"
                               placeholder="Enter conclusion name" maxlength="100" required aria-required="true"
                               @error('discussion_name') aria-describedby="conclusionNameError" @enderror>
                        @error('discussion_name')
                            <span class="mst-field-error" id="conclusionNameError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="conclusionPt" class="mst-form-label d-block">PT Discussion</label>
                        <input type="text" name="pt_discusion" id="conclusionPt"
                               class="form-control mst-control @error('pt_discusion') is-invalid @enderror"
                               value="{{ old('pt_discusion', $conclusion->pt_discusion ?? '') }}"
                               placeholder="Enter PT discussion"
                               @error('pt_discusion') aria-describedby="conclusionPtError" @enderror>
                        @error('pt_discusion')
                            <span class="mst-field-error" id="conclusionPtError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="conclusionStatus" class="mst-form-label d-block">
                            Status <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="active_inactive" id="conclusionStatus"
                                class="form-select mst-control mst-searchable @error('active_inactive') is-invalid @enderror"
                                data-placeholder="Select Status" required aria-required="true"
                                @error('active_inactive') aria-describedby="conclusionStatusError" @enderror>
                            <option value="1" @selected($conclusionStatus === '1')>Active</option>
                            <option value="0" @selected($conclusionStatus !== '1')>Inactive</option>
                        </select>
                        @error('active_inactive')
                            <span class="mst-field-error" id="conclusionStatusError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('master.memo.conclusion.master.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4">
                        {{ isset($conclusion) ? 'Update' : 'Save' }}
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
