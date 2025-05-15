<!-- resources/views/forms/show.blade.php -->
@extends('admin.layouts.master')
@section('title', 'Create Form - Sargam | Lal Bahadur')
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row mt-3">
                <div class="col-md-3">
                    <!-- Nav tabs -->
                    <div class="nav flex-column nav-pills mb-4 mb-md-0" id="v-pills-tab" role="tablist"
                        aria-orientation="vertical">
                        @if($form)
                        <a class="nav-link active" id="{{ route('forms.show', $form->id) }}-tab" data-bs-toggle="pill"
                            href="#{{ route('forms.show', $form->id) }}" role="tab"
                            aria-controls="{{ route('forms.show', $form->id) }}" aria-selected="true">
                            {{ $form->name }}
                        </a>
                        {{-- <a href="{{ route('forms.download', ['formId' => $form->id, 'userId' => auth()->id()]) }}"
                        class="btn"
                        style="text-align: left;"> --}}
                        {{-- Download PDF --}}
                        </a>
                        @else
                        <p>No form found with the specified ID.</p>
                        @endif
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="{{ route('forms.show', $form->id) }}" role="tabpanel"
                            aria-labelledby="{{ route('forms.show', $form->id) }}-tab">
                            @if($form->description)
                            <div class="description">
                                {{ $form->description }}
                            </div>
                            @endif
                            <form method="POST" action="{{ route('forms.submit', $form->id) }}"
                                enctype="multipart/form-data">
                                @csrf

                                @foreach($sections as $section)
                                <div class="section-container">
                                    <div class="section-title">{{ $section->section_title }}</div>

                                    @if(isset($fieldsBySection[$section->id]))
                                    @php
                                    $maxCol = 0;
                                    if (isset($fieldsBySection[$section->id])) {
                                    foreach ($fieldsBySection[$section->id] as $row) {
                                    $maxCol = max($maxCol, max(array_keys($row)));
                                    }
                                    }
                                    @endphp

                                    <table class="dynamic-table">
                                        <thead>
                                            <tr>
                                                @for($i = 0; $i <= $maxCol; $i++) @if(isset($headersBySection[$section->
                                                    id][$i]))
                                                    <th>{{ $headersBySection[$section->id][$i] }}</th>
                                                    <input type="hidden" name="header_{{ $section->id }}_{{ $i }}"
                                                        value="{{ $headersBySection[$section->id][$i] }}">
                                                    @else
                                                    <th></th>
                                                    @endif
                                                    @endfor
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(isset($fieldsBySection[$section->id]))
                                            @foreach($fieldsBySection[$section->id] as $rowIndex => $row)
                                            <tr id="row-{{ $rowIndex }}">
                                                @for($i = 0; $i <= $maxCol; $i++) <td>
                                                    @if(isset($row[$i]))
                                                    @include('admin.forms.field-types', [
                                                    'field' => $row[$i],
                                                    'value' => $submissions[$row[$i]->formname]->fieldvalue ?? null,
                                                    'name' => "table_{$section->id}_{$rowIndex}_{$i}",
                                                    // 'sectionId' => $section->id
                                                    ])
                                                    @else
                                                    &nbsp;
                                                    @endif
                                                    </td>
                                                    @endfor
                                            </tr>
                                            @endforeach
                                            @endif
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="{{ $maxCol + 1 }}" style="text-align: left;">
                                                    <button class="replicate-row btn btn-success"
                                                        onclick="replicateRow(event)"
                                                        style="cursor: pointer; border: none; background: none; padding: 0;">
                                                        <img src="{{ asset('images/increase.png') }}" alt="Increase"
                                                            style="width: 15px; height: 15px;">
                                                    </button>

                                                    <button class="remove-row btn btn-danger" onclick="removeRow(event)"
                                                        style="cursor: pointer; border: none; background: none; padding: 0;">
                                                        <img src="{{ asset('images/decrease.png') }}" alt="Decrease"
                                                            style="width: 15px; height: 15px;">
                                                    </button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    @endif

                                    @if(isset($gridFields[$section->id]))
                                    <div class="row">
                                        @foreach($gridFields[$section->id] as $field)
                                        <div class="col-6">
                                            <div class="mb-3">
                                                @include('admin.forms.field-types', [
                                                'field' => $field,
                                                'value' => $submissions[$field->formname]->fieldvalue ?? null,
                                                'name' => "field_{$field->formname}"
                                                ])
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                                <div class="form-actions border-top">
                                    <div class="card-body float-end">
                                        <button type="submit" class="btn btn-primary">
                                            Submit
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

    @push('scripts')
    <script>
    // Same JavaScript functions as before
    function previewImage(event, input) {
        const fileList = input.files;
        const previewContainer = document.getElementById(`image-preview-${input.id}`);

        previewContainer.innerHTML = '';

        if (fileList.length > 0) {
            Array.from(fileList).forEach(file => {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.style.maxWidth = '100px';
                img.style.margin = '5px';
                img.style.display = 'inline-block';
                previewContainer.appendChild(img);
            });
        }
    }

    function replicateRow(event) {
        event.preventDefault();

        var table = event.target.closest('table').getElementsByTagName('tbody')[0];

        if (table.rows.length === 0) {
            addNewRow(table, 0);
        } else {
            var lastRow = table.rows[table.rows.length - 1];
            var newRow = lastRow.cloneNode(true);

            const isDuplicate = checkDropdownDuplicates(newRow);

            if (isDuplicate) {
                resetRowInputs(lastRow);
            } else {
                var newRowIndex = table.rows.length;
                newRow.id = 'row-' + newRowIndex;

                var inputs = newRow.querySelectorAll('input, select, textarea');
                inputs.forEach(function(input) {
                    var namePattern = input.name.match(/^table_(\d+)_\d+_(\d+)$/);
                    if (namePattern) {
                        var sectionId = namePattern[1];
                        var colIndex = namePattern[2];
                        var newName = `table_${sectionId}_${newRowIndex}_${colIndex}`;
                        input.name = newName;
                        input.id = newName;
                    }
                });

                resetRowInputs(newRow);
                table.appendChild(newRow);
            }
        }
    }


    function resetRowInputs(row) {
        const inputs = row.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else {
                input.value = '';
            }
        });
    }

    function checkDropdownDuplicates(row) {
        const dropdowns = document.querySelectorAll('.dynamic-table tbody tr td:nth-child(1) select');
        const selectedValues = [];
        let isDuplicate = false;

        dropdowns.forEach(dropdown => {
            const selectedValue = dropdown.value;
            const selectedText = dropdown.options[dropdown.selectedIndex].text;

            if (selectedValue && selectedValues.includes(selectedValue)) {
                alert(selectedValue + ' [' + selectedText + '] is already entered');
                isDuplicate = true;
            } else {
                selectedValues.push(selectedValue);
            }
        });

        return isDuplicate;
    }

    function removeRow(event) {
        event.preventDefault();
        var table = event.target.closest('table').getElementsByTagName('tbody')[0];

        if (table.rows.length === 1) {
            // alert('You cannot remove the last row!');
        } else if (table.rows.length > 0) {
            table.deleteRow(table.rows.length - 1);
        }
    }

    function addNewRow(table, rowIndex) {
        var newRow = table.insertRow(rowIndex);
        // Add cells and inputs as needed
    }
    </script>
    @endpush