<?php
if (!defined('ABSPATH')) {
    exit();
}

/**
 * OrkestaPay_Helper class.
 *
 */
class OrkestaPay_Helper
{
    const PAYMENT_METHOD_CARD = 'CARD';
    const PAYMENT_METHOD_ALIAS = 'Default card';

    /**
     * Description
     *
     * @return array
     */
    public static function transform_data_4_customers($order)
    {
        $customerData = [
            'external_id' => "{$order->get_user_id()}",
            'name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
            'country' => $order->get_billing_country(),
        ];

        return $customerData;
    }

    /**
     * Description
     *
     * @return array
     */
    public static function transform_data_4_orders($orkestaCustomerId, $order)
    {
        $products = [];
        foreach ($order->get_items() as $item) {
            $product = wc_get_product($item->get_product_id());
            $name = trim(preg_replace('/[[:^print:]]/', '', strip_tags($product->get_title())));
            $desc = trim(preg_replace('/[[:^print:]]/', '', strip_tags($product->get_description())));
            $thumbnailUrl = wp_get_attachment_image_url($product->get_image_id());

            $products[] = [
                'id' => "{$item->get_product_id()}",
                'name' => $name,
                'description' => substr($desc, 0, 250),
                'quantity' => $item->get_quantity(),
                'unit_price' => $item->get_subtotal(),
                'thumbnail_url' => $thumbnailUrl,
            ];
        }

        $orderData = [
            'merchant_order_id' => "{$order->get_id()}",
            'customer_id' => $orkestaCustomerId,
            'currency' => $order->get_currency(),
            'subtotal_amount' => $order->get_subtotal(),
            'order_country' => $order->get_billing_country(),
            'additional_charges' => [
                'shipment' => $order->get_shipping_total(),
                'taxes' => $order->get_total_tax(),
            ],
            'discounts' => [
                'promo_discount' => $order->get_discount_total(),
            ],
            'total_amount' => $order->get_total(),
            'products' => $products,
            'shipping_address' => [
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'email' => $order->get_billing_email(),
                'address' => [
                    'line_1' => $order->get_shipping_address_1(),
                    'line_2' => $order->get_shipping_address_2(),
                    'city' => $order->get_shipping_city(),
                    'state' => $order->get_shipping_state(),
                    'country' => $order->get_shipping_country(),
                    'zip_code' => $order->get_shipping_postcode(),
                ],
            ],
            'billing_address' => [
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'address' => [
                    'line_1' => $order->get_billing_address_1(),
                    'line_2' => $order->get_billing_address_2(),
                    'city' => $order->get_billing_city(),
                    'state' => $order->get_billing_state(),
                    'country' => $order->get_billing_country(),
                    'zip_code' => $order->get_billing_postcode(),
                ],
            ],
        ];

        return $orderData;
    }

    /**
     * Description
     *
     * @return array
     */
    public static function transform_data_4_payment_method($order, $creditCard)
    {
        $paymentMethodData = [
            'type' => self::PAYMENT_METHOD_CARD,
            'card' => [
                'holder_name' => $order->get_billing_first_name(),
                'holder_last_name' => $order->get_billing_last_name(),
                'number' => $creditCard['card_number'],
                'expiration_month' => $creditCard['exp_month'],
                'expiration_year' => $creditCard['exp_year'],
                'cvv' => intval($creditCard['cvv']),
                'one_time_use' => true,
            ],
            'billing_address' => [
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'address' => [
                    'line_1' => $order->get_billing_address_1(),
                    'line_2' => $order->get_billing_address_2(),
                    'city' => $order->get_billing_city(),
                    'state' => $order->get_billing_state(),
                    'country' => $order->get_billing_country(),
                    'zip_code' => $order->get_billing_postcode(),
                ],
            ],
        ];

        return $paymentMethodData;
    }

    /**
     * Description
     *
     * @return array
     */
    public static function transform_data_4_payment($orkestaPaymentMethodId, $order, $deviceSessionId, $orkestaCardCvc)
    {
        $paymentData = [
            'payment_method' => $orkestaPaymentMethodId,
            'payment_amount' => [
                'amount' => $order->get_total(),
                'currency' => $order->get_currency(),
            ],
            'device_info' => [
                'device_session_id' => $deviceSessionId,
            ],
            'payment_options' => [
                'capture' => true,
                'cvv' => $orkestaCardCvc,
            ],
            'metadata' => [
                'merchant_order_id' => "{$order->get_id()}",
            ],
        ];

        return $paymentData;
    }

