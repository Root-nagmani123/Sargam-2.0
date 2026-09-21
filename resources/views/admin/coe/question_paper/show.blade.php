@extends('admin.layouts.master')

@section('title', 'Question Paper Detail')

@php
    use App\Models\QuestionPaperFile;
    use App\Services\COE\QuestionPaperAccess;

    $subject = optional(optional($paper->componentMap)->subject)->subject_name ?? 'N/A';
    $component = optional(optional($paper->componentMap)->component)->component_name ?? 'N/A';

    // The original and its translations are shown as separate blocks: keeping
    // them apart on screen is the point of storing them as separate rows.
    $filesByLanguage = $paper->currentFiles->groupBy('language');
    $originalLanguage = config('coe.question_paper.original_language', 'EN');
    $languageNames = config('coe.question_paper.languages', []);
@endphp

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Question Paper Detail"></x-breadcrum>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="mb-1">{{ $subject }}</h4>
                    <p class="text-muted mb-0">
                        {{ $component }}
                        &middot; {{ optional(optional($paper->drive)->course)->course_name ?? '' }}
                        &middot; {{ optional(optional($paper->drive)->term)->term_name ?? '' }}
                        &middot; {{ optional($paper->drive)->phase }}
                    </p>
                </div>

                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-{{ $paper->statusBadge() }} fs-6">{{ $paper->statusLabel() }}</span>

                    <a href="{{ route('coe.question_paper.index', ['examination_drive_id' => $paper->examination_drive_id]) }}"
                       class="btn btn-sm btn-outline-secondary ms-2">Back</a>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted d-block">Paper Setter</small>
                    <strong>{{ optional($paper->faculty)->full_name ?? 'Not assigned' }}</strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Deadline</small>
                    <strong>{{ $paper->deadline ? $paper->deadline->format('d-m-Y') : '-' }}</strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Frozen On</small>
                    <strong>{{ $paper->frozen_at ? $paper->frozen_at->format('d-m-Y H:i') : '-' }}</strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Finalized On</small>
                    <strong>{{ $paper->finalized_at ? $paper->finalized_at->format('d-m-Y H:i') : '-' }}</strong>
                </div>
            </div>
        </div>
    </div>

    @if (QuestionPaperAccess::canMarkForTranslation($paper))
        <div class="card mb-3">
            <div class="card-body">
                <form method="POST" action="{{ route('coe.question_paper.mark_translation', encrypt($paper->id)) }}"
                      class="row align-items-end g-2">
                    @csrf

                    <div class="col-md-4">
                        <label for="target_language" class="form-label mb-1">Send for Translation</label>
                        <select id="target_language" name="target_language" class="form-select" required>
                            @foreach ($languageNames as $code => $name)
                                @continue($code === $originalLanguage)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-8">
                        <button type="submit" class="btn btn-primary">Mark for Translation</button>
                        <small class="text-muted d-block mt-1">
                            The original stays frozen and untouched; the translation is uploaded as a separate file.
                        </small>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($paper->pendingUnfreezeRequest)
        <div class="alert alert-warning">
            <strong>Unfreeze requested.</strong>
            {{ $paper->pendingUnfreezeRequest->reason }}
            <small class="d-block mt-1 text-muted">
                Requested on {{ optional($paper->pendingUnfreezeRequest->requested_at)->format('d-m-Y H:i') }}
            </small>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-3">Files</h5>

            @forelse ($filesByLanguage as $language => $files)
                <h6 class="text-muted">
                    {{ $languageNames[$language] ?? $language }}
                    @if ($language === $originalLanguage)
                        <span class="badge bg-light text-dark">Original</span>
                    @else
                        <span class="badge bg-light text-dark">Translation</span>
                    @endif
                </h6>

                <table class="table table-sm mb-4">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>File</th>
                            <th>Size</th>
                            <th>Version</th>
                            <th>Uploaded</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($files as $file)
                            <tr>
                                <td>{{ $file->typeLabel() }}</td>
                                <td>{{ $file->original_name }}</td>
                                <td>{{ $file->readableSize() }}</td>
                                <td>v{{ $file->version_no }}</td>
                                <td>{{ optional($file->uploaded_at)->format('d-m-Y H:i') }}</td>
                                <td class="text-end">
                                    @if (QuestionPaperAccess::canDownloadFile($file, $paper))
                                        <a href="{{ route('coe.question_paper.file.preview', $file->id) }}"
                                           target="_blank" title="Preview">
                                            <i class="material-icons">visibility</i>
                                        </a>
                                        <a href="{{ route('coe.question_paper.file.download', $file->id) }}"
                                           title="Download" class="ms-2">
                                            <i class="material-icons">download</i>
                                        </a>
                                    @else
                                        <span class="text-muted">Restricted</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @empty
                <p class="text-muted mb-0">No files uploaded yet.</p>
            @endforelse
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Version History</h5>

                    @forelse ($paper->versions as $version)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <div>
                                <strong>v{{ $version->version_no }}</strong>
                                &mdash; {{ $version->statusLabel() }}
                                @if ($version->reason)
                                    <small class="d-block text-muted">{{ $version->reason }}</small>
                                @endif
                            </div>
                            <small class="text-muted">
                                {{ optional($version->created_date)->format('d-m-Y H:i') }}
                            </small>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No versions recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Unfreeze Requests</h5>

                    @forelse ($paper->unfreezeRequests as $request)
                        <div class="border-bottom py-2">
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-{{ $request->statusBadge() }}">
                                    {{ $request->statusLabel() }}
                                </span>
                                <small class="text-muted">
                                    {{ optional($request->requested_at)->format('d-m-Y H:i') }}
                                </small>
                            </div>
                            <p class="mb-0 mt-1">{{ $request->reason }}</p>
                            @if ($request->approver_remarks)
                                <small class="text-muted d-block">
                                    Officer: {{ $request->approver_remarks }}
                                </small>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">No unfreeze requests.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if ($auditLogs->isNotEmpty())
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Audit Trail</h5>

                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Action</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Details</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($auditLogs as $log)
                                <tr>
                                    <td class="text-nowrap">
                                        {{ optional($log->created_date)->format('d-m-Y H:i') }}
                                    </td>
                                    <td>
                                        {{ \App\Services\COE\QuestionPaperAuditService::ACTION_LABELS[$log->action] ?? $log->action }}
                                    </td>
                                    <td>{{ $log->user_name ?? '-' }}</td>
                                    <td><small class="text-muted">{{ $log->user_role ?? '-' }}</small></td>
                                    <td>
                                        @if ($log->details)
                                            <small class="text-muted">
                                                @foreach ($log->details as $key => $value)
                                                    {{ str_replace('_', ' ', $key) }}:
                                                    {{ is_bool($value) ? ($value ? 'yes' : 'no') : $value }}@if (! $loop->last), @endif
                                                @endforeach
                                            </small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $log->ip_address ?? '-' }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
