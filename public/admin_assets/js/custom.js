// Show Loader
function showLoader() {
    $('.preloader').show();
}
// Hide Loader
function hideLoader() {
    $('.preloader').hide();
}


function showAjaxLoader() {
    $('#ajaxLoader').removeClass('d-none');
}
function hideAjaxLoader() {
    $('#ajaxLoader').addClass('d-none');
}
// Step 4 : Contact Information


document.addEventListener('DOMContentLoaded', function () {
    const deleteForms = document.querySelectorAll('.delete-form');

    deleteForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const status = form.getAttribute('data-status');

            if (status == "1") {
                e.preventDefault(); // Stop form submission

                Swal.fire({
                    icon: 'warning',
                    title: 'Not Allowed',
                    text: 'Active List cannt be delete.',
                    confirmButtonColor: '#3085d6',
                });
            } else {
                // For inactive venues, confirm deletion
                e.preventDefault(); // Stop default first
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you really want to delete this?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit(); // Submit manually
                    }
                });
            }
        });
    });
});

$(document).on('change', 'input[name="styled_max_checkbox"]', function () {
    let address  = $('[name="address"]').val();
    let country  = $('[name="country"]').val();
    let state    = $('[name="state"]').val();
    let district = $('[name="district"]').val();
    let city     = $('[name="city"]').val();
    let postal   = $('[name="postal"]').val();

    const $permanentFields = {
        address: $('[name="permanentaddress"]'),
        country: $('[name="permanentcountry"]'),
        state: $('[name="permanentstate"]'),
        district: $('[name="permanentdistrict"]'),
        city: $('[name="permanentcity"]'),
        postal: $('[name="permanentpostal"]'),
    };

    if ($(this).is(':checked')) {
        if (!address || !country || !state || !district || !city || !postal) {
            alert('Please fill all the fields');
            $(this).prop('checked', false);
            return;
        }

        // Set values and apply readonly
        $permanentFields.address.val(address).prop('readonly', true);
        $permanentFields.postal.val(postal).prop('readonly', true);

        $permanentFields.country.val(country).attr('data-readonly', true);
        $permanentFields.state.val(state).attr('data-readonly', true);
        $permanentFields.district.val(district).attr('data-readonly', true);
        $permanentFields.city.val(city).attr('data-readonly', true);

    } else {
        // Clear values and remove readonly
        $permanentFields.address.val('').prop('readonly', false);
        $permanentFields.postal.val('').prop('readonly', false);

        $.each([$permanentFields.country, $permanentFields.state, $permanentFields.district, $permanentFields.city], function (_, $el) {
            $el.val('').removeAttr('data-readonly');
        });
    }
});

// Prevent change on readonly <select>
$(document).on('mousedown', 'select[data-readonly]', function (e) {
    e.preventDefault();
});



$(document).on('change', '.status-toggle', function () {
    let $checkbox = $(this);
    let table = $checkbox.data('table');
    let column = $checkbox.data('column');
    let id = $checkbox.data('id');
    let id_column = $checkbox.data('id_column');
    let status = $checkbox.is(':checked') ? 1 : 0;


    table = $(this).data('table');
    column = $(this).data('column');
    id = $(this).data('id');
    id_column = $(this).data('id_column');
    status = $(this).is(':checked') ? 1 : 0;
    // SweetAlert confirmation text based on status
    let actionText = status === 1 ? 'activate' : 'deactivate';

    Swal.fire({
        title: 'Are you sure?',
        text: `Are you sure? You want to ${actionText} this item?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: `Yes, ${actionText}`,
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceed with AJAX call
            updateStatus(table, column, id, id_column, status, $checkbox);
        } else {
            // Revert checkbox back
            $checkbox.prop('checked', !status);
        }
    });

    function updateStatus(table, column, id, id_column, status, $checkbox) {
        $.ajax({
            url: routes.toggleStatus, // Laravel route name define hona chahiye JS me
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                table: table,
                column: column,
                id: id,
                id_column: id_column,
                status: status
            },
            success: function (response) {
                $('#status-msg').html(`
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        ${response.message || 'Status updated successfully'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                 setTimeout(function() {
       // location.reload();
        $('#status-msg .alert').alert('close');
       //console.log(data);
    }, 1000);
            },
            error: function () {
                Swal.fire('Error', 'Status update failed', 'error');
                // Revert checkbox if error
                $checkbox.prop('checked', !status);
            }
        });
    }
});

// Faculty Form Creation

