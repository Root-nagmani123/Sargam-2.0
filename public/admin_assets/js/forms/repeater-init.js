// education repeater

var edu_room = 0;

function education_fields() {
    edu_room++;
    var objTo = document.getElementById("education_fields");

    const currentYear = new Date().getFullYear();
    let yearOptions = '<option value="">Select Year</option>';
    for (let y = currentYear; y >= 1950; y--) {
        yearOptions += '<option value="' + y + '">' + y + '</option>';
    }

    var divtest = document.createElement("div");
    divtest.setAttribute("class", "row degree-row g-3 align-items-end mb-3 removeclass" + room);

    divtest.innerHTML =
        '<div class="col-12 col-md-6">' +
        '<label class="form-label">Degree :</label>' +
        '<div class="mb-3">' +
        '<input type="text" class="form-control" name="degree[]" placeholder="eg. B.Tech">' +
        '<small class="text-muted">Bachelors, Masters, PhD</small>' +
        '</div></div>' +

        '<div class="col-12 col-md-6">' +
        '<label class="form-label">University/Institution Name :</label>' +
        '<div class="mb-3">' +
        '<input type="text" class="form-control" name="university_institution_name[]" placeholder="eg. Delhi University">' +
        '</div></div>' +

        '<div class="col-12 col-md-6">' +
        '<label class="form-label">Year of Passing :</label>' +
        '<div class="mb-3">' +
        '<select name="year_of_passing[]" class="form-select">' +
        yearOptions +
        '</select>' +
        '</div></div>' +

        '<div class="col-12 col-md-6">' +
        '<label class="form-label">Percentage/CGPA :</label>' +
        '<div class="mb-3">' +
        '<input type="number" name="percentage_CGPA[]" placeholder="eg. 6.6" class="form-control" min="0" max="100">' +
        '</div></div>' +

        '<div class="col-12 col-md-6">' +
        '<label class="form-label">Certificates/Documents Upload :</label>' +
        '<div class="mb-3">' +
        '<input type="file" name="certificate[]" class="form-control">' +
        '<div class="existing-certificate"></div>' +
        '</div></div>' +

        '<div class="col-auto ms-md-auto">' +
        '<label class="form-label d-none d-md-block">&nbsp;</label>' +
        '<div class="mb-3">' +
        '<button class="btn btn-outline-danger" type="button" onclick="remove_education_fields(' + room + ');" title="Remove qualification">' +
        '<i class="bi bi-trash" aria-hidden="true"></i>' +
        '</button></div></div>';

    objTo.parentNode.insertBefore(divtest, objTo.nextSibling);
}


function remove_education_fields(rid) {
    $(".removeclass" + rid).remove();
}





// experience repeater

var exp_room = 0;

function experience_fields() {
    exp_room++;
    var objTo = document.getElementById("experience_fields");
    var divtest = document.createElement("div");
    divtest.setAttribute("class", "row experience-row g-3 align-items-end mb-3 removeclass" + room);
    divtest.innerHTML =
        '<div class="col-12 col-md-6"><label class="form-label">Years of Experience :</label><div class="mb-3"><input type="text" class="form-control" name="experience[]" placeholder="Years of Experience"></div></div>' +
        '<div class="col-12 col-md-6"><label class="form-label">Area of Specialization :</label><div class="mb-3"><input type="text" class="form-control" name="specialization[]" placeholder="Area of Specialization"></div></div>' +
        '<div class="col-12 col-md-6"><label class="form-label">Previous Institutions :</label><div class="mb-3"><input type="text" class="form-control" name="institution[]" placeholder="Previous Institutions"></div></div>' +
        '<div class="col-12 col-md-6"><label class="form-label">Position Held :</label><div class="mb-3"><input type="text" name="position[]" placeholder="Position Held" class="form-control"></div></div>' +
        '<div class="col-12 col-md-6"><label class="form-label">Duration :</label><div class="mb-3"><input type="number" name="duration[]" placeholder="Duration" class="form-control" min="0"></div></div>' +
        '<div class="col-12 col-md-6"><label class="form-label">Nature of Work :</label><div class="mb-3"><input type="text" name="work[]" placeholder="Nature of Work" class="form-control"></div></div>' +
        '<div class="col-auto ms-md-auto"><label class="form-label d-none d-md-block">&nbsp;</label><div class="mb-3"><button class="btn btn-outline-danger" type="button" onclick="remove_experience_fields(' + room + ');" title="Remove experience"><i class="bi bi-trash" aria-hidden="true"></i></button></div></div>';

    objTo.parentNode.insertBefore(divtest, objTo.nextSibling);
}

