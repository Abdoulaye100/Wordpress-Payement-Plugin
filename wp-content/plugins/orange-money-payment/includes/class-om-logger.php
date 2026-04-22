<?php


if (!defined('ABSPATH')) {
    exit;
}

class OM_Logger {
    
    private static $instance = null;
    private $logger;
    
   
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
  
    private function __construct() {
        if (function_exists('wc_get_logger')) {
            $this->logger = wc_get_logger();
        }
    }
    
   
    public function info($message, $context = array()) {
        $this->log('info', $message, $context);
    }
    
    
    public function error($message, $context = array()) {
        $this->log('error', $message, $context);
    }
    
  
    public function debug($message, $context = array()) {
        $this->log('debug', $message, $context);
    }
    
   
    public function warning($message, $context = array()) {
        $this->log('warning', $message, $context);
    }
    
    
    private function log($level, $message, $context = array()) {
        if ($this->logger) {
            $context['source'] = 'orange-money-payment';
            $this->logger->log($level, $message, $context);
        }
    }
  
    public function log_api_request($endpoint, $data, $response = null) {
        $log_data = array(
            'endpoint' => $endpoint,
            'request_data' => $this->sanitize_log_data($data),
            'timestamp' => current_time('mysql')
        );
        
        if ($response !== null) {
            $log_data['response'] = $this->sanitize_log_data($response);
        }
        
        $this->info('API Request: ' . $endpoint, $log_data);
    }
    
    
    private function sanitize_log_data($data) {
        if (is_array($data)) {
            $sanitized = $data;
            $sensitive_keys = array('merchant_key', 'access_token', 'pin', 'password', 'Authorization');
            
            foreach ($sensitive_keys as $key) {
                if (isset($sanitized[$key])) {
                    $sanitized[$key] = '***REDACTED***';
                }
            }
            
            return $sanitized;
        }
        
        return $data;
    }
}
