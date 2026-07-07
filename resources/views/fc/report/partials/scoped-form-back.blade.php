@if(!empty($scopedForm))
    <a href="{{ route('admin.reports.form', $scopedForm) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to {{ Str::limit($scopedForm->form_name, 28) }}
    </a>
@else
    <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
@endif
