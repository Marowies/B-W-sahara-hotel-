'use strict';

$(document).ready(function () {
    // Trigger search on page load if dates are already set
    if ($('#booking_start_date').val() && $('#booking_end_date').val()) {
        setTimeout(function() {
            $('#search_rooms_button').trigger('click');
        }, 500);
    }

    // Handle room search button click
    $(document).on('click', '#search_rooms_button', function (event) {
        event.preventDefault()
        event.stopPropagation()
        const startDate = $('#booking_start_date').val();
        const endDate = $('#booking_end_date').val();

        if (!startDate || !endDate) {
            Botble.showError('Please select both start date and end date to search for available rooms.');
            return;
        }

        // Show loading indicator
        $('#search_rooms_button').addClass('button-loading');
        $('#room_cards_container').html('');

        // Use Ajax to search for rooms
        $.ajax({
            url: route('booking.search-rooms'),
            type: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(res) {
                $('#search_rooms_button').removeClass('button-loading');

                if (res.error) {
                    Botble.showError(res.message);
                } else {
                    // Display room cards
                    if (res.data.rooms && res.data.rooms.length > 0) {
                        $('#room_cards_container').html(res.data.html);
                        $('#booking_form_container').removeClass('d-none');
                    } else {
                        $('#room_cards_container').html('<div class="alert alert-warning">No rooms available for the selected dates</div>');
                    }
                }
            },
            error: function(error) {
                $('#search_rooms_button').removeClass('button-loading');
                Botble.handleError(error);
            }
        });
    });

    // Handle room selection
    $(document).on('click', '.select-room-button', function() {
        const roomId = $(this).data('room-id');
        const roomName = $(this).data('room-name');

        // Set the selected room ID in the hidden input
        $('#room_id').val(roomId);

        // Show the selected room name
        $('#selected_room_name').text(roomName);

        // Highlight the selected room card
        $('.room-card').removeClass('selected');
        $(this).closest('.room-card').addClass('selected');

        // Show the rest of the booking form
        $('#booking_details_container').removeClass('d-none');

        // Scroll to the booking details
        $('html, body').animate({
            scrollTop: $('#booking_details_container').offset().top - 100
        }, 500);
    });

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

    // Trigger change on page load if customer_id exists
    if ($('#customer_id').length) {
        $('#customer_id').trigger('change');
    }
});
