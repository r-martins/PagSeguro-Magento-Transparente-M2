define(
    [
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/payment-service',
        'PagseguroDirectMethod',
        'jquery'
    ],
    function (Component,url,placeOrder,paymentService,pagseguroDirectMethod,$) {
        'use strict';

        return Component.extend({

            redirectAfterPlaceOrder: false,

            defaults: {
                template: 'RicardoMartins_PagSeguro/payment/rm_pagseguro_pagar_no_pagseguro'
            },

            getCode: function() {
                return 'rm_pagseguro_pagar_no_pagseguro';
            },

            isActive: function() {
                return true;
            },

            afterPlaceOrder: function () {

                $.ajax({
                    url: url.build('pseguro/ajax/redirect'),
                }).done(function(result) {
                    if(result === 'false'){
                        window.location.replace(url.build(window.checkoutConfig.defaultSuccessPageUrl));
                    }
                });
                window.location.replace(url.build('pseguro/ajax/redirect'));
            },

            getInstructions: function () {
                return window.checkoutConfig.payment[this.getCode()].description;
            }
        });
    }
);
