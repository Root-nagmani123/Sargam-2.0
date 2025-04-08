$(function () {
  "use strict";

  // Default
  $(".repeater-default").repeater();

  // Custom Show / Hide Configurations
  $(".file-repeater, .email-repeater").repeater({
    show: function () {
      $(this).slideDown();
    },
    hide: function (remove) {
      if (confirm("Are you sure you want to remove this item?")) {
        $(this).slideUp(remove);
      }
    },
  });
});

var room = 1;

function education_fields() {
  room++;
  var objTo = document.getElementById("education_fields");
  var divtest = document.createElement("div");
  divtest.setAttribute("class", "mb-3 removeclass" + room);
  var rdiv = "removeclass" + room;
  divtest.innerHTML =
    '<form class="row"><div class="col-sm-3"><div class="form-group"><input type="text" class="form-control" id="Schoolname" name="Schoolname" placeholder="School Name"></div></div><div class="col-sm-2"> <div class="form-group"> <input type="text" class="form-control" id="Age" name="Age" placeholder="Age"> </div></div><div class="col-sm-2"> <div class="form-group"> <input type="text" class="form-control" id="Degree" name="Degree" placeholder="Degree"> </div></div><div class="col-sm-3"> <div class="form-group"> <select class="form-select" id="educationDate" name="educationDate"> <option>Date</option> <option value="2015">2015</option> <option value="2016">2016</option> <option value="2017">2017</option> <option value="2018">2018</option> </select> </div></div><div class="col-sm-2"> <div class="form-group"> <button class="btn btn-danger" type="button" onclick="remove_education_fields(' +
    room +
    ');"> <i class="material-icons menu-icon">remove</i> </button> </div></div></form>';

  objTo.appendChild(divtest);
}

function remove_education_fields(rid) {
  $(".removeclass" + rid).remove();
}
function experience_fields() {
  room++;
  var objTo = document.getElementById("experience_fields");
  var divtest = document.createElement("div");
  divtest.setAttribute("class", "mb-3 removeclass" + room);
  var rdiv = "removeclass" + room;
  divtest.innerHTML =
    '<form class="row"><div class="col-sm-3"><div class="form-group"><input type="text" class="form-control" id="Schoolname" name="Schoolname" placeholder="School Name"></div></div><div class="col-sm-2"> <div class="form-group"> <input type="text" class="form-control" id="Age" name="Age" placeholder="Age"> </div></div><div class="col-sm-2"> <div class="form-group"> <input type="text" class="form-control" id="Degree" name="Degree" placeholder="Degree"> </div></div><div class="col-sm-3"> <div class="form-group"> <select class="form-select" id="educationDate" name="educationDate"> <option>Date</option> <option value="2015">2015</option> <option value="2016">2016</option> <option value="2017">2017</option> <option value="2018">2018</option> </select> </div></div><div class="col-sm-2"> <div class="form-group"> <button class="btn btn-danger" type="button" onclick="remove_education_fields(' +
    room +
    ');"> <i class="material-icons menu-icon">remove</i> </button> </div></div></form>';

  objTo.appendChild(divtest);
}

function remove_experience_fields(rid) {
  $(".removeclass" + rid).remove();
}
$(function () {
  "use strict";

  // Default
  $(".repeater-default").repeater();

  // Custom Show / Hide Configurations
  $(".file-repeater, .email-repeater").repeater({
    show: function () {
      $(this).slideDown();
    },
    hide: function (remove) {
      if (confirm("Are you sure you want to remove this item?")) {
        $(this).slideUp(remove);
      }
    },
  });
});

var room = 1;

function course_fields() {
  room++;
  var objTo = document.getElementById("course_fields");
  var divtest = document.createElement("div");
  divtest.setAttribute("class", "mb-3 removeclass" + room);
  var rdiv = "removeclass" + room;
  divtest.innerHTML =
    '<form class="row"><div class="col-sm-5"><div class="form-group"><label for="Schoolname" class="form-label">School Name :</label><input type="text" class="form-control" id="Schoolname" name="Schoolname" placeholder="School Name"></div></div><div class="col-sm-5"> <div class="form-group"> <label for="Schoolname" class="form-label">School Name :</label><input type="text" class="form-control" id="Age" name="Age" placeholder="Age"> </div></div><div class="col-sm-2"> <div class="form-group"> <button class="btn btn-danger" type="button" onclick="remove_course_fields(' +
    room +
    ');"> <i class="material-icons menu-icon">remove</i> </button> </div></div></form>';

  objTo.appendChild(divtest);
}

function remove_course_fields(rid) {
  $(".removeclass" + rid).remove();
}
function experience_fields() {
  room++;
  var objTo = document.getElementById("experience_fields");
  var divtest = document.createElement("div");
  divtest.setAttribute("class", "mb-3 removeclass" + room);
  var rdiv = "removeclass" + room;
  divtest.innerHTML =
    '<form class="row"><div class="col-sm-5"><div class="form-group"><label for="Schoolname" class="form-label">School Name :</label><input type="text" class="form-control" id="Schoolname" name="Schoolname" placeholder="School Name"></div></div><div class="col-sm-5"> <div class="form-group"> <label for="Schoolname" class="form-label">School Name :</label><input type="text" class="form-control" id="Age" name="Age" placeholder="Age"> </div></div><div class="col-sm-2"> <div class="form-group"> <button class="btn btn-danger" type="button" onclick="remove_course_fields(' +
    room +
    ');"> <i class="material-icons menu-icon">remove</i> </button> </div></div></form>';

  objTo.appendChild(divtest);
}

function remove_experience_fields(rid) {
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