{{--
    The info sheet on its own page, for the standalone Weekly Info download.
    Inside the weekly timetable PDF the same component is rendered as the back of
    each week's grid; this wrapper just gives it a document of its own.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $courseName ? $courseName . ' — ' : '' }}Course Information</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; box-sizing: border-box; }
        @page { size: A4 landscape; margin: 8mm 7mm 10mm 7mm; }
        body { margin: 0; color: #000; font-size: 8px; }

        .tt-head { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .tt-head td { vertical-align: middle; }
        .tt-head-logo { width: 92px; text-align: center; }
        .tt-head-logo img { max-height: 56px; max-width: 88px; }
        .tt-head-mid { text-align: center; padding: 0 6px; }
        .tt-head-hi { height: 13px; width: auto; margin-bottom: 1px; }
        .tt-head-en { font-size: 11.5px; font-weight: bold; line-height: 1.3; }
        .tt-head-course { font-size: 9.5px; font-weight: bold; margin-top: 2px; }
        .tt-head-dates { font-size: 9px; font-weight: bold; margin-top: 1px; }
        .tt-head-week { font-size: 9px; font-weight: bold; margin-top: 2px; }

        .is-cols { width: 100%; border-collapse: collapse; }
        .is-col { width: 50%; vertical-align: top; padding: 0 3px; }
        .is-box { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .is-box th, .is-box td { border: 0.75px solid #000; padding: 2px 4px; }
        .is-box th { background: #e2efd9; text-align: center; font-size: 7.5px; font-weight: bold; }
        .is-c { text-align: center; font-size: 7px; }
        .is-l { text-align: left; font-size: 7px; }
        .is-abbr { text-align: left; font-size: 7px; font-weight: bold; white-space: nowrap; width: 78px; }
        .is-free { font-size: 7px; line-height: 1.45; padding: 3px 5px; }

        .is-guests { padding: 3px 4px; }
        .is-guest-list { width: 100%; border-collapse: collapse; }
        .is-guest-list td { border: none; vertical-align: top; padding: 1px 0; font-size: 6.8px; line-height: 1.4; }
        .is-guest-num { width: 14px; font-weight: bold; }
        .is-guest-name { font-weight: bold; }
        .is-guest-mod { display: block; }

        .is-sign { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .is-sign td { border: none; font-size: 7.5px; vertical-align: bottom; }
        .is-sign-date { text-align: left; font-weight: bold; }
        .is-sign-who { text-align: right; }
        .is-sign-name { font-weight: bold; }

        .tt-empty { text-align: center; padding: 40px; color: #555; font-size: 11px; }
    </style>
</head>
<body>

    <x-timetable.header
        :title-hindi="$titleHindi"
        :logo-left="$logoLeft"
        :logo-right="$logoRight"
        :institute-name="$instituteName"
        :course-name="$courseName"
        :course-duration="$courseDuration" />

    <x-timetable.info-sheet :sheet="$sheet" />

</body>
</html>
