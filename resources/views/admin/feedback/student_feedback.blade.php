<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Feedback Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://bootstrapdemos.adminmart.com/matdash/dist/assets/css/styles.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- jQuery Validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

    <!-- jQuery Steps -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.js"></script>

    <style>
    .star-rating {
        direction: rtl;
        display: inline-flex;
        justify-content: flex-start;
    }

    .star-rating input[type="radio"] {
        display: none;
    }

    .star-rating label {
        font-size: 1.5rem;
        color: #ccc;
        cursor: pointer;
    }

    .star-rating input[type="radio"]:checked~label {
        color: #af2910;
    }

    .star-rating label:hover,
    .star-rating label:hover~label {
        color: #af2910;
    }
    </style>
</head>
<x-session_message />


<body>
    <div class="container my-5">
        <div class="card shadow rounded-4">
            <div class="card-header bg-danger-subtle text-center rounded-top-4">
                <h4 class="mb-0 text-danger">Topic Feedback Form</h4>
            </div>
            <div class="card-body">
                <form id="vertical-wizard" method="POST" action="{{ route('feedback.submit.feedback') }}">
                    @csrf

                    @foreach ($data as $index => $feedback)
                    @if($feedback->feedback_checkbox == 1)
                    <h3>Step {{ $index + 1 }}: {{ Str::limit($feedback->subject_topic, 30) }}</h3>
                    <section>
                        {{-- Hidden Inputs --}}
                        <input type="hidden" name="timetable_pk[]" value="{{ $feedback->pk }}">
                        <input type="hidden" name="faculty_pk[]" value="{{ $feedback->faculty_master }}">
                        <input type="hidden" name="topic_name[]" value="{{ $feedback->subject_topic }}">
                        <input type="hidden" name="Remark_checkbox[]" value="{{ $feedback->Remark_checkbox }}">
                        <input type="hidden" name="Ratting_checkbox[]" value="{{ $feedback->Ratting_checkbox }}">



                        <div class="row">
                            <div class="col-4">
                                <div class="mb-3">
                                    <label class="form-label">Topic</label>
                                    <!-- <input type="text" class="form-control" value="{{ $feedback->subject_topic }}" readonly> -->
                                    <p>{{ $feedback->subject_topic }}</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Course</label>
                                <!-- <input type="text" class="form-control" value="{{ $feedback->course_name }}" readonly> -->
                                <p>{{ $feedback->course_name }}</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Faculty</label>
                                <!-- <input type="text" class="form-control" value="{{ $feedback->faculty_name }}" readonly> -->
                                <p>{{ $feedback->faculty_name }}</p>
                            </div>
                        </div>

                        @if($feedback->Ratting_checkbox == 1)
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Rating <span class="text-danger">*</span></label>
                                <select class="form-select" name="rating[{{ $index }}]" required>
                                    <option disabled selected>Choose rating</option>
                                    @for ($i = 1; $i <= 5; $i++) <option value="{{ $i }}"
                                        {{ old('rating.'.$index) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Presentation <span class="text-danger">*</span></label> <br>
                                <div class="star-rating">
                                    @for ($i = 5; $i >= 1; $i--)
                                    <input type="radio" id="presentation-{{ $i }}-{{ $index }}"
                                        name="presentation[{{ $index }}]" value="{{ $i }}"
                                        {{ old('presentation.'.$index) == $i ? 'checked' : '' }}>
                                    <label for="presentation-{{ $i }}-{{ $index }}">&#9733;</label>
                                    @endfor
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Content <span class="text-danger">*</span></label><br>
                                <div class="star-rating">
                                    @for ($i = 5; $i >= 1; $i--)
                                    <input type="radio" id="content-{{ $i }}-{{ $index }}" name="content[{{ $index }}]"
                                        value="{{ $i }}" {{ old('content.'.$index) == $i ? 'checked' : '' }}>
                                    <label for="content-{{ $i }}-{{ $index }}">&#9733;</label>
                                    @endfor
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($feedback->Remark_checkbox == 1)
                        <div class="mb-3">
                            <label class="form-label">Remarks <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="remarks[{{ $index }}]" rows="3"
                                required>{{ old('remarks.'.$index) }}</textarea>
                        </div>
                        @endif
                         <hr>
                    </section>
                   
                    @endif
                    @endforeach
                </form>



            </div>



        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        $(document).ready(function() {
            $("#vertical-wizard").steps({
                headerTag: "h3",
                bodyTag: "section",
                transitionEffect: "slideLeft",
                stepsOrientation: "vertical",
                autoFocus: true,
                onStepChanging: function(event, currentIndex, newIndex) {
                    // Only allow forward if current step is valid
                    const form = $(this);
                    if (newIndex > currentIndex) {
                        return form.valid();
                    }
                    return true;
                },
                onFinishing: function(event, currentIndex) {
                    return $("#vertical-wizard").valid(); // Ensure entire form is valid
                },
                onFinished: function(event, currentIndex) {
                    $("#vertical-wizard").submit(); // Submit form on finish
                }
            });

            // Attach validation
            $("#vertical-wizard").validate({
                errorClass: "text-danger small",
            });
        });
        </script>




</body>

</html>