@extends('admin.layouts.master')

@section('title', isset($courseMemoMap) ? 'Edit Course Memo Mapping' : 'Add Course Memo Mapping')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Course Memo Decision Mapping" />
    <x-session_message />

    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ isset($courseMemoMap) ? 'Edit' : 'Add' }} Course Memo Mapping
            </h4>
            <hr>
            @if(isset($courseMemoMap))

            <form method="POST"
                action="{{ route('course.memo.decision.update', ['id' => encrypt($courseMemoMap->pk)]) }}">
                @else
                <form method="POST" action="{{ route('course.memo.decision.store') }}">
                    @endif
                    @csrf
                    @if(isset($courseMemoMap))
                    <input type="hidden" name="pk" value="{{ encrypt($courseMemoMap->pk) }}">
                    @endif

                    <div class="row">
                        <!-- Course Dropdown -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="course_master_pk" class="form-label">Select Course <span style="color:red;">*</span></label>
                                <select name="course_master_pk" class="form-select" required>
                                    <option value="">-- Select Course --</option>
                                    @foreach($CourseMaster as $course)
                                    <option value="{{ $course->pk }}"
                                        {{ (old('course_master_pk', $courseMemoMap->course_master_pk ?? '') == $course->pk) ? 'selected' : '' }}>
                                        {{ $course->course_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('course_id')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- Memo Dropdown -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="memo_decision_master_pk" class="form-label">Select Memo
                                    <span style="color:red;">*</span></label>
                                <select name="memo_type_master_pk" class="form-select" required>
                                    <option value="">-- Select Memo --</option>
                                    @foreach($MemoTypeMaster as $memo)
                                    <option value="{{ $memo->pk }}"
                                        {{ (old('memo_type_master_pk', $courseMemoMap->memo_type_master_pk ?? '') == $memo->pk) ? 'selected' : '' }}>
                                        {{ $memo->memo_type_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('memo_type_master_pk')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="memo_conclusion_master_pk" class="form-label">Select Memo Conclusion
                                    <span style="color:red;">*</span></label>
                                <select name="memo_conclusion_master_pk" class="form-select" required>
                                    <option value="">-- Select Memo Conclusion --</option>
                                    @foreach($MemoConclusionMaster as $memo)
                                    <option value="{{ $memo->pk }}"
                                        {{ (old('memo_conclusion_master_pk', $courseMemoMap->memo_conclusion_master_pk ?? '') == $memo->pk) ? 'selected' : '' }}>
                                        {{ $memo->discussion_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('memo_conclusion_master_pk')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>


                        <!-- Status -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="active_inactive" class="form-label">Status <span style="color:red;">*</span></label>
                                <select name="active_inactive" class="form-select" required>
                                    <option value="1"
                                        {{ (old('active_inactive', $courseMemoMap->active_inactive ?? 1) == 1) ? 'selected' : '' }}>
                                        Active
                                    </option>
                                    <option value="2"
                                        {{ (old('active_inactive', $courseMemoMap->active_inactive ?? 1) == 2) ? 'selected' : '' }}>
                                        Inactive
                                    </option>
                                </select>
                                @error('active_inactive')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            {{ isset($courseMemoMap) ? 'Update' : 'Submit' }}
                        </button>
                        <a href="{{ route('course.memo.decision.index') }}" class="btn btn-secondary">Back</a>
                    </div>
                </form>
        </div>
    </div>
</div>
@endsection