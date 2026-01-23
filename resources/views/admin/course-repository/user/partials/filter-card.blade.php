<!-- Filter Card Partial -->
<div class="card filter-card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ $route }}" id="filterForm">
            <div class="row g-3 align-items-end">
                <!-- Date Filter -->
                <div class="col-md-2">
                    <label for="filter_date" class="form-label fw-semibold mb-2">Date</label>
                    <div class="input-group">
                        <input type="date" 
                               class="form-control" 
                               id="filter_date" 
                               name="date" 
                               value="{{ $filters['date'] ?? '' }}">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-calendar3"></i>
                        </span>
                    </div>
                </div>

                <!-- Course Filter -->
                <div class="col-md-2">
                    <label for="filter_course" class="form-label fw-semibold mb-2">Course</label>
                    <select class="form-select" id="filter_course" name="course">
                        <option value="">Select Course</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->pk }}" {{ $filters['course'] == $course->pk ? 'selected' : '' }}>
                                {{ $course->course_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Subject Filter -->
                <div class="col-md-2">
                    <label for="filter_subject" class="form-label fw-semibold mb-2">Subject</label>
                    <select class="form-select" id="filter_subject" name="subject">
                        <option value="">Select Subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->pk }}" {{ $filters['subject'] == $subject->pk ? 'selected' : '' }}>
                                {{ $subject->subject_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Week Filter -->
                <div class="col-md-2">
                    <label for="filter_week" class="form-label fw-semibold mb-2">Week</label>
                    <select class="form-select" id="filter_week" name="week">
                        <option value="">Select Week</option>
                        @for($i = 1; $i <= 52; $i++)
                            <option value="{{ $i }}" {{ $filters['week'] == $i ? 'selected' : '' }}>
                                {{ $i }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Faculty Filter -->
                <div class="col-md-2">
                    <label for="filter_faculty" class="form-label fw-semibold mb-2">Faculty</label>
                    <select class="form-select" id="filter_faculty" name="faculty">
                        <option value="">Select Faculty</option>
                        @foreach($faculties as $faculty)
                            <option value="{{ $faculty->pk }}" {{ $filters['faculty'] == $faculty->pk ? 'selected' : '' }}>
                                {{ $faculty->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Apply Button -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>