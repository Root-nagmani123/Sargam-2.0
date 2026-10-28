{{-- Add Question Paper → a template's top half, in English and Hindi: the
     title block, the paper's particulars and the instructions. Values come from
     the paper record; Hindi names are not stored anywhere, so the faculty types
     them over the bracketed placeholders. Styles: .coe-paper-* in coe-admin.css.
     Params: $paper (a FacultyQuestionPaperController row), $marks, $minutes,
             $instructions ([[English, Hindi], ...]), $candidate (bool, optional). --}}
@php
    $hindiMonths = ['जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
    $date = $paper['exam_date'];
    $course = $paper['course_short'] !== '' && $paper['course_short'] !== $paper['course']
        ? $paper['course_short'].' - '.$paper['course']
        : $paper['course'];
    $courseHi = ($paper['course_short'] !== '' ? $paper['course_short'].' - ' : '').'[पाठ्यक्रम का नाम हिंदी में]';
    $subjectHi = '[विषय का नाम हिंदी में]';
@endphp
@if (! empty($candidate))
    <table class="coe-paper-candidate">
        <tbody>
            <tr>
                <td><strong>Candidate Name / अभ्यर्थी का नाम:</strong> ____________________</td>
                <td class="coe-paper-candidate__roll"><strong>Roll No. / अनुक्रमांक:</strong> ____________</td>
            </tr>
        </tbody>
    </table>
@endif
<table class="coe-paper-head">
    <tbody>
        <tr>
            <td class="coe-paper-head__gov">भारत सरकार</td>
            <td class="coe-paper-head__title">
                <h2>{{ $paper['examination'] }}</h2>
                <p class="coe-paper-head__subject">{{ $paper['name'] }}</p>
                <p class="coe-paper-head__subject">{{ $subjectHi }}</p>
                <p class="coe-paper-head__code">{{ $paper['code'] }}</p>
            </td>
            <td class="coe-paper-head__time">
                <p><strong>Time: {{ $minutes }} Minutes</strong></p>
                <p><strong>Maximum Marks: {{ $marks }}</strong></p>
                <p>समय: {{ $minutes }} मिनट</p>
                <p>अधिकतम अंक: {{ $marks }}</p>
            </td>
        </tr>
    </tbody>
</table>

<table class="coe-paper-meta">
    <tbody>
        <tr>
            <td><strong>Course:</strong> {{ $course }}</td>
            <td><strong>Date:</strong> {{ $date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td>पाठ्यक्रम: {{ $courseHi }}</td>
            <td>दिनांक: {{ $date->format('d') }} {{ $hindiMonths[$date->month - 1] }} {{ $date->format('Y') }}</td>
        </tr>
        <tr>
            <td><strong>Batch:</strong> {{ $paper['batch'] }}</td>
            <td><strong>Subject:</strong> {{ $paper['name'] }}</td>
        </tr>
        <tr>
            <td>बैच: {{ $paper['batch'] }}</td>
            <td>विषय: {{ $subjectHi }}</td>
        </tr>
    </tbody>
</table>

<table class="coe-paper-instructions">
    <tbody>
        <tr>
            <td>
                <h4>Instructions:</h4>
                <ol>
                    @foreach ($instructions as [$english])
                        <li>{{ $english }}</li>
                    @endforeach
                </ol>
            </td>
            <td>
                <h4>निर्देश:</h4>
                <ol>
                    @foreach ($instructions as [, $hindi])
                        <li>{{ $hindi }}</li>
                    @endforeach
                </ol>
            </td>
        </tr>
    </tbody>
</table>
