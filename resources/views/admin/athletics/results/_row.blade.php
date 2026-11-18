{{-- One result line. Used for saved rows and, with $result = null, inside the event's <template>.
     $eventId, $result (AthleticsResult|null), $athletes (list of [pk,label,house_id]), $houses, $perf (bool) --}}
@php $base = 'results['.$eventId.'][__i__]'; @endphp
<tr>
    <td style="width:90px">
        <input type="number" class="form-control am-res-position" min="1" max="999" required aria-label="Position"
               data-name="{{ $base }}[position]" value="{{ $result->position ?? '' }}">
    </td>
    <td style="min-width:200px">
        <select class="form-select am-res-athlete" data-name="{{ $base }}[student_master_pk]" aria-label="Athlete" data-placeholder="Select Athlete">
            <option value="">Select Athlete</option>
            @foreach ($athletes as $a)
                <option value="{{ $a['pk'] }}" data-house="{{ $a['house_id'] }}" @selected(($result->student_master_pk ?? null) == $a['pk'])>{{ $a['label'] }}</option>
            @endforeach
        </select>
    </td>
    <td style="min-width:140px">
        <select class="form-select am-res-house" data-name="{{ $base }}[house_id]" aria-label="House" data-placeholder="Select">
            <option value="">Select</option>
            @foreach ($houses as $h)
                <option value="{{ $h->id }}" @selected(($result->house_id ?? null) == $h->id)>{{ $h->name }}</option>
            @endforeach
        </select>
    </td>
    @if ($perf)
        <td style="width:150px">
            <input type="text" class="form-control" inputmode="decimal" maxlength="30" placeholder="-" aria-label="Performance"
                   data-name="{{ $base }}[performance]" value="{{ $result->performance ?? '' }}">
        </td>
    @endif
    <td style="width:100px">
        <input type="number" class="form-control" min="0" max="9999" step="0.01" placeholder="-" aria-label="Points"
               data-name="{{ $base }}[points]" value="{{ isset($result) ? (float) $result->points : '' }}">
    </td>
    <td style="min-width:200px">
        <input type="text" class="form-control" maxlength="255" placeholder="eg. Photo finish" aria-label="Remarks"
               data-name="{{ $base }}[remarks]" value="{{ $result->remarks ?? '' }}">
    </td>
    <td>
        <div class="am-row-actions">
            <button type="button" class="am-row-btn am-row-btn--remove" aria-label="Remove row"><span class="material-icons material-symbols-rounded" aria-hidden="true">remove</span></button>
            <button type="button" class="am-row-btn am-row-btn--add" aria-label="Add row"><span class="material-icons material-symbols-rounded" aria-hidden="true">add</span></button>
        </div>
    </td>
</tr>
