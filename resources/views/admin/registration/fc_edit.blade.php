@extends('admin.layouts.master')

@section('title', 'Edit Form Fields - Sargam | Lal Bahadur')
@section('content')
    <div class="container-fluid">
        <x-session_message />
        <x-breadcrum title="Edit Registration Form" />
        {{-- <h1>Edit Form Fields</h1> --}}

        <form method="POST" action="{{ route('forms.fc_update') }}">
            @csrf

            <input type="hidden" name="form_id" value="{{ $form_id }}">

            <div id="sections-container">
                @foreach ($sections as $index => $section)
                    <div class="section-group" id="section_{{ $index }}">
                        <input type="hidden" name="section_id[]" value="{{ $section->id }}">
                        <input type="hidden" name="sort_order[]" value="{{ $index }}">

                        <div class="form-group">
                            <label class="form-label">Section Title:</label>
                            <input type="text" name="section_title[]" value="{{ $section->section_title }}" required
                                class="form-control">
                        </div>

                        @php
                            $section_fields = $fields->where('section_id', $section->id);
                            $has_table_format = $section_fields->contains('format', 'table');
                        @endphp

                        <div id="fields-container_{{ $index }}">
                            {{-- First display table fields if any exist --}}
                            @php
                                $table_fields = $section_fields->where('format', 'table');
                            @endphp

                            @if ($table_fields->isNotEmpty())
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Label</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Options/URL</th>
                                            <th>Required</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($table_fields as $field)
                                            <tr class="odd">
                                                <input type="hidden" name="field_id[]" value="{{ $field->id }}">
                                                <input type="hidden" name="field_section[]" value="{{ $section->id }}">

                                                <td><input type="text" name="field_label[]"
                                                        value="{{ $field->formlabel }}"  class="form-control">
                                                </td>
                                                <td><input type="text" name="field_name[]"
                                                        value="{{ $field->field_title ?? $field->formname }}" 
                                                        class="form-control"></td>
                                                <td>
                                                    <select name="field_type[]" class="form-control">
                                                        @foreach (['Label', 'Text', 'Date', 'Email', 'Textarea', 'Checkbox', 'Radio Button', 'Select Box', 'File Upload', 'View/Download'] as $type)
                                                            <option value="{{ $type }}"
                                                                {{ ($field->field_type ?? $field->formtype) === $type ? 'selected' : '' }}>
                                                                {{ $type }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input type="text" name="field_options[]"
                                                        value="{{ $field->field_options ?? ($field->fieldoption ?? $field->field_url) }}"
                                                        class="form-control"></td>
                                                <td><input type="checkbox" name="is_required[]"
                                                        {{ $field->required ? 'checked' : '' }} class="form-control">
                                                </td>
                                                <td><input type="checkbox" name="delete_fields[]"
                                                        value="{{ $field->id }}" class="form-control"></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif

                            {{-- Then display regular fields (non-table) --}}
                            @php
                                $regular_fields = $section_fields
                                    ->where('format', '!=', 'table')
                                    ->whereNotNull('formname');
                            @endphp

                            @foreach ($regular_fields as $fieldIndex => $field)
                                <div class="form-group border p-3 mb-4 rounded shadow-sm bg-light">
                                    <input type="hidden" name="field_id[{{ $fieldIndex }}]"
                                        value="{{ $field->id }}">
                                    <input type="hidden" name="field_section[{{ $fieldIndex }}]"
                                        value="{{ $section->id }}">

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Label:
                                                @if ($field->required)
                                                    <span class="text-danger">*</span>
                                                @endif
                                            </label>
                                            <input type="text" name="field_label[{{ $fieldIndex }}]"
                                                value="{{ $field->formlabel }}" required class="form-control">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Field Name:</label>
                                            <select class="form-control" name="field_name[{{ $fieldIndex }}]" required>
                                                @foreach ($columns as $column)
                                                    <option value="{{ $column }}"
                                                        {{ $field->formname == $column ? 'selected' : '' }}>
                                                        {{ ucfirst(str_replace('_', ' ', $column)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Type:</label>
                                            <select name="field_type[{{ $fieldIndex }}]" class="form-control">
                                                @foreach (['text', 'dropdown', 'radio', 'checkbox', 'date', 'file', 'textarea', 'email', 'number', 'time'] as $type)
                                                    <option value="{{ $type }}"
                                                        {{ $field->formtype === $type ? 'selected' : '' }}>
                                                        {{ ucfirst($type) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Options (comma separated):</label>
                                            <input type="text" name="field_options[{{ $fieldIndex }}]"
                                                value="{{ $field->fieldoption }}" class="form-control">
                                        </div>
                                    </div>

                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <div class="form-check" style="padding-left: 0 !important;">
                                                <input type="checkbox" name="is_required[{{ $field->id }}]"
                                                    id="required_{{ $field->id }}"
                                                    {{ $field->required ? 'checked' : '' }}>
                                                <label class="form-check-label"
                                                    for="required_{{ $field->id }}">Required</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check" style="padding-left: 0 !important;">
                                                <input type="checkbox" name="delete_fields[{{ $fieldIndex }}]"
                                                    value="{{ $field->id }}" id="delete_{{ $fieldIndex }}">
                                                <label class="form-check-label text-danger"
                                                    for="delete_{{ $fieldIndex }}">Delete</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" class="btn btn-primary btn-add-field"
                            onclick="addField({{ $index }}, {{ $section->id }})">Add New Field</button>
                        <button type="button" class="btn btn-danger btn-remove-section"
                            onclick="removeSection(this, '{{ $section->id }}')">Remove Section</button>
                    </div>
                @endforeach
            </div>

            <!--  Hidden container for deleted section IDs -->
            <div id="deleted-sections-container"></div>

            <div class="gap-2 text-center mt-4">
                <button type="button" class="btn btn-success btn-add-section" onclick="addSection()">Add New
                    Section</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('forms.index') }}" class="btn btn-secondary">Cancel</a>
            </div>

    </div>

    <script>
        // Track used options
        const usedOptions = new Set();

        // Initialize with already used options
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('select[name^="field_name"]').forEach(select => {
                if (select.value) {
                    usedOptions.add(select.value);
                }

                // Add change event listeners
                select.addEventListener('change', function() {
                    updateDropdownOptions();
                });
            });

            updateDropdownOptions();
        });

        // Function to update dropdown options
        function updateDropdownOptions() {
            const allDropdowns = document.querySelectorAll('select[name^="field_name"]');

            // First, clear all used options and rebuild
            usedOptions.clear();
            allDropdowns.forEach(dropdown => {
                if (dropdown.value) {
                    usedOptions.add(dropdown.value);
                }
            });

            // Then update all dropdowns
            allDropdowns.forEach(dropdown => {
                const currentValue = dropdown.value;
                const options = dropdown.querySelectorAll('option');

                options.forEach(option => {
                    if (option.value === '') return; // Skip placeholder

                    // Disable if used by another dropdown (and not this one)
                    option.disabled = usedOptions.has(option.value) && option.value !== currentValue;
                });
            });
        }
        let sectionCounter = {{ count($sections) }};
        let fieldCounter = {{ count($fields) }};



        function addField(sectionIndex, sectionId) {
            const fieldsContainer = document.querySelector(`#fields-container_${sectionIndex}`);
            const isTableFormat = fieldsContainer.querySelector('table') !== null;
            const newFieldIndex = fieldCounter;

            // Generate options HTML while excluding already used options
            let optionsHtml = '<option value="" selected disabled>Choose an option</option>';
            @foreach ($columns as $column)
                if (!usedOptions.has('{{ $column }}')) {
                    optionsHtml +=
                        `<option value="{{ $column }}">{{ ucfirst(str_replace('_', ' ', $column)) }}</option>`;
                }
            @endforeach

            let fieldHtml;

            if (isTableFormat) {
                fieldHtml = `
            <tr>
                <input type="hidden" name="field_id[${newFieldIndex}]" value="new">
                <input type="hidden" name="field_section[${newFieldIndex}]" value="${sectionId}">
                <td><input type="text" name="field_label[${newFieldIndex}]" required class="form-control"></td>
                <td>
                    <select name="field_name[${newFieldIndex}]" class="form-control" required>
                        ${optionsHtml}
                    </select>
                </td>
                <td>
                    <select name="field_type[${newFieldIndex}]" class="form-control">
                        @foreach (['text', 'dropdown', 'radio', 'checkbox', 'date', 'file', 'textarea', 'email', 'number', 'time'] as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="text" name="field_options[${newFieldIndex}]" class="form-control"></td>
                <td><input type="checkbox" name="is_required[${newFieldIndex}]" class="form-check-input"></td>
                <td><button type="button" class="btn btn-sm btn-danger" onclick="removeField(this)">Remove</button></td>
            </tr>
         `;
            } else {
                fieldHtml = `
            <div class="form-group border p-3 mb-4 rounded shadow-sm">
                <input type="hidden" name="field_id[${newFieldIndex}]" value="new">
                <input type="hidden" name="field_section[${newFieldIndex}]" value="${sectionId}">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Label:</label>
                        <input type="text" name="field_label[${newFieldIndex}]" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Name:</label>
                        <select class="form-control" name="field_name[${newFieldIndex}]" required>
                            ${optionsHtml}
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Type:</label>
                        <select name="field_type[${newFieldIndex}]" class="form-control">
                            @foreach (['text', 'dropdown', 'radio', 'checkbox', 'date', 'file', 'textarea', 'email', 'number', 'time'] as $type)
                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Options (comma separated):</label>
                        <input type="text" name="field_options[${newFieldIndex}]" class="form-control">
                    </div>
                </div>

                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" name="is_required[${newFieldIndex}]" class="form-check-input" id="required_${newFieldIndex}">
                            <label class="form-check-label" for="required_${newFieldIndex}">Required</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeField(this)">Remove Field</button>
                    </div>
                </div>
            </div>
         `;
            }

            if (isTableFormat) {
                fieldsContainer.querySelector('tbody').insertAdjacentHTML('beforeend', fieldHtml);
            } else {
                fieldsContainer.insertAdjacentHTML('beforeend', fieldHtml);
            }

            // Add event listener to the new dropdown
            const newDropdown = fieldsContainer.querySelector(`select[name="field_name[${newFieldIndex}]"]`);
            if (newDropdown) {
                newDropdown.addEventListener('change', function() {
                    // When a value is selected, add it to usedOptions
                    if (this.value) {
                        usedOptions.add(this.value);
                    }
                    updateDropdownOptions();
                });
            }

            fieldCounter++;
        }

        // Function to remove a field and make its option available again
        function removeField(button) {
            const fieldElement = button.closest('tr, .form-group');
            const dropdown = fieldElement.querySelector('select[name^="field_name"]');

            if (dropdown && dropdown.value) {
                usedOptions.delete(dropdown.value);
                updateDropdownOptions();
            }

            fieldElement.remove();
        }

        function addSection() {
            const sectionsContainer = document.getElementById('sections-container');
            const newSectionIndex = sectionCounter++;
            const tempSectionId = `new-${newSectionIndex}`; // create unique ID

            const sectionHtml = `
        <div class="section-group rounded" id="section_${newSectionIndex}">
            <input type="hidden" name="section_id[]" value="${tempSectionId}">
            <input type="hidden" name="sort_order[]" value="${newSectionIndex}">

            <div class="form-group">
                <label class="form-label">Section Title:</label>
                <input type="text" name="section_title[]" required class="form-control">
            </div>

            <div id="fields-container_${newSectionIndex}"></div>

            <button type="button" class="btn btn-primary btn-add-field btn-sm" 
                    onclick="addField(${newSectionIndex}, '${tempSectionId}')">
                Add New Field
            </button>

            <button type="button" class="btn btn-danger btn-remove-section btn-sm" 
                    onclick="removeSection(this)">
                Remove Section
            </button>
        </div>
    `;

            sectionsContainer.insertAdjacentHTML('beforeend', sectionHtml);
        }


        function removeSection(button, sectionId = null) {
            const sectionGroup = button.closest('.section-group');

            if (sectionId) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_sections[]';
                input.value = sectionId;
                document.getElementById('deleted-sections-container').appendChild(input);
            }

            sectionGroup.remove();
        }


        function moveSection(index, direction) {
            const section = document.getElementById(`section_${index}`);
            const container = section.parentNode;

            if (direction === 1 && section.nextElementSibling) {
                container.insertBefore(section.nextElementSibling, section);
            } else if (direction === -1 && section.previousElementSibling) {
                container.insertBefore(section, section.previousElementSibling);
            }

            // Update sort_order values
            const sections = container.querySelectorAll('.section-group');
            sections.forEach((section, i) => {
                section.querySelector('input[name="sort_order[]"]').value = i;
            });
        }

        function removeField(button) {
            const fieldElement = button.closest('tr, .form-group');
            fieldElement.remove();
        }
    </script>

    <!-- <style>
            .section-group {
                margin-bottom: 2rem;
                padding: 1.5rem;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                background-color: #f8f9fa;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .checkbox-container {
                display: flex;
                gap: 1rem;
                margin-top: 0.5rem;
            }

            .btn-group {
                display: inline-flex;
                margin-left: 0.5rem;
            }

            .form-group label {
                /* font-weight: bold !important; */
                color: #000 !important;
                /* Pure black */
            }

            table {
                width: 100%;
                margin-bottom: 1rem;
            }

            table th,
            table td {
                padding: 0.75rem;
                vertical-align: top;
            }

            .form-check .form-check-input {
                width: 1%;
                height: 1.5em;
                margin-top: 0.3em;
                margin-right: 0.5em;
                float: left;
                margin-left: -1.813em;
            }
        </style> -->
@endsection
