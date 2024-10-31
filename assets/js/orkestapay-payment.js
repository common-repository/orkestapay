jQuery(document).ready(async function () {
    var $form = jQuery('form.checkout');

    // Orkesta params
    const pluginPaymentGatewayId = orkestapay_payment_args.plugin_payment_gateway_id;
    const orkestaCheckoutUrl = orkestapay_payment_args.orkesta_checkout_url;

    // Bind to the checkout_place_order event to add the token
    $form.bind('checkout_place_order_orkestapay', function (e) {
        console.log('checkout_place_order_orkestapay', pluginPaymentGatewayId); // For testing (to be removed)

        $form.block({ message: null, overlayCSS: { background: '#fff', opacity: 0.6 } });

        checkoutRequest();
        return false; // Prevent the form from submitting with the default action
    });

    function checkoutRequest() {
        jQuery.ajax({
            type: 'POST',
            url: orkestaCheckoutUrl,
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            enctype: 'multipart/form-data',
            data: $form.serializeArray(),
            success: async function (response) {
                console.log('checkoutRequest', response); // For testing (to be removed)
                const { data, success } = response;

                if (success) {
                    // Success, then redirect to the OrkestaPay checkout page
                    window.location.href = data.checkout_redirect_url;
                    return;
                }
            },
            error: function (error) {
                console.error('checkoutRequest error', error.responseJSON); // For testing (to be removed)
                displayErrorMessage(error.responseJSON.data.message);
            },
        });
    }

    function displayErrorMessage(error) {
        jQuery('.woocommerce_error, .woocommerce-error, .woocommerce-message, .woocommerce_message').remove();
        $form
            .closest('div')
            .before(
                '<ul style="background-color: #e2401c; color: #fff; margin-bottom: 10px; margin-top: 10px; border-radius: 8px;" class="woocommerce_error woocommerce-error"><li> ' +
                    error +
                    ' </li></ul>'
            );

        $form.unblock();

        jQuery('html, body').animate({ scrollTop: '0' }, 1000);
    }
});
