{{-- Single row for a repeatable group --}}
@php
    $groupName = $group->group_name;
    $groupFields = $group->activeGroupFields->isNotEmpty()
        ? $group->activeGroupFields
        : $group->groupFields;
    $isReadonly = $readonly ?? false;

    // Fields shown only when another field in the same row holds a given value.
    // (Spouse Name dropdown appears only when "Is your spouse also registering?" = Yes.)
    $conditionalOn = [
        'spouse_name' => ['field' => 'spouse_in_cse', 'value' => 'Yes'],
    ];
@endphp

@php $hideRemoveRow = ($group->max_rows <= 1 && $group->min_rows >= 1) || $isReadonly; @endphp
<div class="repeatable-row border rounded p-3 mb-2 bg-light position-relative" data-index="{{ $i }}">
    <div class="row g-2">
        @foreach($groupFields as $gf)
            @php
                $fieldName  = "{$groupName}[{$i}][{$gf->field_name}]";
                $fieldValue = old("{$groupName}.{$i}.{$gf->field_name}", $row->{$gf->target_column} ?? '');
                if ($gf->field_type === 'number' && $fieldValue !== '' && $fieldValue !== null) {
                    $fieldValue = fc_numeric_display_value($fieldValue);
                }
                $errorKey   = "{$groupName}.{$i}.{$gf->field_name}";
                $lookupItems = $groupLookups[$gf->field_name] ?? [];
                $options     = $gf->decoded_options ?? [];
                $valCol      = $gf->lookup_value_column ?? 'id';
                $lblCol      = $gf->lookup_label_column ?? 'name';
                $isDistrictField = ($gf->lookup_table ?? '') === 'state_district_mapping'
                    || str_ends_with($gf->field_name, '_district')
                    || str_contains(strtolower((string) $gf->label), 'district');
                $districtRows = $districtOptions ?? collect();
                $pairedStateField = str_ends_with($gf->field_name, '_district')
                    ? str_replace('_district', '_state_id', $gf->field_name)
                    : null;
                $pairedStateInputName = $pairedStateField ? "{$groupName}[{$i}][{$pairedStateField}]" : null;
                $isStateLookup = $gf->field_type === 'select'
                    && $gf->lookup_table
                    && str_contains(strtolower((string) $gf->lookup_table), 'state');
            @endphp
            <div class="{{ $gf->css_class }}"
                @if(isset($conditionalOn[$gf->field_name]))
                    data-fc-cond-name="{{ $groupName }}[{{ $i }}][{{ $conditionalOn[$gf->field_name]['field'] }}]"
                    data-fc-cond-value="{{ $conditionalOn[$gf->field_name]['value'] }}"
                @endif>
                <label class="form-label small fw-semibold">
                    {{ $gf->label }}
                    @if($gf->is_required)<span class="text-danger">*</span>@endif
                </label>

                @if($isDistrictField)
                    <select name="{{ $fieldName }}"
                            class="form-select form-select-sm fc-district-select @error($errorKey) is-invalid @enderror"
                            @if($pairedStateInputName) data-fc-state-field="{{ $pairedStateInputName }}" @endif
                            @if($gf->is_required) data-required="1" @endif
                            {{ $isReadonly ? 'disabled' : '' }}>
                        <option value="">-- Select District --</option>
                        @foreach($districtRows as $item)
                            <option value="{{ $item->district_name }}"
                                    data-state-id="{{ $item->state_master_pk }}"
                                    {{ (string) $fieldValue === (string) $item->district_name ? 'selected' : '' }}>
                                {{ $item->district_name }}
                            </option>
                        @endforeach
                    </select>
                @else
                @switch($gf->field_type)
                    @case('text')
                    @case('email')
                    @case('number')
                    @case('date')
                        <input type="{{ $gf->field_type }}" name="{{ $fieldName }}"
                               class="form-control form-control-sm @error($errorKey) is-invalid @enderror"
                               @if($gf->is_required) data-required="1" @endif
                               value="{{ $fieldValue }}" placeholder="{{ $gf->placeholder ?? '' }}"
                               {{ $isReadonly ? 'disabled' : '' }}>
                        @break
                    @case('textarea')
                        <textarea name="{{ $fieldName }}"
                                  class="form-control form-control-sm @error($errorKey) is-invalid @enderror"
                                  @if($gf->is_required) data-required="1" @endif
                                  rows="2" placeholder="{{ $gf->placeholder ?? '' }}"
                                  {{ $isReadonly ? 'disabled' : '' }}>{{ $fieldValue }}</textarea>
                        @break
                    @case('select')
                        <select name="{{ $fieldName }}"
                                @if($gf->is_required) data-required="1" @endif
                                class="form-select form-select-sm {{ str_contains($gf->css_class ?? '', 'select2-field') ? 'select2-dynamic' : '' }} @error($errorKey) is-invalid @enderror @if($isStateLookup) fc-state-select @endif"
                                @if($isStateLookup && str_ends_with($gf->field_name, '_state_id'))
                                    data-fc-country-field="{{ str_replace('_state_id', '_country_id', $gf->field_name) }}"
                                @endif
                                {{ $isReadonly ? 'disabled' : '' }}>
                            <option value="">-- Select --</option>
                            @if(count($lookupItems) > 0)
                                @foreach($lookupItems as $item)
                                    @if(trim((string) ($item->{$lblCol} ?? '')) === '') @continue @endif
                                    <option value="{{ $item->{$valCol} }}"
                                            @if($isStateLookup && isset($item->country_master_pk))
                                                data-country-id="{{ $item->country_master_pk }}"
                                            @endif
                                            {{ (string)$fieldValue === (string)$item->{$valCol} ? 'selected' : '' }}>{{ $item->{$lblCol} }}</option>
                                @endforeach
                            @elseif(count($options) > 0)
                                @foreach($options as $opt)
                                    <option value="{{ $opt['value'] }}" {{ (string)$fieldValue === (string)$opt['value'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                @endforeach
                            @endif
                        </select>
                        @break
                    @case('radio')
                        <div class="d-flex flex-wrap gap-3 mt-1">
                            @foreach($options as $ri => $opt)
                                <div class="form-check">
                                    <input class="form-check-input @error($errorKey) is-invalid @enderror" type="radio"
                                           name="{{ $fieldName }}" value="{{ $opt['value'] }}"
                                           id="fc_grd_{{ $gf->id }}_{{ $i }}_{{ $ri }}"
                                           @if($gf->is_required) data-required="1" @endif
                                           {{ (string)$fieldValue === (string)$opt['value'] ? 'checked' : '' }}
                                           {{ $isReadonly ? 'disabled' : '' }}>
                                    <label class="form-check-label small" for="fc_grd_{{ $gf->id }}_{{ $i }}_{{ $ri }}">{{ $opt['label'] }}</label>
                                </div>
                            @endforeach
                        </div>
                        @break
                    @case('checkbox')
                        @if(count($options) > 0)
                            @php
                                $storedG = old("{$groupName}.{$i}.{$gf->field_name}", $row->{$gf->target_column} ?? null);
                                $selectedG = is_array($storedG)
                                    ? array_map('strval', $storedG)
                                    : fc_checkbox_multi_selected($storedG, $options);
                            @endphp
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @foreach($options as $oi => $opt)
                                    @php
                                        $gOptVal = (string) ($opt['value'] ?? '');
                                        $gOptLbl = (string) ($opt['label'] ?? $gOptVal);
                                    @endphp
                                    <div class="form-check">
                                        <input class="form-check-input @error($errorKey) is-invalid @enderror" type="checkbox"
                                               name="{{ $fieldName }}[]" value="{{ $gOptVal }}"
                                               id="fc_gcb_{{ $gf->id }}_{{ $i }}_{{ $oi }}"
                                               {{ in_array($gOptVal, $selectedG, true) ? 'checked' : '' }}
                                               {{ $isReadonly ? 'disabled' : '' }}>
                                        <label class="form-check-label small" for="fc_gcb_{{ $gf->id }}_{{ $i }}_{{ $oi }}">{{ $gOptLbl }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="form-check mt-1">
                                <input class="form-check-input @error($errorKey) is-invalid @enderror" type="checkbox"
                                       name="{{ $fieldName }}" value="1"
                                       id="fc_gcb_single_{{ $gf->id }}_{{ $i }}"
                                       {{ fc_checkbox_single_checked($fieldValue) ? 'checked' : '' }}
                                       {{ $isReadonly ? 'disabled' : '' }}>
                                <label class="form-check-label small" for="fc_gcb_single_{{ $gf->id }}_{{ $i }}">{{ $gf->label }}</label>
                            </div>
                        @endif
                        @break
                    @case('file')
                        @php
                            $existingFilePath = ($gf->target_column ?? null) ? ($row->{$gf->target_column} ?? null) : null;
                            $gFileMaxKb = (int) ($gf->file_max_kb ?? 0);
                            $fileHint = $gf->help_text ?: fc_file_upload_hint($gf->validation_rules ?? null, $gFileMaxKb > 0 ? $gFileMaxKb : null);
                            $gMaxKbAttr = $gFileMaxKb > 0 ? $gFileMaxKb : (preg_match('/max:(\d+)/', (string) ($gf->validation_rules ?? ''), $gm) ? (int) $gm[1] : 10240);
                        @endphp
                        <input type="file" name="{{ $fieldName }}"
                               class="form-control form-control-sm fc-file-upload @error($errorKey) is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                               data-max-kb="{{ $gMaxKbAttr }}"
                               {{ $isReadonly ? 'disabled' : '' }}>
                        <div class="form-text text-muted">{{ $fileHint }}</div>
                        @if(! empty($existingFilePath))
                            <div class="small mt-1 dynamic-current-file-hint">Current file: <a href="{{ asset($existingFilePath) }}" target="_blank" rel="noopener">View</a></div>
                        @endif
                        @break
                @endswitch
                @endif

                @error($errorKey)
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        @endforeach
    </div>
    @unless($hideRemoveRow)
        <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 remove-row-btn" onclick="var r=this.closest('.repeatable-row'),c=r.parentElement;if(c.querySelectorAll('.repeatable-row').length>1){r.remove();}else{r.querySelectorAll('input,select,textarea').forEach(function(e){if(e.tagName==='SELECT'){e.selectedIndex=0;}else if(e.type==='checkbox'||e.type==='radio'){e.checked=false;}else{e.value='';}e.classList.remove('is-invalid');});}" title="Remove row">
            <i class="bi bi-x-lg"></i>
        </button>
    @endunless
</div>