document.addEventListener('DOMContentLoaded', function () {

    $('input[name="photo"]').on('change', function () {
        console.log('Photo input changed');

        const input = this;
        const preview = $('#photoPreview');

        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function (e) {
                preview.attr('src', e.target.result).removeClass('d-none');
            };

            reader.readAsDataURL(input.files[0]);
        }
    });


    $('input[name="landline"], input[name="mobile"]').on('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });

    function updateFullName() {
        const first = $('input[name="firstName"]').val().trim();
        const middle = $('input[name="middlename"]').val().trim();
        const last = $('input[name="lastname"]').val().trim();

        // Construct full name, skipping empty values
        const fullName = [first, middle, last].filter(Boolean).join(' ');
        $('input[name="fullname"]').val(fullName);
    }

    $('input[name="firstName"], input[name="middlename"], input[name="lastname"]').on('input', updateFullName);

    $('#saveFacultyForm').click(function (e) {

        const formData = new FormData();
        // remove all error class
        $('label.text-danger').removeClass('text-danger');
        $('input').removeClass('is-invalid');
        $('span.text-danger').remove();

        let facultyType = $('select[name="facultytype"]').val();
        let firstName = $('input[name="firstName"]').val();
        let middleName = $('input[name="middlename"]').val();
        let lastName = $('input[name="lastname"]').val();
        let fullName = $('input[name="fullname"]').val();
        let gender = $('select[name="gender"]').val();
        let landline = $('input[name="landline"]').val();
        let mobile = $('input[name="mobile"]').val();
        let country = $('select[name="country"]').val();
        let state = $('select[name="state"]').val();
        let district = $('select[name="district"]').val();
        let city = $('select[name="city"]').val();
        let email = $('input[name="email"]').val();
        let alternativeEmail = $('input[name="alternativeEmail"]').val();
        let photo = $('input[name="photo"]').val();
        let document = $('input[name="document"]').val();
        let residence_address = $('input[name="residence_address"]').val();
        let permanent_address = $('input[name="permanent_address"]').val();
        let other_city = $('input[name="other_city"]').val();

        formData.append('facultyType', facultyType);
        formData.append('firstName', firstName);
        formData.append('middlename', middleName);
        formData.append('lastname', lastName);
        formData.append('fullname', fullName);
        formData.append('gender', gender);
        formData.append('landline', landline);
        formData.append('mobile', mobile);
        formData.append('country', country);
        formData.append('state', state);
        formData.append('district', district);
        formData.append('city', city);
        formData.append('email', email);
        formData.append('alternativeEmail', alternativeEmail);
        formData.append('residence_address', residence_address);
        formData.append('permanent_address', permanent_address);
        formData.append('other_city', other_city);

        // photo is file
        const photoInput = $('input[name="photo"]')[0];

        if (photoInput && photoInput.files.length > 0) {
            const photo = photoInput.files[0];
            formData.append('photo', photo);
        }
        // document is file
        const documentInput = $('input[name="document"]')[0];

        if (documentInput && documentInput.files.length > 0) {
            const documentFile = documentInput.files[0];
            formData.append('document', documentFile);
        }


        // Qualification Details
        let degrees = [];
        let universities = [];
        let years = [];
        let percentages = [];
        let certFiles = [];

        // Collect all education fields
        $('input[name="degree[]"]').each(function (index) {
            degrees.push($(this).val());
            universities.push($('input[name="university_institution_name[]"]').eq(index).val());
            years.push($('select[name="year_of_passing[]"]').eq(index).val());
            percentages.push($('input[name="percentage_CGPA[]"]').eq(index).val());

            // Handle certificate files
            let certInput = $('input[name="certificate[]"]')[index];
            if (certInput.files.length > 0) {
                certFiles.push(certInput.files[0]);
            } else {
                certFiles.push(null);
            }
        });

        // Append all education data to formData
        degrees.forEach((degree, index) => {
            formData.append('degree[]', degree);
            formData.append('university_institution_name[]', universities[index]);
            formData.append('year_of_passing[]', years[index]);
            formData.append('percentage_CGPA[]', percentages[index]);

            if (certFiles[index]) {
                formData.append('certificate[]', certFiles[index]);
            } else {
                formData.append('certificate[]', null);
            }
        });

        // Collecting the Experience Details
        let experience = [];
        let specialization = [];
        let institution = [];
        let position = [];
        let duration = [];
        let work = [];

        // Collect all experience fields
        $('input[name="experience[]"]').each(function (index) {
            experience.push($(this).val());
            specialization.push($('input[name="specialization[]"]').eq(index).val());
            institution.push($('input[name="institution[]"]').eq(index).val());
            position.push($('input[name="position[]"]').eq(index).val());
            duration.push($('input[name="duration[]"]').eq(index).val());
            work.push($('input[name="work[]"]').eq(index).val());
        });

        // Experience Details
        if (experience.length > 0) {
            experience.forEach((exp, index) => {
                formData.append('experience[]', exp);
                formData.append('specialization[]', specialization[index]);
                formData.append('institution[]', institution[index]);
                formData.append('position[]', position[index]);
                formData.append('duration[]', duration[index]);
                formData.append('work[]', work[index]);
            });
        }

        // Bank Details
        let bankName = $('input[name="bankname"]').val();
        let accountNumber = $('input[name="accountnumber"]').val();
        let ifscCode = $('input[name="ifsccode"]').val();
        let panNumber = $('input[name="pannumber"]').val();
        let facultyId = $('input[name="faculty_id"]').val();

        formData.append('bankname', bankName);
        formData.append('accountnumber', accountNumber);
        formData.append('ifsccode', ifscCode);
        formData.append('pannumber', panNumber);

        if (facultyId != '' && facultyId != null && facultyId != undefined) {
            formData.append('faculty_id', facultyId);
        }

        // Other information
        let researchPublications = $('input[name="researchpublications"]').val();
        let professionalMemberships = $('input[name="professionalmemberships"]').val();
        let recommendationDetails = $('input[name="recommendationdetails"]').val();
        let joiningDate = $('input[name="joiningdate"]').val();

        // researchPublications is file
        if ($('input[name="researchpublications"]')[0] && $('input[name="researchpublications"]')[0].files.length > 0) {
            researchPublications = $('input[name="researchpublications"]')[0].files[0];
            formData.append('researchpublications', researchPublications);
        }

        // professionalMemberships is file
        if ($('input[name="professionalmemberships"]')[0] && $('input[name="professionalmemberships"]')[0].files.length > 0) {
            professionalMemberships = $('input[name="professionalmemberships"]')[0].files[0];
            formData.append('professionalmemberships', professionalMemberships);
        }

        // recommendationDetails is file
        if ($('input[name="recommendationdetails"]')[0] && $('input[name="recommendationdetails"]')[0].files.length > 0) {
            recommendationDetails = $('input[name="recommendationdetails"]')[0].files[0];
            formData.append('recommendationdetails', recommendationDetails);
        }

        // faculties
        let faculties = [];
        $('input[name="faculties[]"]:checked').each(function () {
            faculties.push($(this).val());
        });
        faculties.forEach((faculty) => {
            formData.append('faculties[]', faculty);
        });

        // current_sector
        let currentSector = $('input[name="current_sector"]:checked').val();
        formData.append('current_sector', currentSector);

        // append csrf token
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('joiningdate', joiningDate);


        $.ajax({
            type: 'POST',
            url: routes.facultyStoreUrl,
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function () {
                showLoader();
            },
            success: function (response) {

                // Handle success response
                if (response.status) {
                    toastr.options = {
                        timeOut: 50, // 1.5 seconds
                        onHidden: function () {
                            window.location.href = routes.facultyIndexUrl;
                        }
                    };

                    toastr.success(response.message);

                } else {
                    toastr.error(response.message);
                }
            },
            error: function (error) {
                console.log('Error:', error);
                if (error.status == 422) {
                    let errors = error.responseJSON.errors;

                    for (let key in errors) {
                        // Handle array fields (e.g., degree.0, university_institution_name.1)
                        if (key.includes('.')) {
                            const [fieldName, index] = key.split('.');
                            const inputField = $(`[name="${fieldName}[]"]`).eq(index);
                            const label = $(`label[for="${inputField.attr('id')}"]`);

                            if (inputField.length > 0) {
                                label.addClass('text-danger').append(` <span class="text-danger">*</span>`);
                                const errorDiv = $('<span class="text-danger mt-1"></span><br/>').text(errors[key][0]);
                                inputField.addClass('is-invalid').after(errorDiv);
                            }
                        }
                        // Handle regular fields
                        else {
                            const inputField = $(`[name="${key}"], select[name="${key}"]`);
                            const label = $(`label[for="${inputField.attr('id')}"]`);
                            if (inputField.length > 0) {
                                label.addClass('text-danger').append(` <span class="text-danger">*</span>`);
                                const errorDiv = $('<span class="text-danger mt-1"></span><br/>').text(errors[key][0]);
                                inputField.addClass('is-invalid').after(errorDiv);
                            }
                        }
                    }
                } else {
                    toastr.error('Something went wrong. Please try again.');
                }
            },
            complete: function () {
                hideLoader();
            }
        });
    })

    $(document).on('change', '#country, #permanentcountry', function () {
        const $form = $(this).closest('form');
        const countryId = $(this).val();
        let currentElement = this;
        if (countryId !== '') {
            $.ajax({
                url: routes.getStatesByCountry,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    country_id: countryId
                },
                success: function (response) {
                    if (response.status) {
                        let $stateSelect;
                        if($(currentElement).attr('id') == 'permanentcountry') {
                            $stateSelect = $form.find('#permanentstate');
                        } else {
                            $stateSelect = $form.find('#state');
                        }
                        $stateSelect.empty().append('<option value="">Select State</option>');
                        response.states.forEach(function (state) {
                            $stateSelect.append(`<option value="${state.pk}">${state.state_name}</option>`);
                        });
                    } else {
                        toastr.error(response.message || 'Failed to load states.');
                    }
                },
                error: function () {
                    toastr.error('Error fetching states.');
                }
            });
        }
    });


    // When state changes — load districts
    $(document).on('change', '#state, #permanentstate', function () {
        const $form = $(this).closest('form');
        const stateId = $(this).val();
        let currentElement = this;
        if (stateId !== '') {
            $.ajax({
                url: routes.getDistrictsByState,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    state_id: stateId
                },
                success: function (response) {
                    if (response.status) {
                        let $districtSelect = '';
                        if($(currentElement).attr('id') == 'permanentstate') {
                            $districtSelect = $form.find('#permanentdistrict');
                        } else {
                            $districtSelect = $form.find('#district');
                        }
                        $districtSelect.empty().append('<option value="">Select District</option>');
                        response.districts.forEach(function (district) {
                            $districtSelect.append(`<option value="${district.pk}">${district.district_name}</option>`);
                        });

                    } else {
                        toastr.error(response.message || 'Failed to load districts.');
                    }
                },
                error: function () {
                    toastr.error('Error fetching districts.');
                }
            });
        }
    });

    // When district changes — load cities
    $(document).on('change', '#district, #permanentdistrict', function () {
        const $form = $(this).closest('form');
        const districtId = $(this).val();
        let currentElement = this;
        if (districtId !== '') {
            $.ajax({
                url: routes.getCitiesByDistrict,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    district_id: districtId
                },
                success: function (response) {
                    if (response.status) {
                        let $citySelect = '';
                        if($(currentElement).attr('id') == 'permanentdistrict') {
                            $citySelect = $form.find('#permanentcity');
                            console.log('if part');
                        } else {
                            $citySelect = $form.find('#city');
                            console.log('else part');
                        }
                        $citySelect.empty().append('<option value="">Select City</option>');
                        response.cities.forEach(function (city) {
                            $citySelect.append(`<option value="${city.pk}">${city.city_name}</option>`);
                        });
                    } else {
                        toastr.error(response.message || 'Failed to load cities.');
                    }
                },
                error: function () {
                    toastr.error('Error fetching cities.');
                }
            });
        }
    });

    $(document).on('change', '#customCheck4', function () {
    const isChecked = $(this).is(':checked');

        if (isChecked) {
            // 1. Copy immediate fields
            $('#permanentaddress').val($('#address').val());
            $('#permanentpostal').val($('#postal').val());

            // 2. Start cascading copy with delay (country → state → district → city)
            let countryId = $('#country').val();
            $('#permanentcountry').val(countryId).trigger('change');

            // Wait for state options to load via AJAX
            setTimeout(function () {
                let stateId = $('#state').val();
                $('#permanentstate').val(stateId).trigger('change');


                // Wait for district options
                setTimeout(function () {
                    let districtId = $('#district').val();
                    $('#permanentdistrict').val(districtId).trigger('change');

                    // Wait for city options
                    setTimeout(function () {
                        let cityId = $('#city').val();
                        $('#permanentcity').val(cityId).trigger('change');
                    }, 500);

                }, 500);

            }, 500);


            if (!$('#otherCityContainer').hasClass('d-none')) {
                $('#permanentOtherCityContainer').removeClass('d-none');
                $('input[name="permanent_other_city"]').val($('input[name="other_city"]').val());
            } else {
                $('#permanentOtherCityContainer').addClass('d-none');
                $('input[name="permanent_other_city"]').val('');
            }

        } else {
            // Unchecked: Enable and clear permanent fields
            $('#permanentaddress, #permanentpostal, #permanentcountry, #permanentstate, #permanentdistrict, #permanentcity')
                .prop('disabled', false)
                .val('')
                .trigger('change');
            $('#permanentOtherCityContainer').addClass('d-none');
            $('input[name="permanent_other_city"]').val('');
        }
    });

    // Faculty When City is Other
    $(document).on('change', '#city', function () {
    // $('#city').on('change', function () {

        console.log($(this).find('option:selected').text().toLowerCase().trim());
        console.log($(this).find('option:selected').text().toLowerCase().trim() == 'other');

        if ($(this).find('option:selected').text().toLowerCase().trim() == 'other') {
            console.log('Other city selected');
            $('#otherCityContainer').removeClass('d-none');
        } else {
            $('#otherCityContainer').addClass('d-none');
        }
    });

    $(document).on('change', '#permanentcity', function () {
        const selectedText = $(this).find('option:selected').text().toLowerCase().trim();
        if (selectedText === 'other') {
            $('#permanentOtherCityContainer').removeClass('d-none');
        } else {
            $('#permanentOtherCityContainer').addClass('d-none');
        }
    });
});

