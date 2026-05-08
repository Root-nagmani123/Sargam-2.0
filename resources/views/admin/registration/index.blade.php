@extends('admin.layouts.master')

@section('title', 'Forms - Sargam | Lal Bahadur')

@push('styles')
<style>
    .parent-row {
        background-color: #e9ecef !important;
        font-weight: 600;
    }
    .parent-row:hover {
        background-color: #dee2e6 !important;
    }
    .child-row {
        background-color: #f8f9fa !important;
    }
    .child-row td:nth-child(2) {
        padding-left: 2rem;
    }
    .child-row td:first-child {
        border-left: 3px solid #0d6efd !important;
    }
    .child-row:hover {
        background-color: #e2e6ea !important;
    }
    .toggle-child {
        transition: transform 0.2s ease;
        user-select: none;
    }
    .toggle-child.expanded {
        transform: rotate(90deg);
    }
</style>
@endpush

@section('content')
    <div class="container-fluid">
        <x-breadcrum title="Registration" />
        <x-session_message />
        <div class="card card-body py-3">
            <div class="row">
                <div class="col-6">
                    <h4>Registration</h4>
                </div>
                {{-- <div class="col-6 text-end">
                <a href="{{ route('forms.create') }}" class="btn btn-primary">Add Form</a>
                <a href="{{ route('forms.inactive') }}" class="btn btn-secondary">Inactive Forms</a>
            </div> --}}
                <div class="col-6 text-end d-flex justify-content-end align-items-center gap-2">
                    <a href="{{ route('forms.create') }}" class="btn btn-primary">Add Form</a>

                    <!-- Use Template: Bootstrap dropdown with search -->
                    <div class="dropdown">
                        <button class="btn btn-info dropdown-toggle" type="button" id="templateDropdownBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            Use Template
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" style="width:280px;max-height:350px;overflow:hidden;" aria-labelledby="templateDropdownBtn">
                            <input type="text" class="form-control form-control-sm mb-2" id="templateSearch" placeholder="Search templates..." autocomplete="off">
                            <div style="max-height:260px;overflow-y:auto;">
                                @foreach ($forms_parent as $form)
                                    <a class="dropdown-item template-item" href="{{ route('forms.template.create', ['template' => $form->id]) }}">
                                        {{ $form->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('forms.inactive') }}" class="btn btn-secondary">Archived Courses</a>
                </div>

            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" id="formSearch" class="form-control" placeholder="Search forms...">
                </div>
                <div class="table-responsive">
                    <table id="registration_forms" class="table table-bordered text-nowrap align-middle">
                        <thead>
                            <tr>
                                <th></th> {{-- Chevron column --}}
                                <th>S.No.</th>
                                <th>Form ID</th>
                                <th>Course Name</th>
                                <th>Form Name</th>
                                <th>Submissions List</th>
                                <th>Pending Submissions</th>
                                <th>Edit Form Fields</th>
                                <th>Actions</th>
                                <th>Status</th>
                                <th>Position</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $serial = 1; @endphp

                            @foreach ($groupedForms[null] ?? [] as $parent)
                                {{-- Parent Row --}}
                                <tr class="parent-row" data-id="{{ $parent->id }}" style="cursor:pointer;">
                                    <td class="text-center">
                                        @if (isset($groupedForms[$parent->id]) && count($groupedForms[$parent->id]) > 0)
                                            <span class="toggle-child material-icons" style="font-size:20px;cursor:pointer;vertical-align:middle;">chevron_right</span>
                                        @endif
                                    </td>
                                    <td>{{ $serial++ }}</td>
                                    <td>{{ $parent->id }}</td>
                                    <td>
                                        <span class="material-icons" style="font-size:16px;vertical-align:middle;color:#6c757d;">folder</span>
                                        <strong>{{ $parent->name }}</strong>
                                    </td>
                                    <td>{{ $parent->description }}</td>
                                    <td>
                                        <a href="{{ route('forms.courseList', $parent->id) }}"
                                            class="btn btn-sm btn-success">View</a>
                                    </td>
                                    <td>
                                        <a href="{{ route('forms.show', $parent->id) }}"
                                            class="btn btn-sm btn-info">Preview</a>
                                    </td>
                                    <td>
                                        <a href="{{ route('forms.fc_edit', $parent->id) }}"
                                            class="btn btn-sm btn-warning">Edit Fields</a>
                                    </td>
                                    <td>
                                        <a href="{{ route('forms.edit', $parent->id) }}"
                                            class="btn btn-sm btn-danger">Edit</a>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-visible-switch" type="checkbox"
                                                data-id="{{ $parent->id }}" {{ $parent->visible ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td></td>
                                </tr>

                                {{-- Child Rows --}}
                                @if (isset($groupedForms[$parent->id]))
                                    @foreach ($groupedForms[$parent->id] as $index => $child)
                                        <tr class="child-row" data-parent="{{ $parent->id }}"
                                            style="display:none;">
                                            <td></td>
                                            <td>{{ $serial++ }}</td>
                                            <td>{{ $child->id }}</td>
                                            <td>
                                                <span class="text-muted" style="margin-left:0.5rem;">└─</span>
                                                <span class="material-icons" style="font-size:14px;vertical-align:middle;color:#0d6efd;">description</span>
                                                {{ $child->name ?? '' }}
                                            </td>
                                            <td>{{ $child->description }}</td>
                                            <td>
                                                <a href="{{ route('forms.courseList', $child->id) }}"
                                                    class="btn btn-sm btn-success">View</a>
                                            </td>
                                            <td>
                                                <a href="{{ route('forms.show', $child->id) }}"
                                                    class="btn btn-sm btn-info">Preview</a>
                                            </td>
                                            <td>
                                                <a href="{{ route('forms.fc_edit', $child->id) }}"
                                                    class="btn btn-sm btn-warning">Edit Fields</a>
                                            </td>
                                            <td>
                                                <a href="{{ route('forms.edit', $child->id) }}"
                                                    class="btn btn-sm btn-danger">Edit</a>
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input toggle-visible-switch" type="checkbox"
                                                        data-id="{{ $child->id }}"
                                                        {{ $child->visible ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if ($index > 0)
                                                    <button type="button" class="btn btn-sm btn-secondary btn-move"
                                                        data-id="{{ $child->id }}" data-direction="up"
                                                        title="Move Up">↑</button>
                                                @endif
                                                @if ($index < count($groupedForms[$parent->id]) - 1)
                                                    <button type="button" class="btn btn-sm btn-secondary btn-move"
                                                        data-id="{{ $child->id }}" data-direction="down"
                                                        title="Move Down">↓</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Template dropdown search filter
            $('#templateSearch').on('keyup', function() {
                var val = $(this).val().toLowerCase();
                $('.template-item').each(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
                });
            });
            // Auto-focus search when dropdown opens
            $('#templateDropdownBtn').on('shown.bs.dropdown', function() {
                $('#templateSearch').val('').trigger('keyup').focus();
            });

            // Simple search filter — no DataTable overhead for smooth scrolling
            $('#formSearch').on('keyup', function() {
                var val = $(this).val().toLowerCase();
                $('#registration_forms tbody tr.parent-row').each(function() {
                    var row = $(this);
                    var parentId = row.data('id');
                    var text = row.text().toLowerCase();
                    var match = text.indexOf(val) > -1;
                    row.toggle(match);
                    if (!match) {
                        $('tr.child-row[data-parent="' + parentId + '"]').hide();
                        row.find('.toggle-child').removeClass('expanded');
                    }
                });
            });

            // Expand/Collapse child rows
            $('#registration_forms tbody').on('click', '.toggle-child', function(e) {
                e.stopPropagation();
                e.preventDefault();
                var icon = $(this);
                var tr = icon.closest('tr');
                var parentId = tr.data('id');
                var children = $('tr.child-row[data-parent="' + parentId + '"]');
                var isExpanded = icon.hasClass('expanded');

                if (isExpanded) {
                    children.each(function() {
                        $(this).css('display', 'none');
                    });
                    icon.removeClass('expanded');
                } else {
                    children.each(function() {
                        $(this).css('display', 'table-row');
                    });
                    icon.addClass('expanded');
                }
            });

            // Toggle visibility via AJAX
            $(document).on('change', '.toggle-visible-switch', function() {
                var switchEl = $(this);
                var id = switchEl.data('id');
                switchEl.prop('disabled', true);

                $.ajax({
                    url: '/registration/forms/' + id + '/toggle-visible',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    contentType: 'application/json',
                    data: JSON.stringify({}),
                    success: function(data) {
                        switchEl.prop('disabled', false);
                        if (!data.success) {
                            switchEl.prop('checked', !switchEl.prop('checked'));
                            alert('Failed to update visibility.');
                        }
                    },
                    error: function() {
                        switchEl.prop('disabled', false);
                        switchEl.prop('checked', !switchEl.prop('checked'));
                        alert('An error occurred while updating visibility.');
                    }
                });
            });

            // Move Up / Move Down via AJAX
            $(document).on('click', '.btn-move', function() {
                var btn = $(this);
                var id = btn.data('id');
                var direction = btn.data('direction');
                var url = '/registration/forms/' + id + '/move' + direction;

                btn.prop('disabled', true);

                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    contentType: 'application/json',
                    data: JSON.stringify({}),
                    success: function(data) {
                        if (data.success) {
                            var currentRow = btn.closest('tr');
                            if (direction === 'up') {
                                var prevRow = currentRow.prev('tr.child-row');
                                if (prevRow.length) {
                                    currentRow.insertBefore(prevRow);
                                }
                            } else {
                                var nextRow = currentRow.next('tr.child-row');
                                if (nextRow.length) {
                                    currentRow.insertAfter(nextRow);
                                }
                            }
                            updateMoveButtons(currentRow.data('parent'));
                        } else {
                            alert(data.message || 'Failed to move form.');
                        }
                        btn.prop('disabled', false);
                    },
                    error: function() {
                        alert('An error occurred while moving the form.');
                        btn.prop('disabled', false);
                    }
                });
            });

            function updateMoveButtons(parentId) {
                var children = $('tr.child-row[data-parent="' + parentId + '"]');
                children.each(function(index) {
                    var td = $(this).find('td:last');
                    var childId = $(this).find('.toggle-visible-switch').data('id');
                    td.empty();
                    if (index > 0) {
                        td.append('<button type="button" class="btn btn-sm btn-secondary btn-move" data-id="' + childId + '" data-direction="up" title="Move Up">↑</button> ');
                    }
                    if (index < children.length - 1) {
                        td.append('<button type="button" class="btn btn-sm btn-secondary btn-move" data-id="' + childId + '" data-direction="down" title="Move Down">↓</button>');
                    }
                });
            }
        });
    </script>
@endpush
