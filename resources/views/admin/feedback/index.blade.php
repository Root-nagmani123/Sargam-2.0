@extends('admin.layouts.master')

@section('title', 'Feedback - Sargam | Lal Bahadur')

@section('setup_content')

<style>
.star-rating {
    display: inline-flex;
    flex-direction: row-reverse;
    font-size: 1.25rem;
}

.star-rating label {
    color: #ddd;
    cursor: default;
}

.star-rating .active {
    color: gold;
}
</style>
<div class="container-fluid">
<x-breadcrum title="Feedback Management" />
    <div>
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h4>Feedback</h4>
                    </div>
                    <div class="col-6">
                    <div class="d-flex justify-content-end align-items-center gap-2">
                    </div>
                </div>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="table w-100" style="border-radius: 10px; overflow: hidden;">
                        <thead style="background-color: #af2910;">
                            <!-- start row -->
                            <tr>
                                <th class="col">S.No.</th>
                                <th class="col">Course Name</th>
                                <th class="col">Faculty Name</th>
                                <th class="col">Subject</th>
                                <th class="col">Topic</th>
                                <th class="col">Action</th>
                            </tr>
                            <!-- end row -->
                        </thead>
                        <tbody>
                            @foreach ($events as $key => $event)
                            <tr>
                                <td>{{ $events->firstItem() + $key }}</td>
                                <td>{{ $event->course_name }}</td>
                                <td>{{ $event->faculty_name }}</td>
                                <td>{{ $event->subject_name }}</td>
                                <td>{{ \Illuminate\Support\Str::words($event->subject_topic, 10, '...') }}</td>
                                <td class="text-center">

                                    <div class="dropdown">
                                        <a href="javascript:void(0)" class="px-2"
                                            id="actionMenu{{ $event->event_id }}" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <span class="material-symbols-rounded fs-5">more_horiz</span>
                                        </a>

                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                                            aria-labelledby="actionMenu{{ $event->event_id }}">

                                            <!-- View Option -->
                                            <li>
                                                <a href="javascript:void(0)"
                                                    class="dropdown-item d-flex align-items-center gap-2 view-btn"
                                                    data-event="{{ $event->event_id }}" data-bs-toggle="modal"
                                                    data-bs-target="#viewModal">

                                                    <span
                                                        class="material-symbols-rounded fs-6 text-primary">visibility</span>
                                                    View
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                </td>

                            </tr>
                            @endforeach
                        </tbody>

                    </table>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                    <div class="text-muted small mb-2">
                        Showing {{ $events->firstItem() }}
                        to {{ $events->lastItem() }}
                        of {{ $events->total() }} items
                    </div>

                    <div>
                        {{ $events->links('vendor.pagination.custom') }}
                    </div>

                </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

<!-- Feedback View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel">Feedback Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="zero_config_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer table-responsive">

                    <table id="zero_config"
                        class="table table-striped table-bordered text-nowrap align-middle dataTable"
                        aria-describedby="zero_config_info">
                        <thead>
                            <!-- start row -->
                            <tr>
                                <th class="col">S.No.</th>
                                <th class="col">Rating</th>
                                <th class="col">Remarks</th>
                                <th class="col">Presentation</th>
                                <th class="col">Content</th>
                            </tr>
                            <!-- end row -->
                        </thead>
                        <tbody>
                            <tr class="odd">
                                <td>1</td>
                                <td class="sorting_1">
                                    <div class="d-flex align-items-center gap-6">
                                        <h6 class="mb-0">feedback</h6>
                                    </div>
                                </td>
                                <td class="sorting_1">
                                    <div class="d-flex align-items-center gap-6">
                                        <h6 class="mb-0">feedback</h6>
                                    </div>
                                </td>
                            </tr>
                            <tr class="odd">
                                <td>2</td>
                                <td class="sorting_1">
                                    <div class="d-flex align-items-center gap-6">
                                        <h6 class="mb-0">feedback</h6>
                                    </div>
                                </td>
                                <td class="sorting_1">
                                    <div class="d-flex align-items-center gap-6">
                                        <h6 class="mb-0">feedback</h6>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('.view-btn');

    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Populate form fields
            document.getElementById('modalCourse').value = this.dataset.course;
            document.getElementById('modalFacultyName').value = this.dataset.faculty;
            document.getElementById('modalSubject').value = this.dataset.subject;
            document.getElementById('modalTopic').value = this.dataset.topic;
            document.getElementById('modalRating').value = this.dataset.rating;
            document.getElementById('modalRemarks').value = this.dataset.remarks;

            // Set star ratings
            setStars('modalPresentationStars', this.dataset.presentation);
            setStars('modalContentStars', this.dataset.content);
        });
    });

    function setStars(containerId, rating) {
        const container = document.getElementById(containerId);
        container.innerHTML = ''; // Clear old stars
        for (let i = 5; i >= 1; i--) {
            const star = document.createElement('span');
            star.innerHTML = '&#9733;';
            star.classList.add(i <= rating ? 'active' : '');
            container.appendChild(star);
        }
    }
});
</>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('.view-btn');

    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event');
            const tbody = document.querySelector('#viewModal tbody');
            tbody.innerHTML = `<tr><td colspan="3">Loading...</td></tr>`;

            fetch(`/feedback/event-feedback/${eventId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        let rows = '';
                        data.forEach((item, index) => {
                            rows += `<tr>
                                    <td>${index + 1}</td>
                                    <td>${item.rating}</td>
                                    <td>${item.remark}</td>
                                    <td>${item.presentation}</td>
                                    <td>${item.content}</td>
                                </tr>`;
                        });
                        tbody.innerHTML = rows;
                    } else {
                        tbody.innerHTML =
                            `<tr><td colspan="3">No feedback found.</td></tr>`;
                    }
                });
        });
    });
});
</script>




@endsection