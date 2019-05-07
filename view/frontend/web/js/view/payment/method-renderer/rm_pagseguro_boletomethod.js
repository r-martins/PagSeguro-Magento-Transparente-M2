define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component, $) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'RicardoMartins_PagSeguro/payment/rm_pagseguro_boleto'
            },

            getCode: function() {
                return 'rm_pagseguro_boleto';
            },

            isActive: function() {
                return true;
            },
        });
    }
);
