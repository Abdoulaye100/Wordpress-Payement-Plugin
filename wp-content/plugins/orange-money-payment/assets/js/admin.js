/**
 * Orange Money Payment Gateway - Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        console.log('JavaScript Admin Orange Money chargé');
        
        // Gestionnaire du bouton de test de connexion
        $(document).on('click', '#woocommerce_orange_money_test_connection', function(e) {
            e.preventDefault();
            
            console.log('Bouton de test cliqué');
            
            var $button = $(this);
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('Test en cours...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'om_test_connection',
                    security: typeof om_admin_params !== 'undefined' ? om_admin_params.nonce : ''
                },
                success: function(response) {
                    console.log('Réponse:', response);
                    if (response.success) {
                        alert('✓ Connexion réussie!\n\n' + response.data.message);
                    } else {
                        alert('✗ Échec de connexion\n\n' + response.data.message);
                    }
                },
                error: function(jqXHR, status, error) {
                    console.error('Erreur AJAX:', status, error);
                    alert('✗ Erreur lors du test de connexion: ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Basculer la visibilité des identifiants production/test
        $('#woocommerce_orange_money_testmode').on('change', function() {
            var isTestMode = $(this).is(':checked');
            
            if (isTestMode) {
                $('.test_credentials_section').closest('tr').show();
                $('.prod_credentials_section').closest('tr').show();
            } else {
                $('.test_credentials_section').closest('tr').show();
                $('.prod_credentials_section').closest('tr').show();
            }
        }).trigger('change');
        
        // Valider le format de la clé marchand
        $('input[id*="merchant_key"]').on('blur', function() {
            var value = $(this).val();
            if (value && !/^[a-f0-9]{8}$/.test(value)) {
                alert('Format de Merchant Key invalide. Il doit contenir 8 caractères hexadécimaux.');
            }
        });
    });
    
})(jQuery);
