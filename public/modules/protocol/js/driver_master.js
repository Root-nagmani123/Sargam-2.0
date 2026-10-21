$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Rules
    |--------------------------------------------------------------------------
    */

    // Driver/helper name validation
    $.validator.addMethod(
        "nameRegex",
        function (value, element) {
            return this.optional(element) ||
                /^[A-Za-z .'-]+$/.test(value);
        },
        "Only letters, spaces, dots, apostrophes and hyphens are allowed."
    );


    // Driver number validation
    $.validator.addMethod(
        "driverNumberRegex",
        function (value, element) {
        return this.optional(element) || /^[0-9]+$/.test(value);
    },
    "Driver's no. must contain numbers only."
    );


    // Driving licence validation
    $.validator.addMethod(
        "licenceRegex",
        function (value, element) {
            return this.optional(element) ||
                /^[A-Za-z0-9\-]+$/.test(value);
        },
        "Please enter a valid driving licence number."
    );


    // Postal code validation
    $.validator.addMethod(
        "postalCodeRegex",
        function (value, element) {
        return this.optional(element) || /^[0-9]+$/.test(value);
    },
    "Postal code must contain numbers only."
    );


    /*
    |--------------------------------------------------------------------------
    | Driver Form Validation
    |--------------------------------------------------------------------------
    */

    $("#driverForm").validate({

        ignore: ".ignore",

        rules: {

            name: {
                required: true,
                minlength: 2,
                maxlength: 255,
                nameRegex: true
            },

            type: {
                required: true
            },

            dob: {
                required: true,
                date: true
            },

            driver_no: {
                required: true,
                minlength: 10,
                maxlength: 10,
                driverNumberRegex: true
            },

            driving_licence_no: {
                required: true,
                minlength: 5,
                maxlength: 255,
                licenceRegex: true
            },

            licence_expiry_date: {
                required: true,
                date: true
            },

            hiring_date: {
                required: true,
                date: true
            },

            helper_one: {
                required: false,
                maxlength: 255,
                nameRegex: true
            },

            helper_two: {
                required: false,
                maxlength: 255,
                nameRegex: true
            },

            note_about_driver: {
                required: false,
                maxlength: 5000
            },

            country_id: {
                required: true,
                digits: true
            },

            state_id: {
                required: true,
                digits: true
            },

            city_id: {
                required: true,
                digits: true
            },

            postal_code: {
                required: true,
                maxlength: 6,
                postalCodeRegex: true
            },

            email: {
                required: true,
                email: true,
                maxlength: 150
            },

            address: {
                required: true,
                maxlength: 5000
            }

        },


        messages: {

            name: {
                required: "Please enter driver name.",
                minlength: "Driver name must be at least 2 characters.",
                maxlength: "Driver name cannot exceed 255 characters.",
                nameRegex: "Please enter a valid driver name."
            },

            type: {
                required: "Please select driver type."
            },

            dob: {
                date: "Please enter a valid date of birth."
            },

            driver_no: {
                required: "Please enter driver number.",
                minlength: "Driver number must be at least 10 digits",
                maxlength: "Driver number cannot exceed 10 digits.",
                driverNumberRegex: "Please enter a valid driver number."
            },

            driving_licence_no: {
                required: "Please enter driving licence number.",
                minlength: "Driving licence number must be at least 5 characters.",
                maxlength: "Driving licence number cannot exceed 255 characters.",
                licenceRegex: "Please enter a valid driving licence number."
            },

            licence_expiry_date: {
                required: "Please enter licence expiry date.",
                date: "Please enter a valid licence expiry date."
            },

            hiring_date: {
                required: "Please enter hiring date.",
                date: "Please enter a valid hiring date."
            },

            helper_one: {
                maxlength: "Helper One cannot exceed 255 characters.",
                nameRegex: "Please enter a valid helper name."
            },

            helper_two: {
                maxlength: "Helper Two cannot exceed 255 characters.",
                nameRegex: "Please enter a valid helper name."
            },

            note_about_driver: {
                maxlength: "Note cannot exceed 5000 characters."
            },

            country_id: {
                required: "Please select a country.",
                digits: "Please select a valid country."
            },

            state_id: {
                required: "Please select a state.",
                digits: "Please select a valid state."
            },

            city_id: {
                required: "Please select a city.",
                digits: "Please select a valid city."
            },

            postal_code: {
                required: "Please enter postal_code.",
                maxlength: "Postal code cannot exceed 6 characters.",
                postalCodeRegex: "Please enter a valid postal code."
            },

            email: {
                required: "Please enter email address.",
                email: "Please enter a valid email address.",
                maxlength: "Email cannot exceed 150 characters."
            },

            address: {
                required: "Please enter address.",
                maxlength: "Address cannot exceed 5000 characters."
            }

        },


        /*
        |--------------------------------------------------------------------------
        | Validation UI
        |--------------------------------------------------------------------------
        */

        errorClass: "is-invalid",
        validClass: "is-valid",
        errorElement: "div",

        highlight: function (element) {

            $(element)
                .addClass("is-invalid")
                .removeClass("is-valid");

        },

        unhighlight: function (element) {

            $(element)
                .removeClass("is-invalid")
                .addClass("is-valid");

        },

        errorPlacement: function (error, element) {

            error.addClass("invalid-feedback");

            if (element.parent(".input-group").length) {

                error.insertAfter(element.parent());

            } else {

                error.insertAfter(element);

            }

        },


        /*
        |--------------------------------------------------------------------------
        | Submit
        |--------------------------------------------------------------------------
        */

        submitHandler: function (form) {

            let btn = $("#submitDriverForm");

            btn.prop("disabled", true);

            btn.html(
                '<i class="fa fa-spinner fa-spin me-2"></i> Processing...'
            );

            form.submit();

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Driving Licence Number - Uppercase
    |--------------------------------------------------------------------------
    */

    $("#drivingLicenceNo").on("input", function () {

        this.value = this.value.toUpperCase();

    });


    /*
    |--------------------------------------------------------------------------
    | Prevent Future DOB
    |--------------------------------------------------------------------------
    */

    $("#dob").on("change", function () {

        let selectedDate = new Date(this.value);
        let today = new Date();

        today.setHours(0, 0, 0, 0);

        if (selectedDate > today) {

            $(this).val("");

            toastr.error("Date of birth cannot be in the future.");

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Licence Expiry Date
    |--------------------------------------------------------------------------
    */

    $("#licenceExpiryDate").on("change", function () {

        let expiryDate = new Date(this.value);
        let today = new Date();

        today.setHours(0, 0, 0, 0);

        if (expiryDate < today) {

            toastr.warning("Licence expiry date has already passed.");

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Hiring Date
    |--------------------------------------------------------------------------
    */

    $("#hiringDate").on("change", function () {

        let hiringDate = new Date(this.value);
        let today = new Date();

        today.setHours(0, 0, 0, 0);

        if (hiringDate > today) {

            $(this).val("");

            toastr.error("Hiring date cannot be in the future.");

        }

    });

});