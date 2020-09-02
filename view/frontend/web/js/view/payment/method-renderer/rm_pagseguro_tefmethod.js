define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'PagseguroDirectMethod'
    ],
    function (Component, $) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'RicardoMartins_PagSeguro/payment/rm_pagseguro_tef',
                tefOwnerCpf: '',
                tefOwnerBank: ''
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'tefOwnerCpf',
                        'tefOwnerBank'
                    ]);

                return this;
            },

            getData: function () {
            return {
                    'method': this.item.method,
                    'additional_data': {
                        'tef_cpf' : this.tefOwnerCpf(),
                        'tef_bank' : this.tefOwnerBank(),
                        'sender_hash' : $('input[name="payment[pagseguropro_tef_senderhash]"]').val()
                    }
                };
            },

            getCode: function() {
                return 'rm_pagseguro_tef';
            },

            isActive: function() {
                return true;
            }
        });
    }
);
