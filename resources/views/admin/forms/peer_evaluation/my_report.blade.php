@extends('admin.layouts.master')
@section('title', 'My Peer Evaluation Report | Sargam')

{{-- The OT's own report: what their peers scored them.

     Anonymous by design, and not by omission - see
     PeerEvaluationForm::receivedSummary(). There is no evaluator column here and
     there is nothing to add one from: the names never leave that method. The
     remarks are printed as a plain list for the same reason - one card per
     evaluator, in a fixed order, would let anybody who knows the group work back
     to who wrote what.

     Reached from My Peer Evaluation, and only once the evaluation has closed. --}}
@section('setup_content')
    <div class="container-fluid py-4">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-gradient-primary py-4 border-0">
                <div class="d-flex align-items-center">
                    <div class="icon-wrapper bg-white bg-opacity-20 rounded-circle p-3 me-3">
                        <i class="material-icons material-symbols-rounded" style="font-size: 2.5rem;">insights</i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold text-white">My Peer Evaluation Report</h3>
                        <p class="mb-0 opacity-75 small">
                            {{ $group->group_name }}{{ $group->event_name ? ' · ' . $group->event_name : '' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                    <a href="{{ route('peer.user_groups') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="material-icons material-symbols-rounded me-1 align-middle" style="font-size: 1rem;">arrow_back</i>
                        Back to my groups
                    </a>
                    {{-- Null while the evaluation is still running - the report is
                         readable then too. When there IS a reason it is the same
                         sentence the form gives, so the two never disagree about
                         what happened to this group. --}}
                    @if ($closedReason)
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill">{{ $closedReason }}</span>
                    @else
                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                            Evaluation still open — more scores may come in
                        </span>
                    @endif
                </div>

                @if ($evaluators === 0)
                    <div class="alert alert-info border-0 rounded-3 shadow-sm mb-0">
                        <div class="d-flex align-items-center">
                            <i class="material-icons material-symbols-rounded text-info me-3" style="font-size: 1.5rem;">info</i>
                            <div>
                                <strong>No scores yet.</strong>
                                Nobody in this group submitted an evaluation of you, so there is nothing to report.
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Headline figures --}}
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100 bg-primary-subtle">
                                <div class="card-body text-center py-4">
                                    <div class="text-primary text-uppercase small fw-semibold mb-2">Overall Peer Score</div>
                                    <div class="display-5 fw-bold text-primary">
                                        {{ $overall === null ? '-' : number_format($overall, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100 bg-light">
                                <div class="card-body text-center py-4">
                                    <div class="text-muted text-uppercase small fw-semibold mb-2">Peers Who Scored You</div>
                                    <div class="display-5 fw-bold text-dark">{{ $evaluators }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Per criterion. An average of everybody who scored it, so a
                         criterion nobody filled reads "-" rather than 0 - not
                         scored and scored nothing are different things. --}}
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-light border-0 py-3">
                            <h5 class="mb-0 fw-bold text-primary">
                                <i class="material-icons material-symbols-rounded me-2 align-middle" style="font-size: 1.25rem;">grading</i>
                                Score by Criterion
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="fw-semibold text-uppercase small text-muted border-0 py-3 ps-4">Criterion</th>
                                            <th class="fw-semibold text-uppercase small text-muted border-0 py-3 text-center">Out Of</th>
                                            <th class="fw-semibold text-uppercase small text-muted border-0 py-3 text-center">Your Average</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($criteria as $criterion)
                                            <tr>
                                                <td class="ps-4 fw-medium text-dark">{{ $criterion->column_name }}</td>
                                                <td class="text-center text-muted">
                                                    {{ rtrim(rtrim(number_format((float) \App\Support\PeerEvaluationForm::cellMax($criterion, $group), 2, '.', ''), '0'), '.') }}
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-6">
                                                        {{ $averages[$criterion->id] === null ? '-' : number_format($averages[$criterion->id], 2) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-4 text-muted">
                                                    This evaluation has no active criteria.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Remarks, unattributed. --}}
                    @if (count($remarks))
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-light border-0 py-3">
                                <h5 class="mb-0 fw-bold text-primary">
                                    <i class="material-icons material-symbols-rounded me-2 align-middle" style="font-size: 1.25rem;">forum</i>
                                    What Your Peers Wrote
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">
                                    <i class="material-icons material-symbols-rounded me-1 align-middle" style="font-size: 0.875rem;">visibility_off</i>
                                    Feedback is anonymous - it is not shown who wrote which remark.
                                </p>
                                @foreach ($remarks as $remark)
                                    <div class="border-start border-3 border-primary-subtle ps-3 {{ $loop->last ? '' : 'mb-3' }}">
                                        <div class="text-dark" style="white-space: pre-wrap;">{{ $remark }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
