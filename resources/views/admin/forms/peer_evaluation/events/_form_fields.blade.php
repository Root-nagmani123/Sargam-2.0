{{-- Fields shared by the Add Event and Edit Event modals.

     One partial, so the two modals cannot drift apart (docs/new-design-index-page.md
     §3c: create and edit look alike; only the contents and the submit caption differ).

     Props:
       $prefix   unique id prefix for this instance ("peAdd" / "peEdit")
       $courses  id => course_name for the Course Name select

     Every control is wrapped in .pe-field so the page script can find the
     matching .pe-error to fill from a 422 response. --}}

<div class="pe-field mb-3">
    <label class="pe-form-label" for="{{ $prefix }}CourseId">Course Name<span class="pe-req">*</span></label>
    <select class="form-select pe-control" id="{{ $prefix }}CourseId" name="course_id" required>
        <option value="">Select Course</option>
        @foreach($courses as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
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
