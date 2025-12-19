<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Course Details - {{ $course->course_name }}</title>

<style>
/* ======================================================
   RESET & BASE (PDF SAFE)
====================================================== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "DejaVu Sans", sans-serif;
    font-size: 12px;
    color: #222;
    line-height: 1.6;
    padding: 20px;
    background: #ffffff;
}

/* ======================================================
   HEADER
====================================================== */
.course-header {
    background: #004a93;
    color: #ffffff;
    padding: 18px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
    text-align: center;
}

.course-header h1 {
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 6px;
}

.course-header .subtitle {
    font-size: 13px;
    opacity: 0.95;
}

/* ======================================================
   SECTION TITLE
====================================================== */
.section-title {
    font-size: 15px;
    font-weight: bold;
    color: #004a93;
    margin: 25px 0 12px;
    padding-bottom: 6px;
    border-bottom: 2px solid #004a93;
}

/* ======================================================
   INFO TABLE
====================================================== */
.info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.info-table td {
    border: 1px solid #dcdcdc;
    padding: 9px 10px;
    vertical-align: top;
}

.info-table td:first-child {
    width: 200px;
    background: #f5f7fa;
    font-weight: bold;
    color: #333;
}

/* ======================================================
   DATE TAG
====================================================== */
.date-info {
    display: inline-block;
    background: #e7f3ff;
    border: 1px solid #cfe3ff;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: 600;
    margin: 3px 2px;
}

/* ======================================================
   ROLE BADGE
====================================================== */
.role-badge {
    display: inline-block;
    background: #004a93;
    color: #ffffff;
    padding: 6px 16px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 10px;
}

/* ======================================================
   PHOTOS
====================================================== */
.photo-container {
    text-align: center;
    margin: 15px 0;
    page-break-inside: avoid;
}

.photo-frame {
    display: inline-block;
    border: 2px solid #004a93;
    padding: 6px;
    background: #ffffff;
    margin-bottom: 6px;
}

.photo-frame img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    display: block;
}

.photo-placeholder {
    width: 120px;
    height: 120px;
    background: #e9ecef;
    line-height: 120px;
    font-size: 36px;
    color: #777;
    text-align: center;
}

/* ======================================================
   NAMES
====================================================== */
.person-name {
    margin-top: 6px;
    font-size: 12px;
    font-weight: bold;
    color: #004a93;
}

/* ======================================================
   ASSISTANT GRID
====================================================== */
.assistant-section {
    margin-top: 20px;
    page-break-inside: avoid;
}

.assistant-grid {
    width: 100%;
    text-align: center;
}

.assistant-item {
    display: inline-block;
    width: 30%;
    margin: 10px 1%;
    vertical-align: top;
}

/* ======================================================
   FOOTER
====================================================== */
.footer {
    margin-top: 30px;
    text-align: center;
    font-size: 10px;
    color: #555;
    border-top: 1px solid #cccccc;
    padding-top: 8px;
}
</style>
</head>

<body>

<!-- =========================
     COURSE HEADER
========================= -->
<div class="course-header">
    <h1>{{ $course->course_name }}</h1>
    <div class="subtitle">
        {{ $course->couse_short_name }} | Course Year: {{ $course->course_year }}
    </div>
</div>

<!-- =========================
     COURSE INFORMATION
========================= -->
<h2 class="section-title">Course Information</h2>

<table class="info-table">
    <tr>
        <td>Course Name</td>
        <td>{{ $course->course_name }}</td>
    </tr>
    <tr>
        <td>Short Name</td>
        <td>{{ $course->couse_short_name }}</td>
    </tr>
    <tr>
        <td>Course Year</td>
        <td>{{ $course->course_year }}</td>
    </tr>
    <tr>
        <td>Start Date</td>
        <td>
            @if($course->start_year)
                {{ \Carbon\Carbon::parse($course->start_year)->format('F d, Y') }}
            @else
                Not specified
            @endif
        </td>
    </tr>
    <tr>
        <td>End Date</td>
        <td>
            @if($course->end_date)
                {{ \Carbon\Carbon::parse($course->end_date)->format('F d, Y') }}
            @else
                Not specified
            @endif
        </td>
    </tr>

    @if($course->start_year && $course->end_date)
    <tr>
        <td>Duration</td>
        <td>
            <span class="date-info">
                {{ \Carbon\Carbon::parse($course->start_year)->format('M d, Y') }}
            </span>
            to
            <span class="date-info">
                {{ \Carbon\Carbon::parse($course->end_date)->format('M d, Y') }}
            </span>
        </td>
    </tr>
    @endif
</table>

<!-- =========================
     COURSE TEAM
========================= -->
<h2 class="section-title">Course Team</h2>

<!-- Coordinator -->
<div class="photo-container">
    <span class="role-badge">Course Coordinator</span><br>

    <div class="photo-frame">
        @if($coordinatorFaculty && $coordinatorFaculty->photo_uplode_path && file_exists(storage_path('app/public/' . $coordinatorFaculty->photo_uplode_path)))
            <img src="{{ storage_path('app/public/' . $coordinatorFaculty->photo_uplode_path) }}">
        @else
            <div class="photo-placeholder">👤</div>
        @endif
    </div>

    <div class="person-name">{{ $coordinatorName }}</div>
</div>

<!-- Assistant Coordinators -->
@if(!empty($assistantCoordinatorsData))
<div class="assistant-section">
    <div style="text-align:center">
        <span class="role-badge">Assistant Coordinators</span>
    </div>

    <div class="assistant-grid">
        @foreach($assistantCoordinatorsData as $assistant)
        <div class="assistant-item">
            <div class="photo-frame">
                @if($assistant['photo'] && file_exists(storage_path('app/public/' . $assistant['photo'])))
                    <img src="{{ storage_path('app/public/' . $assistant['photo']) }}">
                @else
                    <div class="photo-placeholder">👤</div>
                @endif
            </div>
            <div class="person-name">{{ $assistant['name'] }}</div>
            <div style="font-size:10px;color:#666">{{ $assistant['role'] }}</div>
        </div>
        @endforeach
    </div>
</div>
@else
<div style="text-align:center;color:#666;padding:15px">
    No Assistant Coordinators assigned
</div>
@endif

<!-- =========================
     FOOTER
========================= -->
<div class="footer">
    Generated on {{ \Carbon\Carbon::now()->format('F d, Y \a\t h:i A') }}
</div>

</body>
</html>
