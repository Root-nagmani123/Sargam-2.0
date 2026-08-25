{{-- Fields shared by the Add and Edit Reflection Field modals, so the two cannot
     drift apart (docs/new-design-index-page.md §3c).

     Props:
       $prefix   unique id prefix for this instance ("prfAdd" / "prfEdit")
       $courses  pk => course_name for the Course Name select

     ORDER: Course -> Event -> Group. The mockup lays Group out above Event, but a
     group belongs to an event, so the dropdowns are dependent in this direction —
     rendering Group first would mean picking from an empty list until the user
     scrolled past it to choose an Event. Event and Group are filled by AJAX
     (PeerReflectionFieldController::options) whenever the level above changes.

     All three scope levels are OPTIONAL: leaving them blank makes the field
     GLOBAL, i.e. shown on every evaluation form. Only the label is required.

     All three carry js-prf-select2 so the page script skins them with Select2
     (dropdownParent = the modal, or the search box can't take focus). --}}

<div class="pe-field mb-3">
    <label class="pe-form-label" for="{{ $prefix }}CourseId">Course Name</label>
    <select class="form-select pe-control js-prf-select2" id="{{ $prefix }}CourseId" name="course_id">
        <option value="">Select Course</option>
        @foreach($courses as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
        @endforeach
    </select>
    <div class="pe-error"></div>
</div>

<div class="pe-field mb-3">
    <label class="pe-form-label" for="{{ $prefix }}EventId">Event Name</label>
    <select class="form-select pe-control js-prf-select2" id="{{ $prefix }}EventId" name="event_id">
        <option value="">Select Event</option>
    </select>
    <div class="pe-error"></div>
</div>

<div class="pe-field mb-3">
    <label class="pe-form-label" for="{{ $prefix }}GroupId">Group Name</label>
    <select class="form-select pe-control js-prf-select2" id="{{ $prefix }}GroupId" name="group_id">
        <option value="">Select Group</option>
    </select>
    <div class="pe-error"></div>
</div>

<div class="pe-field mb-2">
    <label class="pe-form-label" for="{{ $prefix }}FieldLabel">Field Name<span class="pe-req">*</span></label>
    <input type="text" class="form-control pe-control" id="{{ $prefix }}FieldLabel" name="field_label"
           placeholder="eg. Presentation Skills" maxlength="255" required>
    <div class="pe-error"></div>
</div>
