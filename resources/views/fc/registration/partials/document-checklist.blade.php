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

    // Static blank-form fallbacks — always available even where the sample-document
    // master row is absent/inactive (e.g. environments where that migration hasn't
    // run). Maps field_name => public-relative PDF path. Takes precedence over the DB.
    $staticBlankForms = [
        'doc_group_insurance' => 'admin_assets/sample/joining_documents/group_insurance_blank_form.pdf',
        'doc_nps_subscription' => 'admin_assets/sample/joining_documents/nps_blank_form.pdf',
        'doc_employee_info_sheet' => 'admin_assets/sample/joining_documents/employee_info_blank_form.pdf',
    ];
@endphp

@if(! $readonly)
    <div class="alert alert-warning small py-2 mb-3">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Documents marked <strong>Mandatory</strong> must be uploaded before final submission.
        Accepted formats: <strong>PDF, JPG, PNG</strong> (max 5MB each).
    </div>
@endif

@foreach($grouped as $section => $sectionFields)
    @php
        // Show the "Blank Form" column only for sections that actually have one
        // (e.g. Accounts Section) — hide it for sections with no static blank form.
        $sectionHasBlank = $sectionFields->contains(fn ($f) => isset($staticBlankForms[$f->field_name]));

        // Envelope label next to the section heading (Admin = Envelope 1, Accounts = Envelope 2).
        $envelope = null;
        if (stripos($section, 'Administration') !== false)  { $envelope = 'Envelope 1'; }
        elseif (stripos($section, 'Account') !== false)      { $envelope = 'Envelope 2'; }
    @endphp
    <div class="card mb-4" style="border-left:4px solid #004a93;">
        <div class="card-body p-3">
            <h6 class="fw-bold text-primary mb-3 text-uppercase" style="letter-spacing:0.3px;">{{ $section }}@if($envelope) <span class="badge bg-warning text-dark ms-2 align-middle" style="letter-spacing:0.5px;">{{ $envelope }}</span>@endif</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle table-hover table-striped mb-0">
                    <thead class="table-light text-center">
                        <tr>
                            <th style="width:60px;">Sr.No.</th>
                            <th class="text-start">Document Title</th>
                            <th style="width:260px;">Upload</th>
                            <th style="width:140px;">View Uploaded forms</th>
                            @if($sectionHasBlank)<th style="width:120px;">Blank Form</th>@endif
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
                                // Static blank-form link takes precedence, then the DB sample.
                                $staticBlank = $staticBlankForms[$field->field_name] ?? null;
                                $blankUrl    = ($staticBlank && file_exists(public_path($staticBlank)))
                                    ? asset($staticBlank)
                                    : $sampleUrl;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>
                                    <div class="fw-semibold small">
                                        {{ $field->label }}
                                        @if($field->is_required)
                                            <span class="badge bg-danger-subtle text-danger ms-1">Mandatory</span>
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
                                    @if($field->form_template)
                                        {{-- Fillable form document: fill online instead of uploading --}}
                                        @if($readonly || ! isset($form, $step))
                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" disabled>
                                                <i class="bi bi-pencil-square me-1"></i>Fill Form
                                            </button>
                                        @else
                                            <a href="{{ route('fc-reg.forms.doc-form', [$form, $step, $field->field_name]) }}"
                                               class="btn btn-sm {{ $isDone ? 'btn-outline-primary' : 'btn-primary' }} py-0 px-2 text-nowrap">
                                                <i class="bi bi-pencil-square me-1"></i>{{ $isDone ? 'Edit Form' : 'Fill Form' }}
                                            </a>
                                        @endif
                                    @elseif($readonly)
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
                                                   class="form-control form-control-sm py-0 fc-doc-upload-input @error($field->field_name) is-invalid @enderror"
                                                   accept="{{ $accept }}"
                                                   data-max-kb="{{ \App\Rules\SafeUploadedDocument::maxKilobytes((int) ($field->file_max_kb ?: 5120)) }}"
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
                                            <i class="bi bi-eye me-1"></i>View/Download
                                        </a>
                                    @else
                                        <span class="text-muted small">No file uploaded</span>
                                    @endif
                                </td>
                                @if($sectionHasBlank)
                                <td class="text-center">
                                    @if($blankUrl)
                                        <a href="{{ $blankUrl }}" target="_blank" rel="noopener"
                                           class="btn btn-link btn-sm p-0 text-primary">
                                            <i class="bi bi-file-earmark-text me-1"></i>View Blank Form
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                @endif
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

