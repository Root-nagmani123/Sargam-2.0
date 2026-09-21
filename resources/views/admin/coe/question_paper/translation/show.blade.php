@extends('admin.layouts.master')

@section('title', 'Translate Question Paper')

@php
    use App\Models\QuestionPaperFile;
    use App\Services\COE\QuestionPaperAccess;

    $subject = optional(optional($paper->componentMap)->subject)->subject_name ?? 'N/A';
    $component = optional(optional($paper->componentMap)->component)->component_name ?? '';

    $originalLanguage = config('coe.question_paper.original_language', 'EN');
    $languageNames = config('coe.question_paper.languages', []);
    $targetName = $languageNames[$translation->target_language] ?? $translation->target_language;

    // The original side is filtered by what this user may actually read: the
    // answer key is withheld from the Translation Section.
    $originalFiles = $paper->currentFiles
        ->where('language', $originalLanguage)
        ->filter(fn ($file) => QuestionPaperAccess::canDownloadFile($file, $paper));

    $translatedFiles = $paper->currentFiles->where('language', $translation->target_language);

    $canWork = $translation->isEditable() && QuestionPaperAccess::canUploadTranslation($paper);
    $canFreeze = $translation->canFreeze();

    $encryptedId = encrypt($translation->id);

    $allowed = strtoupper(implode(', ', config('coe.question_paper.allowed_extensions', [])));
    $maxMb = round(((int) config('coe.question_paper.max_file_size_kb', 20480)) / 1024);

    // The answer key is not translated, so it is not offered as an upload type.
    $uploadableTypes = collect($fileTypes)->except(QuestionPaperFile::TYPE_ANSWER_KEY);
@endphp

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Translate Question Paper"></x-breadcrum>

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
                        &middot; {{ $languageNames[$originalLanguage] ?? $originalLanguage }} &rarr; {{ $targetName }}
                    </p>
                </div>

                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-{{ $translation->statusBadge() }} fs-6">
                        {{ $translation->statusLabel() }}
                    </span>
                    <a href="{{ route('coe.translation.index') }}"
                       class="btn btn-sm btn-outline-secondary ms-2">Back</a>
                </div>
            </div>
        </div>
    </div>

    @if (! $translation->isEditable())
        <div class="alert alert-info">
            <i class="material-icons align-middle" style="font-size:20px;">lock</i>
            This translation is frozen and is with the approving authority.
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="mb-1">Original</h5>
                    <p class="text-muted small">
                        {{ $languageNames[$originalLanguage] ?? $originalLanguage }} &mdash;
                        frozen by the paper setter. This is read-only.
                    </p>

                    @forelse ($originalFiles as $file)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <strong>{{ $file->typeLabel() }}</strong>
                                <small class="d-block text-muted">
                                    {{ $file->original_name }} &middot; {{ $file->readableSize() }}
                                </small>
                            </div>
                            <div>
                                <a href="{{ route('coe.question_paper.file.preview', $file->id) }}"
                                   target="_blank" title="Preview">
                                    <i class="material-icons">visibility</i>
                                </a>
                                <a href="{{ route('coe.question_paper.file.download', $file->id) }}"
                                   class="ms-2" title="Download">
                                    <i class="material-icons">download</i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No files available.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h5 class="mb-0">{{ $targetName }}</h5>

                        @if ($canWork)
                            <button type="button" class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="material-icons align-middle" style="font-size:20px;">upload</i>
                                Upload
                            </button>
                        @endif
                    </div>

                    <p class="text-muted small">
                        Uploaded here as a separate file. The original is never replaced.
                    </p>

                    @forelse ($translatedFiles as $file)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <strong>{{ $file->typeLabel() }}</strong>
                                <small class="d-block text-muted">
                                    {{ $file->original_name }} &middot; {{ $file->readableSize() }}
                                    &middot; v{{ $file->version_no }}
                                </small>
                            </div>
                            <a href="{{ route('coe.question_paper.file.preview', $file->id) }}"
                               target="_blank" title="Preview">
                                <i class="material-icons">visibility</i>
                            </a>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Nothing uploaded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if ($canFreeze)
        <div class="card">
            <div class="card-body text-end">
                <button type="button" class="btn btn-primary"
                        data-bs-toggle="modal" data-bs-target="#freezeModal">
                    <i class="material-icons align-middle" style="font-size:20px;">lock</i>
                    Freeze Translation
                </button>
            </div>
        </div>
    @endif
</div>

@if ($canWork)
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content"
                  action="{{ route('coe.translation.upload', $encryptedId) }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Upload {{ $targetName }} File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="file_type" class="form-label">File Type <span class="text-danger">*</span></label>
                        <select id="file_type" name="file_type" class="form-select" required>
                            @foreach ($uploadableTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
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
                  action="{{ route('coe.translation.freeze', $encryptedId) }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Confirm Freeze</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p>
                        The {{ $targetName }} translation will be locked and the paper sent
                        for final approval. You will not be able to change it afterwards.
                    </p>
                    <p class="mb-0">Please preview every file before continuing.</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Yes, Freeze</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
