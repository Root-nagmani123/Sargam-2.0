@extends('admin.layouts.master')

@section('title', 'My Question Papers')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="My Question Papers"></x-breadcrum>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @forelse ($papers as $paper)
        @php
            $subject = optional(optional($paper->componentMap)->subject)->subject_name ?? 'N/A';
            $component = optional(optional($paper->componentMap)->component)->component_name ?? '';
            $maxMarks = optional($paper->componentMap)->max_marks;

            // Only a paper still being written can run late; a frozen one has
            // already been handed in.
            $isOverdue = $paper->deadline
                && $paper->isEditable()
                && $paper->deadline->isPast();
        @endphp

        <div class="card mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h5 class="mb-1">
                            {{ $subject }}
                            @if ($component)
                                <small class="text-muted">&mdash; {{ $component }}</small>
                            @endif
                        </h5>

                        <p class="text-muted mb-2">
                            {{ optional(optional($paper->drive)->course)->course_name ?? '' }}
                            &middot; {{ optional(optional($paper->drive)->term)->term_name ?? '' }}
                            &middot; {{ optional($paper->drive)->phase }}
                            @if ($maxMarks !== null)
                                &middot; Max Marks: {{ rtrim(rtrim(number_format((float) $maxMarks, 2), '0'), '.') }}
                            @endif
                        </p>

                        <span class="badge bg-{{ $paper->statusBadge() }}">{{ $paper->statusLabel() }}</span>

                        @if ($paper->currentFiles->count())
                            <span class="badge bg-light text-dark">
                                {{ $paper->currentFiles->count() }} file(s)
                            </span>
                        @endif

                        @if ($paper->pendingUnfreezeRequest)
                            <span class="badge bg-warning">Unfreeze request pending</span>
                        @endif
                    </div>

                    <div class="col-md-5 text-md-end mt-3 mt-md-0">
                        @if ($paper->deadline)
                            <div class="mb-2">
                                <small class="text-muted d-block">Deadline</small>
                                <strong class="{{ $isOverdue ? 'text-danger' : '' }}">
                                    {{ $paper->deadline->format('d-m-Y') }}
                                    @if ($isOverdue)
                                        (overdue)
                                    @endif
                                </strong>
                            </div>
                        @endif

                        <a href="{{ route('coe.faculty.question_paper.show', encrypt($paper->id)) }}"
                           class="btn btn-primary">
                            @if ($paper->status === \App\Support\COE\QuestionPaperStatus::DRAFT)
                                Upload Question Paper
                            @else
                                Open
                            @endif
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="material-icons text-muted" style="font-size:48px;">description</i>
                <p class="text-muted mb-0 mt-2">No question paper has been assigned to you yet.</p>
            </div>
        </div>
    @endforelse
</div>
@endsection