// Group Mapping Modules

$(document).ready(function () {

    function resetImportModal() {
        $('#importErrorTableBody').empty();
        $('#importErrors').addClass('d-none');
        $('#importExcelForm')[0].reset();
    }

    // Handle close (X) and cancel buttons
    $('#importModal').on('click', '.btn-close, .btn-cancel', function (e) {
        e.preventDefault();
        resetImportModal();
    });

    // Also reset when modal fully hides (in case of backdrop click or ESC key)
    $('#importModal').on('hidden.bs.modal', function () {
        resetImportModal();
    });

    // Handle import upload button click
    $('#upload_import').on('click', function (e) {
        e.preventDefault();

        const fileInput = $('#importFile')[0];
        if (fileInput.files.length === 0) {
            alert('Please select a file to upload.');
            return;
        }

        const fileName = fileInput.files[0].name;
        const allowedExtensions = /\.(xlsx|xls|csv)$/i;
        if (!allowedExtensions.test(fileName)) {
            alert('Invalid file type. Please upload a .xlsx, .xls, or .csv file.');
            fileInput.value = '';
            return;
        }

        const formData = new FormData($('#importExcelForm')[0]);

        $.ajax({
            url: routes.groupMappingExcelUpload,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $('#upload_import').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Uploading...');
            },
            success: function (response) {

                alert('File imported successfully!');
                $('#importModal').modal('hide');
                resetImportModal();
                location.reload();
            },
            error: function (xhr) {
                console.log('Error response:', xhr);
                $('#importErrorTableBody').empty();

                if (xhr.status === 422 && xhr.responseJSON.failures) {
                    let failures = xhr.responseJSON.failures;
                    failures.forEach(function (failure) {
                        let errorRow = `
                            <tr>
                                <td><p class="text-danger">${failure.row}</p></td>
                                <td><p class="text-danger">${failure.errors.join('<br>')}</p></td>
                            </tr>
                        `;
                        $('#importErrorTableBody').append(errorRow);
                    });
                    $('#importErrors').removeClass('d-none').addClass('');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    alert(xhr.responseJSON.message);
                } else {
                    alert('An unexpected error occurred.');
                }
            },
            complete: function () {
                $('#upload_import').prop('disabled', false).html('<i class="mdi mdi-upload"></i> Upload & Import');
            }
        });
    });

    $(".select2").select2();
});