@if(! $readonly)
    <div class="card border-0 shadow-sm mb-3" style="border-left:5px solid #004a93 !important; background:#f6faff;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle me-2"
                      style="width:32px;height:32px;background:#004a93;color:#fff;"><i class="bi bi-info-lg"></i></span>
                <h6 class="fw-bold text-primary mb-0 text-uppercase" style="letter-spacing:0.5px;">Important Instructions</h6>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="p-3 rounded h-100" style="background:#fff;border:1px solid #dbe7f5;">
                        <span class="badge bg-warning text-dark mb-2" style="letter-spacing:0.5px;">ENVELOPE&ndash;1</span>
                        <p class="small mb-0 text-secondary">At the time of online registration, complete all the forms/documents pertaining to <strong class="text-dark">Envelope&ndash;1</strong>, download them, and bring the duly signed hard copies in <strong class="text-dark">Envelope&ndash;1</strong> while reporting to the Academy.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded h-100" style="background:#fff;border:1px solid #dbe7f5;">
                        <span class="badge bg-warning text-dark mb-2" style="letter-spacing:0.5px;">ENVELOPE&ndash;2</span>
                        <p class="small mb-0 text-secondary">Download all the prescribed forms/documents for <strong class="text-dark">Envelope&ndash;2</strong>, complete them, upload the duly signed &amp; scanned copies to the portal, and also bring the duly signed hard copies in <strong class="text-dark">Envelope&ndash;2</strong> at the time of reporting to the Academy.</p>
                    </div>
                </div>
            </div>

            <ul class="small mb-0 ps-3 text-secondary">
                <li class="mb-1">The checklist of the forms/documents to be submitted in <strong class="text-dark">Envelope&ndash;1</strong> and <strong class="text-dark">Envelope&ndash;2</strong> is provided in <strong class="text-dark">Annexure&ndash;V</strong>.</li>
                <li>You are required to submit all <strong class="text-dark">15 documents</strong>. If any document is not applicable, fill <strong class="text-dark">NA</strong> and submit on the online portal.</li>
            </ul>
        </div>
    </div>
@endif

