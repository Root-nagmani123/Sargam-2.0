@extends('admin.layouts.master')

@section('title', 'Feedback - Sargam | Lal Bahadur')

@section('content')

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

    <x-breadcrum title="Feedback" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card shadow-sm border-0 rounded-3" style="border-left: 4px solid #004a93;">
            <div class="card-body">

                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold text-primary">Feedback</h4>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-primary text-white">
                            <tr class="text-center bg-danger text-white">
                                <th scope="col">S.No.</th>
                                <th scope="col">Course Name</th>
                                <th scope="col">Faculty Name</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Topic</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($events as $key => $event)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $event->course_name }}</td>
                                <td>{{ $event->faculty_name }}</td>
                                <td>{{ $event->subject_name }}</td>
                                <td>{{ \Illuminate\Support\Str::words($event->subject_topic, 10, '...') }}</td>

                                <td class="text-center">

                                    <!-- Edit -->
                                    <a href="javascript:void(0);" class="text-primary me-2 edit-btn"
                                        data-event="{{ $event->event_id }}" data-bs-toggle="modal"
                                        data-bs-target="#editModal" title="Edit">
                                        <i class="bi bi-pencil fs-5"></i>
                                    </a>

                                    <!-- Delete -->
                                    <a href="javascript:void(0);" class="text-primary me-2 delete-btn"
                                        data-event="{{ $event->event_id }}" title="Delete">
                                        <i class="bi bi-trash fs-5"></i>
                                    </a>

                                    <!-- View -->
                                    <a href="javascript:void(0);" class="text-primary view-btn"
                                        data-event="{{ $event->event_id }}" data-bs-toggle="modal"
                                        data-bs-target="#viewModal" title="View">
                                        <i class="bi bi-eye fs-5"></i>
                                    </a>

                                </td>


                            </tr>
                            @endforeach
                        </tbody>

                    </table>
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
</script>
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