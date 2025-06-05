@extends('admin.layouts.master')
@section('title', 'Create Form - Sargam | Lal Bahadur')
@section('css')
    <link href="{{ asset('css/forms.css') }}" rel="stylesheet">
@endsection
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

                <div class="card-title">
                    <div class="row">
                        {{-- @if (!empty($data->logo1))
                            <div class="col-3">
                                <img src="{{ asset('storage/' . $data->logo1) }}" alt="logo1" class="img-fluid"
                                    style="height: 100px;">
                            </div>
                        @endif --}}

                        <div class="col-3">
                            <img src="{{ asset($data->logo1 ? 'storage/' . $data->logo1 : 'admin_assets/images/logos/logo.png') }}"
                                alt="logo1" class="img-fluid" style="height: 100px;">
                        </div>


                        {{-- <div class="col-6 text-center">
                            <!-- Main heading -->
                            @if (!empty($data->heading))
                                <h2 class="text-center">{!! $data->heading !!}</h2>
                            @endif

                            <!-- Subheading -->
                            @if (!empty($data->sub_heading))
                                <small class="text-center"><b>{{ $data->sub_heading }}</b></small>
                            @endif
                        </div> --}}

                        <div class="col-6 text-center">

                            {{-- <h2 class="text-center"><b>{{ $data->heading ?? 'Main Heading Here' }}</b></h2> --}}
                            <!-- Main heading -->
                            <h2 class="text-center">{!! $data->heading ?? '<b>Main Heading Here</b>' !!}</h2>
                            <!-- Subheading -->
                            {{-- <small class="text-center">(August 26, 2024 to November 29, 2024)</small> --}}
                            <small class="text-center"><b>{{ $data->sub_heading ?? 'Sub Heading Here' }}</b></small>

                        </div>



                        {{-- @if (!empty($data->logo2))
                            <div class="col-3">
                                <img src="{{ asset('storage/' . $data->logo2) }}" alt="logo2" class="float-end"
                                    style="height: 100px;">
                            </div>
                        @endif --}}


                        <div class="col-3">
                            <img src="{{ asset($data->logo2 ? 'storage/' . $data->logo2 : 'images/azadi.png') }}"
                                alt="logo2" class="float-end" style="height: 100px;"> <!--logo2-->
                        </div>


                    </div>
                    <div class="row mt-3">
                        {{-- Sidebar: Only child forms of the current form (or its parent) --}}
                       <div class="col-md-3">
    <div class="nav flex-column nav-pills mb-4 mb-md-0" id="v-pills-tab" role="tablist"
        aria-orientation="vertical" style="margin-top: 100px;">

        @if ($childForms->isEmpty())
            {{-- No children: show parent as active --}}
            <a class="nav-link active" href="{{ route('forms.show', $form->id) }}">
                {{ $form->name }}
            </a>
        @else
            {{-- Has children: show all child forms (siblings) --}}
            @foreach ($childForms as $child)
                <a class="nav-link {{ $child->id == $form->id ? 'active' : '' }}"
                    href="{{ route('forms.show', $child->id) }}">
                    {{ $child->name }}
                </a>
            @endforeach
        @endif

    </div>
