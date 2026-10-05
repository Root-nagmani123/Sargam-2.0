{{-- COE → Add Question Paper → Create: the starting document loaded into the
     editor. Header and particulars come from the paper record; the faculty
     replaces the placeholder questions. Plain tables keep the layout, because
     the editor (Summernote) edits HTML as it is. Styles: .coe-qp-editor in
     public/css/coe-admin.css.
     Params: $paper (a FacultyQuestionPaperController row). --}}
@php
    // [letter, kind of question, marks per question, placeholder rows]
    $sections = [
        ['A', 'Short Answer Questions', 2, 3],
        ['B', 'Long Answer Questions', 6, 3],
        ['C', 'Detailed Answer Questions', 10, 3],
    ];
    $questionNo = 0;
@endphp
<table class="coe-paper-head">
    <tbody>
        <tr>
            <td class="coe-paper-head__emblem">
                <img src="{{ asset('images/ashoka.png') }}" alt="Emblem of India" width="64" height="64">
            </td>
            <td class="coe-paper-head__title">
                <h2>{{ $paper['examination'] }}</h2>
                <p class="coe-paper-head__subject">{{ $paper['name'] }}</p>
                <p class="coe-paper-head__code">{{ $paper['code'] }}</p>
            </td>
            <td class="coe-paper-head__time">
                <p><strong>Time:</strong> {{ $paper['duration'] }} Minutes</p>
                <p><strong>Maximum Marks:</strong> {{ $paper['max_marks'] }}</p>
            </td>
        </tr>
    </tbody>
</table>

<table class="coe-paper-meta">
    <tbody>
        <tr>
            <td class="coe-paper-meta__key">Course</td>
            <td>: {{ $paper['course'] }}</td>
            <td class="coe-paper-meta__key">Date</td>
            <td>: {{ $paper['exam_date']->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="coe-paper-meta__key">Batch</td>
            <td>: {{ $paper['batch'] }}</td>
            <td class="coe-paper-meta__key">Subject</td>
            <td>: {{ $paper['name'] }}</td>
        </tr>
    </tbody>
</table>

<div class="coe-paper-box">
    <p><strong>Instructions:</strong></p>
    <ol>
        <li>Answer all questions.</li>
        <li>Marks for each question are shown against it.</li>
        <li>Write your roll number on the top of each page.</li>
    </ol>
</div>

@foreach ($sections as [$letter, $kind, $marks, $rows])
    <table class="coe-paper-section">
        <tbody>
            <tr>
                <td><h3><u>Section {{ $letter }}</u></h3></td>
                <td class="coe-paper-marks">({{ $marks }} Marks Each)</td>
            </tr>
        </tbody>
    </table>
    <h4>{{ $kind }}</h4>
    <table class="coe-paper-questions">
        <tbody>
            @for ($i = 0; $i < $rows; $i++)
                @php $questionNo++; @endphp
                <tr>
                    <td class="coe-paper-questions__no">{{ $questionNo }}.</td>
                    <td>Type question {{ $questionNo }} here.</td>
                    <td class="coe-paper-marks">({{ $marks }})</td>
                </tr>
            @endfor
        </tbody>
    </table>
@endforeach

<p class="coe-paper-end">All the best!</p>