window.selectedOtIds = new Set();
window.currentGroupMappingId = null;

function resetBulkMessageForm() {
    $('#bulkMessageText').val('');
    $('#bulkMessageCharCount').text('0');
    clearBulkMessageAlert();
    $('#bulkMessageContainer').addClass('d-none');
}

function clearBulkMessageAlert() {
    $('#bulkMessageAlert')
        .addClass('d-none')
        .removeClass('alert-success alert-danger alert-warning')
        .text('');
}

function showBulkMessageAlert(type, message) {
    const alertBox = $('#bulkMessageAlert');
    if (!alertBox.length) {
        return;
    }

    alertBox
        .removeClass('d-none alert-success alert-danger alert-warning')
        .addClass(`alert alert-${type}`)
        .text(message);
}

function updateSelectedOtCount() {
    const count = window.selectedOtIds ? window.selectedOtIds.size : 0;
    $('#selectedOtCount').text(`${count} OT(s) selected`);

    const hasSelection = count > 0;
    $('#toggleBulkMessage').prop('disabled', !hasSelection);
    $('.send-bulk-message').prop('disabled', !hasSelection);

    if (!hasSelection) {
        $('#bulkMessageContainer').addClass('d-none');
        clearBulkMessageAlert();
    }
}

function updateSelectAllState() {
    const checkboxList = $('.student-select');
    const selectAll = $('#selectAllOts');

    if (!selectAll.length) {
        return;
    }

    if (!checkboxList.length) {
        selectAll.prop({
            checked: false,
            indeterminate: false,
        });
        return;
    }

    const total = checkboxList.length;
    const checked = checkboxList.filter(':checked').length;

    if (checked === 0) {
        selectAll.prop({
            checked: false,
            indeterminate: false,
        });
    } else if (checked === total) {
        selectAll.prop({
            checked: true,
            indeterminate: false,
        });
    } else {
        selectAll.prop({
            checked: false,
            indeterminate: true,
        });
    }
}

function initializeStudentListSelection() {
    $('.student-select').each(function () {
        const studentId = $(this).val();
        if (window.selectedOtIds && window.selectedOtIds.has(studentId)) {
            $(this).prop('checked', true);
        }
    });

    updateSelectAllState();
    updateSelectedOtCount();
}

function showEditStudentAlert(type, message) {
    const alertEl = $('#editStudentAlert');
    alertEl.removeClass('d-none alert-success alert-danger alert-warning alert-info')
        .addClass(`alert alert-${type}`)
        .text(message);
}

function clearEditStudentAlert() {
    $('#editStudentAlert').addClass('d-none').removeClass('alert-success alert-danger alert-warning alert-info').text('');
}

