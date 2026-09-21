<?php

/*
 * COE Examination - shared configuration for the Question Paper module.
 *
 * The Examination Drive tables are built by a separate developer on a parallel
 * branch. Their names and key columns are read from here rather than hard-coded
 * so a rename on that side is a one-line change, not a sweep through migrations,
 * models and queries. Note the key columns differ: examination_drives uses `id`
 * while the Sargam masters it points at use `pk`.
 */
return [

    'question_paper' => [

        /*
         * Where uploaded papers live. Deliberately NOT the public disk - a
         * question paper leaking before the exam is the one failure this module
         * exists to prevent. Files are served only through a controller that
         * checks the role and the assignment first.
         */
        'disk' => 'coe_private',
        'path' => 'question-papers',

        // Upload validation. Extension and MIME are both checked: an extension
        // alone is caller-supplied text, and a MIME alone can be spoofed.
        'allowed_extensions' => ['pdf', 'doc', 'docx'],
        'allowed_mimes' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'max_file_size_kb' => 20480, // 20 MB

        /*
         * Only components whose marks come from a written exam need a question
         * paper. Values must match ExaminationDriveComponentMap::SOURCES
         * ('Faculty', 'System', 'Manual Entry'): 'Faculty' is the faculty-marked
         * written component, while 'System' comes from Moodle and 'Manual Entry'
         * is keyed in by the office - neither of those is sat as a paper.
         *
         * Re-check this list if the Examination Drive module adds a source.
         */
        'paper_required_sources' => ['Faculty'],

        // Languages a paper can exist in. 'EN' is the original the faculty
        // uploads; the rest are produced by the Raj Bhasha section.
        'languages' => [
            'EN' => 'English',
            'HI' => 'Hindi',
        ],
        'original_language' => 'EN',

        /*
         * Who does what, by role name.
         *
         * The document lists these roles ("Examination Section", "Translation
         * Section", "Approving Authority") but they do not exist in Sargam yet -
         * roles are Milestone 1 and this module is Milestone 7. Until they are
         * created, only Super Admin matches, so a Super Admin can drive the
         * whole workflow on a test system.
         *
         * Translation is done by the Raj Bhasha (official language) section;
         * both names are listed so whichever the institute actually creates
         * will match. Add the real role names here rather than in the code
         * when Milestone 1 settles them.
         */
        'roles' => [
            'exam_section' => [
                'Super Admin',
                'SuperAdmin',
                'Examination Section',
                'Examination Administrator',
            ],

            'translation_section' => [
                'Super Admin',
                'SuperAdmin',
                'Raj Bhasha',
                'Raj Bhasha Section',
                'Translation Section',
            ],

            'approver' => [
                'Super Admin',
                'SuperAdmin',
                'Approving Authority',
                'Examination Administrator',
            ],
        ],
    ],

];
