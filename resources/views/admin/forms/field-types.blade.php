<!-- resources/views/forms/field-types.blade.php -->
{{-- @php
    $validFieldHeadings = [
        'country','state','district','language','admissioncategory','stream','institution','jobtype','boardname','qualification',
        'religion_master_pk','last_service_pk','sports','size','fcscale','distinction','fatherprofession',
        'shoessize','studentskill','birth_district','birth_state','birth_country','pdistrict_id','mdistrict_id','admission_category_pk',
        'highest_stream_pk','city','postal_city','service_master_pk','last_service_pk','t-Shirt','Blazer','Trouser','Tracksuite','father_profession',
        'mother_profession'
    ];

    $isTableField = isset($field->field_type);
    $fieldType = $isTableField ? $field->field_type : $field->formtype;
    $fieldLabel = $isTableField ? $field->field_title ?? '' : $field->formlabel ?? '';
    $fieldName = $name ?? ($isTableField ? "table_{$i}_{$j}" : "field_{$field->formname}");
    $required = $isTableField ? $field->required ?? false : $field->required ?? false;
    $requiredAsterisk = $required ? '<span class="text-danger">*</span>' : '';
    $mappedHeading = '';
    $isMappedField = false;
    foreach ($validFieldHeadings as $validHeading) {
        // Prefer exact match first
        if (strcasecmp($field->formname ?? ($field->field_title ?? ''), $validHeading) === 0) {
            $isMappedField = true;
            $mappedHeading = $validHeading;
            break;
        }
    }

    // If no exact match, fall back to partial match
    if (!$isMappedField) {
        foreach ($validFieldHeadings as $validHeading) {
            if (stripos($field->formname ?? ($field->field_title ?? ''), $validHeading) !== false) {
                $isMappedField = true;
                $mappedHeading = $validHeading;
                break;
            }
        }
    }

@endphp --}}

@php
    $validFieldHeadings = [
        'country','state','district','language','admissioncategory','stream','institution','jobtype','boardname','qualification',
        'religion_master_pk','last_service_pk','sports','size','fcscale','distinction','fatherprofession',
        'shoessize','studentskill','birth_district','birth_state','birth_country','pdistrict_id','mdistrict_id','admission_category_pk',
        'highest_stream_pk','city','postal_city','service_master_pk','last_service_pk','t-Shirt','Blazer','Trouser','Tracksuite','father_profession',
        'mother_profession','Degree','instituitontype'
    ];

    $isTableField = isset($field->field_type);
    $fieldType = $isTableField ? $field->field_type : $field->formtype;

    // Label
    $fieldLabel = $isTableField ? ($field->field_title ?? $field->header ?? '') : ($field->formlabel ?? '');

    // ✅ Key for mapping (use header if table, else formname/title)
    // $fieldKey = (trim(
    //     $isTableField
    //         ? ($field->header ?? $field->field_title ?? '')
    //         : ($field->formname ?? $field->field_title ?? '')
    // ));
   $fieldKey = strtolower(
    preg_replace('/[^a-z0-9]/i', '', trim(
        $isTableField
            ? ($field->header ?? $field->field_title ?? '')
            : ($field->formname ?? $field->field_title ?? '')
    ))
);

    $fieldName = $name ?? ($isTableField ? "table_{$i}_{$j}" : "field_{$field->formname}");
    $required = $field->required ?? false;
    $requiredAsterisk = $required ? '<span class="text-danger">*</span>' : '';

    $mappedHeading = '';
    $isMappedField = false;

    foreach ($validFieldHeadings as $validHeading) {
        if (strcasecmp($fieldKey, $validHeading) === 0) {
            $isMappedField = true;
            $mappedHeading = strtolower($validHeading);
            break;
        }
    }

    if (!$isMappedField) {
        foreach ($validFieldHeadings as $validHeading) {
            if (stripos($fieldKey, $validHeading) !== false) {
                $isMappedField = true;
                $mappedHeading = strtolower($validHeading);
                break;
            }
        }
    }
@endphp

