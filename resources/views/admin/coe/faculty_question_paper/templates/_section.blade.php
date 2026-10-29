{{-- Add Question Paper → one section of a template: the tinted heading band
     (English · Hindi · marks each), the kind of question, and placeholder
     questions in both languages for the faculty to type over.
     Params: $letter ('A'...), $kind ([English, Hindi]), $count, $marks (each),
             $start (first question number), $minutes (optional, suggested time),
             $mcq (bool, optional: adds an options line under each question). --}}
@php
    $hindiLetter = ['A' => 'क', 'B' => 'ख', 'C' => 'ग', 'D' => 'घ'][$letter] ?? $letter;
    $total = $count * $marks;
@endphp
<table class="coe-paper-section">
    <tbody>
        <tr>
            <td class="coe-paper-section__en"><h3>Section {{ $letter }} ({{ $count }} × {{ $marks }} = {{ $total }} Marks)</h3></td>
            <td class="coe-paper-section__hi"><h3>खंड {{ $hindiLetter }} ({{ $count }} × {{ $marks }} = {{ $total }} अंक)</h3></td>
            <td class="coe-paper-marks">
                ({{ $marks }} Marks Each / प्रत्येक {{ $marks }} अंक)
                @if (! empty($minutes))
                    <br>Suggested Time: {{ $minutes }} Minutes / सुझाया गया समय: {{ $minutes }} मिनट
                @endif
            </td>
        </tr>
    </tbody>
</table>
<h4>{{ $kind[0] }} / {{ $kind[1] }}</h4>
<table class="coe-paper-questions">
    <tbody>
        @for ($no = $start; $no < $start + $count; $no++)
            <tr>
                <td class="coe-paper-questions__no"><strong>{{ $no }}.</strong></td>
                <td>
                    Type question {{ $no }} here.<br>
                    प्रश्न {{ $no }} यहाँ लिखें।
                    @if (! empty($mcq))
                        <br>(a) __________ &nbsp; (b) __________ &nbsp; (c) __________ &nbsp; (d) __________
                    @endif
                </td>
                <td class="coe-paper-marks">({{ $marks }})</td>
            </tr>
        @endfor
    </tbody>
</table>
