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

{{-- Add takes MANY fields, Edit takes one.

     A form usually needs several reflection questions and they all share the same
     Course / Event / Group, so one-at-a-time meant re-picking that scope for every
     question. Repeating just the label - exactly what Manage Evaluation Columns
     does with its Columns cards - keeps the scope picked once.

     Card and button classes are borrowed from that repeater on purpose: the CSS
     is already loaded on this page, so the two modals stay identical for free. --}}
@if ($multiple ?? false)
    <hr class="my-3">
    <h6 class="pec-section-title mb-3">Reflection Fields</h6>

    <div id="prfFieldsContainer">
        <div class="pec-column-card prf-field-card" data-index="0">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-10">
                    <label class="pe-form-label" for="{{ $prefix }}FieldLabel">Field Name<span class="pe-req">*</span></label>
                    <input type="text" class="form-control pe-control prf-label" id="{{ $prefix }}FieldLabel"
                           name="fields[0][field_label]" placeholder="eg. Presentation Skills"
                           maxlength="255" required>
                </div>
                <div class="col-12 col-md-2">
                    <div class="pec-card-actions">
                        <button type="button" class="pec-card-btn pec-card-btn--remove" title="Remove this field"
                                aria-label="Remove this field">
                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="pec-card-btn pec-card-btn--add" title="Add another field"
                                aria-label="Add another field">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="pe-error pec-card-error"></div>
        </div>
    </div>
    <div class="pe-error" id="prfFieldsError"></div>
@else
    <div class="pe-field mb-2">
        <label class="pe-form-label" for="{{ $prefix }}FieldLabel">Field Name<span class="pe-req">*</span></label>
        <input type="text" class="form-control pe-control" id="{{ $prefix }}FieldLabel" name="field_label"
               placeholder="eg. Presentation Skills" maxlength="255" required>
        <div class="pe-error"></div>
    </div>
@endif
