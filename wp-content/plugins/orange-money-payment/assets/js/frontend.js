jQuery(function($) {

    let om_url = null;

    // 🔥 intercepter la réponse AJAX WooCommerce
    $(document.body).on('checkout_place_order', function() {

        let method = $('input[name="payment_method"]:checked').val();

        if (method === 'orange_money') {
            console.log("Orange Money sélectionné");
        }

    });

    // 🔥 écouter la réponse AJAX globale
    $(document).ajaxSuccess(function(event, xhr, settings) {

        if (settings.url.indexOf('wc-ajax=checkout') !== -1) {

            let response = xhr.responseJSON;

            console.log("RESPONSE AJAX :", response);

            if (response && response.om_payment_url) {

                // 🔥 bloquer la redirection
                window.stop();

                // ouvrir modal
                $('#om-modal').show();

                // injecter URL
                $('#om-frame').attr('src', response.om_payment_url);
                
                $(document.body).trigger('checkout_error');
                
                $('form.checkout').removeClass('processing');
                $('.woocommerce-checkout-payment, .woocommerce-checkout-review-order').removeClass('processing');
                $('.blockUI').remove();
                $('.blockOverlay').remove();
            }
        }

    });

    $(document).on('click', '#om-close', function () {
        $('#om-modal').hide();
        $('#om-frame').attr('src', '');
    });
    
});
