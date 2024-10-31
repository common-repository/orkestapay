const orkestapaySettings = window.wc.wcSettings.getSetting('orkestapay_data', {});

const orkestapayLabel = window.wp.htmlEntities.decodeEntities(orkestapaySettings.title) || window.wp.i18n.__('Orkestapay', 'orkestapay');

const OrkestaPayContent = (props) => {
    const { eventRegistration, emitResponse, billing, shippingData } = props;
    const { onPaymentProcessing } = eventRegistration;

    wp.element.useEffect(() => {
        // Verificar si se ha ingresado información de facturación
        if (billing.billingAddress.email === '' && billing.billingAddress.first_name === '' && billing.billingAddress.last_name === '' && billing.billingAddress.country === '') {
            console.log('Billing Address is empty');
            return;
        }

        const unsubscribe = onPaymentProcessing(async () => {
            console.log('Billing Data from Props', JSON.stringify(billing.billingAddress, null, 2));

            // Realizar la llamada AJAX
            try {
                const response = await fetch(orkestapaySettings.orkesta_checkout_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        shipping_address: shippingData.shippingAddress,
                        billing_address: billing.billingAddress,
                    }),
                });

                if (response.ok) {
                    const result = await response.json();
                    const { data, success } = result;
                    console.log('AJAX validation response:', JSON.stringify(result, null, 2)); // For testing (to be removed)

                    // Verificar si la respuesta fue exitosa
                    if (success) {
                        window.location.href = data.checkout_redirect_url;
                        console.log('checkout_redirect_url:', data.checkout_redirect_url); // For testing (to be removed)
                        return {
                            type: emitResponse.responseTypes.ERROR,
                            message: '',
                        };
                    }

                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'There was an error',
                    };
                } else {
                    console.error('Error during AJAX call', response.status, response.statusText);
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: response.statusText,
                    };
                }
            } catch (error) {
                console.error('Error:', error.message);
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: response.statusText,
                };
            }
        });
        // Unsubscribes when this component is unmounted.
        return () => {
            unsubscribe();
        };
    }, [onPaymentProcessing, billing.billingAddress]);

    return window.wp.htmlEntities.decodeEntities(orkestapaySettings.description || '');
};

const OrkestaPay_Block_Gateway = {
    name: 'orkestapay',
    label: orkestapayLabel,
    content: Object(window.wp.element.createElement)(OrkestaPayContent, null),
    edit: Object(window.wp.element.createElement)(OrkestaPayContent, null),
    canMakePayment: () => true,
    ariaLabel: orkestapayLabel,
    supports: {
        features: orkestapaySettings.supports,
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(OrkestaPay_Block_Gateway);
