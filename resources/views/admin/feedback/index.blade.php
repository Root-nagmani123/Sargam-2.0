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
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Feedback</h4>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer table-responsive">

                        <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Course Name</th>
                                    <th class="col">Faculty Name</th>
                                    <th class="col">Subject</th>
                                    <th class="col">Topic</th>
                                    <th class="col">Rating</th>
                                    <th class="col">Remarks</th>
                                    <th class="col">Action</th>
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
                                    <td class="sorting_1">
                                        <div class="d-flex align-items-center gap-6">
                                            <h6 class="mb-0">feedback</h6>
                                        </div>
                                    </td>
                                    <td class="sorting_1">
                                        <div class="d-flex align-items-center gap-6">
                                            <h6 class="mb-0">
                                                {{ \Illuminate\Support\Str::words($feedback->remarks ?? 'feedback', 15, '...') }}
                                            </h6>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            <a href="#" class="btn btn-success text-white btn-sm view-btn"
                                                data-bs-toggle="modal" data-bs-target="#viewModal"
                                                data-course="B.Tech Computer Science" data-faculty="Dr. Anjali Mehta"
                                                data-subject="Data Structures" data-topic="Stacks and Queues"
                                                data-rating="4" data-presentation="4" data-content="5"
                                                data-remarks="Very clear explanations and engaging session.">
                                                View
                                            </a>


                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
                <form>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="course" class="form-label">Course</label>
                            <input type="text" class="form-control" id="modalCourse" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="facultyName" class="form-label">Faculty Name</label>
                            <input type="text" class="form-control" id="modalFacultyName" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="modalSubject" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="topic" class="form-label">Topic</label>
                            <input type="text" class="form-control" id="modalTopic" readonly>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Rating</label>
                            <select class="form-select" id="modalRating" disabled>
                                <option disabled>Choose rating</option>
                                <option value="1">1 - Poor</option>
                                <option value="2">2</option>
                                <option value="3">3 - Average</option>
                                <option value="4">4</option>
                                <option value="5">5 - Excellent</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Presentation</label><br>
                            <div class="star-rating" id="modalPresentationStars"></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Content</label><br>
                            <div class="star-rating" id="modalContentStars"></div>
                        </div>
                        <div class="col-12">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="modalRemarks" rows="3" readonly></textarea>
                        </div>
                    </div>
                </form>
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



@endsection