<?php

if (!defined('ABSPATH')) {
    exit;
}

class OM_Ajax_Handler {
    
    /**
     * Initialisation 
     */
    public static function init() {
        add_action('wp_ajax_om_test_connection', array(__CLASS__, 'test_connection'));
        add_action('wp_ajax_om_check_payment_status', array(__CLASS__, 'check_payment_status'));
    }
    
    /**
     * Test API connection
     */
    public static function test_connection() {
        check_ajax_referer('om_test_connection', 'security');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Permission refusée'));
            return;
        }
        
        try {
            // Get gateway settings directly
            $gateways = WC()->payment_gateways->payment_gateways();
            
            if (!isset($gateways['orange_money'])) {
                wp_send_json_error(array('message' => 'Gateway Orange Money non trouvé. Assurez-vous que le plugin est activé.'));
                return;
            }
            
            $gateway = $gateways['orange_money'];
            
            // obttention des credentials s
            $testmode = $gateway->testmode;
            $client_id = $testmode ? $gateway->get_option('test_client_id') : $gateway->get_option('client_id');
            $client_secret = $testmode ? $gateway->get_option('test_client_secret') : $gateway->get_option('client_secret');
            $merchant_key = $testmode ? $gateway->get_option('test_merchant_key') : $gateway->get_option('merchant_key');
            

            $missing = array();
            if (empty($client_id)) {
                $missing[] = 'Client ID';
            }
            if (empty($client_secret)) {
                $missing[] = 'Client Secret';
            }
            if (empty($merchant_key)) {
                $missing[] = 'Merchant Key';
            }
            
            if (!empty($missing)) {
                $mode = $testmode ? 'Test' : 'Production';
                wp_send_json_error(array(
                    'message' => 'Identifiants manquants en mode ' . $mode . ': ' . implode(', ', $missing) . 
                                 '\n\nVeuillez remplir tous les champs et cliquer sur "Enregistrer les modifications" avant de tester.'
                ));
                return;
            }
            
            // Validation du code du merchant key
            if (!preg_match('/^[a-f0-9]{8}$/i', $merchant_key)) {
                wp_send_json_error(array(
                    'message' => 'Format de Merchant Key invalide. Il doit contenir exactement 8 caractères hexadécimaux (0-9, a-f).\n\nExemple: a86b2087'
                ));
                return;
            }
            
            // creation de API client
            $environment = $testmode ? 'sandbox' : 'production';
            $api_client = new OM_API_Client($client_id, $client_secret, $merchant_key, $environment);
            

            $result = $api_client->test_connection();
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Erreur: ' . $e->getMessage()));
        }
    }
    
    /**
     * Checker le statut du paiement de la commande 
     */
    public static function check_payment_status() {
        check_ajax_referer('om_check_status', 'security');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Permission refusée'));
        }
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => 'Order ID manquant'));
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(array('message' => 'Commande introuvable'));
        }
        
        $pay_token = $order->get_meta('_om_pay_token');
        $om_order_id = $order->get_meta('_om_order_id');
        
        if (!$pay_token) {
            wp_send_json_error(array('message' => 'Pas de paiement Orange Money pour cette commande'));
        }
        
        try {
            // obtention gateway settings
            $gateways = WC()->payment_gateways->payment_gateways();
            
            if (!isset($gateways['orange_money'])) {
                wp_send_json_error(array('message' => 'Gateway Orange Money non trouvé'));
                return;
            }
            
            $gateway = $gateways['orange_money'];
            
            // obtention de s credentials
            $testmode = $gateway->testmode;
            $client_id = $testmode ? $gateway->get_option('test_client_id') : $gateway->get_option('client_id');
            $client_secret = $testmode ? $gateway->get_option('test_client_secret') : $gateway->get_option('client_secret');
            $merchant_key = $testmode ? $gateway->get_option('test_merchant_key') : $gateway->get_option('merchant_key');
            
            // Create API client
            $environment = $testmode ? 'sandbox' : 'production';
            $api_client = new OM_API_Client($client_id, $client_secret, $merchant_key, $environment);
            
            $status = $api_client->get_transaction_status(
                $om_order_id,
                $order->get_total(),
                $pay_token
            );
            
            wp_send_json_success($status);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
}

OM_Ajax_Handler::init();
