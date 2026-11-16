{{-- Template: OMR Answer Sheet — the bubble sheet for an objective paper:
     instructions in English and Hindi, the marking example, the paper's
     particulars (subject, course / batch, date - filled from the paper record),
     a 10-digit roll number grid and 100 questions of four options.
     Tables, spans and classes only: PaperHtmlSanitizer drops inline styles.
     Styles: .coe-omr-* in coe-admin.css. Params: $paper. --}}
@php
    $instructions = [
        ['Mark your answer as shown in the example alongside.', 'प्रश्नों के उत्तर केवल निर्दिष्ट स्थान पर ही अंकित करें।'],
        ['Appropriate circle should be darkened completely.', 'उत्तर को पूर्ण रूप से काला करें।'],
        ['There are no negative marks for wrong answers.', 'गलत उत्तर पर कोई नकारात्मक अंक नहीं हैं।'],
        ['Answer marked once should not be changed.', 'एक प्रश्न के उत्तर को एक बार ही अंकित करें।'],
        ['Do not use eraser or whitener to hide the entries.', 'इरेज़र या व्हाइटनर का प्रयोग न करें।'],
        ['More than one circle for a question will be considered wrong and no marks will be given.', 'एक से अधिक गोले पर निशान लगाने पर प्रश्न रद्द कर दिया जाएगा।'],
        ['If the answer is in oval, it must be darkened in Black Ball Point Pen only.', 'उत्तर हेतु केवल काले बॉल प्वाइंट पेन का उपयोग करें।'],
        ['Do not make any stray marks or rough work on this OMR Sheet.', 'अनावश्यक चिन्ह, रेखाएँ या कोई अन्य कार्य न करें।'],
        ['Do not fold or spoil the answer sheet. Answer sheet given by the invigilator is final.', 'इस उत्तर पत्रक को न मोड़ें और न ही क्षतिग्रस्त करें।'],
        ['Note down your answer sheet number on the present report and put your signature in front of your name.', 'उपस्थिति कुंडली में अपना उत्तर पत्रक क्रमांक लिखें तथा हस्ताक्षर करें।'],
    ];
    $options = ['A', 'B', 'C', 'D'];
    $course = $paper['course'].($paper['batch'] !== '' ? ' / '.$paper['batch'] : '');
