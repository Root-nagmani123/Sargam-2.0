@extends('admin.layouts.master')

@section('title', 'Create New Form - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
               @if (session('success') || session('error'))
                    <div class="container mt-3">
                        <div class="alert alert-{{ session('success') ? 'success' : 'danger' }} alert-dismissible fade show"
                            role="alert">
                            {{ session('success') ?? session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                @endif
            <h3 class="my-4 text-center">Create New Form</h3>
            <hr>
            <form method="POST" action="{{ route('forms.save', ['formid' => $formid]) }}">
                @csrf

                <!-- Sections Container -->
                <div id="sections-container">
                    <!-- Sections will be added here -->
                </div>

                <!-- Add Section Button -->
                <div class="text-center my-3">
                    <button type="button" class="btn btn-success" onclick="addSection()">
                        <i class="bi bi-plus-circle"></i> Add New Section
                    </button>
                </div>

                <hr>

                <!-- Submit and Cancel Button -->
                <div class="text-center my-4 gap-3">
                    <button type="submit" class="btn btn-primary">
                        Save Form
                    </button>
                    <a href="{{ route('forms.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
const submissionColumns = @json($submissionColumns);

function addSection() {
    const container = document.getElementById("sections-container");
    const index = container.children.length;

    const sectionHtml = `
                <div class="section-entry border p-4 rounded shadow-sm my-4" id="section_${index}">
                    <h3 class="mb-3">Section ${index + 1}</h3>
                    <div class="form-group mb-3">
                        <label for="section_title_${index}">Section Title:</label>
                        <input type="text" class="form-control" id="section_title_${index}" name="section_title[]" required>
                    </div>
                    <div id="fields-container_${index}">
                        <!-- Fields for this section will be added here -->
                    </div>
                    <div id="tables-container_${index}">
                        <!-- Tables for this section will be added here -->
                    </div>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-primary" onclick="addField(${index})">Add Field</button>
                        <button type="button" class="btn btn-info" onclick="addTable(${index})">Add Table</button>
                        <button type="button" class="btn btn-danger" onclick="removeSection(${index})">Remove Section</button>
                    </div>
                    <hr>
                </div>
            `;
    container.insertAdjacentHTML("beforeend", sectionHtml);
}

function addField(sectionIndex) {
    const container = document.getElementById(`fields-container_${sectionIndex}`);
    const index = container.children.length;

    let columnOptions = '<option value="">Select Column</option>';
    submissionColumns.forEach(col => {
        columnOptions += `<option value="${col}">${col}</option>`;
    });

    const fieldHtml = `
                <div class="field-entry border p-4 rounded shadow-sm my-3" id="field_${sectionIndex}_${index}">
                    <h4>Field ${index + 1}</h4>
                    <input type="hidden" name="field_section[]" value="${sectionIndex}">
                    <div class="form-group mb-3">
                        <label for="field_name_${sectionIndex}_${index}">Field Name:</label>
                        <select class="form-control" id="field_name_${sectionIndex}_${index}" name="field_name[]" required>
                            ${columnOptions}
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="field_type_${sectionIndex}_${index}">Field Type:</label>
                        <select class="form-control" id="field_type_${sectionIndex}_${index}" name="field_type[]">
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
                    <div class="form-group mb-3">
                        <label for="field_label_${sectionIndex}_${index}">Field Label:</label>
                        <input type="text" class="form-control" id="field_label_${sectionIndex}_${index}" name="field_label[]" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="field_options_${sectionIndex}_${index}">Options (comma separated):</label>
                        <input type="text" class="form-control" id="field_options_${sectionIndex}_${index}" name="field_options[]">
                    </div>
                    <div class="form-group mb-3">
                        <label>Required:</label>
                        <input type="checkbox" name="is_required[]" value="1">
                    </div>
                    <div class="form-group mb-3">
                        <label for="field_layout_${sectionIndex}_${index}">Layout:</label>
                        <select class="form-control" id="field_layout_${sectionIndex}_${index}" name="field_layout[]">
                            <option value="vertical">Vertical</option>
                            <option value="horizontal">Horizontal</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-danger" onclick="removeField(${sectionIndex}, ${index})">Remove Field</button>
                    <hr>
                </div>
            `;
    container.insertAdjacentHTML("beforeend", fieldHtml);
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
    const container = document.getElementById(`tables-container_${sectionIndex}`);
    const index = container.children.length;

    const tableHtml = `
                <div class="table-entry border p-4 rounded shadow-sm my-3" id="table_${sectionIndex}_${index}">
                    <h4>Table ${index + 1}</h4>
                    <input type="hidden" name="table_section[]" value="${sectionIndex}">
                    <div class="form-group mb-3">
                        <label for="table_rows_${sectionIndex}_${index}">Number of Rows:</label>
                        <input type="number" class="form-control" id="table_rows_${sectionIndex}_${index}" name="table_rows[]" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="table_columns_${sectionIndex}_${index}">Number of Columns:</label>
                        <input type="number" class="form-control" id="table_columns_${sectionIndex}_${index}" name="table_columns[]" required>
                    </div>
                    <div id="table-container${sectionIndex}_${index}"></div>
                    <button type="button" class="btn btn-primary" onclick="generateTable(${sectionIndex}, ${index})">Generate Table</button>
                    <button type="button" class="btn btn-danger" onclick="removeTable(${sectionIndex}, ${index})">Remove Table</button>
                    <hr>
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
            '<div class="alert alert-warning" role="alert">Please specify both number of rows and columns.</div>';
        return;
    }

    let table = '<table class="table table-bordered table-striped">';

    // Generate column headings
    table += '<thead class="table-secondary"><tr>';
    for (let j = 0; j < cols; j++) {
        table +=
            `<th><input type='text' placeholder='Column ${j + 1} Heading' class='form-control' name="table_column_heading_${sectionIndex}_${tableIndex}[]" required></th>`;
    }
    table += '</tr></thead>';

    // Generate table body
    for (let i = 0; i < rows; i++) {
        table += '<tr>';
        for (let j = 0; j < cols; j++) {
            table += `<td>
                <select class="form-control" onchange="fieldtype(this.value, ${j + 1}, ${i + 1}, ${sectionIndex}, ${tableIndex})" name="table_row${i}_${sectionIndex}_${tableIndex}[]">
                    <option value="">Select Type</option>
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
                <div id="type_label${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options" style='display:none'>
                    <hr>
                    <input type="text" class="form-control" name="table_title${i}_${sectionIndex}_${tableIndex}[]" placeholder='Title'>
                </div>
                <div id="type_url${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options" style='display:none'>
                    <hr>
                    <input type="text" class="form-control" name="table_url${i}_${sectionIndex}_${tableIndex}[]" placeholder='URL'>
                </div>
                <div id="option${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options" style='display:none'>
                    <hr>
                    <input type="text" class="form-control" name="table_options${i}_${sectionIndex}_${tableIndex}[]" placeholder='Options (comma separated)'>
                </div>
                <div id="checkbox_options${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options" style='display:none'>
                    <hr>
                    <input type="text" class="form-control" name="checkbox_options${i}_${sectionIndex}_${tableIndex}[]" placeholder='Checkbox Options (comma separated)'>
                </div>
                <div id="radio_options${j + 1}_${i + 1}_${sectionIndex}_${tableIndex}" class="field-options" style='display:none'>
                    <hr>
                    <input type="text" class="form-control" name="radio_options${i}_${sectionIndex}_${tableIndex}[]" placeholder='Radio Options (comma separated)'>
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