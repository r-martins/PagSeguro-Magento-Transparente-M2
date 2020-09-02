define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/url',
        'PagseguroDirectMethod'
    ],
    function (ko, $, Component, quote, additionalValidators, redirectOnSuccessAction, urlBuilder) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: true,
            isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),

            defaults: {
                template: 'RicardoMartins_PagSeguro/payment/rm_pagseguro_boleto',
                boletoOwnerCpf: ''
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'boletoOwnerCpf'
                    ]);

                return this;
            },

            getCode: function() {
                return 'rm_pagseguro_boleto';
            },

            getData: function () {
            return {
                    'method': this.item.method,
                    'additional_data': {
                        'boleto_cpf' : this.boletoOwnerCpf(),
                        'sender_hash' : $('input[name="payment[pagseguro_boleto_senderhash]"]').val()
                    }
                };
            },

            isActive: function() {
                return true;
            },
            /**
            * Place order.
            */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                var senderHash = PagSeguroDirectPayment.getSenderHash();
                this.updatePaymentHashes(senderHash);

                if (this.validate() &&
                    additionalValidators.validate() &&
                    this.isPlaceOrderActionAllowed() === true
                ) {
                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .done(
                            function () {
                                self.afterPlaceOrder();

                                if (self.redirectAfterPlaceOrder) {
                                    redirectOnSuccessAction.execute();
                                }
                            }
                        ).always(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        );

                    return true;
                }

                return false;
            },
            /**
            * @return {Boolean}
            */
            validate: function () {
                var boletoCpf = $('input[name="payment[pagseguro_boleto_cpf]"]');
                if (boletoCpf.val() != '') {
                    return true;
                }

                return false;
            },
            updatePaymentHashes: function(senderHash){
                var inputBoletoSenderHash = $('input[name="payment[pagseguro_boleto_senderhash]"]');
                inputBoletoSenderHash.val(senderHash);
                
                return true;                 
            }
        });
    }
);
