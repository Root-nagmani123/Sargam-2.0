{{-- Template: Standard Question Paper — single column, three sections, 100
     marks (as designed in Figma, "Add Question Paper"). Params: $paper. --}}
@include('admin.coe.faculty_question_paper.templates._head', [
    'marks' => 100,
    'minutes' => 120,
    'instructions' => [
        ['Answer all questions.', 'सभी प्रश्नों के उत्तर दें।'],
        ['All questions carry equal marks.', 'सभी प्रश्नों के अंक समान हैं।'],
        ['Write your roll number on the top of each page.', 'प्रत्येक पृष्ठ के शीर्ष पर अपना अनुक्रमांक लिखें।'],
    ],
])

@include('admin.coe.faculty_question_paper.templates._section', [
    'letter' => 'A', 'kind' => ['Short Answer Questions', 'लघु उत्तरीय प्रश्न'], 'count' => 10, 'marks' => 2, 'start' => 1,
])
@include('admin.coe.faculty_question_paper.templates._section', [
    'letter' => 'B', 'kind' => ['Long Answer Questions', 'दीर्घ उत्तरीय प्रश्न'], 'count' => 5, 'marks' => 6, 'start' => 11,
])
@include('admin.coe.faculty_question_paper.templates._section', [
    'letter' => 'C', 'kind' => ['Detailed Answer Questions', 'विस्तृत उत्तरीय प्रश्न'], 'count' => 5, 'marks' => 10, 'start' => 16,
])

<p class="coe-paper-end">All the best! / शुभकामनाएँ!</p>
