@extends('admin.layouts.master')

@section('title', 'Question Paper Approval')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Question Paper Approval"></x-breadcrum>

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
                    {{ $showFinalized ? 'Finalized Papers' : 'Awaiting Approval' }}
                    @if (! $showFinalized && $pendingCount)
                        <span class="badge bg-warning">{{ $pendingCount }}</span>
                    @endif
                </h4>

                <div class="btn-group">
                    <a href="{{ route('coe.approval.index') }}"
                       class="btn btn-sm {{ $showFinalized ? 'btn-outline-primary' : 'btn-primary' }}">
                        Pending
                    </a>
                    <a href="{{ route('coe.approval.index', ['finalized' => 1]) }}"
                       class="btn btn-sm {{ $showFinalized ? 'btn-primary' : 'btn-outline-primary' }}">
                        Finalized
                    </a>
                </div>
            </div>

            <hr>

            @forelse ($papers as $paper)
                @php
                    $subject = optional(optional($paper->componentMap)->subject)->subject_name ?? 'N/A';
                    $component = optional(optional($paper->componentMap)->component)->component_name ?? '';

                    // Grouped by language so the approver can see at a glance
                    // that both the original and the translation are present.
                    $byLanguage = $paper->currentFiles->groupBy('language');
                    $languageNames = config('coe.question_paper.languages', []);
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
                                {{ optional(optional($paper->drive)->course)->course_name ?? '' }}
                                &middot; {{ optional(optional($paper->drive)->term)->term_name ?? '' }}
                                &middot; Paper setter: {{ optional($paper->faculty)->full_name ?? '-' }}
                            </p>

                            @foreach ($byLanguage as $language => $files)
                                <span class="badge bg-light text-dark">
                                    {{ $languageNames[$language] ?? $language }}: {{ $files->count() }} file(s)
                                </span>
                            @endforeach
                        </div>

                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <span class="badge bg-{{ $paper->statusBadge() }}">{{ $paper->statusLabel() }}</span>

                            @if ($paper->finalized_at)
                                <small class="text-muted d-block mt-1">
                                    Finalized {{ $paper->finalized_at->format('d-m-Y H:i') }}
                                </small>
                            @endif

                            <div class="mt-3">
                                <a href="{{ route('coe.question_paper.show', encrypt($paper->id)) }}"
                                   class="btn btn-sm btn-outline-secondary">Review Files</a>

                                @if ($paper->canFinalize())
                                    <button type="button" class="btn btn-sm btn-success"
                                            data-bs-toggle="modal" data-bs-target="#finalizeModal{{ $paper->id }}">
                                        Approve &amp; Finalize
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if ($paper->canFinalize())
                    <div class="modal fade" id="finalizeModal{{ $paper->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content"
                                  action="{{ route('coe.approval.finalize', encrypt($paper->id)) }}">
                                @csrf

                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Approval</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body">
                                    <p><strong>{{ $subject }}</strong> will be finalized and locked.</p>
                                    <ul class="mb-3">
                                        <li>Marks cannot be edited afterwards</li>
                                        <li>Files cannot be replaced</li>
                                        <li>The paper becomes examination-ready</li>
                                    </ul>
                                    <p class="mb-0">
                                        Please review both the original and the translation before continuing.
                                    </p>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success">Approve &amp; Finalize</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @empty
                <div class="text-center py-5">
                    <i class="material-icons text-muted" style="font-size:48px;">verified</i>
                    <p class="text-muted mb-0 mt-2">
                        {{ $showFinalized ? 'No finalized papers yet.' : 'No papers are awaiting approval.' }}
                    </p>
                </div>
            @endforelse

            {{ $papers->links() }}
        </div>
    </div>
</div>
@endsection
