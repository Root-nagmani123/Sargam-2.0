@extends('admin.layouts.master')

@section('title', $mode === 'full' ? 'Full Form Preview' : 'Preview Reflection Fields')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/peer-evaluation-admin.css') }}?v={{ @filemtime(public_path('css/peer-evaluation-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // The scope query string, reused by the mode links and the edit pencil.
    $scope = array_filter([
        'course_id' => $courseId,
        'event_id'  => $eventId,
        'group_id'  => $groupId,
    ]);
@endphp
<div class="container-fluid pe-page pe-preview-page">
    <x-breadcrum :title="$mode === 'full' ? 'Full Form Preview' : 'Preview Reflection Fields'"
                 :items="[
                     ['label' => 'Home', 'url' => route('admin.dashboard')],
                     ['label' => 'Setup', 'url' => null],
                     ['label' => 'FC Forms', 'url' => null],
                     ['label' => 'Peer Evaluation', 'url' => route('admin.peer.reflection-fields.index')],
                     ['label' => 'Form Preview', 'url' => null],
                 ]" />

    {{-- Scope row. The three selects are DISABLED on purpose: this page previews
         one particular scope, it doesn't re-filter. Remove Filter goes back to the
         grid, which is where filtering actually happens. --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3 pe-preview-scope">
        <span class="programme-dt-filters-label">Filter</span>

        @foreach ([
            ['Course Name', $courseName],
            ['Event Name',  $eventName],
            ['Group Name',  $groupName],
        ] as [$placeholder, $value])
            <div class="programme-dt-filter-select">
                <select class="form-select" disabled aria-label="{{ $placeholder }} (preview scope)">
                    <option>{{ $value ?: $placeholder }}</option>
                </select>
            </div>
        @endforeach

        <a href="{{ route('admin.peer.reflection-fields.index') }}"
           class="btn programme-dt-btn-reset">Remove Filter</a>

        {{-- Not in the mockups, but both of them exist and nothing else reaches the
             other one. One control, two states. --}}
        <div class="btn-group ms-auto pe-preview-modes" role="group" aria-label="Preview mode">
            <a href="{{ route('admin.peer.reflection-fields.preview', $scope + ['mode' => 'reflection']) }}"
               class="btn {{ $mode === 'reflection' ? 'active' : '' }}"
               @if($mode === 'reflection') aria-current="page" @endif>Reflection Only</a>
            <a href="{{ route('admin.peer.reflection-fields.preview', $scope + ['mode' => 'full']) }}"
               class="btn {{ $mode === 'full' ? 'active' : '' }}"
               @if($mode === 'full') aria-current="page" @endif>Full Form</a>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            @if($mode === 'full')
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h6 class="pe-preview-heading mb-0">Evaluation Form</h6>
                    <label class="d-inline-flex align-items-center gap-2 mb-0 pe-preview-remarks">
                        <input type="checkbox" class="form-check-input m-0" disabled>
                        <span>Remarks</span>
                    </label>
                </div>

                <div class="programme-dt-panel mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                            <thead>
                                <tr>
                                    <th scope="col">S. No.</th>
                                    <th scope="col">Name &amp; OT Code</th>
                                    @forelse($columns as $column)
                                        <th scope="col">{{ $column->column_name }} (1-10)</th>
                                    @empty
                                        <th scope="col">Score</th>
                                    @endforelse
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($members as $index => $member)
                                    <tr>
                                        <td>{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                        <td>
                                            <div class="pe-preview-member">{{ $member->user_name ?: 'Unnamed' }}</div>
                                            <div class="pe-preview-otcode">- {{ $member->ot_code ?: 'No OT code' }}</div>
                                        </td>
                                        @forelse($columns as $column)
                                            <td>
                                                <input type="number" class="form-control pe-preview-score"
                                                       value="0.00" step="0.01" min="0" max="10" disabled
                                                       aria-label="{{ $column->column_name }} for {{ $member->user_name }}">
                                            </td>
                                        @empty
                                            <td>
                                                <input type="number" class="form-control pe-preview-score"
                                                       value="0.00" step="0.01" disabled aria-label="Score">
                                            </td>
                                        @endforelse
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 2 + max(1, $columns->count()) }}" class="text-center py-4 text-body-secondary">
                                            @if(! $groupId)
                                                Pick a group to preview its members — this scope has no group set.
                                            @else
                                                This group has no members yet.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="d-flex align-items-center gap-2 mb-3">
                <h6 class="pe-preview-heading mb-0">Reflection &amp; Feedback</h6>
                <a href="{{ route('admin.peer.reflection-fields.index', array_filter([
                        'course_filter' => $courseId,
                        'event_filter'  => $eventId,
                   ])) }}"
                   class="pe-preview-edit" title="Edit these reflection fields" aria-label="Edit these reflection fields">
                    <i class="bi bi-pencil" aria-hidden="true"></i>
                </a>
            </div>

            @forelse($fields as $field)
                <div class="pe-field mb-3">
                    <label class="pe-form-label" for="preview_field_{{ $field->id }}">
                        {{ $field->field_label }}<span class="pe-req">*</span>
                    </label>
                    <textarea class="form-control pe-control" id="preview_field_{{ $field->id }}" rows="3"
                              placeholder="Write your thoughts here" disabled></textarea>
                </div>
            @empty
                <p class="text-body-secondary mb-3">
                    No active reflection fields apply to this scope, so the form would show none.
                </p>
            @endforelse

            {{-- Disabled throughout: this previews what an Officer Trainee sees, it
                 is not a form an admin submits. --}}
            <div class="d-flex flex-wrap justify-content-end gap-3 mt-4 pe-preview-actions">
                <button type="button" class="btn pe-preview-btn pe-preview-btn--cancel" disabled>Cancel</button>
                <button type="button" class="btn pe-preview-btn pe-preview-btn--reset" disabled>Reset Scores</button>
                <button type="button" class="btn pe-preview-btn pe-preview-btn--submit" disabled>Submit Evaluation</button>
            </div>

        </div>
    </div>
</div>
@endsection
