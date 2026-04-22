<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) {
    exit;
}

final class WC_Orange_Money_Blocks_Support extends AbstractPaymentMethodType {
    
    protected $name = 'orange_money';
    
    public function initialize() {
        $this->settings = get_option('woocommerce_orange_money_settings', array());
    }
    
    public function is_active() {
        return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }
    
    public function get_payment_method_script_handles() {
        wp_register_script(
            'wc-orange-money-blocks-integration',
            OM_PAYMENT_PLUGIN_URL . 'assets/js/blocks.js',
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ),
            OM_PAYMENT_VERSION,
            true
        );
        
        return array('wc-orange-money-blocks-integration');
    }
    
    public function get_payment_method_data() {
        return array(
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'supports' => $this->get_supported_features(),
        );
    }
    
    private function get_supported_features() {
        $gateway = WC()->payment_gateways->payment_gateways()['orange_money'] ?? null;
        
        if ($gateway) {
            return $gateway->supports;
        }
        
        return array('products');
    }
}