@switch($fieldType)
    @case('Text')
    @case('text')
        <div class="form-group">
            <label class="form-label">{!! $fieldLabel . $requiredAsterisk !!}</label>
            <input type="text" class="form-control" name="{{ $fieldName }}" value="{{ $value }}"
                {{ $required ? 'required' : '' }} />
        </div>
    @break

    @case('Label')
    @case('label')
        <label class="form-label">{!! $fieldLabel . $requiredAsterisk !!}</label>
    @break

    @case('Date')
    @case('date')
        <div class="form-group">
            <label class="form-label">{!! $fieldLabel . $requiredAsterisk !!}</label>
            <input type="date" class="form-control" name="{{ $fieldName }}" value="{{ $value }}"
                {{ $required ? 'required' : '' }} />
        </div>
    @break

    @case('Email')
    @case('email')
        <div class="form-group">
            <label class="form-label">{!! $fieldLabel . $requiredAsterisk !!}</label>
            <input type="email" class="form-control" name="{{ $fieldName }}" value="{{ $value }}"
                {{ $required ? 'required' : '' }} />
        </div>
    @break

    @case('Textarea')
    @case('textarea')
        <div class="form-group">
            <label class="form-label">{!! $fieldLabel . $requiredAsterisk !!}</label>
            <textarea name="{{ $fieldName }}" class="form-control" {{ $required ? 'required' : '' }}>{{ $value }}</textarea>
        </div>
    @break

  @case('Select Box')
