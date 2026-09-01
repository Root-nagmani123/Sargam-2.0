@extends('admin.layouts.master')

@section('title', 'Full Form Preview')

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/peer-evaluation-admin.css') }}?v={{ @filemtime(public_path('css/peer-evaluation-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // The scope query string, reused by the edit pencil.
    $scope = array_filter([
        'course_id' => $courseId,
        'event_id'  => $eventId,
        'group_id'  => $groupId,
    ]);
@endphp
<div class="container-fluid pe-page pe-preview-page">
    <x-breadcrum :title="'Full Form Preview'"
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

        {{-- The Reflection Only / Full Form pair lived here. Reflection-only showed
             a fragment of a form no OT is ever served, so there is one view now:
             the whole form, which is the thing worth checking. --}}
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h6 class="pe-preview-heading mb-0">Evaluation Form</h6>
                    {{-- Live, not disabled: it is the one control on this page that
                         has something to show, and it behaves exactly like the OT
                         form's own toggle. Rendered only when a criterion on this
                         form asks for remarks, same gate the real form applies. --}}
                    @if($allowsRemarks)
                    <label class="d-inline-flex align-items-center gap-2 mb-0 pe-preview-remarks">
                        <input type="checkbox" class="form-check-input m-0" id="pePreviewRemarksToggle">
                        <span>Remarks</span>
                    </label>
                    @endif
                </div>

                <div class="programme-dt-panel mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                            <thead>
                                <tr>
                                    <th scope="col">S. No.</th>
                                    <th scope="col">Name &amp; OT Code</th>
                                    {{-- The range is the column's OWN max, not a
                                         hardcoded 10: Manage Evaluation Columns
                                         gives each criterion its own Max Marks and
                                         the OT form caps on that, so printing
                                         "(1-10)" against a column capped at 5 was a
                                         preview of a form that does not exist. --}}
                                    @forelse($columns as $column)
                                        <th scope="col">{{ $column->column_name }} (0-{{ rtrim(rtrim(number_format((float) ($column->max_marks ?? 10), 2, '.', ''), '0'), '.') }})</th>
                                    @empty
                                        <th scope="col">Score</th>
                                    @endforelse
                                    @if($allowsRemarks)
                                        <th scope="col" class="pe-preview-remarks-col d-none">Remarks</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($members as $index => $member)
                                    <tr>
                                        <td>{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                        <td>
                                            <div class="pe-preview-member">{{ $member->first_name ?: 'Unnamed' }}</div>
                                            <div class="pe-preview-otcode">- {{ $member->ot_code ?: 'No OT code' }}</div>
                                        </td>
                                        @forelse($columns as $column)
                                            <td>
                                                <input type="number" class="form-control pe-preview-score"
                                                       value="0.00" step="0.01" min="0"
                                                       max="{{ $column->max_marks ?? 10 }}" disabled
                                                       aria-label="{{ $column->column_name }} for {{ $member->first_name }}">
                                            </td>
                                        @empty
                                            <td>
                                                <input type="number" class="form-control pe-preview-score"
                                                       value="0.00" step="0.01" disabled aria-label="Score">
                                            </td>
                                        @endforelse
                                        @if($allowsRemarks)
                                            <td class="pe-preview-remarks-col d-none">
                                                <textarea class="form-control pe-control" rows="2" disabled
                                                          placeholder="Optional note about {{ $member->first_name }}"
                                                          aria-label="Remarks for {{ $member->first_name }}"></textarea>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 2 + max(1, $columns->count()) + ($allowsRemarks ? 1 : 0) }}" class="text-center py-4 text-body-secondary">
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

@if($allowsRemarks)
    <script>
        /* Same behaviour as the OT form's own Remarks toggle: the cells are
           <th>/<td>, so one class keeps the header and the body in step. */
        (function () {
            var toggle = document.getElementById('pePreviewRemarksToggle');
            if (!toggle) { return; }

            toggle.addEventListener('change', function () {
                document.querySelectorAll('.pe-preview-remarks-col').forEach(function (cell) {
                    cell.classList.toggle('d-none', !toggle.checked);
                });
            });
        })();
    </script>
@endif
@endsection
