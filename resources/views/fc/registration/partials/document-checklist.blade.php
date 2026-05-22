{{--
    Joining document upload checklist (admin preview + trainee step).
    Expects: $fields (collection), $existingData (object|null), $readonly (bool),
             optional $form + $step for live upload forms.
--}}
@php
    $readonly = $readonly ?? true;
    $existingData = $existingData ?? null;
    $uploadedCount = 0;
    foreach ($fields as $f) {
        if ($f->field_type !== 'file') {
            continue;
        }
        $col = $f->target_column ?: $f->field_name;
        if (filled($existingData?->{$col} ?? null)) {
            $uploadedCount++;
        }
    }
    $fileFieldCount = $fields->filter(fn ($f) => $f->field_type === 'file')->count();
@endphp

@if(! $readonly)
    <div class="alert alert-warning small py-2 mb-3">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Documents marked <strong>Mandatory</strong> must be uploaded before final submission.
        Accepted formats: <strong>PDF, JPG, PNG</strong> (max 5MB each).
    </div>
@endif

<div class="row g-3">
    @foreach($fields as $field)
        @if($field->field_type !== 'file')
            @continue
        @endif
        @php
            $col = $field->target_column ?: $field->field_name;
            $storedPath = $existingData?->{$col} ?? null;
            $isDone = filled($storedPath);
            $fileName = $isDone ? basename((string) $storedPath) : null;
            $fileUrl = $isDone ? asset('storage/'.ltrim((string) $storedPath, '/')) : null;
            $accept = $field->file_extensions
                ? '.' . implode(',.', array_map('trim', explode(',', (string) $field->file_extensions)))
                : '.pdf,.jpg,.jpeg,.png';
        @endphp
        <div class="col-12">
            <div class="border rounded p-3 {{ $isDone && ! $readonly ? 'border-success bg-success-subtle' : 'bg-white' }}"
                 style="border-radius:8px!important;">
                <div class="row align-items-center g-2">
                    <div class="col-auto">
                        @if($isDone && ! $readonly)
                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        @else
                            <i class="bi bi-circle text-muted fs-4"></i>
                        @endif
                    </div>
                    <div class="col">
                        <div class="fw-semibold small">
                            {{ $field->label }}
                            @if($field->is_required)
                                <span class="badge bg-danger-subtle text-danger ms-1 small">Mandatory</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary ms-1 small">Optional</span>
                            @endif
                        </div>
                        <small class="text-muted">Field: {{ $field->field_name }}</small>
                        @if($isDone && $fileName)
                            <div class="text-muted" style="font-size:0.75rem;">{{ $fileName }}</div>
                        @endif
                        @error($field->field_name)
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-auto">
                        @if($readonly)
                            <div class="d-flex gap-1">
                                <input type="file" class="form-control form-control-sm py-0" style="max-width:220px;" disabled>
                                <button type="button" class="btn btn-sm btn-primary py-0 px-2" disabled>
                                    <i class="bi bi-upload me-1"></i>Upload
                                </button>
                            </div>
                        @elseif(isset($form, $step))
                            <div class="d-flex gap-1 flex-wrap align-items-center">
                                @if($isDone && $fileUrl)
                                    <a href="{{ $fileUrl }}" target="_blank" rel="noopener"
                                       class="btn btn-sm btn-outline-success py-0 px-2">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                @endif
                                <form method="POST"
                                      action="{{ route('fc-reg.forms.step.save', [$form, $step]) }}"
                                      enctype="multipart/form-data"
                                      class="d-flex gap-1 flex-wrap align-items-center mb-0">
                                    @csrf
                                    <input type="hidden" name="upload_single" value="{{ $field->field_name }}">
                                    <input type="file"
                                           name="{{ $field->field_name }}"
                                           class="form-control form-control-sm py-0 @error($field->field_name) is-invalid @enderror"
                                           style="max-width:220px;"
                                           accept="{{ $accept }}"
                                           {{ $isDone ? '' : 'required' }}>
                                    <button type="submit" class="btn btn-sm btn-primary py-0 px-2">
                                        <i class="bi bi-upload me-1"></i>{{ $isDone ? 'Replace' : 'Upload' }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
                @if($field->help_text)
                    <small class="text-muted d-block mt-2">{{ $field->help_text }}</small>
                @endif
            </div>
        </div>
    @endforeach
</div>

@if(! $readonly && $fileFieldCount > 0)
    <p class="text-muted small mt-2 mb-0">
        <i class="bi bi-info-circle me-1"></i>{{ $uploadedCount }} / {{ $fileFieldCount }} uploaded.
        Use <strong>Save &amp; Continue</strong> when all mandatory documents are on file.
    </p>
@endif
