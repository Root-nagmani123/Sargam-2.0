@extends('admin.layouts.master')

@section('title', 'Create New Form - Sargam | Lal Bahadur')
@section('css')
    <link href="{{ asset('css/forms.css') }}" rel="stylesheet">
    <style>
        .form-builder-page .section-entry .card-header {
            border-bottom: 1px solid var(--bs-border-color-translucent);
        }
        .form-builder-page .field-entry .card-body,
        .form-builder-page .table-entry .card-body {
            padding: 1rem 1.25rem;
        }
        @media (min-width: 576px) {
            .form-builder-page .field-toolbar,
            .form-builder-page .section-toolbar {
                flex-wrap: nowrap;
            }
        }
    </style>
@endsection
@section('setup_content')
    <div class="container-fluid px-2 px-sm-3 px-md-4 pb-4 pb-lg-5 form-builder-page">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-body-secondary bg-opacity-50 border-0 py-3 px-4">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <div class="flex-shrink-0 rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width:2.75rem;height:2.75rem;" aria-hidden="true">
                            <i class="bi bi-ui-checks-grid fs-4"></i>
                        </div>
                        <div class="min-w-0">
                            <h1 class="h4 fw-semibold text-body mb-1">Create new form</h1>
                            <p class="small text-body-secondary mb-0">Add sections, fields, and tables. All names and POST fields are unchanged.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                @if (session('success') || session('error'))
                    <div class="alert alert-{{ session('success') ? 'success' : 'danger' }} alert-dismissible fade show rounded-3 border-0 shadow-sm mb-4"
                        role="alert">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-{{ session('success') ? 'check-circle-fill' : 'exclamation-triangle-fill' }} flex-shrink-0 align-self-start"></i>
                            <span>{{ session('success') ?? session('error') }}</span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('forms.save', ['formid' => $formid]) }}">
                    @csrf

                    <div id="sections-container" class="vstack gap-4">
                        <!-- Sections will be added here -->
                    </div>

                    <div class="d-flex justify-content-center my-4">
                        <button type="button" class="btn btn-success rounded-3 px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2" onclick="addSection()">
                            <i class="bi bi-plus-circle"></i>
                            <span>Add new section</span>
                        </button>
                    </div>

                    <hr class="text-secondary border-opacity-25 my-4">

                    <div class="d-flex flex-column flex-sm-row flex-wrap align-items-stretch align-items-sm-center justify-content-center gap-2 gap-sm-3 my-2">
                        <button type="submit" class="btn btn-primary rounded-3 px-4 py-2 shadow-sm d-inline-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-save" aria-hidden="true"></i>
                            <span>Save form</span>
                        </button>
                        <a href="{{ route('forms.index') }}" class="btn btn-outline-secondary rounded-3 px-4 py-2 d-inline-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                            <span>Cancel</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        const submissionColumns = @json($submissionColumns);

        function addSection() {
            const container = document.getElementById("sections-container");
            const index = container.children.length;
            const sectionHtml = `
    <div class="section-entry card border-0 shadow-sm rounded-4 overflow-hidden" id="section_${index}">
        <div class="card-header bg-primary bg-opacity-10 border-0 py-3 px-3 px-md-4 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2">
            <h2 class="h5 fw-semibold text-body mb-0 d-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-primary">Section ${index + 1}</span>
            </h2>
        </div>
        <div class="card-body p-3 p-md-4">
        <div class="mb-3">
            <label class="form-label fw-semibold small text-uppercase text-body-secondary letter-spacing-tight" for="section_title_${index}">Section title</label>
            <input type="text" class="form-control shadow-sm rounded-3" id="section_title_${index}" name="section_title[]" required placeholder="e.g. Personal details">
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold small text-uppercase text-body-secondary letter-spacing-tight" for="section_layout_${index}">Section layout</label>
            <select class="form-select shadow-sm rounded-3" id="section_layout_${index}" name="section_layout[]">
                <option value="col-12">1 column</option>
                <option value="col-6">2 columns</option>
                <option value="col-4">3 columns</option>
                <option value="col-3">4 columns</option>
                <option value="col-2">6 columns</option>
            </select>
        </div>

        <div id="elements-container_${index}" class="vstack gap-3">
        </div>
        <div class="section-toolbar d-flex flex-wrap gap-2 justify-content-center justify-content-md-start mt-4 pt-3 border-top border-secondary border-opacity-10">
            <button type="button" class="btn btn-sm btn-primary rounded-3 px-3 d-inline-flex align-items-center gap-1" onclick="addField(${index})"><i class="bi bi-input-cursor-text"></i> Add field</button>
            <button type="button" class="btn btn-sm btn-info text-white rounded-3 px-3 d-inline-flex align-items-center gap-1" onclick="addTable(${index})"><i class="bi bi-table"></i> Add table</button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-3 px-3 d-inline-flex align-items-center gap-1" onclick="removeSection(${index})"><i class="bi bi-trash"></i> Remove section</button>
        </div>
        </div>
    </div>
`;
            container.insertAdjacentHTML("beforeend", sectionHtml);
        }

        const sectionFieldCount = {};

        function addField(sectionIndex) {
            const container = document.getElementById(`elements-container_${sectionIndex}`);
            if (!sectionFieldCount[sectionIndex]) {
                sectionFieldCount[sectionIndex] = 0;
            }
            const index = sectionFieldCount[sectionIndex]++;
            const usedColumns = Array.from(document.querySelectorAll('select[name="field_name[]"]'))
                .map(select => select.value)
                .filter(val => val !== "");

            let columnOptions = '<option value="">Select column</option>';
            submissionColumns.forEach(col => {
                if (!usedColumns.includes(col)) {
                    columnOptions += `<option value="${col}">${col}</option>`;
                }
            });

            const fieldHtml = `
        <div class="field-entry card border border-secondary border-opacity-25 rounded-4 shadow-sm" id="field_${sectionIndex}_${index}">
            <div class="card-header bg-body-secondary bg-opacity-50 border-0 py-2 px-3 d-flex align-items-center justify-content-between">
                <h3 class="h6 fw-semibold text-body mb-0">Field ${index + 1}</h3>
            </div>
            <div class="card-body p-3">
            <input type="hidden" name="field_section[]" value="${sectionIndex}">
            <div class="mb-3">
                <label class="form-label fw-semibold small" for="field_name_${sectionIndex}_${index}">Field name</label>
                <select class="form-select select2 rounded-3" id="field_name_${sectionIndex}_${index}" name="field_name[]" required>
                    ${columnOptions}
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small" for="field_type_${sectionIndex}_${index}">Field type</label>
                <select class="form-select rounded-3" id="field_type_${sectionIndex}_${index}" name="field_type[]">
                    <option value="text">Text</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="email">Email</option>
                    <option value="textarea">Textarea</option>
                    <option value="radio">Radio</option>
                    <option value="dropdown">Dropdown</option>
                    <option value="date">Date</option>
                    <option value="file">File</option>
                    <option value="view_download">View/Download</option>
                    <option value="number">Number</option>
                    <option value="time">Time</option>
                </select>
            </div>
    <div class="mb-3 layout-options" id="layout_group_${sectionIndex}_${index}" style="display:none;">
        <label class="form-label fw-semibold small" for="field_layout_${sectionIndex}_${index}">Display layout</label>
        <select class="form-select rounded-3" id="field_layout_${sectionIndex}_${index}" name="field_layout[]">
            <option value="inline">Horizontal</option>
            <option value="block">Vertical</option>
        </select>
    </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small" for="field_label_${sectionIndex}_${index}">Field label</label>
                <input type="text" class="form-control rounded-3" id="field_label_${sectionIndex}_${index}" name="field_label[]" required placeholder="Label shown to users">
            </div>
           <div class="mb-3 field-options" id="field_options_group_${sectionIndex}_${index}" style="display:none;">
                <label class="form-label fw-semibold small" for="field_options_${sectionIndex}_${index}">Options (comma separated)</label>
                <input type="text" class="form-control rounded-3" id="field_options_${sectionIndex}_${index}" name="field_options[]" placeholder="Option A, Option B">
            </div>
          <div class="mb-3">
    <span class="form-label fw-semibold small d-block mb-2">Required</span>
    <input type="hidden" name="is_required[${sectionIndex}_${index}]" value="0">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" name="is_required[${sectionIndex}_${index}]" value="1" id="is_required_${sectionIndex}_${index}">
        <label class="form-check-label text-body-secondary small" for="is_required_${sectionIndex}_${index}">Mark this field as required</label>
    </div>
</div>

          <!--  <div class="form-group mb-3">
                <label for="field_layout_${sectionIndex}_${index}">Layout:</label>
                <select class="form-control" id="field_layout_${sectionIndex}_${index}" name="field_layout[]">
                    <option value="vertical">Vertical</option>
                    <option value="horizontal">Horizontal</option>
                </select> -->
            <div class="field-toolbar d-flex flex-wrap gap-2 pt-2 border-top border-secondary border-opacity-10">
            <button type="button" class="btn btn-sm btn-outline-danger rounded-3 d-inline-flex align-items-center gap-1" onclick="removeField(${sectionIndex}, ${index})"><i class="bi bi-trash"></i> Remove field</button>
            </div>
            </div>
        </div>
    `;

            container.insertAdjacentHTML("beforeend", fieldHtml);

            const fieldTypeSelect = document.getElementById(`field_type_${sectionIndex}_${index}`);
            const optionsGroup = document.getElementById(`field_options_group_${sectionIndex}_${index}`);
            const layoutGroup = document.getElementById(`layout_group_${sectionIndex}_${index}`);

            function toggleOptionsField() {
                const val = fieldTypeSelect.value;
                if (val === 'radio' || val === 'checkbox' || val === 'dropdown') {
                    optionsGroup.style.display = 'block';
                } else {
                    optionsGroup.style.display = 'none';
                }
                if (val === 'radio' || val === 'checkbox') {
                    layoutGroup.style.display = 'block';
                } else {
                    layoutGroup.style.display = 'none';
                }
            }

            toggleOptionsField();
            fieldTypeSelect.addEventListener('change', toggleOptionsField);

            document.getElementById(`field_name_${sectionIndex}_${index}`).addEventListener('change',
                updateFieldNameOptions);

                $('.select2').select2()

        }

        function updateFieldNameOptions() {
            const allSelects = document.querySelectorAll('select[name="field_name[]"]');

            const selectedValues = Array.from(allSelects).map(select => select.value).filter(v => v !== '');

            allSelects.forEach(select => {
                const currentValue = select.value;
                while (select.firstChild) {
                    select.removeChild(select.firstChild);
                }

                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Select column';
                select.appendChild(defaultOption);

                submissionColumns.forEach(col => {
                    if (col === currentValue || !selectedValues.includes(col)) {
                        const option = document.createElement('option');
                        option.value = col;
                        option.textContent = col;
                        select.appendChild(option);
                    }
                });

                if (currentValue !== '') {
                    select.value = currentValue;
                }
            });
        }

        function removeSection(sectionIndex) {
            const section = document.getElementById(`section_${sectionIndex}`);
            if (section) {
                section.remove();
            }
        }

        function removeField(sectionIndex, fieldIndex) {
            const field = document.getElementById(`field_${sectionIndex}_${fieldIndex}`);
            if (field) {
                field.remove();
            }
        }

        function addTable(sectionIndex) {
            const container = document.getElementById(`elements-container_${sectionIndex}`);
            const index = container.children.length;

            const tableHtml = `
                <div class="table-entry card border border-secondary border-opacity-25 rounded-4 shadow-sm" id="table_${sectionIndex}_${index}">
                    <div class="card-header bg-body-secondary bg-opacity-50 border-0 py-2 px-3 d-flex align-items-center justify-content-between">
                        <h3 class="h6 fw-semibold text-body mb-0">Table ${index + 1}</h3>
                    </div>
                    <div class="card-body p-3">
                    <input type="hidden" name="table_section[]" value="${sectionIndex}">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small" for="table_rows_${sectionIndex}_${index}">Number of rows</label>
                            <input type="number" class="form-control rounded-3" id="table_rows_${sectionIndex}_${index}" name="table_rows[]" required min="1" placeholder="e.g. 3">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small" for="table_columns_${sectionIndex}_${index}">Number of columns</label>
                            <input type="number" class="form-control rounded-3" id="table_columns_${sectionIndex}_${index}" name="table_columns[]" required min="1" placeholder="e.g. 2">
                        </div>
                    </div>
                    <div id="table-container${sectionIndex}_${index}" class="table-responsive rounded-3 border border-secondary border-opacity-25 mb-3"></div>
                    <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-primary rounded-3 d-inline-flex align-items-center gap-1" onclick="generateTable(${sectionIndex}, ${index})"><i class="bi bi-grid-3x3-gap"></i> Generate table</button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-3 d-inline-flex align-items-center gap-1" onclick="removeTable(${sectionIndex}, ${index})"><i class="bi bi-trash"></i> Remove table</button>
                    </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML("beforeend", tableHtml);
        }

        function generateTable(sectionIndex, tableIndex) {
            const rows = document.getElementById(`table_rows_${sectionIndex}_${tableIndex}`).value;
            const cols = document.getElementById(`table_columns_${sectionIndex}_${tableIndex}`).value;
            const tableContainer = document.getElementById(`table-container${sectionIndex}_${tableIndex}`);

            if (!rows || !cols) {
                tableContainer.innerHTML =
                    '<div class="alert alert-warning border-0 rounded-3 shadow-sm mb-0" role="alert"><i class="bi bi-exclamation-triangle me-2"></i>Please specify both number of rows and columns.</div>';
                return;
            }

            let table = '<table class="table table-bordered table-striped table-hover align-middle mb-0">';

            table += '<thead class="table-light"><tr>';
            for (let j = 0; j < cols; j++) {
                table +=
                    `<th class="small"><input type='text' placeholder='Column ${j + 1} heading' class='form-control form-control-sm rounded-2' name="table_column_heading_${sectionIndex}_${tableIndex}[]" required></th>`;
            }
            table += '</tr></thead>';

            for (let i = 0; i < rows; i++) {
                table += '<tr>';
                for (let j = 0; j < cols; j++) {
                    table += `<td class="small">
                <select class="form-select form-select-sm rounded-2" onchange="fieldtype(this.value, ${j + 1}, ${i + 1}, ${sectionIndex}, ${tableIndex})" name="table_row${i}_${sectionIndex}_${tableIndex}[]">
                    <option value="">Select type</option>
                    <option value="Label">Label</option>
                    <option value="Text">Text</option>
                    <option value="Date">Date</option>
                    <option value="Email">Email</option>
                    <option value="Textarea">Textarea</option>
                    <option value="Checkbox">Checkbox</option>
                    <option value="Radio Button">Radio Button</option>
                    <option value="Select Box">Select Box</option>
                    <option value="File Upload">File Upload</option>
                    <option value="View/Download">View/Download</option>
                </select>
                <div id="type_label${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options mt-2" style='display:none'>
                    <input type="text" class="form-control form-control-sm rounded-2" name="table_title${i}_${sectionIndex}_${tableIndex}[]" placeholder='Title'>
                </div>
                <div id="type_url${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options mt-2" style='display:none'>
                    <input type="text" class="form-control form-control-sm rounded-2" name="table_url${i}_${sectionIndex}_${tableIndex}[]" placeholder='URL'>
                </div>
                <div id="option${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options mt-2" style='display:none'>
                    <input type="text" class="form-control form-control-sm rounded-2" name="table_options${i}_${sectionIndex}_${tableIndex}[]" placeholder='Options (comma separated)'>
                </div>
                <div id="checkbox_options${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options mt-2" style='display:none'>
                    <input type="text" class="form-control form-control-sm rounded-2" name="checkbox_options${i}_${sectionIndex}_${tableIndex}[]" placeholder='Checkbox options (comma separated)'>
                </div>
                <div id="radio_options${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options mt-2" style='display:none'>
                    <input type="text" class="form-control form-control-sm rounded-2" name="radio_options${i}_${sectionIndex}_${tableIndex}[]" placeholder='Radio options (comma separated)'>
                </div>
            </td>`;
                }
                table += '</tr>';
            }
            table += '</table>';

            tableContainer.innerHTML = table;
        }

        function removeTable(sectionIndex, tableIndex) {
            const table = document.getElementById(`table_${sectionIndex}_${tableIndex}`);
            if (table) {
                table.remove();
            }
        }

        function fieldtype(value, col, row, sectionIndex, tableIndex) {
            const typeLabel = document.getElementById(`type_label${col}_${row}_${sectionIndex}_${tableIndex}`);
            const typeUrl = document.getElementById(`type_url${col}_${row}_${sectionIndex}_${tableIndex}`);
            const option = document.getElementById(`option${col}_${row}_${sectionIndex}_${tableIndex}`);
            const checkboxOptions = document.getElementById(`checkbox_options${col}_${row}_${sectionIndex}_${tableIndex}`);
            const radioOptions = document.getElementById(`radio_options${col}_${row}_${sectionIndex}_${tableIndex}`);

            typeLabel.style.display = value === 'Label' || value === 'View/Download' ? 'block' : 'none';
            typeUrl.style.display = value === 'View/Download' ? 'block' : 'none';
            option.style.display = value === 'Select Box' || value === 'Radio Button' || value === 'Checkbox' ? 'block' :
                'none';
            checkboxOptions.style.display = value === 'Checkbox' ? 'block' : 'none';
            radioOptions.style.display = value === 'Radio Button' ? 'block' : 'none';
        }
    </script>

@endsection
