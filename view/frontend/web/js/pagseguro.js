/**
 * PagSeguro Transparente para Magento
 * @author Ricardo Martins <pagseguro-transparente@ricardomartins.net.br>
 * @link http://bit.ly/pagseguromagento
 * @version 1.0.0
 */

function RMPagSeguro(config) {
        if(config.PagSeguroSessionId == false){
            console.error('Unable to get PagSeguro SessionId. Check your token, key and settings.');
        }
         console.log('RMPagSeguro has been initialized.');

        this.config = config;
        this.config.maxSenderHashAttempts = 30;
        var methis = this;
        PagSeguroDirectPayment.setSessionId(config.PagSeguroSessionId);
        var senderHashSuccess = this.updateSenderHash();
        if(!senderHashSuccess){
            console.log('A new attempt to obtain sender_hash will be performed in 3 seconds.');
            var intervalSenderHash;
            var senderHashAttempts = 0;
            intervalSenderHash = setInterval(function(){
                senderHashAttempts++;
                if(PagSeguroDirectPayment.ready){
                    methis.updateSenderHash();
                    clearInterval(intervalSenderHash);
                    return true;
                }
                if (senderHashAttempts == 40) {
                    clearInterval(intervalSenderHash);
                    console.error('Unable to get sender_hash after multiple attempts.');
                }
            }, 3000 );
        }

        var parcelsDrop = jQuery('#rm_pagseguro_cc_cc_installments');
        //Please enter credit card data to calculate
        parcelsDrop.append('<option value="">Informe os dados do cartão para calcular</option>');
}

RMPagSeguro.prototype.updateSenderHash = function(){
   var senderHash = PagSeguroDirectPayment.getSenderHash();
    if(typeof senderHash != "undefined" && senderHash != '')
    {
        this.senderHash = senderHash;
        //this.updatePaymentHashes();
        return true;
    }
    console.log('PagSeguro: Failed to get senderHash.');
    return false;
}

RMPagSeguro.prototype.addCardFieldsObserver = function(obj){
    try {
        var ccNumElm = jQuery('input[name="payment[ps_cc_number]"]');
        var ccExpMoElm = jQuery('input[name="payment[ps_cc_exp_month]"]');
        var ccExpYrElm = jQuery('input[name="payment[ps_cc_exp_year]"]');
        var ccCvvElm = jQuery('input[name="payment[ps_cc_cid]"]');
        var ccExpYrVisibileElm = jQuery('#rm_pagseguro_cc_cc_year_visible');
        var ccNumVisibleElm = jQuery('.cc_number_visible');

        jQuery(ccNumElm).keyup(function( event ) {
            obj.updateCreditCardToken();
        });
        jQuery(ccNumVisibleElm).keyup(function( event ) {

            jQuery(this).val(function (index, value) {
                var cc_num;
                var key = event.which || event.keyCode || event.charCode;
                if(key == 8) {
                    cc_num = value.replace(/\s+/g, '');
                    jQuery(ccNumElm).val(cc_num);

                } else {
                    if (value != ' ') {
                        var cc_num_original = value.replace(/\s+/g, '');

                        jQuery(ccNumElm).val(cc_num_original);
                        jQuery(ccNumElm).keyup();
                    }
                }

                cc_num = value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ');
                cc_num = cc_num.trim();
                return cc_num;
            });
            obj.updateCreditCardToken();
        });
        jQuery(ccExpMoElm).keyup(function( event ) {
            obj.updateCreditCardToken();
        });
        jQuery(ccExpYrElm).keyup(function( event ) {
            obj.updateCreditCardToken();
        });
        jQuery(ccCvvElm).keyup(function( event ) {
            obj.updateCreditCardToken();
        });
        /*jQuery(cpf).keyup(function( event ) {
            obj.updateCreditCardToken();
        });*/
        jQuery(ccExpYrVisibileElm).keyup(function( event ) {
            var ccExpYr = '';
            if(jQuery(this).val().length == 1) {
                ccExpYr = '200' + jQuery(ccExpYrVisibileElm).val();
            }

            if(jQuery(this).val().length == 2) {
                ccExpYr = '20' + jQuery(ccExpYrVisibileElm).val();
            }
            jQuery(ccExpYrElm).val(ccExpYr);
        });

        jQuery( "#pagseguro_cc_method .actions-toolbar .checkout" ).on("click", function() {
                obj.updateCreditCardToken();
        });

        jQuery( "#pagseguro_tef_method .actions-toolbar .checkout" ).on("click", function() {
                obj.updatePaymentHashes();
        });

        jQuery("#rm_pagseguro_cc_cc_installments").change(function( event ) {
            obj.updateInstallments();
        });

        jQuery("#pagseguropro_tef_bank").change(function( event ) {
            jQuery(".tefbank").val(jQuery(this).val());
        });

        jQuery('#rm_pagseguro_tef').change(function() {
            if(this.checked) {
               obj.removeUnavailableBanks();
            }
        });

    } catch(e) {
        console.error('Unable to add greeting to cards. ' + e.message);
    }
}

