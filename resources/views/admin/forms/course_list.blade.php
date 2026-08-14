@extends('admin.layouts.master')
@section('title', 'Course List')

@section('setup_content')
    <div class="container-fluid course-list-page">
        <x-breadcrum title="Course List" />

        {{-- Export row above the card (§1). No status pills on this grid, so the row
             carries the running total on the left and the export alone on the right. --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <span class="fw-semibold text-secondary">Total Registered Students: {{ $total_students }}</span>

            <form action="{{ route('forms.export', ['formid' => $formid]) }}" method="GET"
                class="d-flex align-items-center gap-2">
                <label for="format" class="form-label mb-0 fw-semibold">Export:</label>
                <select name="format" id="format" class="form-select w-auto" required>
                    <option value="">Select Format </option>
                    <option value="xlsx">Excel (.xlsx)</option>
                    <option value="csv">CSV (.csv)</option>
                    <option value="pdf">PDF (.pdf)</option>
                </select>
                <input type="hidden" name="statusval" value="{{ request('statusval') }}">
                {{-- Carry the grid's search into the export so the file matches the screen. --}}
                <input type="hidden" name="search" value="{{ $search }}">
                <button type="submit" class="btn programme-dt-btn-columns border-0 text-primary">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
            </form>
        </div>

        <div class="card overflow-hidden rounded-3">
            <div class="card-body p-3 p-md-4">

                {{-- Toolbar (§2): Filters label + status select + red reset on the left,
                     search on the right. The status select and the search are separate
                     GET forms, so each carries the other's value as a hidden input —
                     otherwise submitting one silently clears the other. --}}
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        @if ($statusFilterAvailable)
                            <span class="programme-dt-filters-label">Filters</span>

                            {{-- Only rendered when fc_registration_master actually has a
                                 confirm_status column. It does not today, and querying it
                                 is a hard 500 — so the control is hidden rather than
                                 offered as a button that only ever errors. See the note
                                 in FormController::courseList(). --}}
                            <form method="GET" class="programme-dt-filter-select mb-0">
                                <input type="hidden" name="search" value="{{ $search }}">
                                <input type="hidden" name="per_page" value="{{ request('per_page', '25') }}">
                                <select name="statusval" onchange="this.form.submit()" class="form-select"
                                    aria-label="Confirmation status">
                                    <option value="">All Status</option>
                                    <option value="1" {{ $statusval == 1 ? 'selected' : '' }}>Confirmed</option>
                                    <option value="2" {{ $statusval == 2 ? 'selected' : '' }}>Not confirmed</option>
                                </select>
                            </form>
                        @endif

                        @if ($search !== '' || ($statusFilterAvailable && $statusval !== null && $statusval !== ''))
                            <a href="{{ route('forms.courseList', ['form' => $formid]) }}"
                                class="btn programme-dt-btn-reset">Reset Filters</a>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <x-programme-dt-search :action="route('forms.courseList', ['form' => $formid])"
                            placeholder="Search student name" label="Search by student name" :value="$search">
                            <input type="hidden" name="statusval" value="{{ request('statusval') }}">
                            <input type="hidden" name="per_page" value="{{ request('per_page', '25') }}">
                        </x-programme-dt-search>
                    </div>
                </div>





                <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- data-sargam-dt-ui="false": Laravel paginates this grid and the
                         footer below is hand-written (§5 "Opting out"). --}}
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table"
                        data-sargam-dt-ui="false">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                @foreach ($fields as $field)
                                    <th>{{ ucfirst($field) }}</th>
                                @endforeach
                                <th>Download PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($records as $record)
                                @php $uid = $record->uid; @endphp
                                @if (isset($users[$uid]))
                                    <tr>
                                        {{-- firstItem() so S.No reflects the row's position in the whole
                                             result set, not its position on this page. --}}
                                        <td>{{ $records->firstItem() + $loop->index }}</td> {{-- S.No column --}}
                                        @foreach ($fields as $field)
                                            <td>
                                                @php
                                                    $value = $users[$uid][$field] ?? '';
                                                    $extension = pathinfo($value, PATHINFO_EXTENSION);
                                                @endphp

                                                @if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']))
                                                    <img src="{{ asset('storage/' . $value) }}" width="100"
                                                        alt="Image">
                                                @elseif (strtolower($extension) === 'pdf')
                                                    <a href="{{ asset('storage/' . $value) }}" target="_blank "
                                                        style="color: #007bff; text-decoration: underline;">View PDF</a>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        @endforeach
                                        <td>
                                            <a href="{{ route('forms.pdf', ['form_id' => $formid, 'user_id' => $uid]) }}"
                                                style="color: #007bff; text-decoration: underline;">Download</a>
                                        </td>
                                        {{-- <td>{{ $record->confirm_status }}</td> --}}
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="{{ count($fields) + 2 }}" class="text-center py-4 text-muted">
                                        @if ($search !== '')
                                            No student matches “{{ $search }}”.
                                            <a
                                                href="{{ route('forms.courseList', ['form' => $formid]) }}">Clear the search</a>.
                                        @else
                                            No submissions found for this form.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant B (§4B). statusval / search / per_page ride along
                     via withQueryString() in the controller. --}}
                <x-programme-dt-footer :paginator="$records" per-page-id="courseListPerPage" default="25" />
                </div>

            </div>
        </div>
    </div>
@endsection
