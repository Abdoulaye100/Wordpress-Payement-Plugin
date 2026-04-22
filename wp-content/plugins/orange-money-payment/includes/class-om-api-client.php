<?php
/**
 * Orange Money API Client
 * 
 * Handles all API communications with Orange Money Web Payment API
 */

if (!defined('ABSPATH')) {
    exit;
}

class OM_API_Client {
    
    private $client_id;
    private $client_secret;
    private $merchant_key;
    private $environment;
    private $access_token;
    private $token_expiry;
    private $logger;
    
    // Points de terminaison API
    const API_BASE_URL = 'https://api.orange.com';
    const TOKEN_ENDPOINT = '/oauth/v3/token';
    const WEBPAYMENT_ENDPOINT_DEV = '/orange-money-webpay/dev/v1/webpayment';
    const WEBPAYMENT_ENDPOINT_PROD = '/orange-money-webpay/{country}/v1/webpayment';
    const TRANSACTION_STATUS_ENDPOINT_DEV = '/orange-money-webpay/dev/v1/transactionstatus';
    const TRANSACTION_STATUS_ENDPOINT_PROD = '/orange-money-webpay/{country}/v1/transactionstatus';
    
    // URLs des pages de paiement
    const PAYMENT_PAGE_DEV = 'https://webpayment-ow-sb.orange-money.com/payment/pay_token/';
    const PAYMENT_PAGE_PROD = 'https://webpayment.orange-money.com/payment/pay_token/';
    
    /**
     * Constructor
     */
    public function __construct($client_id, $client_secret, $merchant_key, $environment = 'sandbox') {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->merchant_key = $merchant_key;
        $this->environment = $environment;
        $this->logger = OM_Logger::get_instance();
        
        // Charger le token sauvegardé s'il existe
        $this->load_token();
    }
    
