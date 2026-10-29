{{-- Template: Case Study Format — two cases, one per page, each a passage,
     an exhibit table and four questions; 200 marks in 180 minutes.
     Params: $paper. --}}
@include('admin.coe.faculty_question_paper.templates._head', [
    'marks' => 200,
    'minutes' => 180,
    'instructions' => [
        ['Read each case carefully before answering its questions.', 'प्रश्नों के उत्तर देने से पहले प्रत्येक केस को ध्यान से पढ़ें।'],
        ['Answer all questions of both cases.', 'दोनों केसों के सभी प्रश्नों के उत्तर दें।'],
        ['Support your answers with facts from the case.', 'अपने उत्तरों को केस में दिए गए तथ्यों से पुष्ट करें।'],
        ['Write your roll number on the top of each page.', 'प्रत्येक पृष्ठ के शीर्ष पर अपना अनुक्रमांक लिखें।'],
    ],
])

@foreach ([1, 2] as $case)
    @if ($case > 1)
        <hr class="coe-paper-break">
    @endif
    <table class="coe-paper-section">
        <tbody>
            <tr>
                <td class="coe-paper-section__en"><h3>Case Study {{ $case }} (4 × 25 = 100 Marks)</h3></td>
                <td class="coe-paper-section__hi"><h3>केस अध्ययन {{ $case }} (4 × 25 = 100 अंक)</h3></td>
                <td class="coe-paper-marks">(25 Marks Each / प्रत्येक 25 अंक)</td>
            </tr>
        </tbody>
    </table>
    <h4>Case / केस</h4>
    <div class="coe-paper-box">
        <p>Write the case here: the background, the situation, the people involved and the decision to be made.</p>
        <p>केस यहाँ लिखें: पृष्ठभूमि, स्थिति, संबंधित व्यक्ति और लिया जाने वाला निर्णय।</p>
    </div>
    <h4>Exhibit {{ $case }} / प्रदर्श {{ $case }}</h4>
    <table class="coe-paper-exhibit">
        <tbody>
            <tr>
                <td><strong>Particulars / विवरण</strong></td>
                <td><strong>Year 1 / वर्ष 1</strong></td>
                <td><strong>Year 2 / वर्ष 2</strong></td>
                <td><strong>Year 3 / वर्ष 3</strong></td>
            </tr>
            @for ($row = 1; $row <= 3; $row++)
                <tr>
                    <td>Item {{ $row }} / मद {{ $row }}</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
            @endfor
        </tbody>
    </table>
    <h4>Questions / प्रश्न</h4>
    <table class="coe-paper-questions">
        <tbody>
            @for ($no = ($case - 1) * 4 + 1; $no <= $case * 4; $no++)
                <tr>
                    <td class="coe-paper-questions__no"><strong>{{ $no }}.</strong></td>
                    <td>
                        Type question {{ $no }} here.<br>
                        प्रश्न {{ $no }} यहाँ लिखें।
                    </td>
                    <td class="coe-paper-marks">(25)</td>
                </tr>
            @endfor
        </tbody>
    </table>
@endforeach

<p class="coe-paper-end">All the best! / शुभकामनाएँ!</p>
