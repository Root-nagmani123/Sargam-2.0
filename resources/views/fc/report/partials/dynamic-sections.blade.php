{{-- Dynamic form sections for admin student overview (mirrors PDF structure). --}}
@foreach($sections as $sec)
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius:8px;">
            <div class="card-header bg-white border-bottom py-2 px-3 fw-semibold small" style="color:#1a3c6e;">
                <i class="bi bi-journal-text me-1"></i>{{ $sec['title_en'] ?? 'Section' }}
                @if(!empty($sec['title_hi']))
                    <span class="text-muted fw-normal"> | {{ $sec['title_hi'] }}</span>
                @endif
            </div>

            @if(($sec['type'] ?? '') === 'fields' && !empty($sec['rows']))
                <div class="card-body p-0">
                    @php $lastGroup = null; @endphp
                    @foreach($sec['rows'] as $row)
                        @if(!empty($row['group']) && $row['group'] !== $lastGroup)
                            <div class="px-3 py-2 bg-light border-bottom">
                                <span class="text-uppercase small fw-bold text-muted" style="letter-spacing:0.4px;">{{ $row['group'] }}</span>
                            </div>
                            @php $lastGroup = $row['group']; @endphp
                        @endif
                        <div class="d-flex border-bottom px-3 py-2" style="font-size:12px;">
                            <div class="text-muted fw-semibold" style="width:38%;flex-shrink:0;">{{ $row['en'] ?? '' }}</div>
                            <div class="flex-grow-1">
                                @if(!empty($row['file_href']))
                                    <a href="{{ $row['file_href'] }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:11px;">
                                        <i class="bi bi-eye me-1"></i>{{ $row['value'] ?? 'View file' }}
                                    </a>
                                @else
                                    {{ $row['value'] ?? '—' }}
                                @endif
                            </div>
                        </div>
                    @endforeach
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
