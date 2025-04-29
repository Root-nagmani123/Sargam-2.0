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
    const toggleUrl = "{{ route('admin.toggleStatus') }}";

    let table = $(this).data('table');
    let column = $(this).data('column');
    let id = $(this).data('id');
    let status = $(this).is(':checked') ? 1 : 0;

    $.ajax({
        url: window.statusToggleUrl, // Update with correct route
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            table: table,
            column: column,
            id: id,
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
