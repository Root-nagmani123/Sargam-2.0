{{--
    Row actions for the Define Electric Slab grid (new-design-index-page.md §3b):
    icon over caption, Edit then Delete.

    Edit carries the whole record on data-* attributes, so the modal opens
    populated without a second request — every field the form needs is already
    on the row.
--}}
<div class="es-act-group" role="group" aria-label="Row actions">
    <button type="button" class="es-act es-act--edit js-slab-edit" title="Edit"
        data-pk="{{ $row->pk }}"
        data-start="{{ $row->start_unit_range }}"
        data-end="{{ $row->end_unit_range }}"
        data-rate="{{ number_format((float) $row->rate_per_unit, 2, '.', '') }}"
        data-unit-type="{{ $row->estate_unit_type_master_pk }}">
        <span class="es-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
        <span class="es-act__label">Edit</span>
    </button>
    <button type="button" class="es-act es-act--delete js-slab-delete" title="Delete"
        data-url="{{ route('admin.estate.define-electric-slab.destroy', $row->pk) }}">
        <span class="es-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
        <span class="es-act__label">Delete</span>
    </button>
</div>
