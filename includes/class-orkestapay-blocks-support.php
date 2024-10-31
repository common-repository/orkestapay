<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Orkestapay_Blocks_Support extends AbstractPaymentMethodType
{

    private $gateway;
    protected $name = 'orkestapay';

    public function initialize()
    {
        $this->settings = get_option('woocommerce_orkestapay_settings', []);
        $this->gateway = new OrkestaPay_Gateway();
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {

        wp_register_script(
            'orkestapay-blocks-integration',
            plugin_dir_url(__DIR__) . 'block/checkout.js',
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n'
            ),
            null,
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('orkestapay-blocks-integration');
        }

        return array('orkestapay-blocks-integration');
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'orkesta_checkout_url' => $this->gateway->getOrkestaPayCheckoutUrl(),
        ];
    }
}