function reloadStudentDetails(pageUrl = null, searchQuery = null) {
    const groupMappingID = $('#groupMappingEncryptedId').val() || window.currentGroupMappingId;
    const token = $('meta[name="csrf-token"]').attr('content');

    if (!groupMappingID || !token || !routes.groupMappingStudentList) {
        return;
    }

    // Get search query from input if not provided
    if (searchQuery === null) {
        searchQuery = $('#studentSearchInput').val() || '';
    }

    // Prepare data object
    const requestData = {
        _token: token,
        groupMappingID: groupMappingID
    };

    // Add search query if provided
    if (searchQuery && searchQuery.trim()) {
        requestData.search = searchQuery.trim();
    }

    // Extract page number from URL if pageUrl is provided
    let page = 1;
    if (pageUrl) {
        const urlParams = new URLSearchParams(pageUrl.split('?')[1]);
        page = urlParams.get('page') || 1;
        requestData.page = page;
    }

    $.ajax({
        url: routes.groupMappingStudentList,
        type: 'POST',
        data: requestData,
        success: function (response) {
            if (!response || !response.html) {
                return;
            }
            $('#studentDetailsContent').html(response.html);
            const encryptedId = $('#groupMappingEncryptedId').val();
            if (encryptedId) {
                window.currentGroupMappingId = encryptedId;
            }
            initializeStudentListSelection();
            initStudentActionTooltips();
            
            // Update search results count
            updateSearchResultsCount();
        },
        error: function () {
            alert('Error refreshing student details.');
        }
    });
}

function setSendingState(channel, isSending) {
    const button = $(`.send-bulk-message[data-channel="${channel}"]`);
    if (!button.length) {
        return;
    }

    if (isSending) {
        button.data('original-html', button.html());
        button.prop('disabled', true).html(`
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Sending...
        `);
        $('.send-bulk-message').not(button).prop('disabled', true);
    } else {
        const originalHtml = button.data('original-html');
        if (originalHtml) {
            button.html(originalHtml);
        }
        $('.send-bulk-message').prop('disabled', window.selectedOtIds.size === 0);
    }
}


$(document).on('click', '.view-student', function (e) {
    e.preventDefault();

    const groupMappingID = $(this).data('id');
    const token = $('meta[name="csrf-token"]').attr('content');

    window.selectedOtIds = new Set();
    window.currentGroupMappingId = groupMappingID;
    resetBulkMessageForm();
    updateSelectedOtCount();
    
    // Clear search when opening modal
    $('#studentSearchInput').val('');
    $('#clearStudentSearch').hide();
    $('#studentSearchResultsCount').text('');

    $.ajax({
        url: routes.groupMappingStudentList,
        type: 'POST',
        data: {
            _token: token,
            groupMappingID: groupMappingID
        },
        success: function (response) {
            $('#studentDetailsContent').html(response.html);
            const encryptedId = $('#groupMappingEncryptedId').val();
            if (encryptedId) {
                window.currentGroupMappingId = encryptedId;
            }
            initializeStudentListSelection();
            initStudentActionTooltips();
            updateSearchResultsCount();
            $('#studentDetailsModal').modal('show');
            
            // Focus search input after modal is shown (one-time event)
            $('#studentDetailsModal').one('shown.bs.modal', function () {
                $('#studentSearchInput').focus();
            });
        },
        error: function () {
            alert('Error fetching student details');
        }
    });
});


function updateSearchResultsCount() {
    const searchValue = $('#studentSearchInput').val().trim();
    const countElement = $('#studentSearchResultsCount');
    
    if (searchValue) {
        // Try to get count from the table
        const rowCount = $('.student-table-wrapper tbody tr').length;
        const hasNoResults = $('.student-table-wrapper tbody tr').first().find('td').text().includes('No students found');
        
        if (hasNoResults) {
            countElement.text('No results found').removeClass('text-success').addClass('text-danger');
        } else {
            countElement.text(`${rowCount} result(s) found`).removeClass('text-danger').addClass('text-success');
        }
    } else {
        countElement.text('');
    }
}

$(document).on('click', '.student-list-pagination .pagination a', function (e) {
    e.preventDefault();

    const pageUrl = $(this).attr('href');
    reloadStudentDetails(pageUrl);
});

$(document).on('change', '.student-select', function () {
    if (!window.selectedOtIds) {
        window.selectedOtIds = new Set();
    }

    const studentId = $(this).val();

    if ($(this).is(':checked')) {
        if (studentId) {
            window.selectedOtIds.add(studentId);
        }
    } else if (studentId) {
        window.selectedOtIds.delete(studentId);
    }

    updateSelectAllState();
    updateSelectedOtCount();
});

$(document).on('change', '#selectAllOts', function () {
    const shouldSelect = $(this).is(':checked');
    const checkboxes = $('.student-select');

    checkboxes.each(function () {
        const isChecked = $(this).is(':checked');
        if (isChecked !== shouldSelect) {
            $(this).prop('checked', shouldSelect).trigger('change');
        }
    });

    updateSelectAllState();
});

$(document).on('click', '#toggleBulkMessage', function (e) {
    e.preventDefault();

    if ($(this).prop('disabled')) {
        return;
    }

    const container = $('#bulkMessageContainer');
    const isHidden = container.hasClass('d-none');

    if (isHidden) {
        container.removeClass('d-none');
        clearBulkMessageAlert();
        $('#bulkMessageText').trigger('input').focus();
    } else {
        container.addClass('d-none');
        clearBulkMessageAlert();
    }
});

$(document).on('click', '#closeBulkMessage', function (e) {
    e.preventDefault();
    $('#bulkMessageContainer').addClass('d-none');
    clearBulkMessageAlert();
});

$(document).on('input', '#bulkMessageText', function () {
    $('#bulkMessageCharCount').text($(this).val().length);
});

