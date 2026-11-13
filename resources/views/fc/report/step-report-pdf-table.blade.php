@php
    /** @var \App\Models\FC\FcForm $form */
    /** @var \App\Services\FC\FcStepReport $report */
    // Table layout for step reports made of short fields (see FcStepReport::pdfLayout()).
    // Same columns, in the same order, as the on-screen table and the Columns menu.
@endphp
<style>
    /* No @page rule: mPDF mis-parses it — page size and margins come from the controller. */
    body { font-family: sans-serif; font-size: 7.5pt; color: #000; }
    .head { text-align: center; margin-bottom: 6px; }
    .head h1 { font-size: 11pt; color: #0a3d6b; margin: 0 0 2px; }
    .head .sub { font-size: 8.5pt; color: #333; margin: 0; }
    .head .meta { font-size: 7pt; color: #555; margin: 2px 0 0; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th {
        background: #e4ebf2; color: #24486e; border: 0.5pt solid #a6b4c3;
        padding: 3px 3px; text-align: left; font-size: 7pt;
    }
    table.grid td {
        border: 0.5pt solid #c3cedb; padding: 2px 3px; vertical-align: top;
        word-wrap: break-word;
    }
    table.grid tr:nth-child(even) td { background: #fafbfc; }
    .note { margin-top: 6px; font-size: 6.5pt; color: #555; text-align: center; }
    .warn { margin-bottom: 6px; font-size: 7.5pt; color: #8a1f11; text-align: center; font-weight: bold; }
</style>

<div class="head">
    <h1>Lal Bahadur Shastri National Academy of Administration</h1>
    <p class="sub">{{ $report->title() }} Report — {{ $form->form_name }}</p>
    <p class="meta">Printed {{ $printedAt }} · {{ count($rows) }} trainee(s)</p>
</div>

@if ($truncated)
    <p class="warn">
        Showing the first {{ number_format($maxRows) }} records only — this selection has more.
        Narrow the filters, or use the Excel export.
    </p>
@endif

<table class="grid">
    <thead>
        <tr>
            <th style="width:22px;">S.No.</th>
            @foreach ($columns as $column)
                <th>{{ $column['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                @foreach ($columns as $key => $column)
                    @php $value = trim((string) ($row->{$key} ?? '')); @endphp
                    <td>{{ $value !== '' ? $value : '—' }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

<p class="note">Computer-generated document from Sargam FC Registration module.</p>