function remove_last_experience_field() {
    if (exp_room > 0) {
        $(".exp-removeclass" + exp_room).remove();
        exp_room--;
    }
}

var room = 1;

function course_fields() {
    room++;
    var objTo = document.getElementById("course_fields");
    var divtest = document.createElement("div");
    divtest.setAttribute("class", "mb-3 removeclass" + room);
    var rdiv = "removeclass" + room;
    divtest.innerHTML =
        '<form class="row"><div class="col-md-6"><label for="coursename" class="form-label">Course Name :</label><div class="mb-3"><input type="text" class="form-control" id="coursename" name="coursename" placeholder="Course Name"></div></div><div class="col-md-6"><label for="abbreviation" class="form-label">Abbreviation :</label><div class="mb-3"><input type="text" class="form-control" id="abbreviation" name="abbreviation" placeholder="Abbreviation"></div></div><div class="col-md-6"><label for="courseyear" class="form-label">Course Year :</label><div class="mb-3"><input type="text" class="form-control" id="courseyear" name="courseyear" placeholder="Course Year"></div></div><div class="col-md-6"><label for="startdate" class="form-label">Start Date :</label><div class="mb-3"><input type="text" class="form-control" id="startdate" name="startdate" placeholder="Start Date"></div></div><div class="col-md-6"><label for="enddate" class="form-label">End Date :</label><div class="mb-3"><input type="text" class="form-control" id="enddate" name="enddate" placeholder="End Date"></div></div><div class="col-sm-2"> <div class="form-group"> <button class="btn btn-danger btn-sm" type="button" onclick="remove_course_fields(' +
        room +
        ');"> <i class="material-icons menu-icon">remove</i> </button> </div></div></form> <hr>';

    objTo.appendChild(divtest);
}

function remove_course_fields(rid) {
    $(".removeclass" + rid).remove();
}

// Batch repeater

function remove_batch_fields(rid) {
    $(".removeclass" + rid).remove();
}
function batch_fields() {
    room++;
    var objTo = document.getElementById("batch_fields");
    var divtest = document.createElement("div");
    divtest.setAttribute("class", "mb-3 removeclass" + room);
    var rdiv = "removeclass" + room;
    divtest.innerHTML =
        '<form class="row"><div class="col-sm-5"><div class="form-group"><label for="Schoolname" class="form-label">Batch Name :</label><input type="text" class="form-control" id="Schoolname" name="Schoolname" placeholder="Batch Name"></div></div><div class="col-sm-5"> <div class="form-group"> <label for="Schoolname" class="form-label">Abbreviation :</label><input type="text" class="form-control" id="Age" name="Age" placeholder="Abbreviation"> </div></div><div class="col-sm-2"> <div class="form-group"> <button class="btn btn-danger" type="button" onclick="remove_course_fields(' +
        room +
        ');"> <i class="material-icons menu-icon">remove</i> </button> </div></div></form>';

    objTo.appendChild(divtest);
}

function remove_batch_fields(rid) {
    $(".removeclass" + rid).remove();
}

// Subject repeater

