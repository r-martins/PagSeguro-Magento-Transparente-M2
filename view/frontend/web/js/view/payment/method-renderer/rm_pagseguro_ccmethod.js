define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'PagseguroDirectMethod'
    ],
    function (Component, $, fullScreenLoader) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'RicardoMartins_PagSeguro/payment/rm_pagseguro_cc',
                creditCardOwnerName: '',
                creditCardOwnerBirthDay: '',
                creditCardOwnerBirthMonth: '',
                creditCardOwnerBirthYear: '',
                creditCardOwnerCpf: '',
                creditCardInstallments: '',
                disablePlaceOrderButton: false
            },

            getPagSeguroCcImagesHtml: function () {
                return document.getElementById('pagSeguroCcImagesHtml').innerHTML;
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'creditCardOwnerName',
                        'creditCardOwnerBirthDay',
                        'creditCardOwnerBirthMonth',
                        'creditCardOwnerBirthYear',
                        'creditCardOwnerCpf',
                        'creditCardInstallments',
                        'disablePlaceOrderButton'
                    ]);

                return this;
            },

            getCode: function() {
                return 'rm_pagseguro_cc';
            },

            getData: function () {
                let originalCcNumber = this.creditCardNumber();
                let ccNumber =
                    originalCcNumber.substring(0, 4).padEnd(originalCcNumber.length - 4, '*') +
                    originalCcNumber.substring(originalCcNumber.length - 4);


                return {
                    'method': this.item.method,
                    'additional_data': {
                        'cc_cid': '',
                        'cc_ss_start_month': this.creditCardSsStartMonth(),
                        'cc_ss_start_year': this.creditCardSsStartYear(),
                        'cc_ss_issue': this.creditCardSsIssue(),
                        'cc_type': this.creditCardType(),
                        'cc_exp_year': '',
                        'cc_exp_month': '',
                        'cc_number': ccNumber,
                        'cc_owner_name' : this.creditCardOwnerName(),
                        'cc_owner_birthday_day' : this.creditCardOwnerBirthDay(),
                        'cc_owner_birthday_month' : this.creditCardOwnerBirthMonth(),
                        'cc_owner_birthday_year' : this.creditCardOwnerBirthYear(),
                        'cc_owner_cpf' : this.creditCardOwnerCpf(),
                        'cc_installments' : this.creditCardInstallments(),
                        'sender_hash' : $('input[name="payment[pagseguropro_cc_senderhash]"]').val(),
                        'credit_card_token' : $('input[name="payment[pagseguropro_cc_cctoken]"]').val(),
                        'cc_type' : $('input[name="payment[pagseguropro_cc_cctype]"]').val(),
                        'is_admin' : $('input[name="payment[pagseguropro_cc_isadmin]"]').val(),
                    }
                };
            },

            isActive: function() {
                return true;
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            limitvalue: function(data, e) {
                if($(e.currentTarget).val().length == 2 && e.key!=8) {
                    return false;
                }
                return true;
            },

            numbervalidation: function(data, e) {
                var charCode = (e.which) ? e.which : e.keyCode
                if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                    return false;
                }
                return true;
            },

            placeOrder: function(data, event) {

                if (this.validate()) {
                    fullScreenLoader.startLoader();
                    this.disablePlaceOrderButton(true);

                    this.RMPagSeguroObj.updateOneCreditCardToken(
                        // success callback function
                        this._super.bind(this, data, event),

                        // error callback function
                        (function(errors) {
                            let formattedErrors = [];

                            _.each(errors, function(value, errorCode) {
                                switch (errorCode) {
                                    case '30400':
                                        formattedErrors.push('Verifique número, data de validade e CVV.');
                                        break;
                                    case '10001':
                                        formattedErrors.push('Tamanho do cartão inválido.');
                                        break;
                                    case '10006':
                                        formattedErrors.push('Tamanho do CVV inválido.');
                                        break;
                                    case '30405':
                                        formattedErrors.push('Data de validade incorreta.');
                                        break;
                                    case '11157':
                                        formattedErrors.push('CPF inválido.');
                                        break;
                                }
                            });

                            if (formattedErrors.length == 0) {
                                formattedErrors.push('Verifique os dados do cartão.');
                            }

                            let errorMessage = 'Dados do cartão inválidos. ' + formattedErrors.join(' ');
                            let messageContainer = $('#pagseguro_cc_method div.messages');
                            let errorTemplate = 
                                `<div role="alert" class="message message-error error">
                                    <div data-ui-id="checkout-cart-validationmessages-message-error">
                                        ${errorMessage}
                                    </div>
                                </div>`;
                            
                            // shows error and scroll to it
                            messageContainer.html(errorTemplate);
                            $('html, body').animate({
                                scrollTop: messageContainer.offset().top - 20
                            }, 1000);
                        }).bind(this),

                        // complete callback function
                        (function() {
                            fullScreenLoader.stopLoader();
                            this.disablePlaceOrderButton(false);
                        }).bind(this)
                    );
                }

                return false;
            }
        });
    }
);
