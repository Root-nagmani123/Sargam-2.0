@php
    /** @var \App\Models\FC\FcFormFieldGroup $group */
    /** @var \App\Models\FC\FcFormGroupField $field */
@endphp
<tr data-id="{{ $field->id }}" data-group-id="{{ $group->id }}">
    <td class="text-muted small">{{ $field->display_order }}</td>
    <td class="fw-semibold small">{{ $field->label }}</td>
    <td><code class="small">{{ $field->field_name }}</code></td>
    <td><span class="badge bg-light text-dark">{{ $field->field_type }}</span></td>
    <td><code class="small">{{ $field->target_column }}</code></td>
    <td class="small text-muted">{{ $group->group_label }}</td>
    <td>
        @if($field->is_required)
            <span class="badge bg-danger">Yes</span>
        @else
            <span class="badge bg-secondary">No</span>
        @endif
    </td>
    <td>
        @if($field->is_active)
            <span class="badge bg-success">Yes</span>
        @else
            <span class="badge bg-secondary">No</span>
        @endif
    </td>
    <td class="fc-fb-actions-col">
        <div class="fc-fb-actions">
            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1 btn-edit-group-field" data-field='@json($field)' title="Edit">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moveGroupField({{ $field->id }}, {{ $group->id }}, 'up')" title="Move Up">
                <i class="bi bi-arrow-up"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="moveGroupField({{ $field->id }}, {{ $group->id }}, 'down')" title="Move Down">
                <i class="bi bi-arrow-down"></i>
            </button>
            @if($field->is_active)
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Cannot delete — field is in use"
                    onclick="alert('This field is currently in use on the form and cannot be deleted. Set it to inactive first, then try again.')">
                    <i class="bi bi-trash"></i>
                </button>
            @else
                <form method="POST" action="{{ route('fc-reg.admin.form-builder.group-field.delete', $field) }}" class="fc-fb-actions__form" onsubmit="return confirm('Delete this field?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
            @endif
        </div>
    </td>
</tr>
