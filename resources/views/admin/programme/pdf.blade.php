<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details - {{ $course->course_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .course-header {
            background-color: #004a93;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .course-header h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .course-header .subtitle {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .section-title {
            color: #004a93;
            font-size: 16px;
            font-weight: bold;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #004a93;
        }
        
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        
        .info-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        
        .info-table td:first-child {
            background-color: #f8f9fa;
            font-weight: bold;
            width: 200px;
        }
        
        .photo-container {
            text-align: center;
            margin: 20px 0;
            page-break-inside: avoid;
        }
        
        .photo-frame {
            display: inline-block;
            border: 2px solid #004a93;
            padding: 8px;
            background-color: white;
            margin: 10px;
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
            background-color: #e9ecef;
            display: inline-block;
            text-align: center;
            line-height: 120px;
            color: #6c757d;
            font-size: 40px;
        }
        
        .person-name {
            margin-top: 8px;
            font-weight: bold;
            color: #004a93;
            font-size: 12px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 5px 15px;
            background-color: #004a93;
            color: white;
            border-radius: 15px;
            font-size: 11px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .coordinator-section {
            margin: 20px 0;
            page-break-inside: avoid;
        }
        
        .assistant-section {
            margin: 20px 0;
            page-break-inside: avoid;
        }
        
        .assistant-grid {
            display: table;
            width: 100%;
            margin-top: 15px;
        }
        
        .assistant-item {
            display: inline-block;
            width: 30%;
            margin: 10px 1%;
            vertical-align: top;
            text-align: center;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .date-info {
            background-color: #e7f3ff;
            padding: 8px 15px;
            border-radius: 4px;
            display: inline-block;
            margin: 5px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Course Header -->
    <div class="course-header">
        <h1>{{ $course->course_name }}</h1>
        <div class="subtitle">
            {{ $course->couse_short_name }} | Course Year: {{ $course->course_year }}
        </div>
    </div>

    <!-- Course Information -->
    <div>
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
    </div>

    <!-- Course Team -->
    <div>
        <h2 class="section-title">Course Team</h2>
        
        <!-- Course Coordinator -->
        <div class="coordinator-section">
            <div style="text-align: center; margin-bottom: 15px;">
                <span class="role-badge">Course Coordinator</span>
            </div>
            <div class="photo-container">
                <div class="photo-frame">
                    @if($coordinatorFaculty && $coordinatorFaculty->photo_uplode_path && file_exists(storage_path('app/public/' . $coordinatorFaculty->photo_uplode_path)))
                        <img src="{{ storage_path('app/public/' . $coordinatorFaculty->photo_uplode_path) }}" alt="{{ $coordinatorName }}">
                    @else
                        <div class="photo-placeholder">👤</div>
                    @endif
                </div>
                <div class="person-name">{{ $coordinatorName }}</div>
            </div>
        </div>

        <!-- Assistant Coordinators -->
        @if(!empty($assistantCoordinatorsData))
            <div class="assistant-section">
                <div style="text-align: center; margin-bottom: 15px;">
                    <span class="role-badge">Assistant Coordinators</span>
                </div>
                <div class="assistant-grid">
                    @foreach($assistantCoordinatorsData as $assistant)
                        <div class="assistant-item">
                            <div class="photo-frame">
                                @if($assistant['photo'] && file_exists(storage_path('app/public/' . $assistant['photo'])))
                                    <img src="{{ storage_path('app/public/' . $assistant['photo']) }}" alt="{{ $assistant['name'] }}">
                                @else
                                    <div class="photo-placeholder">👤</div>
                                @endif
                            </div>
                            <div class="person-name">{{ $assistant['name'] }}</div>
                            <div style="font-size: 10px; color: #666; margin-top: 5px;">{{ $assistant['role'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div style="text-align: center; color: #666; padding: 20px;">
                No Assistant Coordinators assigned
            </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        Generated on {{ \Carbon\Carbon::now()->format('F d, Y \a\t h:i A') }}
    </div>
</body>
</html>

