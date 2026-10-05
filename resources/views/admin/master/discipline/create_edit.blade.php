@extends('admin.layouts.master')

@section('title', isset($discipline) ? 'Edit Discipline' : 'Add Discipline')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // One value drives the Status select. The old markup defaulted each option
    // separately (?? 1 and ?? 2), so both were "selected" on Add and the browser
    // kept the last one — new records silently defaulted to Inactive.
    // The grid's switch saves inactive as 0, which neither option matches — read
    // anything other than 1 as Inactive so saving can't silently re-activate it.
    $disciplineStatus = (string) old('active_inactive', isset($discipline) && (int) $discipline->active_inactive !== 1 ? 2 : 1);
@endphp
<div class="container-fluid mst-page">

    <x-breadcrum title="{{ isset($discipline) ? 'Edit Discipline' : 'Add Discipline' }}" />
    <x-session_message />

    <form method="POST" action="{{ route('master.discipline.store') }}">
        @csrf

        @if(isset($discipline))
            <input type="hidden" name="id" value="{{ encrypt($discipline->pk) }}">
        @endif

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Discipline Details</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="discCourse" class="mst-form-label d-block">
                            Course <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="course_master_pk" id="discCourse"
                                class="form-select mst-control mst-searchable @error('course_master_pk') is-invalid @enderror"
                                data-placeholder="Select Course" required aria-required="true"
                                @error('course_master_pk') aria-describedby="discCourseError" @enderror>
                            <option value="">Select Course</option>
                            @foreach($courses as $c)
                                <option value="{{ $c->pk }}"
                                    {{ old('course_master_pk', $discipline->course_master_pk ?? '') == $c->pk ? 'selected' : '' }}>
                                    {{ $c->course_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('course_master_pk')
                            <span class="mst-field-error" id="discCourseError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="discName" class="mst-form-label d-block">
                            Discipline Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="text" name="discipline_name" id="discName"
                               class="form-control mst-control @error('discipline_name') is-invalid @enderror"
                               value="{{ old('discipline_name', $discipline->discipline_name ?? '') }}"
                               placeholder="Enter discipline name" maxlength="100" required aria-required="true"
                               @error('discipline_name') aria-describedby="discNameError" @enderror>
                        @error('discipline_name')
                            <span class="mst-field-error" id="discNameError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="discMark" class="mst-form-label d-block">
                            Mark Deduction <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="number" step="0.01" name="mark_deduction" id="discMark"
                               class="form-control mst-control @error('mark_deduction') is-invalid @enderror"
                               value="{{ old('mark_deduction', $discipline->mark_deduction ?? '') }}"
                               placeholder="Enter mark deduction" required aria-required="true"
                               @error('mark_deduction') aria-describedby="discMarkError" @enderror>
                        @error('mark_deduction')
                            <span class="mst-field-error" id="discMarkError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="discStatus" class="mst-form-label d-block">
                            Status <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="active_inactive" id="discStatus"
                                class="form-select mst-control mst-searchable @error('active_inactive') is-invalid @enderror"
                                data-placeholder="Select Status" required aria-required="true"
                                @error('active_inactive') aria-describedby="discStatusError" @enderror>
                            <option value="1" @selected($disciplineStatus === '1')>Active</option>
                            <option value="2" @selected($disciplineStatus === '2')>Inactive</option>
                        </select>
                        @error('active_inactive')
                            <span class="mst-field-error" id="discStatusError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('master.discipline.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4">
                        {{ isset($discipline) ? 'Update' : 'Save' }}
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
