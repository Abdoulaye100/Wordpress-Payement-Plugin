<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Orange_Money_Gateway extends WC_Payment_Gateway {
    
    private $api_client;
    private $logger;
   

    public function __construct() {
        $this->id = 'orange_money';
        $this->icon = OM_PAYMENT_PLUGIN_URL . 'assets/images/orange-money-logo.png';
        $this->has_fields = false;
        $this->method_title = __('Orange Money', 'orange-money-payment');
        $this->method_description = __('Acceptez les paiements via Orange Money dans 17 pays d\'Afrique et du Moyen-Orient', 'orange-money-payment');
        
        // Charger les paramètres
        $this->init_form_fields();
        $this->init_settings();
        
        // Définir les variables définies par l'utilisateur
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        
        // Identifiants API
        $this->client_id = $this->testmode ? $this->get_option('test_client_id') : $this->get_option('client_id');
        $this->client_secret = $this->testmode ? $this->get_option('test_client_secret') : $this->get_option('client_secret');
        $this->merchant_key = $this->testmode ? $this->get_option('test_merchant_key') : $this->get_option('merchant_key');
        
        // Initialiser le client API
        $environment = $this->testmode ? 'sandbox' : 'production';
        $this->api_client = new OM_API_Client($this->client_id, $this->client_secret, $this->merchant_key, $environment);
        $this->logger = OM_Logger::get_instance();
        
        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_wc_orange_money_gateway', array($this, 'webhook'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'display_admin_order_meta'));
    }
    
    /**
     * Vérifier si la passerelle est disponible
     */
    public function is_available() {
        $available = true;
        $reason = '';
        
        if ($this->enabled !== 'yes') {
            $available = false;
            $reason = 'Passerelle non activée';
        }
        
        // En production, vérifier si les identifiants sont définis
        if ($available && !$this->testmode) {
            if (empty($this->client_id) || empty($this->client_secret) || empty($this->merchant_key)) {
                $available = false;
                $reason = 'Identifiants manquants en mode production';
            }
        }
        
        // Vérifier les conditions parentes
        $parent_available = parent::is_available();
        
        return $available && $parent_available;
    }
    
    /**
     * Initialiser les champs du formulaire de paramètres de la passerelle
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Activer/Désactiver', 'orange-money-payment'),
                'type' => 'checkbox',
                'label' => __('Activer Orange Money', 'orange-money-payment'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Titre', 'orange-money-payment'),
                'type' => 'text',
                'description' => __('Le titre que vos clients verront lors du paiement', 'orange-money-payment'),
                'default' => __('Orange Money', 'orange-money-payment'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'orange-money-payment'),
                'type' => 'textarea',
                'description' => __('La description que vos clients verront lors du paiement', 'orange-money-payment'),
                'default' => __('Payez en toute sécurité avec Orange Money', 'orange-money-payment'),
                'desc_tip' => true,
            ),
            'testmode' => array(
                'title' => __('Mode Test', 'orange-money-payment'),
                'type' => 'checkbox',
                'label' => __('Activer le mode test (Sandbox)', 'orange-money-payment'),
                'default' => 'yes',
                'description' => __('Utilisez le mode test pour tester les paiements sans transactions réelles', 'orange-money-payment'),
            ),
            'test_credentials_section' => array(
                'title' => __('Identifiants de Test (Sandbox)', 'orange-money-payment'),
                'type' => 'title',
                'description' => __('Obtenez vos identifiants sur https://developer.orange.com', 'orange-money-payment'),
            ),
            'test_client_id' => array(
                'title' => __('Client ID (Test)', 'orange-money-payment'),
                'type' => 'text',
                'description' => __('Votre Client ID pour le mode test', 'orange-money-payment'),
                'default' => '',
                'desc_tip' => true,
            ),
            'test_client_secret' => array(
                'title' => __('Client Secret (Test)', 'orange-money-payment'),
                'type' => 'password',
                'description' => __('Votre Client Secret pour le mode test', 'orange-money-payment'),
                'default' => '',
                'desc_tip' => true,
            ),
            'test_merchant_key' => array(
                'title' => __('Merchant Key (Test)', 'orange-money-payment'),
                'type' => 'text',
                'description' => __('Votre Merchant Key pour le mode test', 'orange-money-payment'),
                'default' => '',
                'desc_tip' => true,
            ),
            'prod_credentials_section' => array(
                'title' => __('Identifiants de Production', 'orange-money-payment'),
                'type' => 'title',
                'description' => __('Identifiants pour les transactions réelles', 'orange-money-payment'),
            ),
            'client_id' => array(
                'title' => __('Client ID (Production)', 'orange-money-payment'),
                'type' => 'text',
                'description' => __('Votre Client ID pour la production', 'orange-money-payment'),
                'default' => '',
                'desc_tip' => true,
            ),
            'client_secret' => array(
                'title' => __('Client Secret (Production)', 'orange-money-payment'),
                'type' => 'password',
                'description' => __('Votre Client Secret pour la production', 'orange-money-payment'),
                'default' => '',
                'desc_tip' => true,
            ),
            'merchant_key' => array(
                'title' => __('Merchant Key (Production)', 'orange-money-payment'),
                'type' => 'text',
                'description' => __('Votre Merchant Key pour la production', 'orange-money-payment'),
                'default' => '',
                'desc_tip' => true,
            ),
            'advanced_section' => array(
                'title' => __('Paramètres Avancés', 'orange-money-payment'),
                'type' => 'title',
            ),
            'country_code' => array(
                'title' => __('Code Pays', 'orange-money-payment'),
                'type' => 'select',
                'description' => __('Sélectionnez votre pays (pour la production)', 'orange-money-payment'),
                'default' => 'ci',
                'options' => array(
                    'ci' => __('Côte d\'Ivoire', 'orange-money-payment'),
                    'sn' => __('Sénégal', 'orange-money-payment'),
                    'ml' => __('Mali', 'orange-money-payment'),
                    'bf' => __('Burkina Faso', 'orange-money-payment'),
                    'cm' => __('Cameroun', 'orange-money-payment'),
                    'mg' => __('Madagascar', 'orange-money-payment'),
                    'cd' => __('RD Congo', 'orange-money-payment'),
                    'gn' => __('Guinée', 'orange-money-payment'),
                ),
                'desc_tip' => true,
            ),
            'payment_language' => array(
                'title' => __('Langue de Paiement', 'orange-money-payment'),
                'type' => 'select',
                'description' => __('Langue de la page de paiement Orange Money', 'orange-money-payment'),
                'default' => 'fr',
                'options' => array(
                    'fr' => __('Français', 'orange-money-payment'),
                    'en' => __('English', 'orange-money-payment'),
                ),
                'desc_tip' => true,
            ),
            'debug_mode' => array(
                'title' => __('Mode Debug', 'orange-money-payment'),
                'type' => 'checkbox',
                'label' => __('Activer les logs détaillés', 'orange-money-payment'),
                'default' => 'yes',
                'description' => __('Enregistre les événements dans WooCommerce > Status > Logs', 'orange-money-payment'),
            ),
            'test_connection' => array(
                'title' => __('Test de Connexion', 'orange-money-payment'),
                'type' => 'title',
                'description' => '<p><button type="button" id="woocommerce_orange_money_test_connection" class="button button-secondary" style="margin-top: 10px;">' . 
                                __('Tester la connexion API', 'orange-money-payment') . '</button></p>' .
                                '<p class="description">' . __('Cliquez pour vérifier que vos identifiants sont corrects', 'orange-money-payment') . '</p>',
            ),
        );
    }
    
    /**
     * Traiter le paiement
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wc_add_notice(__('Commande introuvable.', 'orange-money-payment'), 'error');
            return array('result' => 'failure');
        }
        
        try {
            $amount = $order->get_total();
            $currency = $this->testmode ? 'OUV' : $order->get_currency();
            
            // Préparation des URLs - Remplacer localhost par des URLs valides pour les tests
            $return_url = $this->get_return_url($order);
            $cancel_url = wc_get_checkout_url();
            $notif_url = WC()->api_request_url('wc_orange_money_gateway');
            
            // Pour les tests : remplacer les URLs localhost par des URLs de test valides
            if (strpos($return_url, 'localhost') !== false || strpos($return_url, '127.0.0.1') !== false) {
                $return_url = 'https://httpbin.org/get?return=success&order=' . $order_id;
            }
            if (strpos($cancel_url, 'localhost') !== false || strpos($cancel_url, '127.0.0.1') !== false) {
                $cancel_url = 'https://httpbin.org/get?return=cancel&order=' . $order_id;
            }
            if (strpos($notif_url, 'localhost') !== false || strpos($notif_url, '127.0.0.1') !== false) {
                $notif_url = 'https://httpbin.org/post?notif=webhook&order=' . $order_id;
            }
            
            // Création du paiement
            $payment_data = $this->api_client->create_payment(
                $order_id,
                $amount,
                $currency,
                $return_url,
                $cancel_url,
                $notif_url,
                get_bloginfo('name'),
                $this->get_option('payment_language', 'fr')
            );
            
            // Sauvegarde des données de paiement dans la commande
            $order->update_meta_data('_om_pay_token', $payment_data['pay_token']);
            $order->update_meta_data('_om_notif_token', $payment_data['notif_token']);
            $order->update_meta_data('_om_order_id', $payment_data['order_id']);
            $order->update_meta_data('_om_payment_url', $payment_data['payment_url']);
            $order->save();
            
            $this->save_transaction($order_id, $payment_data, $amount, $currency);
            
            $order->add_order_note(
                sprintf(
                    __('Paiement Orange Money initié. Pay Token: %s', 'orange-money-payment'),
                    $payment_data['pay_token']
                )
            );
            
            $this->logger->info('Paiement initié pour la commande #' . $order_id, $payment_data);
            
            // Redirection vers la page de paiement Orange Money
            return array(
                'result' => 'success',
                'redirect' => $payment_data['payment_url']
            );
            
        } catch (Exception $e) {
            $this->logger->error('Échec du traitement du paiement pour la commande #' . $order_id . ': ' . $e->getMessage());
            
            wc_add_notice(
                __('Erreur lors de l\'initialisation du paiement: ', 'orange-money-payment') . $e->getMessage(),
                'error'
            );
            
            return array(
                'result' => 'failure',
                'messages' => $e->getMessage()
            );
        }
    }
    
    /**
     * Gestionnaire de webhook pour les notifications de paiement
     */
    public function webhook() {
        $handler = new OM_Webhook_Handler($this->api_client, $this->logger);
        $handler->handle();
    }
    
    /**
     * Page de remerciement
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $pay_token = $order->get_meta('_om_pay_token');
        $om_order_id = $order->get_meta('_om_order_id');
        
        if ($pay_token && $order->has_status('pending')) {
            // Vérifier le statut du paiement
            try {
                $status = $this->api_client->get_transaction_status(
                    $om_order_id,
                    $order->get_total(),
                    $pay_token
                );
                
                if ($status['status'] === 'SUCCESS') {
                    $order->payment_complete($status['txnid']);
                    $order->add_order_note(
                        sprintf(
                            __('Paiement Orange Money confirmé. Transaction ID: %s', 'orange-money-payment'),
                            $status['txnid']
                        )
                    );
                }
            } catch (Exception $e) {
                $this->logger->error('Échec de la vérification du statut sur la page de remerciement: ' . $e->getMessage());
            }
        }
        
        if ($order->has_status('completed') || $order->has_status('processing')) {
            echo '<div class="woocommerce-message">';
            echo '<p>' . esc_html__('Votre paiement Orange Money a été confirmé avec succès.', 'orange-money-payment') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Afficher les métadonnées de commande dans l'admin
     */
    public function display_admin_order_meta($order) {
        $pay_token = $order->get_meta('_om_pay_token');
        $txnid = $order->get_meta('_om_txnid');
        
        if ($pay_token) {
            echo '<div class="order_data_column">';
            echo '<h3>' . esc_html__('Orange Money', 'orange-money-payment') . '</h3>';
            echo '<p><strong>' . esc_html__('Pay Token:', 'orange-money-payment') . '</strong> ' . esc_html($pay_token) . '</p>';
            
            if ($txnid) {
                echo '<p><strong>' . esc_html__('Transaction ID:', 'orange-money-payment') . '</strong> ' . esc_html($txnid) . '</p>';
            }
            
            echo '</div>';
        }
    }
    

    private function save_transaction($order_id, $payment_data, $amount, $currency) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'orange_money_transactions';
        
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'pay_token' => $payment_data['pay_token'],
                'notif_token' => $payment_data['notif_token'],
                'status' => 'INITIATED',
                'amount' => $amount,
                'currency' => $currency,
            ),
            array('%d', '%s', '%s', '%s', '%f', '%s')
        );
    }
    
  
    public function admin_options() {
        ?>
        <h2><?php echo esc_html($this->get_method_title()); ?></h2>
        <p><?php echo esc_html($this->get_method_description()); ?></p>
        
        <?php if ($this->testmode) : ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('Mode Test activé - Les paiements ne seront pas réels', 'orange-money-payment'); ?></p>
            </div>
        <?php endif; ?>
        
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('Script Orange Money chargé');
            
            var testEnCours = false;
            
            // bouton de test de connexion
            $(document).on('click', '#woocommerce_orange_money_test_connection', function(e) {
                e.preventDefault();
                
                if (testEnCours) {
                    console.log('Test déjà en cours');
                    return;
                }
                
                console.log('Bouton de test cliqué');
                
                var $button = $(this);
                var texteOriginal = $button.text();
                
                // Vérifier si le formulaire est sauvegardé
                var clientId = $('#woocommerce_orange_money_test_client_id').val();
                var clientSecret = $('#woocommerce_orange_money_test_client_secret').val();
                var merchantKey = $('#woocommerce_orange_money_test_merchant_key').val();
                
                if (!clientId || !clientSecret || !merchantKey) {
                    alert(' Veuillez d\'abord remplir et ENREGISTRER vos identifiants de test avant de tester la connexion.');
                    return;
                }
                
                if (!confirm('Tester la connexion à l\'API Orange Money?')) {
                    return;
                }
                
                testEnCours = true;
                $button.prop('disabled', true).text('Test en cours...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'om_test_connection',
                        security: '<?php echo wp_create_nonce('om_test_connection'); ?>'
                    },
                    timeout: 30000, // Délai d'attente de 30 secondes
                    success: function(response) {
                        console.log('Réponse:', response);
                        if (response.success) {
                            alert('✓ Connexion réussie!\n\n' + response.data.message);
                        } else {
                            var message = response.data && response.data.message ? response.data.message : 'Erreur inconnue';
                            alert('✗ Échec de connexion\n\n' + message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Erreur AJAX:', xhr.responseText);
                        var errorMsg = 'Erreur de connexion';
                        if (status === 'timeout') {
                            errorMsg = 'Délai d\'attente dépassé. Vérifiez votre connexion internet.';
                        } else if (xhr.responseText) {
                            try {
                                var resp = JSON.parse(xhr.responseText);
                                errorMsg = resp.data && resp.data.message ? resp.data.message : errorMsg;
                            } catch(e) {
                                errorMsg = xhr.responseText.substring(0, 200);
                            }
                        }
                        alert('✗ Erreur lors du test de connexion:\n\n' + errorMsg);
                    },
                    complete: function() {
                        testEnCours = false;
                        $button.prop('disabled', false).text(texteOriginal);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
