$(document).ready(function () {

    const countryDropdown = $("#countryId");
    const stateDropdown = $("#stateId");
    const cityDropdown = $("#cityId");


    /*
    |--------------------------------------------------------------------------
    | Existing Values
    |--------------------------------------------------------------------------
    |
    | These values come from the edit Blade.
    |
    */

    const oldCountryId =
        window.driverMasterOldValues?.country_id || "";

    const oldStateId =
        window.driverMasterOldValues?.state_id || "";

    const oldCityId =
        window.driverMasterOldValues?.city_id || "";


    /*
    |--------------------------------------------------------------------------
    | Load Countries
    |--------------------------------------------------------------------------
    */

    function loadCountries() {

        countryDropdown
            .empty()
            .append('<option value="">Loading countries...</option>')
            .prop("disabled", true);

        $.ajax({

            url: window.driverMasterUrls.countries,

            type: "GET",

            dataType: "json",

            success: function (response) {

                countryDropdown
                    .empty()
                    .append('<option value="">Select Country</option>');

                if (
                    response.success &&
                    response.data &&
                    response.data.length > 0
                ) {

                    $.each(response.data, function (index, country) {

                        countryDropdown.append(
                            $("<option>", {
                                value: country.pk,
                                text: country.country_name
                            })
                        );

                    });

                    /*
                    |--------------------------------------------------------------------------
                    | Select Existing Country
                    |--------------------------------------------------------------------------
                    */

                    if (oldCountryId) {

                        countryDropdown.val(oldCountryId);

                        /*
                        | Load states for selected country
                        */

                        loadStates(
                            oldCountryId,
                            oldStateId
                        );
                        countryDropdown.prop("disabled", false);
                    } else {

                        countryDropdown.prop("disabled", false);

                    }

                } else {

                    countryDropdown
                        .append(
                            '<option value="">No countries found</option>'
                        )
                        .prop("disabled", true);

                }

            },

            error: function (xhr) {

                countryDropdown
                    .empty()
                    .append(
                        '<option value="">Select Country</option>'
                    )
                    .prop("disabled", true);

                toastr.error("Unable to load countries.");

                console.error(
                    "Country AJAX Error:",
                    xhr.responseText
                );

            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Load States
    |--------------------------------------------------------------------------
    */

    function loadStates(countryId, selectedStateId = "") {

        stateDropdown
            .empty()
            .append('<option value="">Loading states...</option>')
            .prop("disabled", true);

        cityDropdown
            .empty()
            .append('<option value="">Select City</option>')
            .prop("disabled", true);


        if (!countryId) {

            stateDropdown
                .empty()
                .append('<option value="">Select State</option>')
                .prop("disabled", true);

            return;
        }


        $.ajax({

            url: window.driverMasterUrls.states.replace(
                ":country_id",
                countryId
            ),

            type: "GET",

            dataType: "json",

            success: function (response) {

                stateDropdown
                    .empty()
                    .append('<option value="">Select State</option>');


                if (
                    response.success &&
                    response.data &&
                    response.data.length > 0
                ) {

                    $.each(response.data, function (index, state) {

                        stateDropdown.append(
                            $("<option>", {
                                value: state.pk,
                                text: state.state_name
                            })
                        );

                    });


                    /*
                    |--------------------------------------------------------------------------
                    | Select Existing State
                    |--------------------------------------------------------------------------
                    */

                    if (selectedStateId) {

                        stateDropdown.val(selectedStateId);

                        /*
                        | Load cities for selected state
                        */

                        loadCities(
                            selectedStateId,
                            oldCityId
                        );
                     stateDropdown.prop("disabled", false);
                    } else {

                        stateDropdown.prop("disabled", false);

                    }

                } else {

                    stateDropdown
                        .append(
                            '<option value="">No states found</option>'
                        )
                        .prop("disabled", true);

                }

            },

            error: function (xhr) {

                stateDropdown
                    .empty()
                    .append(
                        '<option value="">Select State</option>'
                    )
                    .prop("disabled", true);

                toastr.error("Unable to load states.");

                console.error(
                    "State AJAX Error:",
                    xhr.responseText
                );

            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Load Cities
    |--------------------------------------------------------------------------
    */

    function loadCities(stateId, selectedCityId = "") {

        cityDropdown
            .empty()
            .append('<option value="">Loading cities...</option>')
            .prop("disabled", true);


        if (!stateId) {

            cityDropdown
                .empty()
                .append('<option value="">Select City</option>')
                .prop("disabled", true);

            return;
        }


        $.ajax({

            url: window.driverMasterUrls.cities.replace(
                ":state_id",
                stateId
            ),

            type: "GET",

            dataType: "json",

            success: function (response) {

                cityDropdown
                    .empty()
                    .append('<option value="">Select City</option>');


                if (
                    response.success &&
                    response.data &&
                    response.data.length > 0
                ) {

                    $.each(response.data, function (index, city) {

                        cityDropdown.append(
                            $("<option>", {
                                value: city.pk,
                                text: city.city_name
                            })
                        );

                    });


                    /*
                    |--------------------------------------------------------------------------
                    | Select Existing City
                    |--------------------------------------------------------------------------
                    */

                    if (selectedCityId) {

                        cityDropdown.val(selectedCityId);
                        cityDropdown.prop("disabled", false);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Enable City Dropdown
                    |--------------------------------------------------------------------------
                    */

                    cityDropdown.prop("disabled", false);

                } else {

                    cityDropdown
                        .append(
                            '<option value="">No cities found</option>'
                        )
                        .prop("disabled", true);

                }

            },

            error: function (xhr) {

                cityDropdown
                    .empty()
                    .append(
                        '<option value="">Select City</option>'
                    )
                    .prop("disabled", true);

                toastr.error("Unable to load cities.");

                console.error(
                    "City AJAX Error:",
                    xhr.responseText
                );

            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Country Change
    |--------------------------------------------------------------------------
    */

    countryDropdown.on("change", function () {

        const countryId = $(this).val();

        /*
        | When user manually changes country,
        | don't use old state/city values.
        */

        loadStates(countryId);

    });


    /*
    |--------------------------------------------------------------------------
    | State Change
    |--------------------------------------------------------------------------
    */

    stateDropdown.on("change", function () {

        const stateId = $(this).val();

        /*
        | When user manually changes state,
        | don't use old city value.
        */

        loadCities(stateId);

    });

    /*
    |--------------------------------------------------------------------------
    | Initial Load
    |--------------------------------------------------------------------------
    */

    loadCountries();

});