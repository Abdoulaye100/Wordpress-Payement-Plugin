

(function($) {
    'use strict';
    
    var OrangeMoneyModal = {
        modal: null,
        iframe: null,
        checkInterval: null,
        checkCount: 0,
        maxChecks: 60,
        orderId: null,
        
        init: function() {
            this.createModal();
            this.bindEvents();
        },
        
        createModal: function() {
            var modalHTML = `
                <div id="om-payment-modal" class="om-modal-overlay" style="display: none;">
                    <div class="om-modal-container">
                        <div class="om-modal-header">
                            <h3>Paiement Orange Money</h3>
                            <button type="button" class="om-modal-close" id="om-close-modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="om-modal-body">
                            <div class="om-loading">
                                <div class="om-spinner"></div>
                                <p>Chargement du paiement sécurisé...</p>
                            </div>
                            <iframe 
                                id="om-payment-iframe" 
                                frameborder="0"
                                allowfullscreen
                                sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-top-navigation"
                            ></iframe>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            this.modal = $('#om-payment-modal');
            this.iframe = $('#om-payment-iframe');
        },
        
        bindEvents: function() {
            var self = this;
            
            $(document).on('click', '#om-close-modal', function() {
                self.closeModal();
            });
            
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.modal.is(':visible')) {
                    self.closeModal();
                }
            });
            
            $(document).on('click', '#om-payment-modal', function(e) {
                if ($(e.target).is('#om-payment-modal')) {
                    self.closeModal();
                }
            });
            
            this.iframe.on('load', function() {
                $('.om-loading').fadeOut();
                self.iframe.fadeIn();
            });
        },
        
        openModal: function(paymentUrl, orderId) {
            this.orderId = orderId;
            this.checkCount = 0;
            
            this.iframe.hide();
            $('.om-loading').show();
            
            this.iframe.attr('src', paymentUrl);
            
            this.modal.fadeIn(300);
            $('body').css('overflow', 'hidden');
            
            this.startStatusCheck();
        },
        
        closeModal: function() {
            var self = this;
            
            if (confirm('Êtes-vous sûr de vouloir annuler le paiement ?')) {
                this.stopStatusCheck();
                this.modal.fadeOut(300, function() {
                    self.iframe.attr('src', 'about:blank');
                    $('body').css('overflow', '');
                });
            }
        },
        
        startStatusCheck: function() {
            var self = this;
            
            this.checkInterval = setInterval(function() {
                self.checkPaymentStatus();
            }, 5000);
            
            this.checkPaymentStatus();
        },
        
        stopStatusCheck: function() {
            if (this.checkInterval) {
                clearInterval(this.checkInterval);
                this.checkInterval = null;
            }
        },
        
        checkPaymentStatus: function() {
            var self = this;
            this.checkCount++;
            
            if (this.checkCount > this.maxChecks) {
                this.stopStatusCheck();
                this.showMessage('error', 'Le délai de paiement a expiré. Veuillez réessayer.');
                setTimeout(function() {
                    window.location.href = om_checkout_params.checkout_url;
                }, 3000);
                return;
            }
            
            $.ajax({
                url: om_checkout_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'om_check_payment_status',
                    order_id: this.orderId,
                    security: om_checkout_params.nonce
                },
                success: function(response) {
                    if (response.success && response.data.status) {
                        var status = response.data.status;
                        
                        if (status === 'SUCCESS') {
                            self.stopStatusCheck();
                            self.showMessage('success', 'Paiement réussi !', 'Votre paiement a été confirmé avec succès.');
                            setTimeout(function() {
                                window.location.href = om_checkout_params.return_url.replace('{order_id}', self.orderId);
                            }, 2000);
                        } else if (status === 'FAILED') {
                            self.stopStatusCheck();
                            self.showMessage('error', 'Paiement échoué', 'Le paiement n\'a pas pu être effectué.');
                            setTimeout(function() {
                                window.location.href = om_checkout_params.checkout_url;
                            }, 3000);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erreur lors de la vérification du statut:', error);
                }
            });
        },
        
        showMessage: function(type, title, message) {
            var icon = type === 'success' ? '✓' : '✗';
            var iconClass = type === 'success' ? 'om-success-icon' : 'om-error-icon';
            
            var messageHTML = `
                <div class="om-modal-container">
                    <div class="om-modal-body om-${type}-message">
                        <div class="${iconClass}">${icon}</div>
                        <h3>${title}</h3>
                        ${message ? '<p>' + message + '</p>' : ''}
                        ${type === 'success' ? '<p>Redirection en cours...</p>' : ''}
                    </div>
                </div>
            `;
            
            this.modal.html(messageHTML);
        }
    };
    
    $(document).ready(function() {
        OrangeMoneyModal.init();
        
        $(document.body).on('checkout_place_order_orange_money', function() {
            // Laisser WooCommerce traiter la commande normalement
            return true;
        });
        
        $(document.body).on('checkout_error', function() {
        });
    });
    
    window.OrangeMoneyModal = OrangeMoneyModal;
    
})(jQuery);
