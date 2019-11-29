define(
    [
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'RicardoMartins_PagSeguro/js/action/set-payment-method-action',
        'Magento_Checkout/js/model/payment-service',
    ],
    function (Component,url,placeOrder,setPaymentMethodAction,paymentService) {
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
                window.location.replace(url.build('pseguro/ajax/redirect'));
            },

            getInstructions: function () {
                return window.checkoutConfig.payment[this.getCode()].description;
            }
           
        });
    }
);
