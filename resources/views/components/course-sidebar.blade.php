<!-- Course Sidebar Navigation -->
<div class="course-sidebar">
    <div class="sidebar-header">
        <h5 class="sidebar-title mb-0 fw-bold">Courses</h5>
    </div>
    <nav class="sidebar-nav">
        <ul class="list-unstyled mb-0">
            @for($i = 89; $i <= 100; $i++)
                <li class="sidebar-item">
                    <a href="{{ route('admin.course-repository.user.foundation-course.detail', 'FC-' . $i) }}" 
                       class="sidebar-link {{ request()->route('courseCode') == 'FC-' . $i ? 'active' : '' }}"
                       aria-label="View FC-{{ $i }}">
                        <span>FC-{{ $i }}</span>
                        <i class="material-icons material-symbols-rounded">chevron_right</i>
                    </a>
                </li>
            @endfor
        </ul>
    </nav>
</div>