    /**
     * Obtenir le token d'accès OAuth
     */
    public function get_access_token($force_refresh = false) {
        // Retourne le token en cache s'il est encore valide
        if (!$force_refresh && $this->access_token && $this->token_expiry > time()) {
            return $this->access_token;
        }
        
        $url = self::API_BASE_URL . self::TOKEN_ENDPOINT;
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ),
            'body' => array(
                'grant_type' => 'client_credentials'
            ),
            'timeout' => 30
        );
        
        $this->logger->debug('Demande de token OAuth');
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->error('Échec de la demande de token: ' . $response->get_error_message());
            throw new Exception('Échec d\'obtention du token d\'accès: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            $this->logger->error('Échec de la demande de token avec le statut: ' . $status_code, $body);
            throw new Exception('Échec d\'obtention du token d\'accès. Statut: ' . $status_code);
        }
        
        if (!isset($body['access_token'])) {
            $this->logger->error('Aucun token d\'accès dans la réponse', $body);
            throw new Exception('Réponse de token invalide');
        }
        
        $this->access_token = $body['access_token'];
        // Calcul de l'expiration du token (par défaut 90 jours)
        $this->token_expiry = time() + (isset($body['expires_in']) ? intval($body['expires_in']) : 7776000);
        
        // Sauvegarde du token
        $this->save_token();
        
        $this->logger->info('Token d\'accès obtenu avec succès');
        
        return $this->access_token;
    }
    
    /**
     * Créer un paiement web
     */
    public function create_payment($order_id, $amount, $currency, $return_url, $cancel_url, $notif_url, $reference = '', $lang = 'fr') {
        $token = $this->get_access_token();
        
        $endpoint = $this->environment === 'sandbox' 
            ? self::WEBPAYMENT_ENDPOINT_DEV 
            : str_replace('{country}', $this->get_country_code(), self::WEBPAYMENT_ENDPOINT_PROD);
        
        $url = self::API_BASE_URL . $endpoint;
        
        // Génération d'un ID de commande unique (max 30 caractères)
        $unique_order_id = substr($order_id . '_' . time(), 0, 30);
        
        $payload = array(
            'merchant_key' => $this->merchant_key,
            'currency' => $currency,
            'order_id' => $unique_order_id,
            'amount' => intval($amount),
            'return_url' => substr($return_url, 0, 120),
            'cancel_url' => substr($cancel_url, 0, 120),
            'notif_url' => substr($notif_url, 0, 120),
            'lang' => $lang,
            'reference' => substr($reference, 0, 30)
        );
        
        // Log de la requête API
        $this->logger->debug('Création de paiement', array('order_id' => $unique_order_id, 'amount' => $amount));
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($payload),
            'timeout' => 30
        );
        
        $this->logger->log_api_request($url, $payload);
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->error('Échec de la création de paiement: ' . $response->get_error_message());
            throw new Exception('Échec de la requête de paiement: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        // Log de la réponse API
        $this->logger->log_api_request($url, $payload, $body);
        
        if ($status_code !== 201 && $status_code !== 200) {
            $error_message = isset($body['message']) ? $body['message'] : 'Unknown error';
            
            // Message d'erreur détaillé
            if (isset($body['error'])) {
                $error_message = $body['error'];
            }
            if (isset($body['error_description'])) {
                $error_message .= ': ' . $body['error_description'];
            }
            
            $this->logger->error('Échec de la création de paiement avec le statut: ' . $status_code, $body);
            throw new Exception('Échec de la création de paiement: ' . $error_message);
        }
        
        if (!isset($body['pay_token']) || !isset($body['payment_url'])) {
            $this->logger->error('Réponse de paiement invalide', $body);
            throw new Exception('Réponse de paiement invalide');
        }
        
        $this->logger->info('Paiement créé avec succès', array('order_id' => $unique_order_id));
        
        return array(
            'status' => $body['status'],
            'message' => $body['message'],
            'pay_token' => $body['pay_token'],
            'payment_url' => $body['payment_url'],
            'notif_token' => $body['notif_token'],
            'order_id' => $unique_order_id
        );
    }
    
    /**
     * Vérification du statut de la transaction
     */
    public function get_transaction_status($order_id, $amount, $pay_token) {
        $token = $this->get_access_token();
        
        $endpoint = $this->environment === 'sandbox' 
            ? self::TRANSACTION_STATUS_ENDPOINT_DEV 
            : str_replace('{country}', $this->get_country_code(), self::TRANSACTION_STATUS_ENDPOINT_PROD);
        
        $url = self::API_BASE_URL . $endpoint;
        
        $payload = array(
            'order_id' => $order_id,
            'amount' => intval($amount),
            'pay_token' => $pay_token
        );
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($payload),
            'timeout' => 30
        );
        
        $this->logger->log_api_request($url, $payload);
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->error('Échec de la vérification du statut: ' . $response->get_error_message());
            throw new Exception('Échec de la vérification du statut: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        $this->logger->log_api_request($url, $payload, $body);
        
        if ($status_code !== 200 && $status_code !== 201) {
            $this->logger->error('Échec de la vérification du statut avec le statut: ' . $status_code, $body);
            throw new Exception('Échec de la vérification du statut');
        }
        
        return $body;
    }
    
    /**
     * Obtenir l'URL de la page de paiement
     */
    public function get_payment_page_url($pay_token) {
        $base_url = $this->environment === 'sandbox' 
            ? self::PAYMENT_PAGE_DEV 
            : self::PAYMENT_PAGE_PROD;
        
        return $base_url . $pay_token;
    }
    
    /**
     * Obtenir le code pays pour la production
     */
    private function get_country_code() {
        // Configurable dans les paramètres
        return get_option('om_payment_country_code', 'ml'); 
    }
    
    /**
     * Sauvegarder le token en base de données
     */
    private function save_token() {
        update_option('om_payment_access_token', $this->access_token);
        update_option('om_payment_token_expiry', $this->token_expiry);
    }
    
    /**
     * Charger le token depuis la base de données
     */
    private function load_token() {
        $this->access_token = get_option('om_payment_access_token', '');
        $this->token_expiry = get_option('om_payment_token_expiry', 0);
    }
    

    
    public function test_connection() {
        try {
            $token = $this->get_access_token(true);
            return array(
                'success' => true,
                'message' => 'Connexion réussie à l\'API Orange Money',
                'token' => substr($token, 0, 20) . '...'
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Échec de connexion: ' . $e->getMessage()
            );
        }
    }
}