function remove_subject_fields(rid) {
    $(".removeclass" + rid).remove();
}
function subject_fields() {
    room++;
    var objTo = document.getElementById("subject_fields");
    var divtest = document.createElement("div");
    divtest.setAttribute("class", "mb-3 removeclass" + room);
    var rdiv = "removeclass" + room;
    divtest.innerHTML =
        '<form class="row"><div class="col-sm-5"><div class="form-group"><label for="Schoolname" class="form-label">Major Subject :</label><input type="text" class="form-control" id="Schoolname" name="Schoolname" placeholder="Major Subject"></div></div><div class="col-sm-5"> <div class="form-group"> <label for="Schoolname" class="form-label">Abbreviation :</label><input type="text" class="form-control" id="Age" name="Age" placeholder="Abbreviation"> </div></div><div class="col-sm-2"> <div class="form-group"> <button class="btn btn-danger" type="button" onclick="remove_course_fields(' +
        room +
        ');"> <i class="material-icons menu-icon">remove</i> </button> </div></div></form>';

    objTo.appendChild(divtest);
}

function remove_subject_fields(rid) {
    $(".removeclass" + rid).remove();
}

// Stream repeater

function remove_stream_fields(rid) {
    $(".removeclass" + rid).remove();
}
function stream_fields() {
    room++;
    var objTo = document.getElementById("stream_fields");
    var divtest = document.createElement("div");
    divtest.setAttribute("class", "mb-3 removeclass" + room);
    var rdiv = "removeclass" + room;
    divtest.innerHTML =
        '<form class="row"><div class="col-sm-10"><div class="form-group"><label for="Schoolname" class="form-label">Stream :</label><input type="text" class="form-control" id="Schoolname" name="Schoolname" placeholder="Stream"></div></div><div class="col-sm-2"> <div class="form-group"> <button class="btn btn-danger" type="button" onclick="remove_course_fields(' +
        room +
        ');"> <i class="material-icons menu-icon">remove</i> </button> </div></div></form>';

    objTo.appendChild(divtest);
}

function remove_stream_fields(rid) {
    $(".removeclass" + rid).remove();
}

// Country repeater

function remove_country_fields(rid) {
    $(".removeclass" + rid).remove();
}
function country_fields() {
    room++;
    var objTo = document.getElementById("country_fields");
    var divtest = document.createElement("div");
    divtest.setAttribute("class", "mb-3 removeclass" + room);
    var rdiv = "removeclass" + room;
    divtest.innerHTML =
        '<form class="row"><div class="col-sm-10"><div class="form-group"><label for="Schoolname" class="form-label">Country Name :</label><input type="text" class="form-control" id="Schoolname" name="Schoolname" placeholder="Country Name"></div></div><div class="col-sm-2"> <div class="form-group"> <button class="btn btn-danger" type="button" onclick="remove_course_fields(' +
        room +
        ');"> <i class="material-icons menu-icon">remove</i> </button> </div></div></form>';

    objTo.appendChild(divtest);
}

function remove_country_fields(rid) {
    $(".removeclass" + rid).remove();
}

// State repeater

function remove_state_fields(rid) {
    $(".removeclass" + rid).remove();
}
function state_fields() {
    room++;
    var objTo = document.getElementById("state_fields");
    var divtest = document.createElement("div");
    divtest.setAttribute("class", "mb-3 removeclass" + room);
    var rdiv = "removeclass" + room;
    divtest.innerHTML =
        '<form class="row"><div class="col-sm-10"><div class="form-group"><label for="Schoolname" class="form-label">State Name :</label><input type="text" class="form-control" id="Schoolname" name="Schoolname" placeholder="State Name"></div></div><div class="col-sm-2"> <div class="form-group"> <button class="btn btn-danger" type="button" onclick="remove_course_fields(' +
        room +
        ');"> <i class="material-icons menu-icon">remove</i> </button> </div></div></form>';

    objTo.appendChild(divtest);
}

function remove_state_fields(rid) {
    $(".removeclass" + rid).remove();
}