</div>



                        <!-- Main content area -->
                        <div class="col-md-9 border-start">
                            <div class="row my-3">
                                {{-- @if (!empty($data->logo3))
                                    <div class="col-6">
                                        <img src="{{ asset('storage/' . $data->logo3) }}" alt="logo3" class="img-fluid"
                                            style="height: 100px;">
                                    </div>
                                @endif

                                @if (!empty($data->logo4))
                                    <div class="col-6">
                                        <img src="{{ asset('storage/' . $data->logo4) }}" alt="logo4" class="float-end"
                                            style="height: 100px;">
                                    </div>
                                @endif --}}

                                <div class="col-6">
                                    <img src="{{ asset($data->logo3 ? 'storage/' . $data->logo3 : 'images/digital.png') }}"
                                        alt="logo3" class="img-fluid" style="height: 100px;"> <!--logo3-->
                                </div>
                                <div class="col-6">
                                    <img src="{{ asset($data->logo4 ? 'storage/' . $data->logo4 : 'images/swachh.png') }}"
                                        alt="logo4" class="float-end" style="height: 100px;"> <!--logo4-->
                                </div>



                            </div>
                            <div class="tab-content" id="v-pills-tabContent">
                                <div class="tab-pane fade show active" id="{{ route('forms.show', $form->id) }}"
                                    role="tabpanel" aria-labelledby="{{ route('forms.show', $form->id) }}-tab">
                                    @if ($form->description)
                                        <div class="description mb-3">
                                            {{ $form->description }}
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('forms.submit', $form->id) }}"
                                        enctype="multipart/form-data">
                                        @csrf

                                        @foreach ($sections as $section)
                                            <div class="section-container mb-4">
                                                <div class="section-title py-2 fw-bold">{{ $section->section_title }}</div>

                                                @if (isset($fieldsBySection[$section->id]))
                                                    @php
                                                        $maxCol = 0;
                                                        foreach ($fieldsBySection[$section->id] as $row) {
                                                            $maxCol = max($maxCol, max(array_keys($row)));
                                                        }
                                                    @endphp

                                                    <table class="table table-bordered dynamic-table">
                                                        <thead>
                                                            <tr>
                                                                @for ($i = 0; $i <= $maxCol; $i++)
                                                                    @if (isset($headersBySection[$section->id][$i]))
                                                                        <th>{{ $headersBySection[$section->id][$i] }}</th>
                                                                        <input type="hidden"
                                                                            name="header_{{ $section->id }}_{{ $i }}"
                                                                            value="{{ $headersBySection[$section->id][$i] }}">
                                                                    @else
                                                                        <th></th>
                                                                    @endif
                                                                @endfor
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($fieldsBySection[$section->id] as $rowIndex => $row)
                                                                <tr id="row-{{ $rowIndex }}">
                                                                    @for ($i = 0; $i <= $maxCol; $i++)
                                                                        <td>
                                                                            @if (isset($row[$i]))
                                                                                @include(
                                                                                    'admin.forms.field-types',
                                                                                    [
                                                                                        'field' => $row[$i],
                                                                                        'value' =>
                                                                                            $submissions[
                                                                                                $row[$i]->formname
                                                                                            ]->fieldvalue ?? null,
                                                                                        'name' => "table_{$section->id}_{$rowIndex}_{$i}",
                                                                                    ]
                                                                                )
                                                                            @else
                                                                                &nbsp;
                                                                            @endif
                                                                        </td>
                                                                    @endfor
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <td colspan="{{ $maxCol + 1 }}">
                                                                    <button class="replicate-row btn btn-sm btn-success"
                                                                        onclick="replicateRow(event)">
                                                                        <img src="{{ asset('images/increase.png') }}"
                                                                            alt="Add Row"
                                                                            style="width: 15px; height: 15px;">
                                                                    </button>
                                                                    <button class="remove-row btn btn-sm btn-danger"
                                                                        onclick="removeRow(event)">
                                                                        <img src="{{ asset('images/decrease.png') }}"
                                                                            alt="Remove Row"
                                                                            style="width: 15px; height: 15px;">
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                @endif

                                                @if (isset($gridFields[$section->id]))
                                                    <div class="row">
                                                        @foreach ($gridFields[$section->id] as $field)
                                                            <div class="col-md-6 mb-3">
                                                                @include('admin.forms.field-types', [
                                                                    'field' => $field,
                                                                    'value' =>
                                                                        $submissions[$field->formname]->fieldvalue ?? null,
                                                                    'name' => "field_{$field->formname}",
                                                                ])
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach

                                        <div class="form-actions border-top pt-3">
                                            <div class="float-end">
                                                <button type="submit" class="btn btn-primary">Submit</button>
                                            </div>
                                        </div>
                                    </form>
                                </div> <!-- col-md-9 -->
                            </div> <!-- row -->
                        </div> <!-- card-body -->
                    </div> <!-- card -->
                </div> <!-- container -->

            @endsection

            @push('scripts')
                <script>
                    // ffunction previewImage(event, input) {
                    //     const fileList = input.files;
                    //     const previewContainer = document.getElementById(`file-preview-${input.id}`);

                    //     if (!previewContainer) {
                    //         console.error(`Preview container not found for ID: file-preview-${input.id}`);
                    //         return;
                    //     }

                    //     previewContainer.innerHTML = '';

                    //     if (fileList.length > 0) {
                    //         Array.from(fileList).forEach(file => {
                    //             if (file.type.startsWith('image/')) {
                    //                 const img = document.createElement('img');
                    //                 img.src = URL.createObjectURL(file);
                    //                 // img.style.maxWidth = '100px';
                    //                 img.style.width = '100px';
                    //                 img.style.height = '100px';
                    //                 img.style.margin = '5px';
                    //                 img.style.display = 'inline-block';
                    //                 previewContainer.appendChild(img);
                    //             }
                    //         });
                    //     }
                    // }



                    function previewImage(event, input) {
                        const fileList = input.files;
                        const previewContainer = document.getElementById(`file-preview-${input.id || input.name}`);

                        if (!previewContainer) {
                            console.error(`Preview container not found for ID: file-preview-${input.id || input.name}`);
                            return;
                        }

                        previewContainer.innerHTML = '';

                        if (fileList.length > 0) {
                            Array.from(fileList).forEach(file => {
                                const fileName = file.name;
                                const fileExtension = fileName.split('.').pop().toLowerCase();
                                const fileUrl = URL.createObjectURL(file);

                                // Image Preview
                                if (file.type.startsWith('image/')) {
                                    const img = document.createElement('img');
                                    img.src = fileUrl;
                                    img.style.width = '100px';
                                    img.style.height = '100px';
                                    img.style.margin = '5px';
                                    img.style.display = 'inline-block';
                                    previewContainer.appendChild(img);

                                    // PDF Preview
                                } else if (file.type === 'application/pdf') {
                                    const link = document.createElement('a');
                                    link.href = fileUrl;
                                    link.textContent = 'Preview PDF';
                                    link.target = '_blank';
                                    link.classList.add('btn', 'btn-danger', 'm-1');
                                    previewContainer.appendChild(link);

                                    // Other documents: DOC, DOCX, XLSX, PPT, TXT, ZIP, etc.
                                } else {
                                    const link = document.createElement('a');
                                    link.href = fileUrl;
                                    link.textContent = `Download ${fileName}`;
                                    link.setAttribute('download', fileName);
                                    link.classList.add('btn', 'btn-secondary', 'm-1');
                                    previewContainer.appendChild(link);
                                }
                            });
                        }
                    }



                    function replicateRow(event) {
                        event.preventDefault();
                        const table = event.target.closest('table').getElementsByTagName('tbody')[0];

                        const lastRow = table.rows[table.rows.length - 1];
                        const newRow = lastRow.cloneNode(true);

                        const isDuplicate = checkDropdownDuplicates(newRow);
                        if (isDuplicate) {
                            resetRowInputs(lastRow);
                        } else {
                            const newRowIndex = table.rows.length;
                            newRow.id = 'row-' + newRowIndex;

                            const inputs = newRow.querySelectorAll('input, select, textarea');
                            inputs.forEach(function(input) {
                                const match = input.name.match(/^table_(\d+)_\d+_(\d+)$/);
                                if (match) {
                                    const sectionId = match[1];
                                    const colIndex = match[2];
                                    input.name = `table_${sectionId}_${newRowIndex}_${colIndex}`;
                                    input.id = `table_${sectionId}_${newRowIndex}_${colIndex}`;
                                }
                            });

                            resetRowInputs(newRow);
                            table.appendChild(newRow);
                        }
                    }

                    function resetRowInputs(row) {
                        row.querySelectorAll('input, select, textarea').forEach(input => {
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
                        const table = event.target.closest('table').getElementsByTagName('tbody')[0];
                        if (table.rows.length > 1) {
                            table.deleteRow(table.rows.length - 1);
                        }
                    }
                </script>
            @endpush
