<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * COE → Examination Management → Result Calculation.
 *
 * Design-first. Data is only loaded when both Course Name and Subject filters
 * are provided; otherwise the empty "Use Filter to get the data" state is shown.
 */
class ResultCalculationController extends Controller
{
    private const COURSES = ['Phase IV 2025', 'Phase III 2025', 'Foundation Course 2026'];

    private const SUBJECTS = [
        'Law', 'Ethics & Integrity', 'Leadership', 'Discipline', 'Public Policy',
    ];

    /** Sample drives keyed by course → subject */
    private const SAMPLE = [
        'Phase IV 2025' => [
            'Law' => [
                'drive'   => 'Law Drive',
                'term'    => 'Annual Term',
                'course'  => 'Phase IV 2025',
                'session' => '2025',
                'subject' => 'Law',
                'components' => [
                    ['component' => 'Ethics & Integrity', 'max_marks' => 100, 'passing_marks' => 40.00, 'source' => 'Faculty',  'faculty' => 'John Doe',   'weightage' => 40.00, 'exam_type' => 'Online'],
                    ['component' => 'Discipline',         'max_marks' => 100, 'passing_marks' => 40.00, 'source' => 'Sargam',   'faculty' => 'Sargam',    'weightage' => 60.00, 'exam_type' => 'Offline'],
                ],
            ],
        ],
    ];

    public function index(Request $request)
    {
        $course  = $request->query('course', '');
        $subject = $request->query('subject', '');

        $drive = null;
        if ($course && $subject) {
            $drive = self::SAMPLE[$course][$subject] ?? null;
        }

        return view('admin.coe.result_calculation.index', [
            'courses'  => self::COURSES,
            'subjects' => self::SUBJECTS,
            'course'   => $course,
            'subject'  => $subject,
            'drive'    => $drive,
        ]);
    }
}
