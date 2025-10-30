<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details - {{ $course->course_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0 !important;
            }
            .container-fluid {
                padding: 0 !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .page-break {
                page-break-after: always;
            }
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .course-header {
            background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
        }
        
        .course-header h1 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .course-header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .details-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .section-title {
            color: #004a93;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #004a93;
        }
        
        .detail-item {
            margin-bottom: 1.5rem;
        }
        
        .detail-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            font-size: 1.1rem;
            color: #212529;
            padding: 0.75rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #004a93;
        }
        
        .photo-container {
            text-align: center;
            padding: 1rem;
        }
        
        .photo-frame {
            display: inline-block;
            border: 3px solid #004a93;
            border-radius: 10px;
            padding: 10px;
            background-color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .photo-frame img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            display: block;
        }
        
        .photo-placeholder {
            width: 150px;
            height: 150px;
            background-color: #e9ecef;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 3rem;
        }
        
        .person-name {
            margin-top: 0.75rem;
            font-weight: 600;
            color: #004a93;
            font-size: 1rem;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background-color: #004a93;
            color: white;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .action-buttons {
            position: sticky;
            top: 20px;
            z-index: 1000;
        }
        
        .btn-action {
            margin-bottom: 0.5rem;
            width: 100%;
            padding: 0.75rem;
            font-weight: 500;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .date-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #e7f3ff;
            color: #004a93;
            border-radius: 5px;
            font-weight: 500;
            margin: 0.25rem;
        }
        
        .assistant-coordinator-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .course-header h1 {
                font-size: 1.5rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .assistant-coordinator-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Action Buttons -->
        <div class="no-print mb-4">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('programme.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Course List
                        </a>
                        <div class="btn-group">
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="bi bi-printer"></i> Print
                            </button>
                            <a href="{{ route('programme.download.pdf', ['id' => encrypt($course->pk)]) }}" class="btn btn-danger">
                                <i class="bi bi-file-earmark-pdf"></i> Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Header -->
        <div class="course-header">
            <h1>{{ $course->course_name }}</h1>
            <div class="subtitle">
                <span class="badge bg-light text-dark me-2">{{ $course->couse_short_name }}</span>
                <span>Course Year: {{ $course->course_year }}</span>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="card details-card">
            <div class="card-body p-4">
                <!-- Course Information Section -->
                <div class="mb-4">
                    <h3 class="section-title">
                        <i class="bi bi-info-circle"></i> Course Information
                    </h3>
                    <div class="info-grid">
                        <div class="detail-item">
                            <div class="detail-label">Course Name</div>
                            <div class="detail-value">{{ $course->course_name }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Short Name</div>
                            <div class="detail-value">{{ $course->couse_short_name }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Course Year</div>
                            <div class="detail-value">{{ $course->course_year }}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Course Duration</div>
                            <div class="detail-value">
                                @if($course->start_year && $course->end_date)
                                    <span class="date-badge">
                                        <i class="bi bi-calendar-event"></i> 
                                        {{ \Carbon\Carbon::parse($course->start_year)->format('M d, Y') }}
                                    </span>
                                    <span class="mx-2">to</span>
                                    <span class="date-badge">
                                        <i class="bi bi-calendar-check"></i> 
                                        {{ \Carbon\Carbon::parse($course->end_date)->format('M d, Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">Not specified</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Team Section -->
                <div class="mb-4">
                    <h3 class="section-title">
                        <i class="bi bi-people"></i> Course Team
                    </h3>
                    
                    <!-- Course Coordinator -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="text-center mb-3">
                                <span class="role-badge">Course Coordinator</span>
                            </div>
                            <div class="photo-container">
                                <div class="photo-frame">
                                    @if($coordinatorFaculty && $coordinatorFaculty->photo_uplode_path)
                                        <img src="{{ asset('storage/' . $coordinatorFaculty->photo_uplode_path) }}" 
                                             alt="{{ $coordinatorName }}" 
                                             onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'photo-placeholder\'><i class=\'bi bi-person\'></i></div>';">
                                    @else
                                        <div class="photo-placeholder">
                                            <i class="bi bi-person"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="person-name">{{ $coordinatorName }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Assistant Coordinators -->
                    @if(!empty($assistantCoordinatorsData))
                        <div class="row">
                            <div class="col-12">
                                <div class="text-center mb-3">
                                    <span class="role-badge">Assistant Coordinators</span>
                                </div>
                                <div class="assistant-coordinator-grid">
                                    @foreach($assistantCoordinatorsData as $assistant)
                                        <div class="text-center">
                                            <div class="photo-frame">
                                                @if($assistant['photo'])
                                                    <img src="{{ asset('storage/' . $assistant['photo']) }}" 
                                                         alt="{{ $assistant['name'] }}" 
                                                         onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'photo-placeholder\'><i class=\'bi bi-person\'></i></div>';">
                                                @else
                                                    <div class="photo-placeholder">
                                                        <i class="bi bi-person"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="person-name">{{ $assistant['name'] }}</div>
                                            <div class="role-badge" style="margin-top: 0.5rem; font-size: 0.8rem;">{{ $assistant['role'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-info-circle"></i> No Assistant Coordinators assigned
                            @if(config('app.debug'))
                                <br><small>Debug: assistantCoordinatorsData is empty or null</small>
                            @endif
                        </div>
                    @endif

                    <!-- Discipline In-Charge -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="text-center mb-3">
                                <span class="role-badge">Discipline In-Charge</span>
                            </div>
                            <div class="detail-item">
                                <div class="detail-value text-center">
                                    {{ $disciplineInCharge }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Debug Information (only in debug mode) -->
        {{-- @if(config('app.debug'))
            <div class="no-print mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Debug Information</h5>
                    </div>
                    <div class="card-body">
                        <h6>Assistant Coordinators Data:</h6>
                        <pre>{{ json_encode($assistantCoordinatorsData, JSON_PRETTY_PRINT) }}</pre>
                        
                        <h6>Discipline In-Charge:</h6>
                        <pre>{{ $disciplineInCharge }}</pre>
                        
                        <h6>Course Coordinators Raw Data:</h6>
                        <pre>{{ json_encode($course->courseCordinatorMater->toArray(), JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            </div>
        @endif --}}

        <!-- Footer -->
        <div class="text-center text-muted no-print mt-4">
            <small>Generated on {{ \Carbon\Carbon::now()->format('F d, Y \a\t h:i A') }}</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