    /**
     * Description
     *
     * @return array
     */
    public static function transform_data_4_checkout($customer, $cart, $orketaPayCartId, $successUrl, $cancelUrl)
    {
        $products = [];
        $subtotal = 0;
        foreach ($cart->get_cart() as $item) {
            $product = wc_get_product($item['product_id']);
            $prouctID = $product->get_id();
            $productType = $product->get_type();
            $name = trim(preg_replace('/[[:^print:]]/', '', strip_tags($product->get_title())));
            $desc = trim(preg_replace('/[[:^print:]]/', '', strip_tags($product->get_short_description())));
            $thumbnailUrl = wp_get_attachment_image_url($product->get_image_id());
            $productPrice = wc_get_price_including_tax($product);
            $isDigital = $product->is_virtual() || $product->is_downloadable();

            // If product has variations, image, price and ID is taken from here
            if ($productType == 'variable') {
                $prouctID = $item['variation_id'];
                $variable_product = new WC_Product_Variation($item['variation_id']);
                $thumbnailUrl = wp_get_attachment_image_url($variable_product->get_image_id());
                $productPrice = wc_get_price_including_tax($variable_product);
            }

            $products[] = [
                'product_id' => "$prouctID",
                'name' => $name,
                'description' => strlen($desc) > 0 ? substr($desc, 0, 250) : null,
                'quantity' => $item['quantity'],
                'unit_price' => $productPrice,
                'thumbnail_url' => $thumbnailUrl,
                'is_digital' => $isDigital,
                'url' => $product->get_permalink(),
            ];

            $subtotal += $productPrice * $item['quantity'];
        }

        // Definir la fecha futura (en este ejemplo, 1 hora en el futuro)
        $expiresAt = strtotime('+1 hour') * 1000; // Convertir a milisegundos
        $shipping = $cart->get_shipping_total() + $cart->get_shipping_tax();

        $checkoutData = [
            'completed_redirect_url' => $successUrl,
            'canceled_redirect_url' => $cancelUrl,
            'allow_save_payment_methods' => false,
            'order' => [
                'expires_at' => $expiresAt,
                'merchant_order_id' => $orketaPayCartId,
                'currency' => get_woocommerce_currency(),
                'country_code' => $customer['billing_country'],
                // 'taxes' => $cart->get_total_tax(),
                'discounts' => [['amount' => $cart->get_discount_total()]],
                'shipping_details' => [
                    'amount' => $shipping,
                ],
                'subtotal_amount' => $subtotal,
                'total_amount' => $cart->total,
                'products' => $products,
                'customer' => [
                    'merchant_customer_id' => $customer['id'],
                    'first_name' => $customer['first_name'],
                    'last_name' => $customer['last_name'],
                    'email' => $customer['email'],
                ],
            ],
        ];

        // Validar dirección de envío
        if (
            isset($customer['shipping_address_1']) &&
            isset($customer['shipping_city']) &&
            isset($customer['shipping_state']) &&
            isset($customer['shipping_country']) &&
            isset($customer['shipping_postcode'])
        ) {
            $checkoutData['order']['shipping_address'] = [
                'first_name' => $customer['first_name'],
                'last_name' => $customer['last_name'],
                'email' => $customer['email'],
                'line_1' => $customer['shipping_address_1'],
                'line_2' => $customer['shipping_address_2'],
                'city' => $customer['shipping_city'],
                'state' => $customer['shipping_state'],
                'country' => $customer['shipping_country'],
                'zip_code' => $customer['shipping_postcode'],
            ];
        }

        // Si no existe un ID, se remueve el índice
        if ($cart->get_customer()->get_id() === 0) {
            unset($checkoutData['order']['customer']['merchant_customer_id']);
        }

        // Si no gastos de envío, se remueve el índice
        if ($cart->get_shipping_total() <= 0) {
            unset($checkoutData['order']['shipping_details']);
        }

        // Si no hay descuentos, se remueve el índice
        if ($cart->get_discount_total() <= 0) {
            unset($checkoutData['order']['discounts']);
        }

        return $checkoutData;
    }

    public static function remove_string_spaces($text)
    {
        return preg_replace('/\s+/', '', wc_clean($text));
    }

    public static function get_expiration_month($exp_date)
    {
        $exp_date = self::remove_string_spaces($exp_date);
        $exp_date = explode('/', $exp_date);
        return $exp_date[0];
    }

    public static function get_expiration_year($exp_date)
    {
        $exp_date = self::remove_string_spaces($exp_date);
        $exp_date = explode('/', $exp_date);
        return $exp_date[1];
    }

    public static function is_null_or_empty_string($string)
    {
        return !isset($string) || trim($string) === '';
    }

    public static function get_signature_from_url($url)
    {
        $url_components = explode('&signature=', $url);
        return $url_components[1];
    }

    public static function get_order_id_from_url($url)
    {
        $url_components = explode('order_id=', $url);
        return $url_components[1];
    }
}
