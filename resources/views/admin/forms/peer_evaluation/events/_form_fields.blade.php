{{-- Fields shared by the Add Event and Edit Event modals.

     One partial, so the two modals cannot drift apart (docs/new-design-index-page.md
     §3c: create and edit look alike; only the contents and the submit caption differ).

     Props:
       $prefix   unique id prefix for this instance ("peAdd" / "peEdit")
       $courses  rows of {id, name, status, start_date, end_date} for the Course
                 Name select. status is the Active / Archived bucket the pills
                 would put the course in; the two dates are the course's own
                 window, which the page script clamps Start / End Date to.

     Every control is wrapped in .pe-field so the page script can find the
     matching .pe-error to fill from a 422 response. --}}

@php
    // Grouped so the split is visible even without Select2 - a native <select>
    // renders <optgroup> headings, and Select2 renders them too. The badge the
    // page script draws on each row is the same information, styled.
    $coursesByStatus = collect($courses)->groupBy('status');
@endphp

<div class="pe-field mb-3">
    <label class="pe-form-label" for="{{ $prefix }}CourseId">Course Name<span class="pe-req">*</span></label>
    <select class="form-select pe-control" id="{{ $prefix }}CourseId" name="course_id" required>
        <option value="">Select Course</option>
        @foreach(['active' => 'Active', 'archive' => 'Archived'] as $status => $heading)
            @if($coursesByStatus->has($status))
                <optgroup label="{{ $heading }}">
                    @foreach($coursesByStatus[$status] as $course)
                        {{-- data-start-date / data-end-date bound this event's own
                             dates; the server re-checks them, this is only the hint. --}}
                        <option value="{{ $course['id'] }}"
                                data-status="{{ $course['status'] }}"
                                data-status-label="{{ $heading }}"
                                data-start-date="{{ $course['start_date'] }}"
                                data-end-date="{{ $course['end_date'] }}">{{ $course['name'] }}</option>
                    @endforeach
                </optgroup>
            @endif
        @endforeach
    </select>
    <div class="pe-error"></div>
</div>

<div class="pe-field mb-3">
    <label class="pe-form-label" for="{{ $prefix }}EventName">Event Name<span class="pe-req">*</span></label>
    <input type="text" class="form-control pe-control" id="{{ $prefix }}EventName" name="event_name"
           placeholder="eg. Presentation Skills" maxlength="255" required>
    <div class="pe-error"></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6">
        <div class="pe-field">
            <label class="pe-form-label" for="{{ $prefix }}StartDate">Start Date<span class="pe-req">*</span></label>
            {{-- Native date input: it carries its own calendar picker and its own
                 dd/mm/yyyy hint, so no third-party picker is pulled in for it. --}}
            <input type="date" class="form-control pe-control" id="{{ $prefix }}StartDate" name="start_date" required>
            <div class="pe-error"></div>
        </div>
    </div>
    <div class="col-12 col-sm-6">
        <div class="pe-field">
            <label class="pe-form-label" for="{{ $prefix }}EndDate">End Date<span class="pe-req">*</span></label>
            <input type="date" class="form-control pe-control" id="{{ $prefix }}EndDate" name="end_date" required>
            <div class="pe-error"></div>
        </div>
    </div>
</div>

<div class="pe-field mb-2">
    <label class="pe-form-label" for="{{ $prefix }}Description">Description</label>
    <textarea class="form-control pe-control" id="{{ $prefix }}Description" name="description" rows="3"
              maxlength="5000" placeholder="Write your thoughts here"></textarea>
    <div class="pe-error"></div>
</div>
