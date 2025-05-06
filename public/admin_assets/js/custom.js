// Show Loader
function showLoader() {
    $('.preloader').show();
}
// Hide Loader
function hideLoader() {
    $('.preloader').hide();
}

// Step 4 : Contact Information

$(document).on('change', 'input[name="styled_max_checkbox"]', function() {
    let address;
    let country;
    let state;
    let city;
    let postal;

    if ($(this).is(':checked')) {
        address = $(document).find('input[name="address"]').val();
        country = $(document).find('input[name="country"]').val();
        state = $(document).find('input[name="state"]').val();
        city = $(document).find('input[name="city"]').val();
        postal = $(document).find('input[name="postal"]').val();

        if (address == '' || country == '' || state == '' || city == '' || postal == '') {
            alert('Please fill all the fields');
            $(this).prop('checked', false);
            return false;
        } else {

            $(document).find('input[name="permanentaddress"]').val(address);
            $(document).find('input[name="permanentcountry"]').val(country);
            $(document).find('input[name="permanentstate"]').val(state);
            $(document).find('input[name="permanentcity"]').val(city);
            $(document).find('input[name="permanentpostal"]').val(postal);
        }
    } else {
        $(document).find('input[name="permanentaddress"]').val('');
        $(document).find('input[name="permanentcountry"]').val('');
        $(document).find('input[name="permanentstate"]').val('');
        $(document).find('input[name="permanentcity"]').val('');
        $(document).find('input[name="permanentpostal"]').val('');
    }
});

$(document).on('change', '.status-toggle', function () {

    let table = $(this).data('table');
    let column = $(this).data('column');
    let id = $(this).data('id');
    let id_column =  $(this).data('id_column');
    let status = $(this).is(':checked') ? 1 : 0;

    $.ajax({
        url: routes.toggleStatus, // Update with correct route
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
        },
        error: function () {
            alert('Error updating status');
        }
    });
});
// Faculty Form Creation

document.addEventListener('DOMContentLoaded', function() {
        
    $('#saveFacultyForm').click(function (e) {
        
        const formData = new FormData();
        // remove all error class
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

        // photo is file
        if ($('input[name="photo"]')[0].files.length > 0) {
            photo = $('input[name="photo"]')[0].files[0];
            formData.append('photo', photo);
        }
        // document is file
        if ($('input[name="document"]')[0].files.length > 0) {
            document = $('input[name="document"]')[0].files[0];
            formData.append('document', document);
        }

        // Qualification Details
        let degrees = [];
        let universities = [];
        let years = [];
        let percentages = [];
        let certFiles = [];

        // Collect all education fields
        $('input[name="degree[]"]').each(function(index) {
            degrees.push($(this).val());
            universities.push($('input[name="university_institution_name[]"]').eq(index).val());
            years.push($('input[name="year_of_passing[]"]').eq(index).val());
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
        $('input[name="experience[]"]').each(function(index) {
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

        if( facultyId != '' && facultyId != null && facultyId != undefined) {  
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
        $('input[name="faculties[]"]:checked').each(function() {
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
        
        let storeUrl = $('#facultyForm').data('store-url');
        let indexUrl = $('#facultyForm').data('index-url');

        $.ajax({
            type: 'POST',
            url: storeUrl,
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function () {
                showLoader();
            },
            success: function (response) {
                
                // Handle success response
                if (response.status) {
                    window.location.href = indexUrl;
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
                            
                            if (inputField.length > 0) {
                                const errorDiv = $('<span class="text-danger mt-1"></span><br/>').text(errors[key][0]);
                                inputField.addClass('is-invalid').after(errorDiv);
                            }
                        } 
                        // Handle regular fields
                        else {
                            const inputField = $(`[name="${key}"], select[name="${key}"]`);
                            if (inputField.length > 0) {
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
});
