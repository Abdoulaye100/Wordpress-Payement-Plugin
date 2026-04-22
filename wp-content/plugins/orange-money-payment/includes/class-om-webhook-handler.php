<?php

if (!defined('ABSPATH')) {
    exit;
}

class OM_Webhook_Handler {
    
    private $api_client;
    private $logger;
   

    public function __construct($api_client, $logger) {
        $this->api_client = $api_client;
        $this->logger = $logger;
    }
    
    /**
     * Handle webhook notification
     */
    public function handle() {
        $raw_post = file_get_contents('php://input');
        $this->logger->info('Webhook received', array('raw_data' => $raw_post));
        

        $notification = json_decode($raw_post, true);
        
        if (!$notification) {
            $this->logger->error('Invalid webhook data - not JSON');
            $this->send_response(400, 'Invalid JSON');
            return;
        }
        

        if (!isset($notification['status']) || !isset($notification['notif_token'])) {
            $this->logger->error('Invalid notification structure', $notification);
            $this->send_response(400, 'Missing required fields');
            return;
        }
        
        $status = $notification['status'];
        $notif_token = $notification['notif_token'];
        $txnid = isset($notification['txnid']) ? $notification['txnid'] : '';
        
        $this->logger->info('Processing notification', array(
            'status' => $status,
            'notif_token' => $notif_token,
            'txnid' => $txnid
        ));
        

        $order = $this->find_order_by_notif_token($notif_token);
        
        if (!$order) {
            $this->logger->error('Order not found for notif_token: ' . $notif_token);
            $this->send_response(404, 'Order not found');
            return;
        }
        
        $saved_notif_token = $order->get_meta('_om_notif_token');
        
        if ($saved_notif_token !== $notif_token) {
            $this->logger->error('Notif token mismatch for order #' . $order->get_id());
            $this->send_response(403, 'Invalid notification token');
            return;
        }
        

        switch ($status) {
            case 'SUCCESS':
                $this->process_success($order, $txnid);
                break;
                
            case 'FAILED':
                $this->process_failure($order, $txnid);
                break;
                
            case 'PENDING':
                $this->process_pending($order, $txnid);
                break;
                
            default:
                $this->logger->warning('Unknown status: ' . $status);
                $this->send_response(200, 'Status noted');
                return;
        }
        
        $this->update_transaction($order->get_id(), $status, $txnid);
        
        $this->send_response(200, 'OK');
    }
    
    /**
     * sucess  payment
     */
    private function process_success($order, $txnid) {
        if ($order->has_status(array('processing', 'completed'))) {
            $this->logger->info('Order #' . $order->get_id() . ' already completed');
            return;
        }
        
        $order->payment_complete($txnid);
        
        $order->update_meta_data('_om_txnid', $txnid);
        $order->save();
        
        $order->add_order_note(
            sprintf(
                __('Paiement Orange Money confirmé avec succès. Transaction ID: %s', 'orange-money-payment'),
                $txnid
            )
        );
        
        $this->logger->info('Payment completed for order #' . $order->get_id(), array('txnid' => $txnid));
        
        do_action('om_payment_success', $order, $txnid);
    }
    
    /**
     *  echec  payment
     */
    private function process_failure($order, $txnid) {
        // mise à jour  status de la comamnde 
        $order->update_status('failed', __('Paiement Orange Money échoué', 'orange-money-payment'));
        
        if ($txnid) {
            $order->update_meta_data('_om_txnid', $txnid);
            $order->save();
        }
        
        $order->add_order_note(
            sprintf(
                __('Paiement Orange Money échoué. Transaction ID: %s', 'orange-money-payment'),
                $txnid ? $txnid : 'N/A'
            )
        );
        
        $this->logger->warning('Payment failed for order #' . $order->get_id(), array('txnid' => $txnid));
         
        do_action('om_payment_failed', $order, $txnid);
    }
    
    /**
     *  attente de  payment
     */
    private function process_pending($order, $txnid) {
        $order->add_order_note(
            sprintf(
                __('Paiement Orange Money en cours de traitement. Transaction ID: %s', 'orange-money-payment'),
                $txnid ? $txnid : 'N/A'
            )
        );
        
        if ($txnid) {
            $order->update_meta_data('_om_txnid', $txnid);
            $order->save();
        }
        
        $this->logger->info('Payment pending for order #' . $order->get_id(), array('txnid' => $txnid));
        
        do_action('om_payment_pending', $order, $txnid);
    }
    

    private function find_order_by_notif_token($notif_token) {
        $args = array(
            'limit' => 1,
            'meta_key' => '_om_notif_token',
            'meta_value' => $notif_token,
            'return' => 'objects'
        );
        
        $orders = wc_get_orders($args);
        
        return !empty($orders) ? $orders[0] : null;
    }
    
    /**
     * mise 0 jour  transaction dans la  database
     */
    private function update_transaction($order_id, $status, $txnid) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'orange_money_transactions';
        
        $wpdb->update(
            $table_name,
            array(
                'status' => $status,
                'txnid' => $txnid,
            ),
            array('order_id' => $order_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * envoie de la response HTTP 
     */
    private function send_response($code, $message) {
        status_header($code);
        echo json_encode(array('message' => $message));
        exit;
    }
}
