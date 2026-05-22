@forelse($forms as $form)
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:10px;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                         style="width:45px;height:45px;background:{{ $form->is_active ? '#1a3c6e' : '#6c757d' }};color:#fff;font-size:1.2rem;">
                        <i class="bi {{ $form->icon ?? 'bi-file-text' }}"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h6 class="mb-0 text-truncate">{{ $form->form_name }}</h6>
                        <small class="text-muted">{{ $form->form_slug }}</small>
                    </div>
                    @if(! $form->is_active)
                        <span class="badge bg-secondary ms-2 flex-shrink-0">Inactive</span>
                    @endif
                </div>

                <div class="d-flex gap-3 mb-3">
                    <div class="text-center">
                        <div class="fw-bold text-primary" style="font-size:1.3rem;">{{ $form->steps_count }}</div>
                        <small class="text-muted">Steps</small>
                    </div>
                    @if($form->courseMaster && $form->courseMaster->end_date)
                        <div class="text-center">
                            <small class="text-muted d-block">Course ends</small>
                            <small class="fw-semibold">{{ \Carbon\Carbon::parse($form->courseMaster->end_date)->format('d-m-Y') }}</small>
                        </div>
                    @endif
                </div>

                <p class="text-muted small mb-3">{{ Str::limit($form->description, 100) }}</p>

                @if($form->courseMaster)
                    <div class="mb-2">
                        <small class="text-success">
                            <i class="bi bi-mortarboard me-1"></i>
                            <strong>Course:</strong> {{ $form->courseMaster->course_name }}
                        </small>
                    </div>
                @else
                    <div class="mb-2">
                        <small class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Not linked to Course Master</small>
                    </div>
                @endif

                @if($form->consolidation_table)
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="bi bi-database me-1"></i>Tracking: <code>{{ $form->consolidation_table }}</code>
                        </small>
                    </div>
                @endif

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('fc-reg.admin.forms.edit', $form) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil-square me-1"></i>Edit / Steps
                    </a>
                    <a href="{{ route('fc-reg.forms.dashboard', $form) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye me-1"></i>User View
                    </a>
                    <a href="{{ route('admin.reports.form', $form) }}" class="btn btn-sm btn-outline-info" title="View Submissions Report">
                        <i class="bi bi-bar-chart-line me-1"></i>Report
                    </a>
                    <form method="POST" action="{{ route('fc-reg.admin.forms.destroy', $form) }}" class="d-inline" onsubmit="return confirm('Delete this form and ALL its steps/fields? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12">
        <div class="text-center py-5 text-muted" id="formsGridEmpty">
            <i class="bi bi-inbox display-4"></i>
            <p class="mt-3 mb-0">No forms match your filters.</p>
            <p class="small">Try Active/Archived, search, or <a href="{{ route('fc-reg.admin.forms.create') }}">create a new form</a>.</p>
        </div>
    </div>
@endforelse