$(document).on('click', '.send-bulk-message', function (e) {
    e.preventDefault();

    if ($(this).prop('disabled')) {
        return;
    }

    const channel = $(this).data('channel');
    const message = ($('#bulkMessageText').val() || '').trim();
    const studentIds = window.selectedOtIds ? Array.from(window.selectedOtIds) : [];
    const groupMappingID = $('#groupMappingEncryptedId').val() || window.currentGroupMappingId;
    const token = $('meta[name="csrf-token"]').attr('content');

    if (!groupMappingID) {
        showBulkMessageAlert('danger', 'Missing group mapping details. Please reload the student list.');
        return;
    }

    if (!studentIds.length) {
        showBulkMessageAlert('warning', 'Please select at least one OT.');
        return;
    }

    if (!message.length) {
        showBulkMessageAlert('warning', 'Please enter a message before sending.');
        $('#bulkMessageText').focus();
        return;
    }

    setSendingState(channel, true);

    $.ajax({
        url: routes.groupMappingSendMessage,
        type: 'POST',
        data: {
            _token: token,
            channel: channel,
            message: message,
            student_ids: studentIds,
            group_mapping_id: groupMappingID
        },
        success: function (response) {
            if (response && response.status === 'success') {
                showBulkMessageAlert('success', response.message || 'Message sent successfully.');
            } else {
                const infoMessage = (response && response.message) ? response.message : 'Message sent with warnings.';
                showBulkMessageAlert('warning', infoMessage);
            }
        },
        error: function (xhr) {
            const errorMessage = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'Unable to send the message. Please try again.';
            showBulkMessageAlert('danger', errorMessage);
        },
        complete: function () {
            setSendingState(channel, false);
            updateSelectedOtCount();
        }
    });
});

$('#studentDetailsModal').on('hidden.bs.modal', function () {
    window.selectedOtIds = new Set();
    window.currentGroupMappingId = null;
    resetBulkMessageForm();
    updateSelectedOtCount();
    // Clear search when modal is closed
    $('#studentSearchInput').val('');
    $('#clearStudentSearch').hide();
    $('#studentSearchResultsCount').text('');
});

// Student search functionality with debouncing
let studentSearchTimeout;
$(document).on('input', '#studentSearchInput', function () {
    const searchValue = $(this).val().trim();
    const clearBtn = $('#clearStudentSearch');
    
    // Show/hide clear button
    if (searchValue.length > 0) {
        clearBtn.show();
    } else {
        clearBtn.hide();
    }
    
    // Clear previous timeout
    clearTimeout(studentSearchTimeout);
    
    // Set new timeout for debouncing (500ms delay)
    studentSearchTimeout = setTimeout(function () {
        if (window.currentGroupMappingId) {
            reloadStudentDetails(null, searchValue);
        }
    }, 500);
});

// Clear search button
$(document).on('click', '#clearStudentSearch', function () {
    $('#studentSearchInput').val('').trigger('input');
});

// Handle Enter key in search input
$(document).on('keypress', '#studentSearchInput', function (e) {
    if (e.which === 13) {
        e.preventDefault();
        clearTimeout(studentSearchTimeout);
        const searchValue = $(this).val().trim();
        if (window.currentGroupMappingId) {
            reloadStudentDetails(null, searchValue);
        }
    }
});

function initStudentActionTooltips() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
        return;
    }

    document.querySelectorAll('.student-action-btn').forEach(function (el) {
        const existingTooltip = bootstrap.Tooltip.getInstance(el);
        if (existingTooltip) {
            existingTooltip.dispose();
        }
        new bootstrap.Tooltip(el);
    });
}


$(document).on('click', '.edit-student', function () {
    const button = $(this);
    const studentId = button.data('student-id');
    if (!studentId) {
        return;
    }

    clearEditStudentAlert();
    $('#editStudentId').val(studentId);
    $('#editStudentName').val(button.data('name') || '');
    $('#editStudentEmail').val(button.data('email') || '');
    $('#editStudentContact').val(button.data('contact') || '');
    $('#editStudentModal').modal('show');
});

$('#editStudentForm').on('submit', function (e) {
    e.preventDefault();

    const form = $(this);
    const submitButton = form.find('button[type="submit"]');

    clearEditStudentAlert();
    submitButton.prop('disabled', true);

    $.ajax({
        url: routes.groupMappingStudentUpdate,
        type: 'POST',
        data: form.serialize(),
        success: function (response) {
            showEditStudentAlert('success', (response && response.message) ? response.message : 'Student updated successfully.');
            reloadStudentDetails();
            setTimeout(function () {
                $('#editStudentModal').modal('hide');
            }, 800);
        },
        error: function (xhr) {
            const message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'Unable to update student details.';
            showEditStudentAlert('danger', message);
        },
        complete: function () {
            submitButton.prop('disabled', false);
        }
    });
});

$(document).on('click', '.delete-student', function () {
    const button = $(this);
    const mappingId = button.data('mapping-id');
    const studentName = button.data('name') || 'this student';

    if (!mappingId) {
        return;
    }

    if (!confirm(`Are you sure you want to remove ${studentName} from this group?`)) {
        return;
    }

    const token = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: routes.groupMappingStudentDelete,
        type: 'DELETE',
        data: {
            _token: token,
            mapping_id: mappingId
        },
        success: function (response) {
            if (response && response.message) {
                alert(response.message);
            }
            reloadStudentDetails();
        },
        error: function (xhr) {
            const message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'Unable to remove the student from this group.';
            alert(message);
        }
    });
});

$('#editStudentModal').on('hidden.bs.modal', function () {
    $('#editStudentForm')[0].reset();
    $('#editStudentId').val('');
    clearEditStudentAlert();
});

// End Group Mapping Modules

// MDO/Escort Exemption
// let dualListbox; // To keep reference for later reinitialization

// document.addEventListener('DOMContentLoaded', function () {
//     // Initialize DualListbox on page load
//     dualListbox = new DualListbox("#select", {
//         addEvent: function (value) {

//         },
//         removeEvent: function (value) {

//         },
//         availableTitle: "Defaulter Students",
//         selectedTitle: "Selected Students",
//         addButtonText: "Move Right",
//         removeButtonText: "Move Left",
//         addAllButtonText: "Move All Right",
//         removeAllButtonText: "Move All Left",
//         draggable: true
//     });

