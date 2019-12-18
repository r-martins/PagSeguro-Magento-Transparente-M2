define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/url'
    ],
    function (ko, $, Component, quote, additionalValidators, redirectOnSuccessAction, urlBuilder) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: true,
            isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),

            defaults: {
                template: 'RicardoMartins_PagSeguro/payment/rm_pagseguro_boleto'
            },

            getCode: function() {
                return 'rm_pagseguro_boleto';
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
                var url = urlBuilder.build('pseguro/ajax/updatePaymentHashes');
                var boletocpf = $('input[name="payment[pagseguro_boleto_cpf]"]').val();
                var billingCpf = $('input[name="vat_id"]').val();

                if (boletocpf == '' || boletocpf == undefined) {
                    boletocpf = billingCpf;
                }

                var currnetSelectedPayment = $('input[name="payment[method]"]:checked').attr('id');

                if (boletocpf != '' && typeof(boletocpf) !== undefined && currnetSelectedPayment == 'rm_pagseguro_boleto') {
                    var paymentHashes = {
                        "payment[sender_hash]": senderHash,
                        "ownerdata[boleto_cpf]": boletocpf,
                    };
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: paymentHashes,
                    success: function(response){
                        console.debug('Hashes updated successfully.');
                        console.debug(paymentHashes);

                        return true;
                    },
                    error: function(response){
                        console.error('Failed to update session hashes.');
                        console.error(response);

                        return false;
                    }
                });
            }
        });
    }
);