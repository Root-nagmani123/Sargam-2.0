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
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header text-center rounded-top-4" style="background-color: #004a93;">
                <h4 class="mb-0 text-white">Topic Feedback Form</h4>
            </div>
            <form id="vertical-wizard" method="POST" action="{{ route('feedback.submit.feedback') }}">
                @csrf
                    <div class="card-body p-4 mb-4">
                        <div class="table-responsive">
                            <table class="table align-middle text-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">S.No.</th>
                                        <th>Date &amp; Time</th>
                                        <th>Topic Detail</th>
                                        <th>Faculty Name</th>
                                        <th>Q. How did you like the Content?</th>
                                        <th>Q. How did you like the Presentation?</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $index => $feedback)
                                    @if ($feedback->feedback_checkbox == 1)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($feedback->from_date)->format('d-m-Y') }}
                                            <br>
                                            <small
                                                class="text-muted">{{ $feedback->from_time }}–{{ $feedback->to_time }}</small>
                                        </td>
                                        <td>{{ $feedback->subject_topic }}</td>
                                        <td>{{ $feedback->faculty_name }}</td>

                                        {{-- Content Rating --}}
                                        <td>
                                            @if ($feedback->Ratting_checkbox == 1)
                                            <div class="star-rating d-inline-flex flex-row-reverse">
                                                @for ($i = 5; $i >= 1; $i--)
                                                <input type="radio" id="content-{{ $i }}-{{ $index }}"
                                                    name="content[{{ $index }}]" value="{{ $i }}"
                                                    {{ old('content.' . $index) == $i ? 'checked' : '' }} required>
                                                <label for="content-{{ $i }}-{{ $index }}">&#9733;</label>
                                                @endfor
                                            </div>
                                            @endif
                                        </td>

                                        {{-- Presentation Rating --}}
                                        <td>
                                            @if ($feedback->Ratting_checkbox == 1)
                                            <div class="star-rating d-inline-flex flex-row-reverse">
                                                @for ($i = 5; $i >= 1; $i--)
                                                <input type="radio" id="presentation-{{ $i }}-{{ $index }}"
                                                    name="presentation[{{ $index }}]" value="{{ $i }}"
                                                    {{ old('presentation.' . $index) == $i ? 'checked' : '' }} required>
                                                <label for="presentation-{{ $i }}-{{ $index }}">&#9733;</label>
                                                @endfor
                                            </div>
                                            @endif
                                        </td>

                                        {{-- Remarks --}}
                                        <td style="min-width: 180px;">
                                            @if ($feedback->Remark_checkbox == 1)
                                            <textarea class="form-control form-control-sm" name="remarks[{{ $index }}]"
                                                rows="2" placeholder="Enter remarks..."
                                                required>{{ old('remarks.' . $index) }}</textarea>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                <div class="text-end mt-3 mb-4 me-4">
                    <button type="submit" class="btn btn-primary px-4 rounded-pill" style="background-color: #004a93;border-color: #004a93;">Submit Feedback</button>
                </div>
            </form>

            <style>
            /* Star Rating Style */
            .star-rating {
                position: relative;
                display: inline-flex;
            }

            .star-rating input {
                display: none;
            }

            .star-rating label {
                font-size: 1.25rem;
                color: #ffce54;
                cursor: pointer;
                transition: color 0.2s ease-in-out;
                padding: 0 1px;
            }

            .star-rating input:not(:checked)~label {
                color: #ffe8a1;
            }

            .table td,
            .table th {
                vertical-align: middle;
            }
            </style>
        </div>
    </div>
</body>

</html>