RMPagSeguro.prototype.updateCreditCardToken = function(){
    var ccNum = jQuery('input[name="payment[ps_cc_number]"]').val().replace(/^\s+|\s+$/g,'');
    var ccExpMo = jQuery('input[name="payment[ps_cc_exp_month]"]').val().replace(/^\s+|\s+$/g,'');
    var ccExpYr = jQuery('input[name="payment[ps_cc_exp_year]"]').val().replace(/^\s+|\s+$/g,'');
    var ccCvv = jQuery('input[name="payment[ps_cc_cid]"]').val().replace(/^\s+|\s+$/g,'');
    var brandName = '';
    var self = this;
    if(typeof this.lastCcNum != "undefined" || ccNum != this.lastCcNum){
        this.updateBrand();
        if(typeof this.brand != "undefined"){
            brandName = this.brand.name;
        }
    }

    if(ccNum.length > 6 && ccExpMo != "" && ccExpYr != "" && ccCvv.length >= 3)
    {
        PagSeguroDirectPayment.createCardToken({
            cardNumber: ccNum,
            brand: brandName,
            cvv: ccCvv,
            expirationMonth: ccExpMo,
            expirationYear: ccExpYr,
            success: function(psresponse){
                console.log(psresponse);
                self.creditCardToken = psresponse.card.token;
                self.updatePaymentHashes();
                self.getInstallments(self.grandTotal, self.installmentsQty);
                jQuery('#card-msg').html('');
            },
            error: function(psresponse){
                //TODO: get real message instead of trying to catch all errors in the universe
                if(undefined!=psresponse.errors["30400"]) {
                    jQuery('#card-msg').html('Dados do cartão inválidos.');
                }else if(undefined!=psresponse.errors["10001"]){
                    jQuery('#card-msg').html('Tamanho do cartão inválido.');
                }else if(undefined!=psresponse.errors["10006"]){
                    jQuery('#card-msg').html('Tamanho do CVV inválido.');
                }else if(undefined!=psresponse.errors["30405"]){
                    jQuery('#card-msg').html('Data de validade incorreta.');
                }else if(undefined!=psresponse.errors["30403"]){
                    this.updateSessionId(); //Se sessao expirar, atualizamos a session
                }else if(undefined!=psresponse.errors["11157"]){
                    jQuery('#card-cpf-msg').html('CPF invalid value.');
                }else{
                    jQuery('#card-msg').html('Check your typed card data.');
                }
                console.error('Failed to get token from card.');
                console.log(psresponse.errors);
                errors = true;
            },
            complete: function(psresponse){
                 console.info('Card token updated successfully.');

            }
        });
    }

}

