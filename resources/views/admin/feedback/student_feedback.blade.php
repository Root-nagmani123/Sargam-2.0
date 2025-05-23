<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Feedback Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        color: #ffc107;
    }

    .star-rating label:hover,
    .star-rating label:hover~label {
        color: #ffc107;
    }
    </style>
</head>
<x-session_message />
@if ($errors->any())
<div class="alert alert-danger">
    <strong>There were some errors:</strong>
    <ul class="mb-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<body>
    <div class="container my-5">
        <div class="card shadow rounded-4">
            <div class="card-header bg-danger text-white text-center rounded-top-4">
                <h4 class="mb-0">Topic Feedback Form</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('feedback.submit.feedback') }}">
                    @csrf

                    @foreach ($data as $index => $feedback)
                    @if($feedback->feedback_checkbox == 1)
                    <div class="row g-3 border p-3 mb-4 shadow rounded">
                        {{-- Hidden Inputs --}}
                        <input type="hidden" name="timetable_pk[]" value="{{ $feedback->pk }}">
                        <input type="hidden" name="faculty_pk[]" value="{{ $feedback->faculty_master }}">
                        <input type="hidden" name="topic_name[]" value="{{ $feedback->subject_topic }}">

                        <input type="hidden" name="Remark_checkbox[]" value="{{ $feedback->Remark_checkbox }}">
                        <input type="hidden" name="Ratting_checkbox[]" value="{{ $feedback->Ratting_checkbox }}">
                        <div class="col-12 col-md-12">
                            <label class="form-label">Topic</label>
                            <input type="text" class="form-control" value="{{ $feedback->subject_topic }}" readonly>
                        </div>
                        
                        <div class="col-md-4">

                            <label>Course</label>
                            <input type="text" class="form-control" value="{{ $feedback->course_name }}" readonly>
                        </div>

                        <div class="col-md-4">
                            <label>Faculty</label>
                            <input type="text" class="form-control" value="{{ $feedback->faculty_name }}" readonly>
                        </div>

                        
                       @if($feedback->Ratting_checkbox == 1)
                        <div class="col-12 col-md-4">
                            <label class="form-label">Rating <span class="text-danger">*</span></label>
                            <select class="form-select" name="rating[]" >
                                <option disabled {{ old('rating.'.$index) ? '' : 'selected' }}>Choose rating</option>
                                @for ($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" {{ old('rating.'.$index) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                            @error('rating.'.$index)
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <div class="col-12 col-md-4">
                            <label class="form-label">Presentation <span class="text-danger">*</span></label><br>
                            <div class="star-rating">
                                @for ($i = 5; $i >= 1; $i--)
                                    <input type="radio" id="presentation-{{ $i }}-{{ $index }}"
                                        name="presentation[{{ $index }}]" value="{{ $i }}"
                                        {{ old('presentation.'.$index) == $i ? 'checked' : '' }} >
                                    <label for="presentation-{{ $i }}-{{ $index }}">&#9733;</label>
                                @endfor
                            </div>
                            @error('presentation.'.$index)
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Content <span class="text-danger">*</span></label><br>
                            <div class="star-rating">
                                @for ($i = 5; $i >= 1; $i--)
                                    <input type="radio" id="content-{{ $i }}-{{ $index }}"
                                        name="content[{{ $index }}]" value="{{ $i }}"
                                        {{ old('content.'.$index) == $i ? 'checked' : '' }} >
                                    <label for="content-{{ $i }}-{{ $index }}">&#9733;</label>
                                @endfor
                            </div>
                            @error('content.'.$index)
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>


                    @if($feedback->Remark_checkbox == 1)
                            <div class="col-12">
                                <label class="form-label">Remarks <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="remarks[]" rows="3"
                                    placeholder="Enter your remarks here..." >{{ old('remarks.'.$index) }}</textarea>
                                @error('remarks.'.$index)
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                    @endif
                    @endforeach
                    @if($data->isEmpty())
                    <div class="alert alert-info text-center">
                        No feedback data available.
                    </div>
                    @else
                    <div class="text-end">
                        <button type="submit" class="btn btn-success">Submit All Feedback</button>
                    </div>
                    @endif
                </form>


            </div>



        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        document.querySelector("form").addEventListener("submit", function(e) {
            const radiosPresentation = document.querySelectorAll('input[name^="presentation"]');
            const radiosContent = document.querySelectorAll('input[name^="content"]');

            let allPresentationChecked = [...radiosPresentation].every((el, i, arr) => {
                const name = el.name;
                return document.querySelector(`input[name="${name}"]:checked`);
            });

            let allContentChecked = [...radiosContent].every((el, i, arr) => {
                const name = el.name;
                return document.querySelector(`input[name="${name}"]:checked`);
            });

            if (!allPresentationChecked || !allContentChecked) {
                e.preventDefault();
                alert("Please give ratings for all 'Presentation' and 'Content' fields.");
            }
        });
        </script>

</body>

</html>