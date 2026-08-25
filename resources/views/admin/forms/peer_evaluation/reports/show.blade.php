@extends('admin.layouts.master')

@section('title', ($member->user_name ?: 'Officer Trainee') . "'s Evaluation Report")

@push('styles')
<link rel="stylesheet"
      href="{{ asset('css/peer-evaluation-admin.css') }}?v={{ @filemtime(public_path('css/peer-evaluation-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid pe-page per-page per-detail-page">
    <x-breadcrum :title="($member->user_name ?: 'Officer Trainee') . chr(39) . 's Evaluation Report'"
                 :items="[
                     ['label' => 'Home', 'url' => route('admin.dashboard')],
                     ['label' => 'Setup', 'url' => null],
                     ['label' => 'FC Forms', 'url' => null],
                     ['label' => 'Peer Evaluation', 'url' => null],
                     ['label' => 'Reports', 'url' => route('admin.peer.reports.index')],
                     ['label' => 'View Report', 'url' => null],
                 ]" />

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 pe-secondary-actions">
        <div class="dropdown">
            <button type="button" id="perdDownloadToggle" class="btn pe-export-btn dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="perdDownloadToggle">
                @foreach ([['csv', 'bi-filetype-csv', 'CSV'], ['excel', 'bi-file-earmark-excel', 'Excel (.xlsx)'], ['pdf', 'bi-file-earmark-pdf', 'PDF']] as [$fmt, $icon, $label])
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                       href="{{ route('admin.peer.reports.export-detail', ['member' => $member->id, 'format' => $fmt]) }}">
                        <i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}
                    </a>
                </li>
                @endforeach
            </ul>
        </div>

        <a href="{{ route('admin.peer.reports.export-detail', ['member' => $member->id, 'format' => 'print']) }}"
           class="btn pe-export-btn" target="_blank" rel="noopener" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    {{-- Report Summary --}}
    <div class="card overflow-hidden rounded-3 mb-3">
        <div class="card-body p-3 p-md-4">
            <h6 class="per-detail-heading mb-3">Report Summary</h6>
            <hr class="mt-0 mb-3">

            <div class="per-summary">
                @foreach ([
                    ['Course Name', $member->course_name ?: '-'],
                    ['Event Name',  $member->event_name ?: '-'],
                    ['Group Name',  $member->group_name ?: '-'],
                    ['OT Code',     $member->ot_code ?: '-'],
                ] as [$label, $value])
                    <div class="per-summary__cell">
                        <div class="per-summary__label">{{ $label }}</div>
                        <div class="per-summary__value">{{ $value }}</div>
                    </div>
                @endforeach

                <div class="per-summary__cell">
                    <div class="per-summary__label">Overall Peer Score</div>
                    <div class="per-summary__value per-summary__value--score">
                        {{ $overallScore === null ? '-' : number_format($overallScore, 2) }}
                    </div>
                </div>

                <div class="per-summary__cell">
                    <div class="per-summary__label">Status</div>
                    <div class="per-summary__value">
                        <span class="status-pill badge rounded-1 {{ $submitted ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                            {{ $submitted ? 'Submitted' : 'Pending' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Per-evaluator scores --}}
    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table per-detail-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Evaluator and Code</th>
                                @foreach ($criteria as $criterion)
                                    <th scope="col" class="text-center">{{ $criterion->column_name }}</th>
                                @endforeach
                                <th scope="col" class="text-center">Overall</th>
                                <th scope="col">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $row)
                                <tr>
                                    <td>{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td>
                                        <div class="per-detail-name">{{ $row['name'] }}</div>
                                        <div class="per-detail-code">- {{ $row['code'] ?: 'No OT code' }}</div>
                                    </td>
                                    @foreach ($criteria as $criterion)
                                        <td class="text-center">
                                            <span class="per-score-box">
                                                {{ $row['scores'][$criterion->id] === null ? '-' : number_format($row['scores'][$criterion->id], 2) }}
                                            </span>
                                        </td>
                                    @endforeach
                                    <td class="text-center">
                                        {{ $row['overall'] === null ? '-' : number_format($row['overall'], 2) }}
                                    </td>
                                    <td class="per-detail-remarks">{{ $row['remarks'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 4 + $criteria->count() }}" class="text-center py-4 text-body-secondary">
                                        No one has evaluated this Officer Trainee yet.
                                    </td>
                                </tr>
                            @endforelse

                            @if (count($rows))
                                {{-- Average across evaluators, per criterion. --}}
                                <tr class="per-detail-average">
                                    <td colspan="2">Average</td>
                                    @foreach ($criteria as $criterion)
                                        <td class="text-center">
                                            <span class="per-score-box per-score-box--avg">
                                                {{ $averages[$criterion->id] === null ? '-' : number_format($averages[$criterion->id], 2) }}
                                            </span>
                                        </td>
                                    @endforeach
                                    <td class="text-center fw-semibold">
                                        {{ $overallScore === null ? '-' : number_format($overallScore, 2) }}
                                    </td>
                                    <td></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
