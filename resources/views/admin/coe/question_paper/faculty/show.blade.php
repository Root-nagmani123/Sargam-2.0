@extends('admin.layouts.master')

@section('title', 'Question Paper')

@php
    use App\Models\QuestionPaperFile;
    use App\Services\COE\QuestionPaperAccess;

    $subject = optional(optional($paper->componentMap)->subject)->subject_name ?? 'N/A';
    $component = optional(optional($paper->componentMap)->component)->component_name ?? '';

    $canUpload = QuestionPaperAccess::canUpload($paper);
    $canFreeze = QuestionPaperAccess::canFreeze($paper);
    $canRequestUnfreeze = QuestionPaperAccess::canRequestUnfreeze($paper);

    $encryptedId = encrypt($paper->id);

    // The faculty member works in the original language only; translations are
    // the Translation Section's and are not shown here.
    $originalLanguage = config('coe.question_paper.original_language', 'EN');
    $myFiles = $paper->currentFiles->where('language', $originalLanguage);

    $allowed = strtoupper(implode(', ', config('coe.question_paper.allowed_extensions', [])));
    $maxMb = round(((int) config('coe.question_paper.max_file_size_kb', 20480)) / 1024);
@endphp

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Question Paper"></x-breadcrum>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-1">
                        {{ $subject }}
                        @if ($component)
                            <small class="text-muted">&mdash; {{ $component }}</small>
                        @endif
                    </h4>
                    <p class="text-muted mb-0">
                        {{ optional(optional($paper->drive)->course)->course_name ?? '' }}
                        &middot; {{ optional(optional($paper->drive)->term)->term_name ?? '' }}
                        &middot; Deadline: {{ $paper->deadline ? $paper->deadline->format('d-m-Y') : '-' }}
                    </p>
                </div>

                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-{{ $paper->statusBadge() }} fs-6">{{ $paper->statusLabel() }}</span>
                    <a href="{{ route('coe.faculty.question_paper.index') }}"
                       class="btn btn-sm btn-outline-secondary ms-2">Back</a>
                </div>
            </div>
        </div>
    </div>

    @if ($paper->isLocked())
        <div class="alert alert-success">
            This paper has been finalized. No further changes are possible.
        </div>
    @elseif ($paper->pendingUnfreezeRequest)
        <div class="alert alert-warning">
            <strong>Unfreeze request pending.</strong>
            The Examination Section has been notified. The paper stays locked until they decide.
        </div>
    @elseif (! $paper->isEditable())
        <div class="alert alert-info">
            <i class="material-icons align-middle" style="font-size:20px;">lock</i>
            This paper is frozen. Files cannot be edited, replaced or deleted.
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Files</h5>

                @if ($canUpload)
                    <button type="button" class="btn btn-primary btn-sm"
                            data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="material-icons align-middle" style="font-size:20px;">upload</i>
                        Upload File
                    </button>
                @endif
            </div>

            @if ($myFiles->isEmpty())
                <p class="text-muted mb-0">
                    No files uploaded yet. The question paper itself is required before freezing.
                </p>
            @else
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>File</th>
                            <th>Size</th>
                            <th>Version</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($myFiles as $file)
                            <tr>
                                <td>
                                    {{ $file->typeLabel() }}
                                    @if (in_array($file->file_type, QuestionPaperFile::REQUIRED_TYPES, true))
                                        <span class="text-danger">*</span>
                                    @endif
                                </td>
                                <td>{{ $file->original_name }}</td>
                                <td>{{ $file->readableSize() }}</td>
                                <td>v{{ $file->version_no }}</td>
                                <td class="text-end">
                                    <a href="{{ route('coe.question_paper.file.preview', $file->id) }}"
                                       target="_blank" title="Preview">
                                        <i class="material-icons">visibility</i>
                                    </a>

                                    @if ($canUpload && ! in_array($file->file_type, QuestionPaperFile::REQUIRED_TYPES, true))
                                        <form method="POST" class="d-inline ms-2"
                                              action="{{ route('coe.faculty.question_paper.file.remove', [$encryptedId, $file->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="border:none;background:none"
                                                    onclick="return confirm('Remove this file?')" title="Remove">
                                                <i class="material-icons text-danger">delete</i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    @if ($canFreeze || $canRequestUnfreeze)
        <div class="card mb-3">
            <div class="card-body text-end">
                @if ($canFreeze)
                    <button type="button" class="btn btn-primary"
                            data-bs-toggle="modal" data-bs-target="#freezeModal">
                        <i class="material-icons align-middle" style="font-size:20px;">lock</i>
                        Freeze Question Paper
                    </button>
                @endif

                @if ($canRequestUnfreeze)
                    <button type="button" class="btn btn-outline-danger"
                            data-bs-toggle="modal" data-bs-target="#unfreezeModal">
                        Request Unfreeze
                    </button>
                @endif
            </div>
        </div>
    @endif

    @if ($paper->unfreezeRequests->isNotEmpty())
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">My Unfreeze Requests</h5>

                @foreach ($paper->unfreezeRequests as $request)
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-{{ $request->statusBadge() }}">{{ $request->statusLabel() }}</span>
                            <small class="text-muted">
                                {{ optional($request->requested_at)->format('d-m-Y H:i') }}
                            </small>
                        </div>
                        <p class="mb-0 mt-1">{{ $request->reason }}</p>
                        @if ($request->approver_remarks)
                            <small class="text-muted d-block">Officer: {{ $request->approver_remarks }}</small>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@if ($canUpload)
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content"
                  action="{{ route('coe.faculty.question_paper.upload', $encryptedId) }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Upload File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file_type" class="form-label">File Type <span class="text-danger">*</span></label>
                        <select id="file_type" name="file_type" class="form-select" required>
                            @foreach ($fileTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            Uploading a type that already exists replaces it. The earlier file is kept on record.
                        </small>
                    </div>

                    <div>
                        <label for="file" class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" id="file" name="file" class="form-control" required>
                        <small class="text-muted">{{ $allowed }} only, up to {{ $maxMb }} MB.</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($canFreeze)
    <div class="modal fade" id="freezeModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content"
                  action="{{ route('coe.faculty.question_paper.freeze', $encryptedId) }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Confirm Freeze</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p>Once this paper is frozen you will not be able to:</p>
                    <ul class="mb-3">
                        <li>Edit the files</li>
                        <li>Replace the files</li>
                        <li>Delete the files</li>
                    </ul>
                    <p class="mb-0">
                        A correction afterwards needs an unfreeze request approved by the
                        Examination Section. Please preview every file before continuing.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Yes, Freeze</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if ($canRequestUnfreeze)
    <div class="modal fade" id="unfreezeModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content"
                  action="{{ route('coe.faculty.question_paper.unfreeze_request', $encryptedId) }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Request Unfreeze</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <label for="reason" class="form-label">
                        Reason <span class="text-danger">*</span>
                    </label>
                    <textarea id="reason" name="reason" class="form-control" rows="4"
                              minlength="10" maxlength="1000" required
                              placeholder="Say what needs correcting and why."></textarea>

                    <small class="text-muted d-block mt-2">
                        This reason is kept permanently as the record of why a locked paper was reopened.
                    </small>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