@if(! $readonly && $fileFieldCount > 0)
    <p class="text-muted small mt-2 mb-0">
        <i class="bi bi-info-circle me-1"></i>{{ $uploadedCount }} / {{ $fileFieldCount }} uploaded.
        Use <strong>Save &amp; Continue</strong> when all mandatory documents are on file.
    </p>

    {{--
        Client-side pre-check: reject a disallowed selection the instant it is
        picked, so the trainee gets immediate feedback instead of a round-trip.

        This is a CONVENIENCE, not a security control — the browser `accept`
        attribute only filters the file dialog and can be bypassed, and JS can be
        disabled entirely. The authoritative check is server-side
        (App\Rules\SafeUploadedDocument on every upload route), which verifies the
        file's actual bytes, not just its name. Here we only mirror the extension
        and size rules to save the user a failed submit.
    --}}
    @once
        <script>
            (function () {
                function labelFromAccept(accept) {
                    return (accept || '')
                        .split(',')
                        .map(function (s) { return s.trim().replace(/^\./, '').toUpperCase(); })
                        .filter(Boolean)
                        .join(' / ');
                }

                function feedbackEl(input) {
                    var next = input.nextElementSibling;
                    // Reuse our own message node; never clobber a server-rendered one.
                    if (next && next.classList && next.classList.contains('fc-doc-upload-js-error')) {
                        return next;
                    }
                    var el = document.createElement('div');
                    el.className = 'fc-doc-upload-js-error text-danger small mt-1';
                    input.parentNode.insertBefore(el, input.nextSibling);
                    return el;
                }

                function clearError(input) {
                    input.classList.remove('is-invalid');
                    var next = input.nextElementSibling;
                    if (next && next.classList && next.classList.contains('fc-doc-upload-js-error')) {
                        next.textContent = '';
                    }
                }

                function reject(input, message) {
                    input.value = '';
                    input.classList.add('is-invalid');
                    feedbackEl(input).textContent = message;
                }

                // Extensions we never accept regardless of the field's accept list —
                // mirrors the server's dangerous-extension guard so a double
                // extension (report.php.pdf) is caught before submit too.
                var BLOCKED = /\.(php\d?|phtml|phps|pht|phar|cgi|pl|py|rb|jsp|asp|aspx|sh|bash|exe|com|bat|cmd|msi|dll|htaccess|htm|html|svg|js)(\.|$)/i;

                // Active-content markers mirrored from App\Rules\SafeUploadedDocument, so a
                // PDF/DOCX carrying embedded JavaScript, a launch / auto-run action, an
                // embedded file, or a macro project is caught the instant it is picked —
                // not only on the submit round-trip. Best-effort only: the browser can't
                // inflate compressed PDF streams, so the server-side scan stays the
                // authority (it inflates FlateDecode streams and can't be bypassed).
                var ACTIVE_MARKERS = {
                    '.pdf':  ['/JavaScript', '/JS', '/Launch', '/EmbeddedFile', '/RichMedia', '/XFA'],
                    '.docx': ['vbaProject', 'vbaData', 'macroEnabled']
                };

                function submitBtnFor(input) {
                    var form = input.closest ? input.closest('form') : null;
                    return form ? form.querySelector('button[type="submit"]') : null;
                }

                // Read the picked file and reject immediately if it carries active content.
                // The submit button is disabled while the async read runs so the file can't
                // be sent before the scan resolves.
                function scanActiveContent(input, file, ext) {
                    var markers = ACTIVE_MARKERS[ext];
                    if (!markers || typeof FileReader === 'undefined') { return; }

                    var btn = submitBtnFor(input);
                    if (btn) { btn.disabled = true; }

                    var reader = new FileReader();
                    reader.onload = function () {
                        var text = String(reader.result || '');
                        var found = markers.some(function (m) { return text.indexOf(m) !== -1; });
                        if (found) {
                            reject(input, 'This ' + ext.slice(1).toUpperCase()
                                + ' contains embedded scripts, macros, or auto-run actions and cannot be uploaded. '
                                + 'Please upload a plain document (print or "Save as" a flat file).');
                        } else if (btn) {
                            btn.disabled = false;
                        }
                    };
                    // On read error, defer to the server rather than block a legitimate file.
                    reader.onerror = function () { if (btn) { btn.disabled = false; } };
                    reader.readAsText(file, 'ISO-8859-1');
                }

                document.addEventListener('change', function (e) {
                    var input = e.target;
                    if (!input.classList || !input.classList.contains('fc-doc-upload-input')) {
                        return;
                    }

                    clearError(input);
                    var resetBtn = submitBtnFor(input);
                    if (resetBtn) { resetBtn.disabled = false; }

                    var file = input.files && input.files[0];
                    if (!file) {
                        return;
                    }

                    var name = file.name || '';
                    var accept = input.getAttribute('accept') || '';
                    var allowed = accept.split(',').map(function (s) { return s.trim().toLowerCase(); }).filter(Boolean);
                    var dot = name.lastIndexOf('.');
                    var ext = dot >= 0 ? name.slice(dot).toLowerCase() : '';

                    if (BLOCKED.test(name) || (allowed.length && allowed.indexOf(ext) === -1)) {
                        reject(input, 'Only ' + labelFromAccept(accept) + ' files are allowed. "' + name + '" was not accepted.');
                        return;
                    }

                    var maxKb = parseInt(input.getAttribute('data-max-kb'), 10);
                    if (maxKb > 0 && file.size > maxKb * 1024) {
                        var shown = maxKb >= 1024 ? (Math.round(maxKb / 102.4) / 10) + ' MB' : maxKb + ' KB';
                        reject(input, 'File is too large. Maximum allowed size is ' + shown + '.');
                        return;
                    }

                    // Name and size are fine — now scan the bytes and reject on the spot
                    // if the file embeds scripts / macros / auto-run actions.
                    scanActiveContent(input, file, ext);
                }, true);
            })();
        </script>
    @endonce
@endif