@case('dropdown')
    <div class="form-group">
     <label class="form-label" for="{{ $fieldName }}">{!! $fieldLabel . $requiredAsterisk !!}</label>
        <select class="form-control select" id="{{ $fieldName }}" name="{{ $fieldName }}"
            {{ $required ? 'required' : '' }}>
            <option value="">Choose Option</option>

            @if ($isMappedField)
                @php
                    // Mapping: heading → [table, pk_field, value_field]
                    $specialMappings = [
                        'country' => ['country_master', 'pk', 'country_name'],
                        'postal_country' => ['country_master', 'pk', 'country_name'],

                        'state' => ['state_master', 'pk', 'state_name'],
                        'postal_state' => ['state_master', 'pk', 'state_name'],
                        'domicile_state' => ['state_master', 'pk', 'state_name'],
                        'birth_state' => ['state_master', 'pk', 'state_name'],

                        'birth_district' => ['state_district_mapping', 'pk', 'district_name'],
                        'pdistrict_id' => ['state_district_mapping', 'pk', 'district_name'],
                        'mdistrict_id' => ['state_district_mapping', 'pk', 'district_name'],

                        'admission_category_pk' => ['admission_category_master', 'pk', 'Seat_name'],
                        'highest_stream_pk' => ['stream_master', 'pk', 'stream_name'],

                        'religion_master_pk' => ['religion_master', 'pk', 'religion_name'],

                        'service_master_pk' => ['service_master', 'pk', 'service_name'],
                        'last_service_pk' => ['service_master', 'pk', 'service_name'],
                        'city' => ['city_master', 'pk', 'city_name'],
                        'postal_city' => ['city_master', 'pk', 'city_name'],
                        't-Shirt'      => ['student_cloths_size_master',  'pk', 'cloth_size'],
                        'Trouser'       => ['student_cloths_size_master',  'pk', 'cloth_size'],
                        'Blazer'       => ['student_cloths_size_master',  'pk', 'cloth_size'],
                        'Tracksuite'    => ['student_cloths_size_master',  'pk', 'cloth_size'],
                        'father_profession' => ['parents_profession_master', 'pk', 'profession_name'],
                        'mother_profession' => ['parents_profession_master', 'pk', 'profession_name'],
                        'language' => ['language_master', 'pk', 'language_name'],
                        'boardname' => ['university_board_name_master', 'pk', 'board_name'],
                        'university' => ['university_board_name_master', 'pk', 'board_name'],
                        'degree' => ['degree_master', 'Pk', 'degree_name'],
                        'instituitontype' => ['institute_type_master', 'pk', 'type_name'],
                    ];

                    // $map = $specialMappings[$mappedHeading] ?? null;
                     $map = $specialMappings[strtolower($mappedHeading)] ?? null;


                    if ($map) {
                        [$tableName, $pkField, $valueField] = $map;
                        $options = DB::table($tableName)->get();
                    } else {
                        $options = collect(); // empty
                    }
                @endphp

                @foreach ($options as $option)
                    <option value="{{ $option->$pkField }}"
                        {{ old($fieldName, $value) == $option->$pkField ? 'selected' : '' }}>
                        {{ $option->$valueField }}
                    </option>
                @endforeach
            @else
                @php
                    $optionsRaw =
                        $field->field_options ??
                        ($field->field_checkbox_options ??
                            ($field->field_radio_options ?? ($field->fieldoption ?? '')));
                    $options = explode(',', $optionsRaw);
                @endphp

                @foreach ($options as $option)
                    @php $option = trim($option); @endphp
                    <option value="{{ $option }}" {{ old($fieldName, $value) == $option ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endforeach
            @endif
        </select>
    </div>
@break



    @case('Radio Button')
    @case('radio')
        <label class="form-label">{!! $fieldLabel . $requiredAsterisk !!}</label> <br>
        @php
            $radioOptions = explode(
                ',',
                $field->field_radio_options ?? ($field->field_options ?? ($field->fieldoption ?? '')),
            );
        @endphp

        @foreach ($radioOptions as $option)
            @php $option = trim($option); @endphp
            <div class="py-1 form-check form-check-inline">
                <input type="radio" id="{{ $fieldName }}_{{ $loop->index }}" name="{{ $fieldName }}"
                    class="form-check-input" value="{{ $option }}" {{ $value == $option ? 'checked' : '' }}
                    {{ $required ? 'required' : '' }}>
                <label class="form-check-label" for="{{ $fieldName }}_{{ $loop->index }}">{{ $option }}</label>
            </div>
        @endforeach
    @break

    @case('Checkbox')
    @case('checkbox')
        <label class="form-label">{!! $fieldLabel . $requiredAsterisk !!}</label> <br>
        @php
            $checkboxOptions = explode(',', $field->field_checkbox_options ?? ($field->fieldoption ?? ''));
            $selectedValues = is_array($value) ? $value : explode(',', $value ?? '');
        @endphp

        @foreach ($checkboxOptions as $option)
            @php $option = trim($option); @endphp
            <div class="py-1 form-check form-check-inline">
                <input type="checkbox" id="{{ $fieldName }}_{{ $loop->index }}" name="{{ $fieldName }}[]"
                    class="form-check-input" value="{{ $option }}"
                    {{ in_array($option, $selectedValues) ? 'checked' : '' }} {{ $required ? 'required' : '' }}>
                <label class="form-check-label" for="{{ $fieldName }}_{{ $loop->index }}">{{ $option }}</label>
            </div>
        @endforeach
    @break

    @case('number')
        <div class="form-group">
            <label class="form-label">{!! $fieldLabel . $requiredAsterisk !!}</label>
            <input class="form-control" type="number" name="{{ $fieldName }}" value="{{ $value }}"
                {{ $required ? 'required' : '' }} />
        </div>
    @break

    @case('time')
        <div class="form-group">
            <label class="form-label">{!! $fieldLabel . $requiredAsterisk !!}</label>
            <input class="form-control" type="time" name="{{ $fieldName }}" value="{{ $value }}"
                {{ $required ? 'required' : '' }} />
        </div>
    @break

    @case('File Upload')
    @case('file')
        <div class="form-group">
            <label class="form-label">{!! $fieldLabel . $requiredAsterisk !!}</label>
            <input class="form-control" type="file" name="{{ $fieldName }}" id="{{ $fieldName }}"
                {{ $required ? 'required' : '' }} onchange="previewImage(event, this)" />

            <div class="file-preview mt-2" id="file-preview-{{ $fieldName }}">
                @if ($value)
                    @if (in_array(pathinfo($value, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']))
                        <img src="{{ Storage::url($value) }}" alt="Uploaded Image" class="img-fluid" />
                    @elseif(pathinfo($value, PATHINFO_EXTENSION) === 'pdf')
                        <a href="{{ Storage::url($value) }}" target="_blank" class="btn btn-sm btn-primary">View PDF</a>
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

