define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'PagseguroDirectMethod'
    ],
    function (Component, $) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'RicardoMartins_PagSeguro/payment/rm_pagseguro_cc',
                creditCardOwnerName: '',
                creditCardOwnerBirthDay: '',
                creditCardOwnerBirthMonth: '',
                creditCardOwnerBirthYear: '',
                creditCardOwnerCpf: '',
                creditCardInstallments: ''
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
                        'creditCardInstallments'
                    ]);

                return this;
            },

            getCode: function() {
                return 'rm_pagseguro_cc';
            },

            getData: function () {
            return {
                    'method': this.item.method,
                    'additional_data': {
                        'cc_cid': this.creditCardVerificationNumber(),
                        'cc_ss_start_month': this.creditCardSsStartMonth(),
                        'cc_ss_start_year': this.creditCardSsStartYear(),
                        'cc_ss_issue': this.creditCardSsIssue(),
                        'cc_type': this.creditCardType(),
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.creditCardNumber(),
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
            }
        });
    }
);
