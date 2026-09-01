{{-- Level 3: one group's columns, split into Rate Peers / Distribute Marks tabs.

     Both panes are rendered up front and toggled client-side: a group has a
     handful of columns, so fetching the second pane separately would cost a round
     trip to show data already in hand.

     The Distribute Marks pane carries the extra "Buffer Marks for OTs" column.
     That figure is a property of the GROUP, not of each column, which is why it
     repeats down the pane - it is the single pool an OT distributes. --}}
@php
    $bufferMarks = rtrim(rtrim(number_format((float) ($group->buffer_marks ?? 0), 2), '0'), '.');
@endphp

<div class="pec-level pec-level--columns">
    <div class="pec-columns-card">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-3 pec-type-tabs"
            role="tablist" aria-label="Rating type">
            @foreach ($types as $value => $label)
                <li class="nav-item" role="presentation">
                    <button type="button"
                            class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $loop->first ? 'active' : '' }}"
                            data-pec-type="{{ $value }}"
                            data-pec-group="{{ $group->id }}"
                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
                </li>
            @endforeach
        </ul>

        @foreach ($types as $value => $label)
            @php $rows = $byType[$value] ?? collect(); @endphp
            <div class="pec-type-pane {{ $loop->first ? '' : 'd-none' }}"
                 data-pec-pane="{{ $value }}" data-pec-group="{{ $group->id }}">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 w-100 pec-subtable pec-columns-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Column Name</th>
                                <th scope="col" class="text-center">Max Marks</th>
                                <th scope="col" class="text-center">Column Created Date</th>
                                @if ($value === \App\Models\PeerColumn::TYPE_DISTRIBUTE_MARKS)
                                    <th scope="col" class="text-center">Buffer Marks for OTs</th>
                                @endif
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $index => $column)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="pec-col-name">{{ $column->column_name }}</td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format((float) $column->max_marks, 2), '0'), '.') }}</td>
                                    <td class="text-center">{{ optional($column->created_at)->format('d/m/Y') ?: '-' }}</td>
                                    @if ($value === \App\Models\PeerColumn::TYPE_DISTRIBUTE_MARKS)
                                        <td class="text-center">
                                            {{-- Group-level; editable from the Edit modal's Buffer Marks field. --}}
                                            <span class="pec-buffer" data-group-id="{{ $group->id }}">{{ $bufferMarks }}</span>
                                        </td>
                                    @endif
                                    <td class="text-center">
                                        <span class="status-pill badge rounded-1 {{ $column->is_visible ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                            {{ $column->is_visible ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="pe-act-group pe-act-group--wide" role="group" aria-label="Row actions">
                                            <button type="button" class="pe-act pe-act--edit pec-edit-btn"
                                                    data-id="{{ $column->id }}"
                                                    data-column-name="{{ $column->column_name }}"
                                                    data-max-marks="{{ rtrim(rtrim(number_format((float) $column->max_marks, 2), '0'), '.') }}"
                                                    data-has-remarks="{{ $column->has_remarks ? 1 : 0 }}"
                                                    data-evaluation-type="{{ $column->evaluation_type }}"
                                                    data-group-id="{{ $group->id }}"
                                                    data-buffer-marks="{{ $bufferMarks }}">
                                                <span class="pe-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                <span class="pe-act__label">Edit</span>
                                            </button>

                                            <a href="{{ route('admin.peer.reflection-fields.preview', array_filter([
                                                    'course_id' => $group->course_id,
                                                    'event_id'  => $group->event_id,
                                                    'group_id'  => $group->id,
                                               ])) }}"
                                               class="pe-act pe-act--preview" title="Preview the form this column appears on">
                                                <span class="pe-act__icon"><i class="bi bi-clipboard-check" aria-hidden="true"></i></span>
                                                <span class="pe-act__label">Preview<br>Form</span>
                                            </a>

                                            {{-- Driven by the global .status-toggle handler
                                                 (admin_assets/js/custom.js). peer_columns keys on
                                                 `id`, hence data-id_column. No .form-check wrapper:
                                                 that pulls the input -2.375rem left. --}}
                                            <label class="pe-act pe-act--toggle">
                                                <span class="pe-act__icon">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                           data-table="peer_columns" data-column="is_visible"
                                                           data-id_column="id" data-id="{{ $column->id }}"
                                                           @checked($column->is_visible)>
                                                </span>
                                                <span class="pe-act__label">{{ $column->is_visible ? 'Deactivate' : 'Activate' }}</span>
                                            </label>

                                            <button type="button" class="pe-act pe-act--del pec-delete-btn"
                                                    data-id="{{ $column->id }}"
                                                    data-column-name="{{ $column->column_name }}">
                                                <span class="pe-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                <span class="pe-act__label">Delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $value === \App\Models\PeerColumn::TYPE_DISTRIBUTE_MARKS ? 7 : 6 }}"
                                        class="text-center py-4 text-body-secondary">
                                        No {{ $label }} columns for this group yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>
