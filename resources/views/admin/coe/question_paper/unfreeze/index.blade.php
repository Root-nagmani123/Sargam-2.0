@extends('admin.layouts.master')

@section('title', 'Unfreeze Requests')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Unfreeze Requests"></x-breadcrum>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h4 class="mb-0">
                    {{ $showDecided ? 'Decided Requests' : 'Pending Requests' }}
                    @if (! $showDecided && $pendingCount)
                        <span class="badge bg-warning">{{ $pendingCount }}</span>
                    @endif
                </h4>

                <div class="btn-group">
                    <a href="{{ route('coe.question_paper.unfreeze.index') }}"
                       class="btn btn-sm {{ $showDecided ? 'btn-outline-primary' : 'btn-primary' }}">
                        Pending
                    </a>
                    <a href="{{ route('coe.question_paper.unfreeze.index', ['decided' => 1]) }}"
                       class="btn btn-sm {{ $showDecided ? 'btn-primary' : 'btn-outline-primary' }}">
                        Decided
                    </a>
                </div>
            </div>

            <hr>

            @forelse ($requests as $unfreeze)
                @php
                    $paper = $unfreeze->questionPaper;
                    $subject = optional(optional(optional($paper)->componentMap)->subject)->subject_name ?? 'N/A';
                    $component = optional(optional(optional($paper)->componentMap)->component)->component_name ?? '';
                @endphp

                <div class="border rounded p-3 mb-3">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="mb-1">
                                {{ $subject }}
                                @if ($component)
                                    <small class="text-muted">&mdash; {{ $component }}</small>
                                @endif
                            </h5>

                            <p class="text-muted mb-2">
                                {{ optional(optional(optional($paper)->drive)->course)->course_name ?? '' }}
                                &middot; {{ optional(optional(optional($paper)->drive)->term)->term_name ?? '' }}
                                &middot; Paper setter: {{ optional(optional($paper)->faculty)->full_name ?? '-' }}
                            </p>

                            <div class="bg-light rounded p-2 mb-2">
                                <small class="text-muted d-block">Reason given by the faculty</small>
                                {{ $unfreeze->reason }}
                            </div>

                            @if ($unfreeze->approver_remarks)
                                <div class="bg-light rounded p-2">
                                    <small class="text-muted d-block">Officer remarks</small>
                                    {{ $unfreeze->approver_remarks }}
                                </div>
                            @endif
                        </div>

                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-{{ $unfreeze->statusBadge() }}">
                                {{ $unfreeze->statusLabel() }}
                            </span>

                            <small class="text-muted d-block mt-1">
                                Requested {{ optional($unfreeze->requested_at)->format('d-m-Y H:i') }}
                            </small>

                            @if ($unfreeze->approved_at)
                                <small class="text-muted d-block">
                                    Decided {{ $unfreeze->approved_at->format('d-m-Y H:i') }}
                                </small>
                            @endif

                            <div class="mt-3">
                                @if ($paper)
                                    <a href="{{ route('coe.question_paper.show', encrypt($paper->id)) }}"
                                       class="btn btn-sm btn-outline-secondary">View Paper</a>
                                @endif

                                @if ($unfreeze->isPending())
                                    <button type="button" class="btn btn-sm btn-success"
                                            data-bs-toggle="modal"
                                            data-bs-target="#approveModal{{ $unfreeze->id }}">
                                        Approve
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rejectModal{{ $unfreeze->id }}">
                                        Reject
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if ($unfreeze->isPending())
                    @php $encryptedRequestId = encrypt($unfreeze->id); @endphp

                    <div class="modal fade" id="approveModal{{ $unfreeze->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content"
                                  action="{{ route('coe.question_paper.unfreeze.approve', $encryptedRequestId) }}">
                                @csrf

                                <div class="modal-header">
                                    <h5 class="modal-title">Approve Unfreeze</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <p>
                                        <strong>{{ $subject }}</strong> will reopen so
                                        {{ optional(optional($paper)->faculty)->full_name ?? 'the paper setter' }}
                                        can correct it. The files frozen so far stay on record.
                                    </p>

                                    <label for="approve_remarks{{ $unfreeze->id }}" class="form-label">
                                        Remarks (optional)
                                    </label>
                                    <textarea id="approve_remarks{{ $unfreeze->id }}" name="remarks"
                                              class="form-control" rows="3" maxlength="1000"></textarea>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success">Approve</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="modal fade" id="rejectModal{{ $unfreeze->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content"
                                  action="{{ route('coe.question_paper.unfreeze.reject', $encryptedRequestId) }}">
                                @csrf

                                <div class="modal-header">
                                    <h5 class="modal-title">Reject Unfreeze</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <p><strong>{{ $subject }}</strong> will stay frozen.</p>

                                    <label for="reject_remarks{{ $unfreeze->id }}" class="form-label">
                                        Reason <span class="text-danger">*</span>
                                    </label>
                                    <textarea id="reject_remarks{{ $unfreeze->id }}" name="remarks"
                                              class="form-control" rows="3" minlength="5" maxlength="1000"
                                              required
                                              placeholder="The faculty member will see this."></textarea>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Reject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @empty
                <div class="text-center py-5">
                    <i class="material-icons text-muted" style="font-size:48px;">inbox</i>
                    <p class="text-muted mb-0 mt-2">
                        {{ $showDecided ? 'No decided requests yet.' : 'No pending unfreeze requests.' }}
                    </p>
                </div>
            @endforelse

            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
