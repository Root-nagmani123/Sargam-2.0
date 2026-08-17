@extends('admin.layouts.master')

@section('title', 'Discipline Master')

@push('styles')
{{-- Shared with the other Memo Master & Mapping pages: same module, same
     components (docs/new-design-index-page.md §7). The .mtm-* prefix is the
     module's, not one page's. --}}
<link rel="stylesheet"
    href="{{ asset('css/memo-masters-admin.css') }}?v={{ @filemtime(public_path('css/memo-masters-admin.css')) }}">
@endpush

@section('setup_content')
<div class="container-fluid mtm-master-page">
    <x-breadcrum title="Discipline Master" :showBack="false">
        <button type="button" id="dmAddBtn"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Discipline</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-1 overflow-hidden">
            <div class="card-body p-3 p-md-4">

                {{-- Toolbar (§2). Both slots are filled by datatable-global-ui.js:
                     it moves the DataTables search box into .programme-dt-search and
                     the pager + count into .programme-dt-footer below. --}}
                <div class="programme-dt-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2 mb-4">
                    <button type="button" class="btn programme-dt-btn-columns" id="dmColumnsToggle"
                        data-bs-toggle="modal" data-bs-target="#dmColumnsModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search" data-dt-search-for="discipline-table"></div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                    </div>
                </div>

                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="discipline-table"></div>
            </div>
        </div>
    </div>
</div>

{{-- Add / Edit share one modal (§3c): Create and Edit look ALIKE and differ only
     in the title and the submit caption. It posts the same plain form the
     standalone create/edit page used, so store() is untouched. --}}
<div class="modal fade mtm-modal" id="dmFormModal" tabindex="-1" aria-labelledby="dmFormModalLabel"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <form method="POST" action="{{ route('master.discipline.store') }}" id="dmForm">
                @csrf
                <input type="hidden" name="id" id="dm_id" value="">

                <div class="mtm-modal-header">
                    <h5 class="mtm-modal-title" id="dmFormModalLabel">Add Discipline</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="mtm-modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger mb-3" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="mtm-field-card">
                        <div class="mtm-field">
                            <label for="dm_course_master_pk" class="mtm-field-label">
                                Course <span class="mtm-req">*</span>
                            </label>
                            <select name="course_master_pk" id="dm_course_master_pk" class="mtm-control" required>
                                <option value="">Select Course</option>
                                @foreach ($courses ?? [] as $c)
                                    <option value="{{ $c->pk }}" {{ old('course_master_pk') == $c->pk ? 'selected' : '' }}>
                                        {{ $c->course_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_master_pk')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mtm-field">
                            <label for="dm_discipline_name" class="mtm-field-label">
                                Discipline Name <span class="mtm-req">*</span>
                            </label>
                            <input type="text" name="discipline_name" id="dm_discipline_name"
                                class="mtm-control" maxlength="100" placeholder="Enter discipline name"
                                value="{{ old('discipline_name') }}" autocomplete="off" required>
                            @error('discipline_name')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mtm-field">
                            <label for="dm_mark_deduction" class="mtm-field-label">
                                Mark Deduction <span class="mtm-req">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="mark_deduction" id="dm_mark_deduction"
                                class="mtm-control" placeholder="Enter mark deduction"
                                value="{{ old('mark_deduction') }}" required>
                            @error('mark_deduction')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mtm-field">
                            <label for="dm_active_inactive" class="mtm-field-label">
                                Status <span class="mtm-req">*</span>
                            </label>
                            <select name="active_inactive" id="dm_active_inactive" class="mtm-control" required>
                                <option value="1" {{ old('active_inactive', 1) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="2" {{ old('active_inactive') == 2 ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('active_inactive')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mtm-modal-footer">
                    <button type="button" class="btn mtm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mtm-btn-submit" id="dmSubmit">Add Discipline</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade mtm-modal" id="dmColumnsModal" tabindex="-1" aria-labelledby="dmColumnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="mtm-modal-header">
                <h5 class="mtm-modal-title" id="dmColumnsModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="mtm-modal-body">
                <div class="mtm-col-grid" id="dmColumnsGrid"></div>
            </div>
            <div class="mtm-modal-footer">
                <button type="button" class="btn mtm-btn-cancel" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}

<script>
$(function () {
    const formModalEl = document.getElementById('dmFormModal');

    // The modals are appended to <body> so their backdrop stacks above the page
    // chrome rather than inside the card.
    document.querySelectorAll('.mtm-modal').forEach(function (el) {
        if (el.parentElement && el.parentElement !== document.body) {
            document.body.appendChild(el);
        }
    });

    function setMode(mode) {
        $('#dmFormModalLabel').text(mode === 'edit' ? 'Edit Discipline' : 'Add Discipline');
        $('#dmSubmit').text(mode === 'edit' ? 'Update Discipline' : 'Add Discipline');
    }

    $('#dmAddBtn').on('click', function () {
        $('#dmForm')[0].reset();
        $('#dm_id').val('');
        $('#dm_active_inactive').val('1');
        setMode('add');
        bootstrap.Modal.getOrCreateInstance(formModalEl).show();
    });

    $(document).on('click', '.dm-edit-btn', function () {
        const $btn = $(this);
        $('#dmForm')[0].reset();

        $('#dm_id').val($btn.data('id'));
        $('#dm_course_master_pk').val(String($btn.data('course')));
        $('#dm_discipline_name').val($btn.data('name') || '');
        $('#dm_mark_deduction').val($btn.data('mark'));

        // Rows are stored 1 = Active / 0 = Inactive (that is what the grid switch
        // writes), while this form posts 1 / 2 — so anything that is not Active
        // maps to the Inactive option. Without this the select fell back to its
        // first option and saving silently re-activated the row.
        $('#dm_active_inactive').val(String($btn.data('status')) === '1' ? '1' : '2');

        setMode('edit');
        bootstrap.Modal.getOrCreateInstance(formModalEl).show();
    });

    formModalEl.addEventListener('shown.bs.modal', function () {
        $('#dm_course_master_pk').trigger('focus');
    });

    // A failed validation round-trips through store() and re-renders this page,
    // so re-open the modal with the messages already in it.
    @if ($errors->any())
        setMode("{{ old('id') ? 'edit' : 'add' }}");
        $('#dm_id').val("{{ old('id') }}");
        bootstrap.Modal.getOrCreateInstance(formModalEl).show();
    @endif

    // Column visibility chips, built from the live table once it exists.
    $('#discipline-table').on('init.dt', function () {
        const table = $('#discipline-table').DataTable();
        const $grid = $('#dmColumnsGrid').empty();

        table.columns().every(function (idx) {
            const title = $.trim($(this.header()).text()) || ('Column ' + (idx + 1));
            const visible = this.visible();
            $grid.append(
                '<label class="mtm-col-chip' + (visible ? ' is-checked' : '') + '" for="dmColToggle' + idx + '">' +
                    '<input class="form-check-input dm-col-toggle" type="checkbox" ' + (visible ? 'checked ' : '') +
                           'id="dmColToggle' + idx + '" data-column="' + idx + '">' +
                    '<span>' + title + '</span>' +
                '</label>'
            );
        });

        $grid.off('change.dm').on('change.dm', '.dm-col-toggle', function () {
            table.column($(this).data('column')).visible(this.checked);
            $(this).closest('.mtm-col-chip').toggleClass('is-checked', this.checked);
        });
    });
});
</script>
@endpush
