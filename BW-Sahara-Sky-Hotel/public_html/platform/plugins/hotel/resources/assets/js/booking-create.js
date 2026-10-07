'use strict';

$(document).ready(function () {
    // Handle customer selection
    $(document).on('change', '#customer_id', function () {
        let customerId = $(this).val();
        if (customerId == '0') {
            // Show customer fields and clear values
            $('#first_name').val('').closest('.form-group').show();
            $('#last_name').val('').closest('.form-group').show();
            $('#email').val('').closest('.form-group').show();
            $('#phone').val('').closest('.form-group').show();
            $('#address').val('').closest('.form-group').show();
            $('#city').val('').closest('.form-group').show();
            $('#state').val('').closest('.form-group').show();
            $('#country').val('').closest('.form-group').show();
            $('#zip').val('').closest('.form-group').show();
        } else {
            // Hide customer fields and fetch customer info
            $('#first_name').closest('.form-group').hide();
            $('#last_name').closest('.form-group').hide();
            $('#email').closest('.form-group').hide();
            $('#phone').closest('.form-group').hide();
            $('#address').closest('.form-group').hide();
            $('#city').closest('.form-group').hide();
            $('#state').closest('.form-group').hide();
            $('#country').closest('.form-group').hide();
            $('#zip').closest('.form-group').hide();

            // Fetch customer info and fill the fields
            $.ajax({
                url: route('customer.get-info', { id: customerId }),
                type: 'GET',
                success: function(res) {
                    if (res.error) {
                        Botble.showError(res.message);
                    } else {
                        // Fill the fields with customer info
                        $('#first_name').val(res.data.first_name);
                        $('#last_name').val(res.data.last_name);
                        $('#email').val(res.data.email);
                        $('#phone').val(res.data.phone);
                        $('#address').val(res.data.address);
                        $('#city').val(res.data.city);
                        $('#state').val(res.data.state);
                        $('#country').val(res.data.country);
                        $('#zip').val(res.data.zip);
                    }
                },
                error: function(error) {
                    Botble.handleError(error);
                }
            });
        }
    });

    // Trigger change on page load
    $('#customer_id').trigger('change');
});
