'use strict'

$(document).ready(function () {
    $('.service-item').on('change', function () {
        const foods = []
        const services = []
        $('.service-item:checked').each((i, el) => {
            services[i] = $(el).val()
        })

        $('.food-item:checked').each((i, el) => {
            foods[i] = $(el).val()
        })

        $('body').css('cursor', 'progress')
        $('.custom-checkbox label').css('cursor', 'progress')

        let $checkoutButton = $(document).find('.payment-checkout-btn')
        $checkoutButton.prop('disabled', true)
        let $selectedPaymentMethod = $(document).find('.payment-checkout-form .list_payment_method input[name="payment_method"]:checked').val()

        $.ajax({
            type: 'GET',
            cache: false,
            url: '/ajax/calculate-amount',
            data: {
                room_id: $('input[name=room_id]').val(),
                start_date: $('input[name=start_date]').val(),
                end_date: $('input[name=end_date]').val(),
                services,
                foods
            },
            success: ({ error, data }) => {
                if (!error) {
                    $('.total-amount-text').text(data.total_amount)
                    $('input[name=amount]').val(data.amount_raw)
                    $('.amount-text').text(data.sub_total)
                    $('.discount-text').text(data.discount_amount)
                    $('.tax-text').text(data.tax_amount)
                }

                $('body').css('cursor', 'default')
                $('.custom-checkbox label').css('cursor', 'pointer')

                // Without an enabled payment method there is no list to reload, and .load() would never call back.
                if (! $('.payment-checkout-form .list_payment_method').length) {
                    $checkoutButton.prop('disabled', false)
                }

                $('.payment-checkout-form .list_payment_method').load(window.location.href + ' .payment-checkout-form .list_payment_method > *', function() {
                    $checkoutButton.prop('disabled', false)
                    $(document).find('.payment-checkout-form .list_payment_method input[value="' + $selectedPaymentMethod + '"]').prop('checked', true).trigger('change')
                })
            },
            error: (xhr) => {
                // A failed recalculation must not leave the booking button disabled without explanation.
                $('body').css('cursor', 'default')
                $('.custom-checkbox label').css('cursor', 'pointer')
                $checkoutButton.prop('disabled', false)
                RiorelaxTheme.handleError(xhr)
            },
        })
    })

    $('.food-item').on('change', function () {
        const foods = []
        const services = []
        $('.food-item:checked').each((i, el) => {
            foods[i] = $(el).val()
        })

        $('.service-item:checked').each((i, el) => {
            services[i] = $(el).val()
        })

        $('body').css('cursor', 'progress')
        $('.custom-checkbox label').css('cursor', 'progress')

        let $checkoutButton = $(document).find('.payment-checkout-btn')
        $checkoutButton.prop('disabled', true)
        let $selectedPaymentMethod = $(document).find('.payment-checkout-form .list_payment_method input[name="payment_method"]:checked').val()

        $.ajax({
            type: 'GET',
            cache: false,
            url: '/ajax/calculate-amount',
            data: {
                room_id: $('input[name=room_id]').val(),
                start_date: $('input[name=start_date]').val(),
                end_date: $('input[name=end_date]').val(),
                foods,
                services,
            },
            success: ({ error, data }) => {
                if (!error) {
                    $('.total-amount-text').text(data.total_amount)
                    $('input[name=amount]').val(data.amount_raw)
                    $('.amount-text').text(data.sub_total)
                    $('.discount-text').text(data.discount_amount)
                    $('.tax-text').text(data.tax_amount)
                }

                $('body').css('cursor', 'default')
                $('.custom-checkbox label').css('cursor', 'pointer')

                // Without an enabled payment method there is no list to reload, and .load() would never call back.
                if (! $('.payment-checkout-form .list_payment_method').length) {
                    $checkoutButton.prop('disabled', false)
                }

                $('.payment-checkout-form .list_payment_method').load(window.location.href + ' .payment-checkout-form .list_payment_method > *', function() {
                    $checkoutButton.prop('disabled', false)
                    $(document).find('.payment-checkout-form .list_payment_method input[value="' + $selectedPaymentMethod + '"]').prop('checked', true).trigger('change')
                })
            },
            error: (xhr) => {
                // A failed recalculation must not leave the booking button disabled without explanation.
                $('body').css('cursor', 'default')
                $('.custom-checkbox label').css('cursor', 'pointer')
                $checkoutButton.prop('disabled', false)
                RiorelaxTheme.handleError(xhr)
            },
        })
    })

    $('.create-customer').on('change', 'input[name="register_customer"]', function (event) {
        const $formCreate = $('.form-create-customer-password')

        if (event.target.checked) {
            $formCreate.removeClass('d-none')
        } else {
            $formCreate.addClass('d-none')
        }
    })

    const refreshCoupon = () => {
        const services = []
        $('.service-item:checked').each((i, el) => {
            services[i] = $(el).val()
        })

        let $checkoutButton = $(document).find('.payment-checkout-btn')
        $checkoutButton.prop('disabled', true)
        let $selectedPaymentMethod = $(document).find('.payment-checkout-form .list_payment_method input[name="payment_method"]:checked').val()

        $.ajax({
            url: '/ajax/calculate-amount',
            type: 'GET',
            data: {
                room_id: $('input[name=room_id]').val(),
                start_date: $('input[name=start_date]').val(),
                end_date: $('input[name=end_date]').val(),
                services,
            },
            success: ({ error, message, data }) => {
                if (error) {
                    RiorelaxTheme.showError(message)

                    return
                }

                $('.total-amount-text').text(data.total_amount)
                $('input[name=amount]').val(data.amount_raw)
                $('.amount-text').text(data.sub_total)
                $('.discount-text').text(data.discount_amount)
                $('.tax-text').text(data.tax_amount)

                // Without an enabled payment method there is no list to reload, and .load() would never call back.
                if (! $('.payment-checkout-form .list_payment_method').length) {
                    $checkoutButton.prop('disabled', false)
                }

                $('.payment-checkout-form .list_payment_method').load(window.location.href + ' .payment-checkout-form .list_payment_method > *', function() {
                    $checkoutButton.prop('disabled', false)
                    $(document).find('.payment-checkout-form .list_payment_method input[value="' + $selectedPaymentMethod + '"]').prop('checked', true).trigger('change')
                })

                const refreshUrl = $('.order-detail-box').data('refresh-url')

                $.ajax({
                    url: refreshUrl,
                    type: 'GET',
                    data: {
                        coupon_code: $('input[name=coupon_hidden]').val() ?? $('input[name=coupon_code]').val(),
                    },
                    success: ({ error, message, data}) => {
                        if (error) {
                            RiorelaxTheme.showError(message)

                            return
                        }

                        $('.order-detail-box').html(data)
                    },
                    error: (error) => {
                        RiorelaxTheme.handleError(error)
                    },
                })
            },
            error: (error) => {
                RiorelaxTheme.handleError(error)
            },
        })
    }

    $(document)
        .on('click', '.toggle-coupon-form', () => $(document).find('.coupon-form').toggle('fast'))
        // Enter in the coupon field applies the coupon instead of submitting the booking form.
        .on('keydown', '#coupon_code', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault()
                $('.apply-coupon-code').trigger('click')
            }
        })
        .on('click', '.apply-coupon-code', (e) => {
            e.preventDefault()

            const $button = $(e.currentTarget)

            $.ajax({
                url: $button.data('url'),
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    coupon_code: $('input[name=coupon_code]').val(),
                },
                beforeSend: () => {
                    $button.addClass('button-loading')
                },
                success: ({ error, message }) => {
                    if (error) {
                        RiorelaxTheme.showError(message)

                        return
                    }

                    RiorelaxTheme.showSuccess(message)

                    refreshCoupon()
                },
                error: (error) => {
                    RiorelaxTheme.handleError(error)
                },
                complete: () => {
                    $button.removeClass('button-loading')
                }
            })
        })
        .on('click', '.remove-coupon-code', (e) => {
            e.preventDefault()

            const $button = $(e.currentTarget)

            $.ajax({
                url: $button.data('url'),
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: () => {
                    $button.addClass('button-loading')
                },
                success: ({ message, error }) => {
                    if (error) {
                        RiorelaxTheme.showError(message)

                        return
                    }

                    RiorelaxTheme.showSuccess(message)

                    refreshCoupon()
                },
                error: (error) => {
                    RiorelaxTheme.handleError(error)
                },
                complete: () => {
                    $button.removeClass('button-loading')
                },
            })
        })
})
