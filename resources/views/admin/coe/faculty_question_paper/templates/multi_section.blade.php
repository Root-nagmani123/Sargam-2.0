{{-- Template: Multi-Section Paper — candidate line, then objective, short and
     long sections, each with a suggested time; 150 marks in 180 minutes.
     Params: $paper. --}}
@include('admin.coe.faculty_question_paper.templates._head', [
    'candidate' => true,
    'marks' => 150,
    'minutes' => 180,
    'instructions' => [
        ['Answer all three sections.', 'तीनों खंडों के उत्तर दें।'],
        ['Keep to the suggested time shown for each section.', 'प्रत्येक खंड के लिए दिए गए सुझाए गए समय का पालन करें।'],
        ['In Section A, write the letter of the correct option.', 'खंड क में सही विकल्प का अक्षर लिखें।'],
        ['Write your roll number on the top of each page.', 'प्रत्येक पृष्ठ के शीर्ष पर अपना अनुक्रमांक लिखें।'],
    ],
])

@include('admin.coe.faculty_question_paper.templates._section', [
    'letter' => 'A', 'kind' => ['Multiple Choice Questions', 'बहुविकल्पीय प्रश्न'], 'count' => 10, 'marks' => 2, 'start' => 1,
    'minutes' => 20, 'mcq' => true,
])
@include('admin.coe.faculty_question_paper.templates._section', [
    'letter' => 'B', 'kind' => ['Short Answer Questions', 'लघु उत्तरीय प्रश्न'], 'count' => 10, 'marks' => 5, 'start' => 11,
    'minutes' => 60,
])
@include('admin.coe.faculty_question_paper.templates._section', [
    'letter' => 'C', 'kind' => ['Long Answer Questions', 'दीर्घ उत्तरीय प्रश्न'], 'count' => 8, 'marks' => 10, 'start' => 21,
    'minutes' => 100,
])

<p class="coe-paper-end">All the best! / शुभकामनाएँ!</p>