@endphp
<div class="coe-omr">
    <table class="coe-omr-head">
        <tbody>
            <tr>
                {{-- Logos from the web (https - CSP allows it), so a saved paper shows them on any server.
                     The LBSNAA image is emblem + name; .coe-omr-emblem crops it to the emblem. --}}
                <td class="coe-omr-head__logo"><img class="coe-omr-emblem" src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA emblem"></td>
                <td class="coe-omr-head__title">
                    <p class="coe-omr-head__hi">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी</p>
                    <p class="coe-omr-head__en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</p>
                    <p>{{ $paper['examination'] }}</p>
                    <p class="coe-omr-head__note">PLEASE FILL ALL THE ENTRIES WITH BLACK/BLUE BALL POINT PEN ONLY</p>
                </td>
                <td class="coe-omr-head__logo coe-omr-head__logo--right"><img src="https://upload.wikimedia.org/wikipedia/commons/e/ee/Azadi-Ka-Amrit-Mahotsav-Logo.png" alt="Azadi Ka Amrit Mahotsav"></td>
            </tr>
        </tbody>
    </table>

    {{-- The set, centred on a rule. --}}
    <table class="coe-omr-ruled">
        <tbody>
            <tr>
                <td class="coe-omr-ruled__line">&nbsp;</td>
                <td class="coe-omr-ruled__mid"><span class="coe-omr-set">Set : A</span></td>
                <td class="coe-omr-ruled__line">&nbsp;</td>
            </tr>
        </tbody>
    </table>

    <table class="coe-omr-rules">
        <tbody>
            <tr>
                <td>
                    <p class="coe-omr-boxhead">INSTRUCTIONS FOR ENTERING ANSWERS</p>
                    <ol>
                        @foreach ($instructions as [$english])
                            <li>{{ $english }}</li>
                        @endforeach
                    </ol>
                </td>
                <td class="coe-omr-rules__example">
                    <p class="coe-omr-boxhead">Example for Entering Answers (Correct)</p>
                    <p class="coe-omr-sub">Correct Method (सही तरीका)</p>
                    <p><span class="coe-omr-b is-filled">A</span><span class="coe-omr-b">B</span><span class="coe-omr-b">C</span><span class="coe-omr-b">D</span></p>
                    <p class="coe-omr-sub">Wrong Method (गलत तरीका)</p>
                    <p><span class="coe-omr-b">✗</span><span class="coe-omr-b">B</span><span class="coe-omr-b">C</span><span class="coe-omr-b">D</span></p>
                    <p><span class="coe-omr-b">A</span><span class="coe-omr-b">✓</span><span class="coe-omr-b">C</span><span class="coe-omr-b">D</span></p>
                    <p><span class="coe-omr-b">A</span><span class="coe-omr-b">B</span><span class="coe-omr-b is-filled">C</span><span class="coe-omr-b is-filled">D</span></p>
                </td>
                <td>
                    <p class="coe-omr-boxhead">महत्वपूर्ण निर्देश</p>
                    <ol>
                        @foreach ($instructions as [, $hindi])
                            <li>{{ $hindi }}</li>
                        @endforeach
                    </ol>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="coe-omr-details">
        <tbody>
            <tr>
                <td class="coe-omr-details__left">
                    <p class="coe-omr-label coe-omr-label--ink">Subject / Topic</p>
                    <p class="coe-omr-field">{{ $paper['name'] }}</p>
                    <p class="coe-omr-label coe-omr-label--ink">Name of Course / Batch</p>
                    <p class="coe-omr-field">{{ $course }}</p>
                    <p class="coe-omr-label coe-omr-label--ink coe-omr-center">ROLL NUMBER</p>
                    <table class="coe-omr-roll">
                        <tbody>
                            <tr>
                                @for ($col = 0; $col < 10; $col++)
                                    <td class="coe-omr-roll__box">&nbsp;</td>
                                @endfor
                            </tr>
                            @for ($digit = 0; $digit <= 9; $digit++)
                                <tr>
                                    @for ($col = 0; $col < 10; $col++)
                                        <td><span class="coe-omr-b">{{ $digit }}</span></td>
                                    @endfor
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </td>
                <td>
                    <p class="coe-omr-label">NAME OF THE VENUE IN CAPITAL LETTERS</p>
                    <p class="coe-omr-field">&nbsp;</p>
                    <table class="coe-omr-pair">
                        <tbody>
                            <tr>
                                <td>
                                    <p class="coe-omr-label coe-omr-center">MEDIUM</p>
                                    <p class="coe-omr-medium">ENGLISH <span class="coe-omr-b">&nbsp;</span></p>
                                    <p class="coe-omr-medium">HINDI <span class="coe-omr-b">&nbsp;</span></p>
                                </td>
                                <td>
                                    <p class="coe-omr-label coe-omr-center">CANDIDATE'S SIGNATURE</p>
                                    <p class="coe-omr-field coe-omr-field--tall">&nbsp;</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p class="coe-omr-label coe-omr-center">DATE OF EXAMINATION</p>
                                    <p class="coe-omr-field coe-omr-center"><strong>{{ $paper['exam_date']->format('d/m/Y') }}</strong></p>
                                </td>
                                <td>
                                    <p class="coe-omr-label coe-omr-center">INVIGILATOR'S SIGNATURE</p>
                                    <p class="coe-omr-field">&nbsp;</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="coe-omr-answers">
        <tbody>
            <tr>
                @for ($block = 0; $block < 4; $block++)
                    <td>
                        <table class="coe-omr-grid">
                            <tbody>
                                @for ($no = $block * 25 + 1; $no <= ($block + 1) * 25; $no++)
                                    <tr>
                                        <td class="coe-omr-grid__no">{{ $no }}</td>
                                        <td>@foreach ($options as $option)<span class="coe-omr-b">{{ $option }}</span>@endforeach</td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </td>
                @endfor
            </tr>
        </tbody>
    </table>

    <table class="coe-omr-foot">
        <tbody>
            <tr>
                <td class="coe-omr-foot__code"><span class="coe-omr-code">{{ $paper['code'] }}</span></td>
                <td class="coe-omr-ruled__line">&nbsp;</td>
                <td class="coe-omr-ruled__mid"><strong>A</strong></td>
                <td class="coe-omr-ruled__line">&nbsp;</td>
                <td class="coe-omr-foot__code">&nbsp;</td>
            </tr>
        </tbody>
    </table>
</div>
