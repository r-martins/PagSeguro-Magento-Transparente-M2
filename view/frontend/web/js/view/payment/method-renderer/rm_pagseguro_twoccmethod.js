define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'RicardoMartins_PagSeguro/js/model/credit-card-data',
        'Magento_Payment/js/model/credit-card-validation/credit-card-data',
        'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/redirect-on-success',
        'uiRegistry',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'PagseguroDirectMethod'
    ],
    function (
        Component,
        $,
        creditCardSecondData,
        creditCardData,
        cardNumberValidator,
        quote,
        redirectOnSuccessAction,
        uiRegistry
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'RicardoMartins_PagSeguro/payment/rm_pagseguro_twocc',
                creditCardFirstType: '',
                creditCardFirstExpYear: '',
                creditCardFirstExpMonth: '',
                creditCardFirstNumber: '',
                creditCardFirstSsStartMonth: '',
                creditCardFirstSsStartYear: '',
                creditCardFirstSsIssue: '',
                creditCardFirstVerificationNumber: '',
                selectedCardFirstType: null,
                creditCardSecondType: '',
                creditCardSecondExpYear: '',
                creditCardSecondExpMonth: '',
                creditCardSecondNumber: '',
                creditCardSecondSsStartMonth: '',
                creditCardSecondSsStartYear: '',
                creditCardSecondSsIssue: '',
                creditCardSecondVerificationNumber: '',
                selectedCardSecondType: null,
                creditCardFirstOwnerName: '',
                creditCardFirstAmount: '',
                creditCardFirstOwnerBirthDay: '',
                creditCardFirstOwnerBirthMonth: '',
                creditCardFirstOwnerBirthYear: '',
                creditCardFirstOwnerCpf: '',
                creditCardFirstInstallments: '',
                creditCardSecondOwnerName: '',
                creditCardSecondAmount: '',
                creditCardSecondOwnerBirthDay: '',
                creditCardSecondOwnerBirthMonth: '',
                creditCardSecondOwnerBirthYear: '',
                creditCardSecondOwnerCpf: '',
                creditCardSecondInstallments: '',
                amountTotal: 0
            },

            getPagSeguroCcImagesHtml: function () {
                return document.getElementById('pagSeguroMultiCcImagesHtml').innerHTML;
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'creditCardFirstType',
                        'creditCardFirstExpYear',
                        'creditCardFirstExpMonth',
                        'creditCardFirstNumber',
                        'creditCardFirstSsStartMonth',
                        'creditCardFirstSsStartYear',
                        'creditCardFirstSsIssue',
                        'creditCardFirstVerificationNumber',
                        'selectedCardFirstType',
                        'creditCardSecondType',
                        'creditCardSecondExpYear',
                        'creditCardSecondExpMonth',
                        'creditCardSecondNumber',
                        'creditCardSecondSsStartMonth',
                        'creditCardSecondSsStartYear',
                        'creditCardSecondSsIssue',
                        'creditCardSecondVerificationNumber',
                        'selectedCardSecondType',
                        'creditCardFirstOwnerName',
                        'creditCardFirstAmount',
                        'creditCardFirstOwnerBirthDay',
                        'creditCardFirstOwnerBirthMonth',
                        'creditCardFirstOwnerBirthYear',
                        'creditCardFirstOwnerCpf',
                        'creditCardFirstInstallments',
                        'creditCardSecondOwnerName',
                        'creditCardSecondAmount',
                        'creditCardSecondOwnerBirthDay',
                        'creditCardSecondOwnerBirthMonth',
                        'creditCardSecondOwnerBirthYear',
                        'creditCardSecondOwnerCpf',
                        'creditCardSecondInstallments'
                    ]);

                return this;
            },

            initialize: function () {
                var self = this;
    
                this._super();

                uiRegistry.get(this.name + '.' + this.name + '.messages', (function(component) {
                    component.hideTimeout = 12000;
                }));
    
                //Set credit card number to credit card data object
                this.creditCardFirstNumber.subscribe(function (value) {
                    var result;
    
                    self.selectedCardFirstType(null);
    
                    if (value === '' || value === null) {
                        return false;
                    }
                    result = cardNumberValidator(value);
    
                    if (!result.isPotentiallyValid && !result.isValid) {
                        return false;
                    }
    
                    if (result.card !== null) {
                        self.selectedCardFirstType(result.card.type);
                        creditCardData.creditCard = result.card;
                    }
    
                    if (result.isValid) {
                        creditCardData.creditCardNumber = value;
                        self.creditCardFirstType(result.card.type);
                    }
                });
    
                //Set expiration year to credit card data object
                this.creditCardFirstExpYear.subscribe(function (value) {
                    creditCardData.expirationYear = value;
                });
    
                //Set expiration month to credit card data object
                this.creditCardFirstExpMonth.subscribe(function (value) {
                    creditCardData.expirationMonth = value;
                });
    
                //Set cvv code to credit card data object
                this.creditCardFirstVerificationNumber.subscribe(function (value) {
                    creditCardData.cvvCode = value;
                });

                //Set credit card number to credit card data object
                this.creditCardSecondNumber.subscribe(function (value) {
                    var result;
    
                    self.selectedCardSecondType(null);
    
                    if (value === '' || value === null) {
                        return false;
                    }
                    result = cardNumberValidator(value);
    
                    if (!result.isPotentiallyValid && !result.isValid) {
                        return false;
                    }
    
                    if (result.card !== null) {
                        self.selectedCardSecondType(result.card.type);
                        creditCardSecondData.creditCardSecond = result.card;
                    }
    
                    if (result.isValid) {
                        creditCardSecondData.creditCardSecondNumber = value;
                        self.creditCardSecondType(result.card.type);
                    }
                });
    
                //Set expiration year to credit card data object
                this.creditCardSecondExpYear.subscribe(function (value) {
                    creditCardSecondData.expirationSecondYear = value;
                });
    
                //Set expiration month to credit card data object
                this.creditCardSecondExpMonth.subscribe(function (value) {
                    creditCardSecondData.expirationSecondMonth = value;
                });
    
                //Set cvv code to credit card data object
                this.creditCardSecondVerificationNumber.subscribe(function (value) {
                    creditCardSecondData.cvvSecondCode = value;
                });

                var amount_init = this.getAmountInit();
                this.creditCardFirstAmount(amount_init);
                this.creditCardSecondAmount(amount_init);

                // updates grand total on component when magento changes its value
                quote.totals.subscribe(this.updAmount.bind(this));
            },

            getCode: function() {
                return 'rm_pagseguro_twocc';
            },

            getAmountInit: function() {
                var orderAmount = quote.getTotals()().grand_total / 2;
                var amount = orderAmount.toFixed(2);
                var orderAmountOriginal =  amount;
                this.amountTotal = quote.getTotals()().grand_total;
                var amountBalance = (quote.getTotals()().grand_total - orderAmountOriginal).toFixed(2);
                return amountBalance;
            },

            updAmount: function() {
                if (this.amountTotal !== quote.getTotals()().grand_total) {
                    var percentFirst = $('input[name="payment[ps_first_cc_amount]"]').val() / this.amountTotal;
                    this.amountTotal = quote.getTotals()().grand_total;
                    var amountFirst = (this.amountTotal * percentFirst).toFixed(2);
                    var amountSecond = (this.amountTotal - amountFirst).toFixed(2);
                    this.creditCardFirstAmount( amountFirst.toString());
                    this.creditCardSecondAmount( amountSecond.toString());
                }
            },

            /**
            * Place order.
            */
             placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }
                var isInstallments = localStorage.getItem('rm_pagseguro_twocc_installments', false);                

                if (this.validate() &&                    
                    isInstallments == "true"
                ) {
                    this.getPlaceOrderDeferredObject()
                        .done(
                            function () {
                                self.afterPlaceOrder();
                                if (self.redirectAfterPlaceOrder) {
                                    redirectOnSuccessAction.execute();
                                }
                            }
                        );

                    return true;
                }
                if (isInstallments == "false") {
                    alert("A parcela ainda não foi recalculada! Clique fora do form para atualizar as parcelas");
                }
                return false;
            },

            getData: function () {
                var first_cc_amount = $('input[name="payment[ps_first_cc_amount]"]').val();
                var second_cc_amount = $('input[name="payment[ps_second_cc_amount]"]').val();
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'first_cc_cid': this.creditCardFirstVerificationNumber(),
                        'first_cc_ss_start_month': this.creditCardFirstSsStartMonth(),
                        'first_cc_ss_start_year': this.creditCardFirstSsStartYear(),
                        'first_cc_ss_issue': this.creditCardFirstSsIssue(),
                        'first_cc_type': this.creditCardFirstType(),
                        'first_cc_exp_year': this.creditCardFirstExpYear(),
                        'first_cc_exp_month': this.creditCardFirstExpMonth(),
                        'first_cc_number': this.creditCardFirstNumber(),
                        'first_cc_owner_name' : this.creditCardFirstOwnerName(),
                        'first_cc_amount' : first_cc_amount,
                        'first_cc_owner_birthday_day' : this.creditCardFirstOwnerBirthDay(),
                        'first_cc_owner_birthday_month' : this.creditCardFirstOwnerBirthMonth(),
                        'first_cc_owner_birthday_year' : this.creditCardFirstOwnerBirthYear(),
                        'first_cc_owner_cpf' : this.creditCardFirstOwnerCpf(),
                        'first_cc_installments' : $('input[name="payment[pagseguropro_first_cc_installments]"]').val(),
                        'second_cc_cid': this.creditCardSecondVerificationNumber(),
                        'second_cc_ss_start_month': this.creditCardSecondSsStartMonth(),
                        'second_cc_ss_start_year': this.creditCardSecondSsStartYear(),
                        'second_cc_ss_issue': this.creditCardSecondSsIssue(),
                        'second_cc_type': this.creditCardSecondType(),
                        'second_cc_exp_year': this.creditCardSecondExpYear(),
                        'second_cc_exp_month': this.creditCardSecondExpMonth(),
                        'second_cc_number': this.creditCardSecondNumber(),
                        'second_cc_owner_name' : this.creditCardSecondOwnerName(),
                        'second_cc_amount' : second_cc_amount,
                        'second_cc_owner_birthday_day' : this.creditCardSecondOwnerBirthDay(),
                        'second_cc_owner_birthday_month' : this.creditCardSecondOwnerBirthMonth(),
                        'second_cc_owner_birthday_year' : this.creditCardSecondOwnerBirthYear(),
                        'second_cc_owner_cpf' : this.creditCardSecondOwnerCpf(),
                        'second_cc_installments' : $('input[name="payment[pagseguropro_second_cc_installments]"]').val(),
                        'sender_hash' : $('input[name="payment[pagseguropro_cc_senderhash]"]').val(),
                        'credit_card_token_first' : $('input[name="payment[pagseguropro_first_cc_cctoken]"]').val(),
                        'credit_card_token_second' : $('input[name="payment[pagseguropro_second_cc_cctoken]"]').val(),
                        'first_cc_type' : $('input[name="payment[pagseguropro_first_cc_cctype]"]').val(),
                        'second_cc_type' : $('input[name="payment[pagseguropro_second_cc_cctype]"]').val(),
                        'is_admin' : $('input[name="payment[pagseguropro_cc_isadmin]"]').val()
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

            /**
             * Get credit card details
             * @returns {Array}
             */
            getInfo: function () {
                return [
                    {
                        'name': 'First Credit Card Type', value: this.getCcTypeTitleByCode(this.creditCardFirstType())
                    },
                    {
                        'name': 'First Credit Card Number', value: this.formatDisplayCcNumber(this.creditCardFirstNumber())
                    },
                    {
                        'name': 'Second Credit Card Type', value: this.getCcTypeTitleByCode(this.creditCardSecondType())
                    },
                    {
                        'name': 'Second Credit Card Number', value: this.formatDisplayCcNumber(this.creditCardSecondNumber())
                    }
                ];
            }
        });
    }
);
