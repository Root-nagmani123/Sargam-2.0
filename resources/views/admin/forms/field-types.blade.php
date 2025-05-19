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
    <label class="form-label">{{ $fieldLabel }}</label>
    <input type="text" class="form-control" name="{{ $fieldName }}" value="{{ $value }}"
        {{ $required ? 'required' : '' }} />
</div>
@break

@case('Label')
@case('label')
<label class="form-label">{{ $fieldLabel }}</label>
@break

@case('Date')
@case('date')
<div class="form-group">
    <label class="form-label">{{ $fieldLabel }}</label>
    <input type="date" class="form-control" name="{{ $fieldName }}" value="{{ $value }}"
        {{ $required ? 'required' : '' }} />
</div>
@break

@case('Email')
@case('email')
<div class="form-group">
    <label class="form-label">{{ $fieldLabel }}</label>
    <input type="email" class="form-control" name="{{ $fieldName }}" value="{{ $value }}"
        {{ $required ? 'required' : '' }} />
</div>
@break

@case('Textarea')
@case('textarea')
<div class="form-group">
    <label class="form-label">{{ $fieldLabel }}</label>
    <textarea name="{{ $fieldName }}" class="form-control" {{ $required ? 'required' : '' }}>{{ $value }}</textarea>
</div>
@break

@case('Select Box')
@case('dropdown')
<div class="form-group">
    <label class="form-label">{{ $fieldLabel }}</label>
    <select class="form-control" name="{{ $fieldName }}" {{ $required ? 'required' : '' }}>
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
<label class="form-label">{{ $fieldLabel }}</label> <br>
@php
$radioOptions = $isTableField ?
(explode(',', $field->field_options ?? '') ?? []) :
(explode(',', $field->field_radio_options ?? $field->fieldoption ?? '') ?? []);
@endphp

@foreach($radioOptions as $option)
@php $option = trim($option); @endphp
<!-- <label class="form-label">
        <input type="radio" name="{{ $fieldName }}" value="{{ $option }}" {{ $value == $option ? 'checked' : '' }}
            {{ $required ? 'required' : '' }} />
        {{ $option }}
    </label> -->
<div class="py-1 form-check form-check-inline">
    <input type="radio" id="{{ $fieldName }}" name="{{ $fieldName }}" class="form-check-input" value="{{ $option }}"
        {{ $value == $option ? 'checked' : '' }} {{ $required ? 'required' : '' }}>
    <label class="form-check-label">{{ $option }}</label>
</div>
@endforeach
@break

@case('Checkbox')
@case('checkbox')
<label class="form-label">{{ $fieldLabel }}</label> <br>
@php
$checkboxOptions = $isTableField ?
(explode(',', $field->field_checkbox_options ?? '') ?? []) :
(explode(',', $field->field_checkbox_options ?? $field->fieldoption ?? '') ?? []);
$selectedValues = is_array($value) ? $value : explode(',', $value ?? '');
@endphp

@foreach($checkboxOptions as $option)
@php $option = trim($option); @endphp

<div class="py-1 form-check form-check-inline">
    <input type="checkbox" id="{{ $fieldName }}" name="{{ $fieldName }}" class="form-check-input" value="{{ $option }}"
        {{ in_array($option, $selectedValues) ? 'checked' : '' }} {{ $required ? 'required' : '' }}>
    <label class="form-check-label">{{ $option }}</label>
</div>
@endforeach
</div>
@break

@case('number')
<div class="form-group">
    <label class="form-label">{{ $fieldLabel }}</label>
    <input class="form-control" type="number" name="{{ $fieldName }}" value="{{ $value }}"
        {{ $required ? 'required' : '' }} />
</div>
@break

@case('time')
<div class="form-group">
    <label class="form-label">{{ $fieldLabel }}</label>
    <input class="form-control" type="time" name="{{ $fieldName }}" value="{{ $value }}"
        {{ $required ? 'required' : '' }} />
</div>
@break

@case('File Upload')
@case('file')
<div class="form-group">
    <label class="form-label">{{ $fieldLabel }}</label>
    <input class="form-control" type="file" name="{{ $fieldName }}" id="{{ $fieldName }}"
        {{ $required ? 'required' : '' }} onchange="previewImage(event, this)" />

    <div class="file-preview" id="file-preview-{{ $fieldName }}">
        @if($value)
            @if(in_array(pathinfo($value, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']))
            <img src="{{ Storage::url($value) }}" alt="Uploaded Image" class="img-fluid" />
            @elseif(pathinfo($value, PATHINFO_EXTENSION) === 'pdf')
            <a href="{{ Storage::url($value) }}" target="_blank" class="btn btn-primary">View PDF</a>
            @else
            <span>{{ basename($value) }}</span>
            @endif
        @endif
    </div>
</div>

@break

@case('View/Download')
@php
$label = $field->field_title ?? '';
$url = $field->field_url ?? '';
@endphp
<label class="form-label"><a href="{{ $url }}" target="_blank">{{ $label }}</a></label>
@break

@default
<p>Unknown field type: {{ $fieldType }}</p>
@endswitch