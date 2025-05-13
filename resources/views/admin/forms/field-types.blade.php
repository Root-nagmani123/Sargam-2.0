<!-- resources/views/forms/field-types.blade.php -->
@php
    $validFieldHeadings = [
        'country', 'state', 'district', 'language', 'admissioncategory',
        'stream', 'institution', 'jobtype', 'boardname', 'qualification',
        'religion', 'service', 'sports', 'size', 'fcscale', 'distinction',
        'fatherprofession', 'trouser', 'shoessize', 'studentskill'
    ];
    
    $isMappedField = false;
    $mappedHeading = '';
    
    foreach ($validFieldHeadings as $validHeading) {
        if (stripos($field->formname ?? $field->field_title ?? '', $validHeading) !== false) {
            $isMappedField = true;
            $mappedHeading = $validHeading;
            break;
        }
    }

    // Determine if this is a table field (has field_type instead of formtype)
    // $sectionId = $sectionId ?? 'unknown'; // This should be passed into the view per section
    $isTableField = isset($field->field_type);
    $fieldType = $isTableField ? $field->field_type : $field->formtype;
    $fieldLabel = $isTableField ? ($field->field_title ?? '') : ($field->formlabel ?? '');
    $fieldName = $name ?? ($isTableField ? "table_{$i}_{$j}" : "field_{$field->formname}");
    // $fieldName = $name ?? ($isTableField ? "table_{$sectionId}_{$i}_{$j}" : "field_{$field->formname}");
    // dd($fieldName);
    $required = $isTableField ? ($field->required ?? false) : ($field->required ?? false);
@endphp

@switch($fieldType)
    @case('Text')
    @case('text')
        <div class="form-group">
            <label>{{ $fieldLabel }}</label>
            <input type="text" name="{{ $fieldName }}" value="{{ $value }}" {{ $required ? 'required' : '' }} />
        </div>
        @break

    @case('Label')
    @case('label')
        <label>{{ $fieldLabel }}</label>
        @break

    @case('Date')
    @case('date')
        <div class="form-group">
            <label>{{ $fieldLabel }}</label>
            <input type="date" name="{{ $fieldName }}" value="{{ $value }}" {{ $required ? 'required' : '' }} />
        </div>
        @break

    @case('Email')
    @case('email')
        <div class="form-group">
            <label>{{ $fieldLabel }}</label>
            <input type="email" name="{{ $fieldName }}" value="{{ $value }}" {{ $required ? 'required' : '' }} />
        </div>
        @break

    @case('Textarea')
    @case('textarea')
        <div class="form-group">
            <label>{{ $fieldLabel }}</label>
            <textarea name="{{ $fieldName }}" {{ $required ? 'required' : '' }}>{{ $value }}</textarea>
        </div>
        @break

    @case('Select Box')
    @case('dropdown')
        <div class="form-group">
            <label>{{ $fieldLabel }}</label>
            <select name="{{ $fieldName }}" {{ $required ? 'required' : '' }}>
                <option value="">Select {{ $fieldLabel }}</option>
                
                @if($isMappedField)
                    @php
                        $tableName = strtolower($mappedHeading) . '_master';
                        $options = DB::table($tableName)->get();
                        $valueField = $mappedHeading . '_name';
                    @endphp
                    
                    @foreach($options as $option)
                        <option value="{{ $option->id }}" {{ $value == $option->id ? 'selected' : '' }}>
                            {{ $option->$valueField ?? $option->name }}
                        </option>
                    @endforeach
                @else
                    @php
                        $options = $isTableField ? 
                            (explode(',', $field->fieldoption ?? '') ?? []) : 
                            (explode(',', $field->fieldoption ?? '') ?? []);
                    @endphp
                    
                    @foreach($options as $option)
                        @php $option = trim($option); @endphp
                        <option value="{{ $option }}" {{ $value == $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>
        @break

    @case('Radio Button')
    @case('radio')
        <div class="form-group horizontal-radio-group">
            <label>{{ $fieldLabel }}</label>
            @php
                $radioOptions = $isTableField ? 
                    (explode(',', $field->field_options ?? '') ?? []) : 
                    (explode(',', $field->field_radio_options ?? $field->fieldoption ?? '') ?? []);
            @endphp
            
            @foreach($radioOptions as $option)
                @php $option = trim($option); @endphp
                <label>
                    <input type="radio" name="{{ $fieldName }}" value="{{ $option }}" 
                        {{ $value == $option ? 'checked' : '' }} {{ $required ? 'required' : '' }} />
                    {{ $option }}
                </label>
            @endforeach
        </div>
        @break

    @case('Checkbox')
    @case('checkbox')
        <div class="form-group">
            <fieldset>
                <legend>{{ $fieldLabel }}</legend>
                @php
                    $checkboxOptions = $isTableField ? 
                        (explode(',', $field->field_checkbox_options ?? '') ?? []) : 
                        (explode(',', $field->field_checkbox_options ?? $field->fieldoption ?? '') ?? []);
                    $selectedValues = is_array($value) ? $value : explode(',', $value ?? '');
                @endphp
                
                @foreach($checkboxOptions as $option)
                    @php $option = trim($option); @endphp
                    <label>
                        <input type="checkbox" name="{{ $fieldName }}" value="{{ $option }}"
                            {{ in_array($option, $selectedValues) ? 'checked' : '' }} {{ $required ? 'required' : '' }} />
                        {{ $option }}
                    </label><br>
                @endforeach
            </fieldset>
        </div>
        @break

    @case('number')
        <div class="form-group">
            <label>{{ $fieldLabel }}</label>
            <input type="number" name="{{ $fieldName }}" value="{{ $value }}" {{ $required ? 'required' : '' }} />
        </div>
        @break

    @case('time')
        <div class="form-group">
            <label>{{ $fieldLabel }}</label>
            <input type="time" name="{{ $fieldName }}" value="{{ $value }}" {{ $required ? 'required' : '' }} />
        </div>
        @break

    @case('File Upload')
    @case('file')
        <div class="form-group">
            <label>{{ $fieldLabel }}</label>
            <input type="file" name="{{ $fieldName }}" id="{{ $fieldName }}" {{ $required ? 'required' : '' }} 
                onchange="previewImage(event, this)" />
            
            @if($value)
                <div class="file-preview" id="file-preview-{{ $fieldName }}">
                    @if(in_array(pathinfo($value, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']))
                        <img src="{{ Storage::url($value) }}" alt="Uploaded Image" style="max-width: 100px; max-height: 100px; margin-top: 10px;" />
                    @elseif(pathinfo($value, PATHINFO_EXTENSION) === 'pdf')
                        <a href="{{ Storage::url($value) }}" target="_blank" class="btn btn-primary">View PDF</a>
                    @else
                        <span>{{ basename($value) }}</span>
                    @endif
                </div>
            @else
                <div class="file-preview" id="file-preview-{{ $fieldName }}"></div>
            @endif
        </div>
        @break

    @case('View/Download')
        @php
            $label = $field->field_title ?? '';
            $url = $field->field_url ?? '';
        @endphp
        <label><a href="{{ $url }}" target="_blank">{{ $label }}</a></label>
        @break

    @default
        <p>Unknown field type: {{ $fieldType }}</p>
@endswitch