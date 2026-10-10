@extends('admin.layouts.master')

@section('title', isset($courseMemoMap) ? 'Edit Course Memo Mapping' : 'Add Course Memo Mapping')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // Same targets as before: update for an existing mapping, store otherwise.
    $cmdmFormAction = isset($courseMemoMap)
        ? route('course.memo.decision.update', ['id' => encrypt($courseMemoMap->pk)])
        : route('course.memo.decision.store');
    $cmdmStatus = old_string('active_inactive', $courseMemoMap->active_inactive ?? 1);
@endphp
<div class="container-fluid mst-page cmdm-page">

    <x-breadcrum title="{{ isset($courseMemoMap) ? 'Edit Course Memo Mapping' : 'Add Course Memo Mapping' }}" />
    <x-session_message />

    <form method="POST" action="{{ $cmdmFormAction }}">
        @csrf
        @if(isset($courseMemoMap))
            <input type="hidden" name="pk" value="{{ encrypt($courseMemoMap->pk) }}">
        @endif

        {{-- Hierarchy: the course first, then the decision mapped to it. --}}
        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Mapped Course</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="cmdmCourse" class="mst-form-label d-block">
                            Course <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="course_master_pk" id="cmdmCourse"
                                class="form-select mst-control mst-searchable @error('course_master_pk') is-invalid @enderror"
                                data-placeholder="Select Course" required aria-required="true"
                                @error('course_master_pk') aria-describedby="cmdmCourseError" @enderror>
                            <option value="">Select Course</option>
                            @foreach($CourseMaster as $course)
                                <option value="{{ $course->pk }}"
                                    {{ (old('course_master_pk', $courseMemoMap->course_master_pk ?? '') == $course->pk) ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('course_master_pk')
                            <span class="mst-field-error" id="cmdmCourseError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Memo Decision &amp; Conclusion</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="cmdmMemo" class="mst-form-label d-block">
                            Memo Decision <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="memo_type_master_pk" id="cmdmMemo"
                                class="form-select mst-control mst-searchable @error('memo_type_master_pk') is-invalid @enderror"
                                data-placeholder="Select Memo Decision" required aria-required="true"
                                @error('memo_type_master_pk') aria-describedby="cmdmMemoError" @enderror>
                            <option value="">Select Memo Decision</option>
                            @foreach($MemoTypeMaster as $memo)
                                <option value="{{ $memo->pk }}"
                                    {{ (old('memo_type_master_pk', $courseMemoMap->memo_type_master_pk ?? '') == $memo->pk) ? 'selected' : '' }}>
                                    {{ $memo->memo_type_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('memo_type_master_pk')
                            <span class="mst-field-error" id="cmdmMemoError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="cmdmConclusion" class="mst-form-label d-block">
                            Memo Conclusion <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="memo_conclusion_master_pk" id="cmdmConclusion"
                                class="form-select mst-control mst-searchable @error('memo_conclusion_master_pk') is-invalid @enderror"
                                data-placeholder="Select Memo Conclusion" required aria-required="true"
                                @error('memo_conclusion_master_pk') aria-describedby="cmdmConclusionError" @enderror>
                            <option value="">Select Memo Conclusion</option>
                            @foreach($MemoConclusionMaster as $memo)
                                <option value="{{ $memo->pk }}"
                                    {{ (old('memo_conclusion_master_pk', $courseMemoMap->memo_conclusion_master_pk ?? '') == $memo->pk) ? 'selected' : '' }}>
                                    {{ $memo->discussion_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('memo_conclusion_master_pk')
                            <span class="mst-field-error" id="cmdmConclusionError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="cmdmStatus" class="mst-form-label d-block">
                            Status <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="active_inactive" id="cmdmStatus"
                                class="form-select mst-control mst-searchable @error('active_inactive') is-invalid @enderror"
                                data-placeholder="Select Status" required aria-required="true"
                                @error('active_inactive') aria-describedby="cmdmStatusError" @enderror>
                            <option value="1" @selected($cmdmStatus === '1')>Active</option>
                            <option value="2" @selected($cmdmStatus !== '1')>Inactive</option>
                        </select>
                        @error('active_inactive')
                            <span class="mst-field-error" id="cmdmStatusError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('course.memo.decision.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4">
                        {{ isset($courseMemoMap) ? 'Update' : 'Save' }}
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