//     $('.course-selected').on('change', function () {
//         let selectedCourses = $(this).val();

//         if (selectedCourses.length > 0) {
//             $.ajax({
//                 url: routes.getStudentListAccordingToGroup,
//                 type: 'POST',
//                 data: {
//                     _token: $('meta[name="csrf-token"]').attr('content'),
//                     selectedCourses: selectedCourses
//                 },
//                 success: function (response) {
//                     if (response.status) {
//                         if (response.students.length === 0) {
//                             alert('No students found for the selected courses.');
//                             return;
//                         }

//                         const currentSelected = $('#select').val();
//                         $('#select').empty();

//                         // Append new options
//                         response.students.forEach(student => {
//                             $('#select').append(
//                                 $('<option>', {
//                                     value: student.pk,
//                                     text: student.display_name
//                                 })
//                             );
//                         });

//                         // Destroy the old dual listbox wrapper (if needed)
//                         $('.dual-listbox').remove(); // depends on your plugin structure

//                         // Reinitialize the DualListbox
//                         dualListbox = new DualListbox("#select", {
//                             addEvent: function (value) { },
//                             removeEvent: function (value) { },
//                             availableTitle: "Available Students",
//                             selectedTitle: "Selected Students",
//                             addButtonText: "Move Right",
//                             removeButtonText: "Move Left",
//                             addAllButtonText: "Move All Right",
//                             removeAllButtonText: "Move All Left",
//                             draggable: true
//                         });

//                     } else {
//                         alert(response.message);
//                     }
//                 },
//                 error: function () {
//                     alert('Error fetching student list');
//                 }
//             });
//         }
//     });

//     if (window.triggerCourseChange) {
//         setTimeout(function () {
//             $('.course-selected').trigger('change');
//         }, 500); // delay ensures event handlers are attached
//     }
// });
document.addEventListener('DOMContentLoaded', function () {
    // Initialize DualListbox on page load
    dualListbox = new DualListbox("#select_memo_student", {
        addEvent: function (value) {

        },
        removeEvent: function (value) {

        },
        availableTitle: "Defaulter Students",
        selectedTitle: "Selected Students",
        addButtonText: "Move Right",
        removeButtonText: "Move Left",
        addAllButtonText: "Move All Right",
        removeAllButtonText: "Move All Left",
        draggable: true
    });

    $('#topic_id').on('change', function () {

        let topic_id = $(this).val();

        if (topic_id.length > 0) {
            $.ajax({
                url: routes.getStudentAttendanceBytopic,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    topic_id: topic_id
                },
                success: function (response) {
                    if (response.status) {
                        const currentSelected = $('#select_memo_student').val() || [];
                        $('#select_memo_student').empty();

                        // Append new options (even if empty array)
                        if (response.students && response.students.length > 0) {
                            response.students.forEach(student => {
                                const isSelected = currentSelected.includes(student.pk.toString());
                                $('#select_memo_student').append(
                                    $('<option>', {
                                        value: student.pk,
                                        text: student.display_name,
                                        selected: isSelected
                                    })
                                );
                            });
                        } else {
                            // Show message if no defaulters found, but don't prevent UI update
                            console.log('No defaulter students found for this topic.');
                        }

                        // Destroy the old dual listbox wrapper
                        if (typeof dualListbox !== 'undefined' && dualListbox) {
                            try {
                                dualListbox.destroy();
                            } catch(e) {
                                console.log('Error destroying dual listbox:', e);
                            }
                        }
                        $('.dual-listbox').remove();

                        // Reinitialize the DualListbox
                        dualListbox = new DualListbox("#select_memo_student", {
                            addEvent: function (value) { },
                            removeEvent: function (value) { },
                            availableTitle: "Defaulter Students",
                            selectedTitle: "Selected Students",
                            addButtonText: "Move Right",
                            removeButtonText: "Move Left",
                            addAllButtonText: "Move All Right",
                            removeAllButtonText: "Move All Left",
                            draggable: true
                        });

                    } else {
                        alert(response.message || 'Error fetching student list.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                    alert('Error fetching defaulter students. Please check the console for details.');
                }
            });
        }
    });
});



// END MDO/Escort Exemption

// Attendance
$(document).on('click', '#searchAttendance', function () {
    let programme = $('#programme').val();
    let fromDate = $('#from_date').val();
    let toDate = $('#to_date').val();
    // let viewType = $('#view_type').val();

    let sessionTypeValue = '';
    let attendanceType = $('input[name="attendance_type"]:checked').val();
    if(attendanceType === 'normal') {
        sessionTypeValue = $('#session').val();
    }
    if(attendanceType === 'manual') {
        sessionTypeValue = $('#manual_session').val();
    }



    // || !viewType
    // Validate inputs
    // if (!programme || !fromDate || !toDate ) {
    //     alert('Please fill all fields before searching.');
    //     return;
    // }
    $.ajax({
        url: routes.getAttendanceList, // initial check only (optional)
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            programme: programme,
            from_date: fromDate,
            to_date: toDate,
            session_value: sessionTypeValue,
            attendance_type: attendanceType,
            // view_type: viewType
        },
        beforeSend: function () {
            showAjaxLoader();
        },
        complete: function () {
            hideAjaxLoader();
        },
        success: function (response) {
            // Optional: validate response format
            if (response && typeof response === 'object') {
                drawAttendanceTable();
            } else {
                alert("Unexpected response format.");
            }
        },
        error: function () {
            alert('Failed to fetch attendance data.');
        }
    });
});

let attendanceTable; // global variable

function drawAttendanceTable() {
    if ($.fn.DataTable.isDataTable('#attendanceTable')) {
        attendanceTable.destroy(); // destroy previous instance
    }

    attendanceTable = $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: routes.getAttendanceList,
            type: 'POST',
            data: function (d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.programme = $('#programme').val();
                d.from_date = $('#from_date').val();
                d.to_date = $('#to_date').val();
                d.view_type = $('#view_type').val();
            }
        },
        drawCallback: function () {
            $('#attendanceTableCard').removeClass('d-none');
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'programme_name', name: 'programme_name' },
            { data: 'mannual_starttime', name: 'mannual_starttime' },
            { data: 'session_time', name: 'session_time', orderable: false, searchable: false },
            { data: 'venue_name', name: 'venue_name' },
            { data: 'group_name', name: 'group_name' },
            { data: 'subject_topic', name: 'subject_topic' },
            { data: 'faculty_name', name: 'faculty_name' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });
}


