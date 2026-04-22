<?php

if (!defined('ABSPATH')) {
    exit;
}

class OM_Hooks {
    
   
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_order_meta_box'));
        
        add_filter('woocommerce_order_actions', array(__CLASS__, 'add_order_actions'));
        add_action('woocommerce_order_action_om_check_status', array(__CLASS__, 'process_check_status'));
        add_action('woocommerce_order_action_om_refund_payment', array(__CLASS__, 'process_refund'));
        
        add_filter('manage_edit-shop_order_columns', array(__CLASS__, 'add_order_columns'));
        add_action('manage_shop_order_posts_custom_column', array(__CLASS__, 'render_order_columns'), 10, 2);
    }
    

    public static function add_order_meta_box() {
        add_meta_box(
            'om_payment_details',
            __('Orange Money - Détails du Paiement', 'orange-money-payment'),
            array(__CLASS__, 'render_order_meta_box'),
            'shop_order',
            'side',
            'high'
        );
    }
    

    public static function render_order_meta_box($post) {
        $order = wc_get_order($post->ID);
        
        if ($order->get_payment_method() !== 'orange_money') {
            echo '<p>' . esc_html__('Cette commande n\'utilise pas Orange Money', 'orange-money-payment') . '</p>';
            return;
        }
        
        $pay_token = $order->get_meta('_om_pay_token');
        $notif_token = $order->get_meta('_om_notif_token');
        $txnid = $order->get_meta('_om_txnid');
        $om_order_id = $order->get_meta('_om_order_id');
        $payment_url = $order->get_meta('_om_payment_url');
        
        ?>
        <div class="om-meta-box">
            <?php if ($pay_token): ?>
                <p>
                    <strong><?php esc_html_e('Pay Token:', 'orange-money-payment'); ?></strong><br>
                    <code><?php echo esc_html($pay_token); ?></code>
                </p>
            <?php endif; ?>
            
            <?php if ($om_order_id): ?>
                <p>
                    <strong><?php esc_html_e('Order ID Orange:', 'orange-money-payment'); ?></strong><br>
                    <code><?php echo esc_html($om_order_id); ?></code>
                </p>
            <?php endif; ?>
            
            <?php if ($txnid): ?>
                <p>
                    <strong><?php esc_html_e('Transaction ID:', 'orange-money-payment'); ?></strong><br>
                    <code><?php echo esc_html($txnid); ?></code>
                </p>
            <?php endif; ?>
            
            <?php if ($payment_url): ?>
                <p>
                    <a href="<?php echo esc_url($payment_url); ?>" target="_blank" class="button">
                        <?php esc_html_e('Voir la page de paiement', 'orange-money-payment'); ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <p>
                <button type="button" class="button button-primary" onclick="omCheckPaymentStatus(<?php echo $order->get_id(); ?>)">
                    <?php esc_html_e('Vérifier le statut', 'orange-money-payment'); ?>
                </button>
            </p>
        </div>
        
        <script>
        function omCheckPaymentStatus(orderId) {
            if (!confirm('<?php esc_html_e('Vérifier le statut du paiement?', 'orange-money-payment'); ?>')) {
                return;
            }
            
            jQuery.post(ajaxurl, {
                action: 'om_check_payment_status',
                order_id: orderId,
                security: '<?php echo wp_create_nonce('om_check_status'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Statut: ' + response.data.status + '\n' + 
                          (response.data.txnid ? 'Transaction ID: ' + response.data.txnid : ''));
                    location.reload();
                } else {
                    alert('Erreur: ' + response.data.message);
                }
            });
        }
        </script>
        <?php
    }
    

    public static function add_order_actions($actions) {
        $actions['om_check_status'] = __('Orange Money: Vérifier le statut', 'orange-money-payment');
        return $actions;
    }
    
   
    public static function process_check_status($order) {
        if ($order->get_payment_method() !== 'orange_money') {
            return;
        }
        
        $gateway = new WC_Orange_Money_Gateway();
        $pay_token = $order->get_meta('_om_pay_token');
        $om_order_id = $order->get_meta('_om_order_id');
        
        if (!$pay_token) {
            $order->add_order_note(__('Impossible de vérifier le statut: pas de pay_token', 'orange-money-payment'));
            return;
        }
        
        try {
            $status = $gateway->api_client->get_transaction_status(
                $om_order_id,
                $order->get_total(),
                $pay_token
            );
            
            $order->add_order_note(
                sprintf(
                    __('Statut vérifié: %s (Transaction ID: %s)', 'orange-money-payment'),
                    $status['status'],
                    isset($status['txnid']) ? $status['txnid'] : 'N/A'
                )
            );
            
            // Update order status if needed
            if ($status['status'] === 'SUCCESS' && !$order->is_paid()) {
                $order->payment_complete($status['txnid']);
            }
            
        } catch (Exception $e) {
            $order->add_order_note(
                sprintf(
                    __('Erreur lors de la vérification du statut: %s', 'orange-money-payment'),
                    $e->getMessage()
                )
            );
        }
    }
    
  
    public static function process_refund($order) {
        
        $order->add_order_note(
            __('Les remboursements Orange Money doivent être traités manuellement via le portail Orange Money', 'orange-money-payment')
        );
    }
    

    public static function add_order_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            if ($key === 'order_status') {
                $new_columns['om_txnid'] = __('Orange Money TxnID', 'orange-money-payment');
            }
        }
        
        return $new_columns;
    }
    
    
    public static function render_order_columns($column, $post_id) {
        if ($column === 'om_txnid') {
            $order = wc_get_order($post_id);
            
            if ($order && $order->get_payment_method() === 'orange_money') {
                $txnid = $order->get_meta('_om_txnid');
                
                if ($txnid) {
                    echo '<span class="om-txnid">' . esc_html($txnid) . '</span>';
                } else {
                    echo '<span class="om-no-txnid">—</span>';
                }
            }
        }
    }
}

OM_Hooks::init();
