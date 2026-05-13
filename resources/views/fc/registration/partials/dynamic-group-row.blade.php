{{-- Single row for a repeatable group --}}
@php
    $groupName = $group->group_name;
    $groupFields = $group->activeGroupFields->isNotEmpty()
        ? $group->activeGroupFields
        : $group->groupFields;
@endphp

@php $hideRemoveRow = ($group->max_rows <= 1 && $group->min_rows >= 1); @endphp
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
            @endphp
            <div class="{{ $gf->css_class }}">
                <label class="form-label small fw-semibold">
                    {{ $gf->label }}
                    @if($gf->is_required)<span class="text-danger">*</span>@endif
                </label>

                @switch($gf->field_type)
                    @case('text')
                    @case('email')
                    @case('number')
                    @case('date')
                        <input type="{{ $gf->field_type }}" name="{{ $fieldName }}"
                               class="form-control form-control-sm @error($errorKey) is-invalid @enderror"
                               value="{{ $fieldValue }}" placeholder="{{ $gf->placeholder ?? '' }}">
                        @break
                    @case('textarea')
                        <textarea name="{{ $fieldName }}"
                                  class="form-control form-control-sm @error($errorKey) is-invalid @enderror"
                                  rows="2" placeholder="{{ $gf->placeholder ?? '' }}">{{ $fieldValue }}</textarea>
                        @break
                    @case('select')
                        <select name="{{ $fieldName }}"
                                class="form-select form-select-sm {{ str_contains($gf->css_class ?? '', 'select2-field') ? 'select2-dynamic' : '' }} @error($errorKey) is-invalid @enderror">
                            <option value="">-- Select --</option>
                            @if(count($lookupItems) > 0)
                                @foreach($lookupItems as $item)
                                    <option value="{{ $item->{$valCol} }}" {{ (string)$fieldValue === (string)$item->{$valCol} ? 'selected' : '' }}>{{ $item->{$lblCol} }}</option>
                                @endforeach
                            @elseif(count($options) > 0)
                                @foreach($options as $opt)
                                    <option value="{{ $opt['value'] }}" {{ (string)$fieldValue === (string)$opt['value'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                @endforeach
                            @endif
                        </select>
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
                                               {{ in_array($gOptVal, $selectedG, true) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="fc_gcb_{{ $gf->id }}_{{ $i }}_{{ $oi }}">{{ $gOptLbl }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="form-check mt-1">
                                <input class="form-check-input @error($errorKey) is-invalid @enderror" type="checkbox"
                                       name="{{ $fieldName }}" value="1"
                                       id="fc_gcb_single_{{ $gf->id }}_{{ $i }}"
                                       {{ fc_checkbox_single_checked($fieldValue) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="fc_gcb_single_{{ $gf->id }}_{{ $i }}">{{ $gf->label }}</label>
                            </div>
                        @endif
                        @break
                    @case('file')
                        @php $existingFilePath = ($gf->target_column ?? null) ? ($row->{$gf->target_column} ?? null) : null; @endphp
                        <input type="file" name="{{ $fieldName }}"
                               class="form-control form-control-sm @error($errorKey) is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
                        @if(! empty($existingFilePath))
                            <div class="small mt-1 dynamic-current-file-hint">Current file: <a href="{{ asset($existingFilePath) }}" target="_blank" rel="noopener">View</a></div>
                        @endif
                        @break
                @endswitch

                @error($errorKey)
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        @endforeach
    </div>
    @unless($hideRemoveRow)
        <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 remove-row-btn" onclick="this.closest('.repeatable-row').remove()" title="Remove row">
            <i class="bi bi-x-lg"></i>
        </button>
    @endunless
</div>
