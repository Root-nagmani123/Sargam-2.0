@extends('admin.layouts.master')

@section('title', 'Translations')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Question Paper Translations"></x-breadcrum>

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
                    {{ $showCompleted ? 'Completed Translations' : 'Pending Translations' }}
                    @if (! $showCompleted && $openCount)
                        <span class="badge bg-warning">{{ $openCount }}</span>
                    @endif
                </h4>

                <div class="btn-group">
                    <a href="{{ route('coe.translation.index') }}"
                       class="btn btn-sm {{ $showCompleted ? 'btn-outline-primary' : 'btn-primary' }}">
                        Pending
                    </a>
                    <a href="{{ route('coe.translation.index', ['completed' => 1]) }}"
                       class="btn btn-sm {{ $showCompleted ? 'btn-primary' : 'btn-outline-primary' }}">
                        Completed
                    </a>
                </div>
            </div>

            <hr>

            @forelse ($requests as $translation)
                @php
                    $paper = $translation->questionPaper;
                    $subject = optional(optional(optional($paper)->componentMap)->subject)->subject_name ?? 'N/A';
                    $component = optional(optional(optional($paper)->componentMap)->component)->component_name ?? '';
                @endphp

                <div class="border rounded p-3 mb-3">
                    <div class="row align-items-center">
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
                            </p>

                            <span class="badge bg-{{ $translation->statusBadge() }}">
                                {{ $translation->statusLabel() }}
                            </span>
                            <span class="badge bg-light text-dark">
                                {{ config('coe.question_paper.original_language', 'EN') }}
                                &rarr;
                                {{ $translation->target_language }}
                            </span>
                        </div>

                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <small class="text-muted d-block">
                                Assigned {{ optional($translation->requested_at)->format('d-m-Y H:i') }}
                            </small>

                            @if ($translation->frozen_at)
                                <small class="text-muted d-block">
                                    Frozen {{ $translation->frozen_at->format('d-m-Y H:i') }}
                                </small>
                            @endif

                            <a href="{{ route('coe.translation.show', encrypt($translation->id)) }}"
                               class="btn btn-primary btn-sm mt-2">
                                {{ $translation->isEditable() ? 'Open' : 'View' }}
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="material-icons text-muted" style="font-size:48px;">translate</i>
                    <p class="text-muted mb-0 mt-2">
                        {{ $showCompleted ? 'No completed translations yet.' : 'No pending translations.' }}
                    </p>
                </div>
            @endforelse

            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
