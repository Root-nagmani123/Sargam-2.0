{{--
    Joining document upload checklist — PDF table format (admin preview + trainee step).
    Expects: $fields (collection), $existingData (object|null), $readonly (bool),
             optional $form + $step for live upload forms.

    Layout mirrors the official 99th FC joining-document page:
      Sr.No | Document Title | Upload | View Uploaded Forms | Sample Document | Status
    Documents are grouped by their section_heading (e.g. Administration / Accounts).
    The per-row upload form posts the SAME field names as before (upload_single +
    file named field_name) so the existing save/insert logic is untouched.
--}}
@php
    $readonly     = $readonly ?? true;
    $existingData = $existingData ?? null;

    $fileFields = $fields->filter(fn ($f) => $f->field_type === 'file')->values();
    $fileFieldCount = $fileFields->count();

    $uploadedCount = 0;
    foreach ($fileFields as $f) {
        $col = $f->target_column ?: $f->field_name;
        if (filled($existingData?->{$col} ?? null)) {
            $uploadedCount++;
        }
    }

    // Group by section heading, preserving order. Fields without a heading fall under "Documents".
    $grouped = $fileFields->groupBy(fn ($f) => $f->section_heading ?: 'Documents');

    // Sample-document master, keyed by field_name (read-only lookup).
    $sampleDocs = collect();
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('fc_joining_sample_documents')) {
            $sampleDocs = \App\Models\FC\FcJoiningSampleDocument::where('is_active', 1)->get()->keyBy('field_name');
        }
    } catch (\Throwable $e) {
        $sampleDocs = collect();
    }
@endphp

@if(! $readonly)
    <div class="alert alert-warning small py-2 mb-3">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Documents marked <strong>Mandatory</strong> must be uploaded before final submission.
        Accepted formats: <strong>PDF, JPG, PNG</strong> (max 5MB each).
    </div>
@endif

@foreach($grouped as $section => $sectionFields)
    <div class="card mb-4" style="border-left:4px solid #004a93;">
        <div class="card-body p-3">
            <h6 class="fw-bold text-primary mb-3 text-uppercase" style="letter-spacing:0.3px;">{{ $section }}</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle table-hover table-striped mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th style="width:60px;">Sr.No.</th>
                            <th class="text-start">Document Title</th>
                            <th style="width:260px;">Upload</th>
                            <th style="width:120px;">View Uploaded</th>
                            <th style="width:120px;">Sample Document</th>
                            <th style="width:110px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sectionFields as $i => $field)
                            @php
                                $col        = $field->target_column ?: $field->field_name;
                                $storedPath = $existingData?->{$col} ?? null;
                                $isDone     = filled($storedPath);
                                $fileName   = $isDone ? basename((string) $storedPath) : null;
                                $fileUrl    = $isDone ? asset('storage/'.ltrim((string) $storedPath, '/')) : null;
                                $accept     = $field->file_extensions
                                    ? '.' . implode(',.', array_map('trim', explode(',', (string) $field->file_extensions)))
                                    : '.pdf,.jpg,.jpeg,.png';
                                $sample     = $sampleDocs->get($field->field_name);
                                $sampleUrl  = ($sample && $sample->sample_file_path)
                                    ? asset(ltrim((string) $sample->sample_file_path, '/'))
                                    : null;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>
                                    <div class="fw-semibold small">
                                        {{ $field->label }}
                                        @if($field->is_required)
                                            <span class="badge bg-danger-subtle text-danger ms-1">Mandatory</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary ms-1">Optional</span>
                                        @endif
                                    </div>
                                    @if($field->help_text)
                                        <small class="text-muted d-block">{{ $field->help_text }}</small>
                                    @endif
                                    @if($isDone && $fileName)
                                        <small class="text-success d-block" style="font-size:0.72rem;">
                                            <i class="bi bi-check-circle"></i> {{ $fileName }}
                                        </small>
                                    @endif
                                    @error($field->field_name)
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    @if($readonly)
                                        <div class="d-flex gap-1">
                                            <input type="file" class="form-control form-control-sm py-0" disabled>
                                            <button type="button" class="btn btn-sm btn-primary py-0 px-2" disabled>
                                                <i class="bi bi-upload"></i>
                                            </button>
                                        </div>
                                    @elseif(isset($form, $step))
                                        <form method="POST"
                                              action="{{ route('fc-reg.forms.step.save', [$form, $step]) }}"
                                              enctype="multipart/form-data"
                                              class="d-flex gap-1 align-items-center mb-0">
                                            @csrf
                                            <input type="hidden" name="upload_single" value="{{ $field->field_name }}">
                                            <input type="file"
                                                   name="{{ $field->field_name }}"
                                                   class="form-control form-control-sm py-0 @error($field->field_name) is-invalid @enderror"
                                                   accept="{{ $accept }}"
                                                   {{ $isDone ? '' : 'required' }}>
                                            <button type="submit" class="btn btn-sm btn-primary py-0 px-2 text-nowrap">
                                                <i class="bi bi-upload me-1"></i>{{ $isDone ? 'Replace' : 'Upload' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($isDone && $fileUrl)
                                        <a href="{{ $fileUrl }}" target="_blank" rel="noopener"
                                           class="btn btn-link btn-sm p-0 text-primary">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    @else
                                        <span class="text-muted small">No file uploaded</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($sampleUrl)
                                        <a href="{{ $sampleUrl }}" target="_blank" rel="noopener"
                                           class="btn btn-link btn-sm p-0 text-primary">
                                            <i class="bi bi-download me-1"></i>View Sample
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($isDone)
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach

@if(! $readonly && $fileFieldCount > 0)
    <p class="text-muted small mt-2 mb-0">
        <i class="bi bi-info-circle me-1"></i>{{ $uploadedCount }} / {{ $fileFieldCount }} uploaded.
        Use <strong>Save &amp; Continue</strong> when all mandatory documents are on file.
    </p>
@endif
