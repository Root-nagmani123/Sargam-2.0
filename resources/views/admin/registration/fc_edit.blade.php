@extends('admin.layouts.master')

@section('title', 'Edit Form Fields - Sargam | Lal Bahadur')
@section('content')
    <div class="container">
        {{-- <h1>Edit Form Fields</h1> --}}

        <form method="POST" action="{{ route('forms.fc_update') }}" method="POST">
            @csrf

            <input type="hidden" name="form_id" value="{{ $form_id }}">

            <div id="sections-container">
                @foreach ($sections as $index => $section)
                    <div class="section-group" id="section_{{ $index }}">
                        <input type="hidden" name="section_id[]" value="{{ $section->id }}">
                        <input type="hidden" name="sort_order[]" value="{{ $index }}">

                        <div class="form-group">
                            <label>Section Title:</label>
                            <input type="text" name="section_title[]" value="{{ $section->section_title }}" required>
                        </div>

                        @php
                            $section_fields = $fields->where('section_id', $section->id);
                            $has_table_format = $section_fields->contains('format', 'table');
                        @endphp

                        <div id="fields-container_{{ $index }}">
                            @if ($has_table_format)
                                <table class="table table-bordered">
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
                                        @foreach ($section_fields as $field)
                                            @if ($field->format === 'table')
                                                <tr>
                                                    <input type="hidden" name="field_id[]" value="{{ $field->id }}">
                                                    <input type="hidden" name="field_section[]"
                                                        value="{{ $section->id }}">

                                                    <td>
                                                        <input type="text" name="field_label[]"
                                                            value="{{ $field->formlabel }}" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="field_name[]"
                                                            value="{{ $field->field_title ?? $field->formname }}" required>
                                                    </td>
                                                    <td>
                                                        <select name="field_type[]" class="form-control">
                                                            @foreach (['Label', 'Text', 'Date', 'Email', 'Textarea', 'Checkbox', 'Radio Button', 'Select Box', 'File Upload', 'View/Download'] as $type)
                                                                <option value="{{ $type }}"
                                                                    {{ ($field->field_type ?? $field->formtype) === $type ? 'selected' : '' }}>
                                                                    {{ $type }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="field_options[]"
                                                            value="{{ $field->field_options ?? ($field->fieldoption ?? $field->field_url) }}">
                                                    </td>
                                                    <td>
                                                        <input type="checkbox" name="is_required[]"
                                                            {{ $field->required ? 'checked' : '' }}>
                                                    </td>
                                                    <td>
                                                        <input type="checkbox" name="delete_fields[]"
                                                            value="{{ $field->id }}">
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                @foreach ($section_fields as $field)
                                    @if ($field->format !== 'table')
                                        <div class="form-group border p-3 mb-4 rounded shadow-sm bg-light">
                                            <input type="hidden" name="field_id[]" value="{{ $field->id }}">
                                            <input type="hidden" name="field_section[]" value="{{ $section->id }}">

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label>Label:</label>
                                                    <input type="text" name="field_label[]" class="form-control"
                                                        value="{{ $field->formlabel }}" required>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="field_name_{{ $loop->index }}">Field Name:</label>
                                                    <select class="form-control" name="field_name[]" required>
                                                        @foreach ($columns as $column)
                                                            <option value="{{ $column }}"
                                                                {{ $field->formname == $column ? 'selected' : '' }}>
                                                                {{-- {{ ucfirst(str_replace('_', ' ', $column)) }} --}}
                                                                {{ $column }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label>Type:</label>
                                                    <select name="field_type[]" class="form-control">
                                                        @foreach (['text', 'dropdown', 'radio', 'checkbox', 'date', 'file','textarea','email','number','time'] as $type)
                                                            <option value="{{ $type }}"
                                                                {{ $field->formtype === $type ? 'selected' : '' }}>
                                                                {{ ucfirst($type) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label>Options (comma separated):</label>
                                                    <input type="text" name="field_options[]" class="form-control"
                                                        value="{{ $field->fieldoption }}">
                                                </div>
                                            </div>

                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input type="checkbox" name="is_required[]" class="form-check-input"
                                                            id="required_{{ $loop->index }}"
                                                            {{ $field->required ? 'checked' : '' }}>
                                                        <label class="form-check-label"
                                                            for="required_{{ $loop->index }}">Required</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input type="checkbox" name="delete_fields[]"
                                                            class="form-check-input" id="delete_{{ $loop->index }}"
                                                            value="{{ $field->id }}">
                                                        <label class="form-check-label text-danger"
                                                            for="delete_{{ $loop->index }}">Delete</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>

                        <button type="button" class="btn btn-primary btn-add-field"
                            onclick="addField({{ $index }}, {{ $section->id }})">
                            Add New Field
                        </button>
                        <button type="button" class="btn btn-danger btn-remove-section" onclick="removeSection(this)">
                            Remove Section
                        </button>
                        <div class="btn-group">
                            {{-- <button type="button" class="btn btn-secondary btn-move-up me-2"
                                onclick="moveSection({{ $index }}, -1)">
                                Move Up
                            </button> --}}
                            {{-- <button type="button" class="btn btn-secondary btn-move-down"
                                onclick="moveSection({{ $index }}, 1)">
                                Move Down
                            </button> --}}
                        </div>

                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <button type="button" class="btn btn-success btn-add-section" onclick="addSection()">
                    Add New Section
                </button>
            </div>

            <div class="form-group mt-4 text-center">
                <button type="submit" class="btn btn-primary me-2">Save Changes</button>
                <a href="{{ route('forms.fc_edit', $form_id) }}" class="btn btn-secondary">Cancel</a>
            </div>


        </form>
    </div>

    <script>
        let sectionCounter = {{ count($sections) }};
        let fieldCounter = {{ count($fields) }};

        function addField(sectionIndex, sectionId) {
            const fieldsContainer = document.querySelector(`#fields-container_${sectionIndex}`);
            const isTableFormat = fieldsContainer.querySelector('table') !== null;
            const newFieldIndex = fieldsContainer.querySelectorAll('tr, .form-group').length;

            let fieldHtml;

            if (isTableFormat) {
                fieldHtml = `
                <tr>
                    <input type="hidden" name="field_id[]" value="new">
                    <input type="hidden" name="field_section[]" value="${sectionId}">
                    <td><input type="text" name="field_label[]" required></td>
                    <td><input type="text" name="field_name[]" required></td>
                    <td>
                        <select name="field_type[]" class="form-control">
                            @foreach (['Label', 'Text', 'Date', 'Email', 'Textarea', 'Checkbox', 'Radio Button', 'Select Box', 'File Upload', 'View/Download'] as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="text" name="field_options[]"></td>
                    <td><input type="checkbox" name="is_required[]"></td>
                    <td><input type="checkbox" name="delete_fields[]" value="new"></td>
                </tr>
            `;
            } else {
                // For non-table format, use the select dropdown for field names
                let optionsHtml = '<option value="" selected disabled>Choose an option</option>';
                @foreach ($columns as $column)
                    optionsHtml +=
                        `<option value="{{ $column }}">{{ ucfirst(str_replace('_', ' ', $column)) }}</option>`;
                @endforeach

                fieldHtml = `
        <div class="form-group border p-3 mb-4 rounded shadow-sm bg-light">
            <input type="hidden" name="field_id[]" value="new">
            <input type="hidden" name="field_section[]" value="${sectionId}">

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Label:</label>
                    <input type="text" name="field_label[]" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label>Name:</label>
                    <select class="form-control" name="field_name[]" required>
                        ${optionsHtml}
                    </select>
                </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label>Type:</label>
            <select name="field_type[]" class="form-control">
                @foreach (['text', 'dropdown', 'radio', 'checkbox', 'date', 'file','textarea','email','number','time'] as $type)
                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label>Options (comma separated):</label>
            <input type="text" name="field_options[]" class="form-control">
        </div>
    </div>

    <div class="row align-items-center">
        <div class="col-md-6">
            <div class="form-check">
                <input type="checkbox" name="is_required[]" class="form-check-input" id="required_${newFieldIndex}">
                <label class="form-check-label" for="required_${newFieldIndex}">Required</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check">
                <input type="checkbox" name="delete_fields[]" class="form-check-input" value="new" id="delete_${newFieldIndex}">
                <label class="form-check-label text-danger" for="delete_${newFieldIndex}">Delete</label>
            </div>
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

            fieldCounter++;
        }

        function addSection() {
            const sectionsContainer = document.getElementById('sections-container');
            const newSectionIndex = sectionCounter++;

            const sectionHtml = `
            <div class="section-group" id="section_${newSectionIndex}">
                <input type="hidden" name="section_id[]" value="new">
                <input type="hidden" name="sort_order[]" value="${newSectionIndex}">

                <div class="form-group">
                    <label>Section Title:</label>
                    <input type="text" name="section_title[]" required>
                </div>

                <div id="fields-container_${newSectionIndex}"></div>

                <button type="button" class="btn btn-primary btn-add-field" 
                        onclick="addField(${newSectionIndex}, 'new')">
                    Add New Field
                </button>
                <button type="button" class="btn btn-danger btn-remove-section" 
                        onclick="removeSection(this)">
                    Remove Section
                </button>
           <!--      <div class="btn-group">
                    <button type="button" class="btn btn-secondary btn-move-up" 
                            onclick="moveSection(${newSectionIndex}, -1)">
                        Move Up
                    </button>
                    <button type="button" class="btn btn-secondary btn-move-down" 
                            onclick="moveSection(${newSectionIndex}, 1)">
                        Move Down
                    </button>
                </div>-->
            </div>
        `;

            sectionsContainer.insertAdjacentHTML('beforeend', sectionHtml);
        }

        function removeSection(button) {
            const sectionGroup = button.closest('.section-group');
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

    <style>
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
    </style>
@endsection