RMPagSeguro.prototype.updateBrand = function(){
    var ccNum ='';
    if(jQuery('input[name="payment[ps_cc_number]"]').val()){
        var ccNum = jQuery('input[name="payment[ps_cc_number]"]').val().replace(/^\s+|\s+$/g,'');
    }
    var currentBin = ccNum.substring(0, 6);
    var flag = this.config.flag;
    var debug = this.config.debug;
    var self = this;

    if(ccNum.length >= 6){
        if (typeof this.cardBin != "undefined" && currentBin == this.cardBin) {
            if(typeof this.brand != "undefined"){
                jQuery('.cc_number_visible').attr('style','background-image:url("https://stc.pagseguro.uol.com.br/public/img/payment-methods-flags/' +flag + '/' + this.brand.name + '.png") !important');
            }
            return;
        }
        this.cardBin = ccNum.substring(0, 6);
        PagSeguroDirectPayment.getBrand({
            cardBin: currentBin,
            success: function(psresponse){
                self.brand = psresponse.brand;
                if(flag != ''){
                    jQuery('.cc_number_visible').attr('style','background-image:url("https://stc.pagseguro.uol.com.br/public/img/payment-methods-flags/' +flag + '/' + psresponse.brand.name + '.png") !important');
                }
            },
            error: function(psresponse){
                console.error('Failed to get card flag.');
                if(debug){
                    console.debug('Check the call to / getBin on df.uol.com on your Network inspector for more details.');
                }
            }
        })
    }
}

RMPagSeguro.prototype.updatePaymentHashes = function(){
    var currentSelectedPayment = jQuery('input[name="payment[method]"]:checked').attr('id');

    if (currentSelectedPayment == 'rm_pagseguro_tef') {
        var inputTefSenderHash = jQuery('input[name="payment[pagseguropro_tef_senderhash]"]');
            inputTefSenderHash.val(this.senderHash);
    }

    if (currentSelectedPayment == 'rm_pagseguro_boleto') {
        var inputBoletoSenderHash = jQuery('input[name="payment[pagseguro_boleto_senderhash]"]');
            inputBoletoSenderHash.val(this.senderHash);
    }

    if (currentSelectedPayment == 'rm_pagseguro_cc') {
        var inputCcSenderHash = jQuery('input[name="payment[pagseguropro_cc_senderhash]"]');
            inputCcSenderHash.val(this.senderHash);
        var inputCcToken = jQuery('input[name="payment[pagseguropro_cc_cctoken]"]');
            inputCcToken.val(this.creditCardToken);
        var inputCcType = jQuery('input[name="payment[pagseguropro_cc_cctype]"]');
            inputCcType.val((this.brand)?this.brand.name:'');
        var inputCcIsadmin = jQuery('input[name="payment[pagseguropro_cc_isadmin]"]');
            inputCcIsadmin.val(this.config.is_admin);
    }
}

RMPagSeguro.prototype.setStoreUrl = function(storeUrl){
    this.storeUrl = storeUrl;
}

RMPagSeguro.prototype.setInstallmentsQty = function(qty){
    this.installmentsQty = qty;
}

RMPagSeguro.prototype.setGrandTotal = function(total){
    this.grandTotal = total;
}

RMPagSeguro.prototype.getGrandTotal = function(){

    var url = this.storeUrl + 'pseguro/ajax/getGrandTotal';
    var self = this;
    jQuery.ajax({
        url: url,
        success: function(response){
            self.setGrandTotal(response.total);
        },
        error: function(response){
            return false;
        }
    });
}

RMPagSeguro.prototype.updateSessionId = function(){
    var url = this.setStoreUrl + 'pseguro/ajax/getSessionId';
    jQuery.ajax({
        url: url,
        onSuccess: function (response) {
            var session_id = response.session_id;
            if(!session_id){
                console.log('Não foi possível obter a session id do PagSeguro. Verifique suas configurações.');
            }
            PagSeguroDirectPayment.setSessionId(session_id);
        }
    });
}

