@php
    $feedbackData = $feedbackData ?? collect();
    $currentPage = $currentPage ?? 1;
    $totalRecords = $totalRecords ?? 0;
    $totalPages = $totalPages ?? 0;
@endphp

@if ($feedbackData->isEmpty())
    <div class="alert alert-info text-center mb-0">
        No feedback data found for the selected filters.
    </div>
@else
    @foreach ($feedbackData as $data)
        <div class="feedback-section mb-4">
            <div class="text-center mb-4">
                <p class="mb-1">
                    <strong>Course:</strong> {{ $data['program_name'] ?? '' }}
                    @if (!empty($data['course_status']))
                        <span class="faculty-type-badge ms-1">{{ $data['course_status'] }}</span>
                    @endif
                </p>
                <p class="mb-1">
                    <strong>Faculty:</strong> {{ $data['faculty_name'] ?? '' }}
                    <span class="faculty-type-badge ms-2">{{ $data['faculty_type'] ?? '' }}</span>
                </p>
                <p class="mb-1"><strong>Topic:</strong> {{ $data['topic_name'] ?? '' }}</p>
                @if (!empty($data['start_date']))
                    <p class="mb-0">
                        <strong>Lecture Date:</strong>
                        {{ $data['formatted_start_date'] ?? \Carbon\Carbon::parse($data['start_date'])->format('d-M-Y') }}
                        @if (!empty($data['time_display']))
                            {{ $data['time_display'] }}
                        @endif
                    </p>
                @endif
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th scope="col">Rating</th>
                            <th scope="col">Content <span class="text-dark">*</span></th>
                            <th scope="col">Presentation <span class="text-dark">*</span></th>
                        </tr>
                    </thead>
                    <tbody class="align-middle text-dark">
                        <tr>
                            <th class="rating-header">Excellent</th>
                            <td>{{ $data['content_counts']['5'] ?? 0 }}</td>
                            <td>{{ $data['presentation_counts']['5'] ?? 0 }}</td>
                        </tr>
                        <tr>
                            <th class="rating-header">Very Good</th>
                            <td>{{ $data['content_counts']['4'] ?? 0 }}</td>
                            <td>{{ $data['presentation_counts']['4'] ?? 0 }}</td>
                        </tr>
                        <tr>
                            <th class="rating-header">Good</th>
                            <td>{{ $data['content_counts']['3'] ?? 0 }}</td>
                            <td>{{ $data['presentation_counts']['3'] ?? 0 }}</td>
                        </tr>
                        <tr>
                            <th class="rating-header">Average</th>
                            <td>{{ $data['content_counts']['2'] ?? 0 }}</td>
                            <td>{{ $data['presentation_counts']['2'] ?? 0 }}</td>
                        </tr>
                        <tr>
                            <th class="rating-header">Below Average</th>
                            <td>{{ $data['content_counts']['1'] ?? 0 }}</td>
                            <td>{{ $data['presentation_counts']['1'] ?? 0 }}</td>
                        </tr>
                        <tr class="fw-semibold">
                            <th class="rating-header">Percentage</th>
                            <td class="percentage-cell">{{ number_format($data['content_percentage'] ?? 0, 2) }}%</td>
                            <td class="percentage-cell">{{ number_format($data['presentation_percentage'] ?? 0, 2) }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if (!empty($data['remarks']))
                <div class="mb-2">
                    <div class="remarks-title">Remarks ({{ count($data['remarks']) }})</div>
                    <ol class="remarks-list py-2">
                        @foreach ($data['remarks'] as $remark)
                            <li>{{ $remark }}</li>
                        @endforeach
                    </ol>
                </div>
            @endif

            <hr class="my-4">
        </div>
    @endforeach

    @if ($totalRecords > 1)
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                <small class="text-muted pagination-info">
                    Showing record {{ $currentPage }} of {{ $totalRecords }}
                    (Page {{ $currentPage }} of {{ $totalPages }})
                </small>
            </div>
            <div class="d-flex gap-2">
                @if ($currentPage > 1)
                    <button type="button" class="btn btn-sm btn-outline-primary portal-page-btn" data-page="{{ $currentPage - 1 }}">
                        ← Previous
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>← Previous</button>
                @endif

                <span class="mx-2 align-self-center">Page {{ $currentPage }} of {{ $totalPages }}</span>

                @if ($currentPage < $totalPages)
                    <button type="button" class="btn btn-sm btn-outline-primary portal-page-btn" data-page="{{ $currentPage + 1 }}">
                        Next →
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Next →</button>
                @endif
            </div>
        </div>
    @elseif ($totalRecords === 1)
        <div class="d-flex justify-content-center mt-3">
            <small class="text-muted pagination-info">Showing 1 record</small>
        </div>
    @endif
@endif
