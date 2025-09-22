@php
    $validFieldHeadings = [
        'country','state','district','language','admissioncategory','stream','institution','jobtype','boardname','qualification','religionmasterpk',
        'lastservicepk','sports','size','fcscale','distinction','shoessize','studentskill','birthdistrict','birthstate','birthcountry','pdistrictid',
        'mdistrictid','admissioncategorypk','higheststreampk','city','postalcity','servicemasterpk','tshirtsize','blazerjacketsize','trousersize','tracksuitsize','fatherprofession',
        'motherprofession','degree','instituitontype','nationality','birthstate','birthdistrict','statedistrictmappingpk','motherlang','academicmedium',
        'universitymedium','preuniversitymedium','upscexammedium','upscvivamedium'
    ];

    $isTableField = isset($field->field_type);
    $fieldType = $isTableField ? $field->field_type : $field->formtype;
    // Label
    $fieldLabel = $isTableField ? $field->field_title ?? ($field->header ?? '') : $field->formlabel ?? '';
    $fieldKey = strtolower(
        preg_replace(
            '/[^a-z0-9]/i',
            '',
            trim(
                $isTableField
                    ? $field->header ?? ($field->field_title ?? '')
                    : $field->formname ?? ($field->field_title ?? ''),
            ),
        ),
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

    {{-- @case('Select Box')
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
                            'nationality' => ['country_master', 'pk', 'country_name'],
                            'state' => ['state_master', 'pk', 'state_name'],
                            'birthstate' => ['state_master', 'pk', 'state_name'],
                            'district' => ['state_district_mapping', 'pk', 'district_name'],
                            'district' => ['state_district_mapping', 'pk', 'district_name'],
                            'pdistrictid' => ['state_district_mapping', 'pk', 'district_name'],
                            'mdistrictid' => ['state_district_mapping', 'pk', 'district_name'],
                            'birthcountry' => ['country_master', 'pk', 'country_name'],
                            'statedistrictmappingpk' => ['state_district_mapping', 'pk', 'district_name'],
                            'birthdistrict' => ['state_district_mapping', 'pk', 'district_name'],
                            'admissioncategorypk' => ['admission_category_master', 'pk', 'Seat_name'],
                            'higheststreampk' => ['stream_master', 'pk', 'stream_name'],
                            'religionmasterpk' => ['religion_master', 'pk', 'religion_name'],
                            'servicemasterpk' => ['service_master', 'pk', 'service_name'],
                            'lastservicepk' => ['service_master', 'pk', 'service_name'],
                            'city' => ['city_master', 'pk', 'city_name'],
                            'tshirt' => ['student_cloths_size_master', 'pk', 'cloth_size'],
                            'trouser' => ['student_cloths_size_master', 'pk', 'cloth_size'],
                            'blazer' => ['student_cloths_size_master', 'pk', 'cloth_size'],
                            'tracksuite' => ['student_cloths_size_master', 'pk', 'cloth_size'],
                            'fatherprofession' => ['parents_profession_master', 'pk', 'profession_name'],
                            'motherprofession' => ['parents_profession_master', 'pk', 'profession_name'],
                            'language' => ['language_master', 'pk', 'language_name'],
                            'boardname' => ['university_board_name_master', 'pk', 'board_name'],
                            'university' => ['university_board_name_master', 'pk', 'board_name'],
                            'degree' => ['degree_master', 'Pk', 'degree_name'],
                            'instituitontype' => ['institute_type_master', 'pk', 'type_name'],
                            'motherlang' => ['language_master', 'pk', 'language_name'],
                            'academicmedium' => ['language_master', 'pk', 'language_name'],
                            'preuniversitymedium' => ['language_master', 'pk', 'language_name'],
                            'universitymedium' => ['language_master', 'pk', 'language_name'],
                            'upscexammedium' => ['language_master', 'pk', 'language_name'],
                            'upscvivamedium' => ['language_master', 'pk', 'language_name'],
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
    @break --}}
    @case('Select Box')
    @case('dropdown')
     @php
    // dump([
    //     'fieldName' => $fieldName ?? null,
    //     'fieldLabel' => $fieldLabel ?? null,
    //     'field' => $field ?? null,
    // ]);
@endphp
        @php
            // Collect options from field definition
            $optionsRaw =
                $field->field_options ??
                ($field->field_checkbox_options ?? ($field->field_radio_options ?? ($field->fieldoption ?? '')));

            $hasTableOptions = !empty(trim($optionsRaw ?? ''));

            $options = [];
            $pkField = null;
            $valueField = null;

            if ($hasTableOptions) {
                // Priority 1: options defined in the field itself
                $options = array_map('trim', explode(',', $optionsRaw));
            } else {
                // Priority 2: mapping from DB
                $specialMappings = [
                    'country' => ['country_master', 'pk', 'country_name'],
                    'nationality' => ['country_master', 'pk', 'country_name'],
                    'state' => ['state_master', 'pk', 'state_name'],
                    'birthstate' => ['state_master', 'pk', 'state_name'],
                    'district' => ['state_district_mapping', 'pk', 'district_name'],
                    'pdistrictid' => ['state_district_mapping', 'pk', 'district_name'],
                    'mdistrictid' => ['state_district_mapping', 'pk', 'district_name'],
                    'birthcountry' => ['country_master', 'pk', 'country_name'],
                    'statedistrictmappingpk' => ['state_district_mapping', 'pk', 'district_name'],
                    'birthdistrict' => ['state_district_mapping', 'pk', 'district_name'],
                    'admissioncategorypk' => ['admission_category_master', 'pk', 'Seat_name'],
                    'higheststreampk' => ['stream_master', 'pk', 'stream_name'],
                    'religionmasterpk' => ['religion_master', 'pk', 'religion_name'],
                    'servicemasterpk' => ['service_master', 'pk', 'service_name'],
                    'lastservicepk' => ['service_master', 'pk', 'service_name'],
                    'city' => ['city_master', 'pk', 'city_name'],
                    'tshirtsize' => ['student_cloths_size_master', 'pk', 'cloth_size'],
                    'trousersize' => ['student_cloths_size_master', 'pk', 'cloth_size'],
                    'blazerjacketsize' => ['student_cloths_size_master', 'pk', 'cloth_size'],
                    'tracksuitsize' => ['student_cloths_size_master', 'pk', 'cloth_size'],
                    'fatherprofession' => ['parents_profession_master', 'pk', 'profession_name'],
                    'motherprofession' => ['parents_profession_master', 'pk', 'profession_name'],
                    'language' => ['language_master', 'pk', 'language_name'],
                    'boardname' => ['university_board_name_master', 'pk', 'board_name'],
                    'university' => ['university_board_name_master', 'pk', 'board_name'],
                    'degree' => ['degree_master', 'Pk', 'degree_name'],
                    'instituitontype' => ['institute_type_master', 'pk', 'type_name'],
                    'motherlang' => ['language_master', 'pk', 'language_name'],
                    'academicmedium' => ['language_master', 'pk', 'language_name'],
                    'preuniversitymedium' => ['language_master', 'pk', 'language_name'],
                    'universitymedium' => ['language_master', 'pk', 'language_name'],
                    'upscexammedium' => ['language_master', 'pk', 'language_name'],
                    'upscvivamedium' => ['language_master', 'pk', 'language_name'],
                ];

                $map = $specialMappings[strtolower($mappedHeading ?? '')] ?? null;

                if ($map) {
                    [$tableName, $pkField, $valueField] = $map;
                    $options = DB::table($tableName)->get();
                }
            }
        @endphp

        <div class="form-group">
            <label class="form-label" for="{{ $fieldName }}">{!! $fieldLabel . $requiredAsterisk !!}</label>
            <select class="form-control select" id="{{ $fieldName }}" name="{{ $fieldName }}"
                {{ $required ? 'required' : '' }}>
                <option value="">Choose Option</option>

                {{-- Priority 1: use table-defined options --}}
                @if ($hasTableOptions)
                    @foreach ($options as $option)
                        <option value="{{ $option }}" {{ old($fieldName, $value) == $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endforeach

                    {{-- Priority 2: fallback to DB mapping --}}
                @elseif(!empty($pkField) && !empty($valueField))
                    @foreach ($options as $option)
                        <option value="{{ $option->$pkField }}"
                            {{ old($fieldName, $value) == $option->$pkField ? 'selected' : '' }}>
                            {{ $option->$valueField }}
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
            //  Use DB layout for radio: 'inline' → form-check-inline, 'block' → d-block
            $layoutClass = $field->layout === 'block' ? 'd-block' : 'form-check-inline';
        @endphp

        @foreach ($radioOptions as $option)
            @php $option = trim($option); @endphp
            <div class="py-1 form-check {{ $layoutClass }}">
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
            //  Use DB layout for radio: 'inline' → form-check-inline, 'block' → d-block
            $layoutClass = $field->layout === 'block' ? 'd-block' : 'form-check-inline';
        @endphp

        @foreach ($checkboxOptions as $option)
            @php $option = trim($option); @endphp
            <div class="py-1 form-check {{ $layoutClass }}">
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