RMPagSeguro.prototype.getInstallments = function(grandTotal, selectedInstallment){
    var brandName = "";
    var self = this;
    if(typeof this.brand == "undefined"){
        return;
    }
    if(typeof grandTotal == "undefined"){
       this.getGrandTotal();
    }

    this.grandTotal = grandTotal;
    brandName = this.brand.name;
    PagSeguroDirectPayment.getInstallments({
        amount: grandTotal,
        brand: brandName,
        success: function(response) {
            var parcelsDrop = jQuery('#rm_pagseguro_cc_cc_installments');
            var b = response.installments[brandName];
            parcelsDrop.empty();

            if(self.config.force_installments_selection == 1){
                parcelsDrop.append('<option value="" selected="selected">Selecione a quantidade de parcelas</option>');
            }

            for(var x=0; x < b.length; x++){
                var optionText = '';
                var optionVal = '';
                optionText = b[x].quantity + "x de R$" + b[x].installmentAmount.toFixed(2).toString().replace('.',',');
                optionText += (b[x].interestFree)?" sem juros":" com juros";
                if(self.config.show_total == 1){
                    optionText += " (total R$" + (b[x].installmentAmount*b[x].quantity).toFixed(2).toString().replace('.', ',') + ")";
                }
                optionVal = b[x].quantity + "|" + b[x].installmentAmount;
                // if(b[x].quantity == selectedInstallment){
                //     parcelsDrop.append('<option value="'+optionVal+'" selected>'+optionText+'</option>');
                // }else{
                    parcelsDrop.append('<option value="'+optionVal+'">'+optionText+'</option>');
                // }
            }
            if(self.config.force_installments_selection != 1){
                $('#rm_pagseguro_cc_cc_installments option[selected="selected"]').each(
                    function() {
                        $(this).removeAttr('selected');
                    }
                );
                parcelsDrop.prop("selectedIndex",0);
            }
             // updating installment value in checkout session
              self.updateInstallments();
        },
        error: function(response) {
            console.error('Error getting parcels:');
            console.error(response);
        },
        complete: function(response) {
             console.log('inside getInstallments complete');
        }
    });
}

RMPagSeguro.prototype.updateInstallments = function(){
    var url = this.storeUrl + 'pseguro/ajax/updateInstallments';
    ccInstallment = jQuery('select[name="payment[ps_cc_installments]"] option:selected').val();
    var arr = ccInstallment.split("|");
    this.setInstallmentsQty(arr[0]);
    var self = this;
    var installmentsData = {
        "installment[cc_installment]": ccInstallment,
    };
    jQuery.ajax({
        url: url,
        type: 'POST',
        data: installmentsData,
        success: function(response){
            if(self.config.debug){
                console.debug('Installments Data updated successfully.');
                console.debug(installmentsData);
            }
        },
        error: function(response){
            if(self.config.debug){
                console.error('Failed to update Installments Data.');
                console.error(response);
            }
            return false;
        }
    });
}

RMPagSeguro.prototype.removeUnavailableBanks = function() {
    var self = this;
    var parcelsDrop = jQuery('#pagseguropro_tef_bank');
    parcelsDrop.empty();
    var tefnodeName = jQuery('#pagseguropro_tef_bank').prop("nodeName");
    if(tefnodeName != "SELECT"){
        //se houve customizações no elemento dropdown de bancos, não selecionaremos aqui
        return;
    }
    PagSeguroDirectPayment.getPaymentMethods({
        amount: this.grandTotal,
        success: function (response) {
            if (response.error == true && this.config.debug) {
                console.log('Não foi possível obter os meios de pagamento que estão funcionando no momento.');
                return;
            }
            try {
                parcelsDrop.empty();
                parcelsDrop.append('<option value="">Selecione o banco</option>');
                for (y in response.paymentMethods.ONLINE_DEBIT.options) {
                    if (response.paymentMethods.ONLINE_DEBIT.options[y].status != 'UNAVAILABLE') {
                        var optName = response.paymentMethods.ONLINE_DEBIT.options[y].displayName.toString();
                        var optValue = response.paymentMethods.ONLINE_DEBIT.options[y].name.toString();
                        parcelsDrop.append('<option value="'+optValue+'">'+optName+'</option>');
                    }
                }

                if(this.config.debug){
                    console.info('Bancos TEF atualizados com sucesso.');
                }
            } catch (err) {
                console.log(err.message);
            }
        }
    })
}

RMPagSeguro.prototype.setCardPlaceHolderImage = function(ccPlaceholderImage){
    jQuery('.cc_number_visible').keyup(function( event ) {
        if (jQuery(this).val().length <= 0) {
            jQuery(this).attr('style','background-image:url("' + ccPlaceholderImage + '") !important');
        }
    });
}
