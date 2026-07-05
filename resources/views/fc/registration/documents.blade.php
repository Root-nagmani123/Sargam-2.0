@extends('admin.layouts.master')
@section('title', 'Document Upload')
@section('setup_content')
<div class="row justify-content-center">
<div class="col-12 col-xl-9">

    @include('partials.step-indicator', ['current' => 6])

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0" style="color:#1a3c6e;">
                    <i class="bi bi-file-earmark-arrow-up me-2"></i>Document Upload
                </h5>
                <small class="text-muted">Upload all required joining documents</small>
            </div>
            <span class="badge bg-primary rounded-pill">
                {{ $uploadedDocs->where('is_uploaded', 1)->count() }} / {{ $docMasters->count() }} Uploaded
            </span>
        </div>

        <div class="card-body p-4">
            <div class="alert alert-warning small py-2 mb-4">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Documents marked <strong>Mandatory</strong> must be uploaded before final submission.
                Accepted formats: <strong>PDF, JPG, PNG</strong> (max 5MB each).
            </div>

            <div class="row g-3">
            @foreach($docMasters as $doc)
                @php
                    $uploaded = $uploadedDocs->get($doc->id);
                    $isDone   = $uploaded && $uploaded->is_uploaded;
                    $isVerified = $uploaded && $uploaded->is_verified;
                @endphp
                <div class="col-12">
                    <div class="border rounded p-3 {{ $isDone ? 'border-success bg-success-subtle' : 'bg-white' }}"
                         style="border-radius:8px!important;">
                        <div class="row align-items-center g-2">
                            <!-- Status icon -->
                            <div class="col-auto">
                                @if($isVerified)
                                    <i class="bi bi-patch-check-fill text-success fs-4"></i>
                                @elseif($isDone)
                                    <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                @else
                                    <i class="bi bi-circle text-muted fs-4"></i>
                                @endif
                            </div>

                            <!-- Doc name -->
                            <div class="col">
                                <div class="fw-semibold small">
                                    {{ $doc->document_name }}
                                    @if($doc->is_mandatory)
                                        <span class="badge bg-danger-subtle text-danger ms-1 small">Mandatory</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary ms-1 small">Optional</span>
                                    @endif
                                </div>
                                @if($isDone)
                                    <div class="text-muted" style="font-size:0.75rem;">
                                        {{ $uploaded->file_original_name }}
                                        @if($isVerified)
                                            · <span class="text-success">Verified ✓</span>
                                        @else
                                            · <span class="text-warning">Pending verification</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Upload / View / Delete actions -->
                            <div class="col-auto d-flex gap-2 flex-wrap">
                                @if($isDone)
                                    <a href="{{ asset('storage/'.$uploaded->file_path) }}" target="_blank"
                                       class="btn btn-sm btn-outline-success py-0 px-2">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                    @if(! $isVerified)
                                        <!-- Re-upload form -->
                                        <form method="POST" action="{{ route('fc-reg.registration.documents.upload', $doc->id) }}"
                                              enctype="multipart/form-data" class="d-flex gap-1">
                                            @csrf
                                            <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png"
                                                   class="form-control form-control-sm py-0" style="max-width:200px;" required>
                                            <button type="submit" class="btn btn-sm btn-outline-primary py-0 px-2">
                                                <i class="bi bi-arrow-clockwise"></i> Replace
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('fc-reg.registration.documents.delete', $doc->id) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2"
                                                    onclick="return confirm('Remove this document?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <form method="POST" action="{{ route('fc-reg.registration.documents.upload', $doc->id) }}"
                                          enctype="multipart/form-data" class="d-flex gap-1">
                                        @csrf
                                        <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png"
                                               class="form-control form-control-sm py-0" style="max-width:220px;" required>
                                        <button type="submit" class="btn btn-sm btn-primary py-0 px-2">
                                            <i class="bi bi-upload me-1"></i>Upload
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>

            <!-- Final Submit -->
            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                <a href="{{ route('fc-reg.registration.bank') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Bank Details
                </a>
                <form method="POST" action="{{ route('fc-reg.registration.documents.submit') }}"
                      onsubmit="return confirm('Are you sure you want to make the final submission? This cannot be undone.')">
                    @csrf
                    <button type="submit" class="btn btn-success px-4 fw-semibold">
                        <i class="bi bi-send-check me-2"></i>Final Submission
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