$(document).ready(function() {

    $('#normal_session_container').hide();
    $('#manual_session_container').hide();


    $('input[name="attendance_type"]').change(function() {
        $('#normal_session_container').hide();
        $('#manual_session_container').hide();

        $('#session').val('').trigger('change');
        $('#manual_session').val('').trigger('change');

        if ($(this).val() === 'normal') {
            $('#normal_session_container').show();
        } else if ($(this).val() === 'manual') {
            $('#manual_session_container').show();
        }
        // For 'full_day', both remain hidden
    });

    // Trigger change on page load to show/hide based on default checked radio
    $('input[name="attendance_type"]:checked').trigger('change');
});
// End of students in attendance

// End Attendance

$('#upload_import_hostel_mapping_to_student').on('click', function (e) {
    e.preventDefault();

    const fileInput = $('#importFile')[0];
    if (fileInput.files.length === 0) {
        alert('Please select a file to upload.');
        return;
    }

    const fileName = fileInput.files[0].name;
    const allowedExtensions = /\.(xlsx|xls|csv)$/i;
    if (!allowedExtensions.test(fileName)) {
        alert('Invalid file type. Please upload a .xlsx, .xls, or .csv file.');
        fileInput.value = '';
        return;
    }

    const formData = new FormData($('#importExcelForm')[0]);

    $.ajax({
        url: routes.assignHostelToStudent,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {
            $('#upload_import_hostel_mapping_to_student').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Uploading...');
        },
        success: function (response) {

            alert('File imported successfully!');
            $('#importModal').modal('hide');
            resetImportModal();
            location.reload();
        },
        error: function (xhr) {
            console.log('Error response:', xhr);
            $('#importErrorTableBody').empty();

            if (xhr.status === 422 && xhr.responseJSON.failures) {
                let failures = xhr.responseJSON.failures;
                failures.forEach(function (failure) {
                    let errorRow = `
                        <tr>
                            <td><p class="text-danger">${failure.row}</p></td>
                            <td><p class="text-danger">${failure.errors.join('<br>')}</p></td>
                        </tr>
                    `;
                    $('#importErrorTableBody').append(errorRow);
                });
                $('#importErrors').removeClass('d-none').addClass('');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                alert(xhr.responseJSON.message);
            } else {
                alert('An unexpected error occurred.');
            }
        },
        complete: function () {
            $('#upload_import_hostel_mapping_to_student').prop('disabled', false).html('<i class="mdi mdi-upload"></i> Upload & Import');
        }
    });
});


// Building Floor Room Mapping
$(document).ready(function () {
    $('#building_master_pk, #floor_master_pk').change(function () {

        let buildingMasterPk = $('#building_master_pk option:selected').text().trim();
        let floorMasterPk = $('#floor_master_pk option:selected').text().trim();

        if(buildingMasterPk != '' && floorMasterPk != '' && buildingMasterPk != 'Select' && floorMasterPk != 'Select') {
            let buildingStart = buildingMasterPk.substring(0, 4);
            let floorStart = floorMasterPk.substring(0, 4);
            let prepend = $('.floor_room_name').html(buildingStart+'-'+floorStart);
        }


    });

    $('#room_type').change(function () {

        let buildingMasterPk = $('#building_master_pk').val();
        let floorMasterPk = $('#floor_master_pk').val();
        let prepend = $('.floor_room_name').text();
        let roomName = $('input[name="room_name"]').val();


        if(buildingMasterPk == '' || buildingMasterPk == null || buildingMasterPk == undefined) {
            alert('Please select Building');
            return false;
        }

        if(floorMasterPk == '' || floorMasterPk == null || floorMasterPk == undefined) {
            alert('Please select Floor');
            return false;
        }

        if(roomName == '' || roomName == null || roomName == undefined) {
            alert('Please enter Room Name/Number');
            return false;
        }
        let finalRoomNameValue = finalRoomName().trim();
        if(finalRoomNameValue == '' || finalRoomNameValue == null || finalRoomNameValue == undefined || finalRoomNameValue == 'undefined') {
            $('.floor_room_name_span').html('');
            return false;
        }

        let html = '<strong> Room Name : '+finalRoomName()+'</strong>';
        $('.floor_room_name_span').html(html);
    });
    $('#building_master_pk, #floor_master_pk, input[name="room_name"]').change(function () {

        let finalRoomNameValue = finalRoomName().trim();
        if(finalRoomNameValue == '' || finalRoomNameValue == null || finalRoomNameValue == undefined || finalRoomNameValue == 'undefined') {
            $('.floor_room_name_span').html('');
            return false;
        }

        let html = '<strong> Room Name : '+finalRoomName()+'</strong>';
        $('.floor_room_name_span').html(html);

    });
    function finalRoomName() {
        let roomType = $('#room_type').val();
        let roomTypeName = $('#room_type option:selected').text().trim();
        let buildingMasterPk = $('#building_master_pk option:selected').text().trim();
        let floorMasterPk = $('#floor_master_pk option:selected').text().trim();
        let roomName = $('input[name="room_name"]').val();

        if(buildingMasterPk != '' && floorMasterPk != '' && buildingMasterPk != 'Select' && floorMasterPk != 'Select' && roomType != '' && roomName != '') {
            if (roomType == 'Room') {
                return buildingMasterPk.substring(0, 4) + '-' + floorMasterPk.substring(0, 4) + roomName;
            } else {
                return buildingMasterPk.substring(0, 4) + '-' + floorMasterPk.substring(0, 4) + roomName + '-' + roomTypeName;
            }
        }
        return '';
    }
});
