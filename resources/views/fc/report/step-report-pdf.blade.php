@php
    /** @var \App\Models\FC\FcForm $form */
    /** @var \App\Services\FC\FcStepReport $report */
    // Short columns become the identity block; long text and uploads get their own sections.
    $meta = collect($columns)->filter(fn ($c) => empty($c['long']) && empty($c['file']));
    $prose = collect($columns)->filter(fn ($c) => ! empty($c['long']));
    $files = collect($columns)->filter(fn ($c) => ! empty($c['file']));
@endphp
<style>
    body { font-family: sans-serif; font-size: 9.5pt; color: #111; }
    .hdr { text-align: center; margin-bottom: 10px; }
    .hdr h2 { margin: 0 0 2px; font-size: 14pt; color: #004a93; }
    .hdr .sub { font-size: 9pt; color: #555; }
    .note { background: #fff4e5; border: 1px solid #ffd8a8; padding: 5px 8px; font-size: 8.5pt; margin-bottom: 8px; }
    .entry { border: 1px solid #d0d5dd; border-radius: 3px; padding: 7px 9px; margin-bottom: 8px; }
    .entry .who { font-size: 10pt; font-weight: bold; color: #004a93; margin-bottom: 3px; }
    table.meta { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
    table.meta td { font-size: 8.5pt; color: #444; padding: 1px 6px 1px 0; }
    table.meta td.k { color: #777; width: 110px; }
    .block { margin-top: 5px; }
    .block .lbl { font-size: 8pt; color: #777; text-transform: uppercase; letter-spacing: .02em; margin-bottom: 1px; }
    .statement { font-size: 9.5pt; line-height: 1.45; text-align: justify; white-space: pre-line; }
    .missing { font-size: 9pt; color: #b42318; font-style: italic; }
</style>

<div class="hdr">
    <h2>{{ $report->title() }}</h2>
    <div class="sub">
        {{ $form->form_name }} &nbsp;|&nbsp; {{ count($rows) }} trainee(s) &nbsp;|&nbsp; Printed {{ $printedAt }}
    </div>
</div>

@if ($truncated)
    <div class="note">
        Showing the first {{ number_format($maxRows) }} records only — this selection has more.
        Narrow the filters to export the rest.
    </div>
@endif

{{-- One block per trainee rather than a grid: free text of this length in a table cell is
     unreadable, and this is the shape a reader actually wants. --}}
@foreach ($rows as $i => $row)
    <div class="entry">
        <div class="who">
            {{ $i + 1 }}.
            {{ trim((string) ($row->display_name ?? '')) !== '' ? $row->display_name : '—' }}
        </div>

        @if ($meta->isNotEmpty())
            <table class="meta">
                @foreach ($meta as $key => $column)
                    @php $value = trim((string) ($row->{$key} ?? '')); @endphp
                    <tr>
                        <td class="k">{{ $column['label'] }}</td>
                        <td>{{ $value !== '' ? $value : '—' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        @foreach ($prose as $key => $column)
            @php $text = trim((string) ($row->{$key} ?? '')); @endphp
            <div class="block">
                <div class="lbl">{{ $column['label'] }}</div>
                @if ($text !== '')
                    <div class="statement">{{ $text }}</div>
                @else
                    <div class="missing">Not submitted.</div>
                @endif
            </div>
        @endforeach

        @foreach ($files as $key => $column)
            @php $path = trim((string) ($row->{$key} ?? '')); @endphp
            <div class="block">
                <div class="lbl">{{ $column['label'] }}</div>
                {{-- A short clickable link, not the raw address. The upload URL carries a ~300
                     character encrypted token; printed in full it swamps every entry and is far
                     too long to retype anyway, so nothing is lost by hiding it behind the text.
                     mPDF keeps the anchor live, so it still opens from the on-screen PDF. --}}
                @if ($path !== '')
                    <div style="font-size:9pt;">
                        <a href="{{ \App\Support\FC\FcUploadUrl::for($path, \App\Http\Controllers\FC\StepReportController::FILE_PATH) }}" style="color:#004a93;">Open document</a>
                        <span style="color:#777;">({{ \Illuminate\Support\Str::limit(basename($path), 40) }})</span>
                    </div>
                @else
                    <div class="missing">No document uploaded.</div>
                @endif
            </div>
        @endforeach
    </div>
@endforeach
