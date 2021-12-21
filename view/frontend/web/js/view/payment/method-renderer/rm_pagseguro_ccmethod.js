define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Checkout/js/model/full-screen-loader',
        'uiRegistry',
        'Magento_Ui/js/model/messageList',
        'Magento_SalesRule/js/action/set-coupon-code',
        'Magento_SalesRule/js/action/cancel-coupon',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'PagseguroDirectMethod'
    ],
    function (
        Component,
        $,
        fullScreenLoader,
        uiRegistry,
        globalMessageList,
        setCouponCodeAction,
        cancelCouponAction,
        quote
    ) {
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

            initialize: function() {
                this._super();
                
                uiRegistry.get(this.name + '.' + this.name + '.messages', (function(component) {
                    component.hideTimeout = 12000;
                }));

                /*
                setCouponCodeAction.registerSuccessCallback(this._updateInstallments.bind(this));
                cancelCouponAction.registerSuccessCallback(this._updateInstallments.bind(this));
                */

                quote.totals.subscribe(this._updateInstallments.bind(this));
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

                let messageContainer = this.messageContainer || globalMessageList;

                if (event) {
                    event.preventDefault();
                }

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

                            messageContainer.addErrorMessage({
                                message: 'Dados do cartão inválidos. ' + formattedErrors.join(' ')
                            });
                            
                        }).bind(this),

                        // complete callback function
                        (function() {
                            fullScreenLoader.stopLoader();
                            this.disablePlaceOrderButton(false);
                        }).bind(this)
                    );
                }

                return false;
            },

            /**
             * Triggers the update of the installments (consulted on PagSeguro)
             */
            _updateInstallments: function() {
                // checks if the component was fully initialized and the form its open
                if (this.RMPagSeguroObj && this.getCode() == this.isChecked()) {
                    console.log('Total changed: triggering the installments update...');
                    this.RMPagSeguroObj.getInstallments(quote.getTotals()().grand_total);
                }
            }
        });
    }
);
