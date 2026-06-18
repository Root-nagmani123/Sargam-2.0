{{-- Dynamic form sections for admin student overview (mirrors PDF structure). --}}
@foreach($sections as $sec)
    <div class="col-12">
        <div class="card dyn-section border-0 shadow-sm" style="border-radius:8px;overflow:hidden;">
            <div class="card-header dyn-section-hd py-2 px-3 fw-semibold small d-flex justify-content-between align-items-center"
                 style="background:#0a3d6b;color:#fff;">
                <span><i class="bi bi-journal-text me-1"></i>{{ $sec['title_en'] ?? 'Section' }}</span>
                @if(!empty($sec['title_hi']))
                    <span class="fw-normal" style="font-size:10px;opacity:.85;">{{ $sec['title_hi'] }}</span>
                @endif
            </div>

            @if(($sec['type'] ?? '') === 'fields' && !empty($sec['rows']))
                <div class="card-body p-0">
                    <div class="section-fields">
                        @php $lastGroup = null; @endphp
                        @foreach($sec['rows'] as $row)
                            @if(!empty($row['group']) && $row['group'] !== $lastGroup)
                                <div class="field-group-hd px-3 py-1 bg-light border-bottom"
                                     style="font-size:11px;font-weight:600;color:#1a3c6e;letter-spacing:.3px;">
                                    {{ $row['group'] }}
                                </div>
                                @php $lastGroup = $row['group']; @endphp
                            @endif
                            <div class="field-row border-bottom" style="font-size:12px;display:flex;">
                                <div class="field-lbl fw-semibold px-3 py-2"
                                     style="width:40%;flex-shrink:0;background:#f0f4f9;color:#0a2a50;">{{ $row['en'] ?? '' }}</div>
                                <div class="field-val flex-grow-1 px-3 py-2">
                                    @if(!empty($row['file_href']))
                                        <a href="{{ $row['file_href'] }}" target="_blank" rel="noopener"
                                           class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:11px;">
                                            <i class="bi bi-eye me-1"></i>{{ $row['value'] ?? 'View file' }}
                                        </a>
                                    @else
                                        {{ $row['value'] ?? 'â' }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif(($sec['type'] ?? '') === 'table' && !empty($sec['body']))
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="font-size:12px;">
                        <thead class="table-light">
                            <tr>
                                @foreach($sec['columns'] ?? [] as $col)
                                    <th>{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sec['body'] as $tr)
                                <tr>
                                    @foreach($tr as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endforeach
