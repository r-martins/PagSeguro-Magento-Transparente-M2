/**
 * PagSeguro Transparente para Magento
 * @author Ricardo Martins <pagseguro-transparente@ricardomartins.net.br>
 * @link http://bit.ly/pagseguromagento
 * @version 2.8.1
 */

function RMPagSeguro(config) {
        if(config.PagSeguroSessionId == false){
            console.error('Unable to get PagSeguro SessionId. Check your token, key and settings.');
        }
         console.log('RMPagSeguro has been initialized.');

        window.rmconfig = config;
        window.rmconfig.maxSenderHashAttempts = 30;
        var methis = this;
        PagSeguroDirectPayment.setSessionId(config.PagSeguroSessionId);
        var senderHashSuccess = this.updateSenderHash();
        if(!senderHashSuccess){
            console.log('A new attempt to obtain sender_hash will be performed in 3 seconds. Note that this is not a required parameter.');
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
                    console.error('Unable to get sender_hash after multiple attempts. Don\'t bother, we don\'t need it.');
                }
            }, 3000 );
        }

        var parcelsDrop = jQuery('#rm_pagseguro_cc_cc_installments');
        var parcelsFirstDrop = jQuery('#rm_pagseguro_twocc_first_cc_installments');
        var parcelsSecondDrop = jQuery('#rm_pagseguro_twocc_second_cc_installments');

        //Please enter credit card data to calculate
        parcelsDrop.append('<option value="">Informe os dados do cartão para calcular</option>');
        parcelsFirstDrop.append('<option value="">Informe os dados do cartão para calcular</option>');
        parcelsSecondDrop.append('<option value="">Informe os dados do cartão para calcular</option>');

}

RMPagSeguro.prototype.updateSenderHash = function(){
   var senderHash = PagSeguroDirectPayment.getSenderHash();
    if(typeof senderHash != "undefined" && senderHash != '')
    {
        this.senderHash = senderHash;
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

        var ccFirstAmount = jQuery('input[name="payment[ps_first_cc_amount]"]');
        var ccFirstNumElm = jQuery('input[name="payment[ps_first_cc_number]"]');
        var ccFirstExpMoElm = jQuery('input[name="payment[ps_first_cc_exp_month]"]');
        var ccFirstExpYrElm = jQuery('input[name="payment[ps_first_cc_exp_year]"]');
        var ccFirstCvvElm = jQuery('input[name="payment[ps_first_cc_cid]"]');
        var ccFirstExpYrVisibileElm = jQuery('#rm_pagseguro_twocc_first_cc_year_visible');
        var ccFirstNumVisibleElm = jQuery('.first_cc_number_visible');

        var ccSecondAmount = jQuery('input[name="payment[ps_second_cc_amount]"]');
        var ccSecondNumElm = jQuery('input[name="payment[ps_second_cc_number]"]');
        var ccSecondExpMoElm = jQuery('input[name="payment[ps_second_cc_exp_month]"]');
        var ccSecondExpYrElm = jQuery('input[name="payment[ps_second_cc_exp_year]"]');
        var ccSecondCvvElm = jQuery('input[name="payment[ps_second_cc_cid]"]');
        var ccSecondExpYrVisibileElm = jQuery('#rm_pagseguro_twocc_second_cc_year_visible');
        var ccSecondNumVisibleElm = jQuery('.second_cc_number_visible');

        jQuery(ccNumElm).keyup(function( event ) {
            obj.updateOneCreditCardToken();
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
            obj.updateOneCreditCardToken();
        });
        jQuery(ccExpMoElm).keyup(function( event ) {
            obj.updateOneCreditCardToken();
        });
        jQuery(ccExpYrElm).keyup(function( event ) {
            obj.updateOneCreditCardToken();
        });
        jQuery(ccCvvElm).keyup(function( event ) {
            obj.updateOneCreditCardToken();
        });
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
        jQuery(ccFirstAmount).keyup(function( event ) {
            obj.updateAmount('first');
        });
        jQuery(ccFirstAmount).blur(function(event){
            obj.setTwoInstallments('first');
        });
        jQuery(ccFirstNumElm).keyup(function( event ) {
            obj.updateTwoCreditCardToken('first');
            obj.setTwoInstallments('first');
        });
        jQuery(ccFirstNumVisibleElm).keyup(function( event ) {

            jQuery(this).val(function (index, value) {
                var cc_num;
                var key = event.which || event.keyCode || event.charCode;
                if(key == 8) {
                    cc_num = value.replace(/\s+/g, '');
                    jQuery(ccFirstNumElm).val(cc_num);

                } else {
                    if (value != ' ') {
                        var cc_num_original = value.replace(/\s+/g, '');

                        jQuery(ccFirstNumElm).val(cc_num_original);
                        jQuery(ccFirstNumElm).keyup();
                    }
                }

                cc_num = value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ');
                cc_num = cc_num.trim();
                return cc_num;
            });
            obj.updateTwoCreditCardToken('first');
        });
        jQuery(ccFirstExpMoElm).keyup(function( event ) {
            obj.updateTwoCreditCardToken('first');
        });
        jQuery(ccFirstExpYrElm).keyup(function( event ) {
            obj.updateTwoCreditCardToken('first');
        });
        jQuery(ccFirstCvvElm).keyup(function( event ) {
            obj.updateTwoCreditCardToken('first');
        });
        jQuery(ccFirstExpYrVisibileElm).keyup(function( event ) {
            var ccExpYr = '';
            if(jQuery(this).val().length == 1) {
                ccExpYr = '200' + jQuery(ccFirstExpYrVisibileElm).val();
            }

            if(jQuery(this).val().length == 2) {
                ccExpYr = '20' + jQuery(ccFirstExpYrVisibileElm).val();
            }
            jQuery(ccFirstExpYrElm).val(ccExpYr);
        });
        jQuery(ccSecondAmount).keyup(function( event ) {
            obj.updateAmount('second');
        });
        jQuery(ccSecondAmount).blur(function(event){            
            obj.setTwoInstallments('first');
        });
        jQuery(ccSecondNumElm).keyup(function( event ) {
            obj.updateTwoCreditCardToken('second');
        });
        jQuery(ccSecondNumVisibleElm).keyup(function( event ) {

            jQuery(this).val(function (index, value) {
                var cc_num;
                var key = event.which || event.keyCode || event.charCode;
                if(key == 8) {
                    cc_num = value.replace(/\s+/g, '');
                    jQuery(ccSecondNumElm).val(cc_num);

                } else {
                    if (value != ' ') {
                        var cc_num_original = value.replace(/\s+/g, '');

                        jQuery(ccSecondNumElm).val(cc_num_original);
                        jQuery(ccSecondNumElm).keyup();
                    }
                }

                cc_num = value.replace(/\W/gi, '').replace(/(.{4})/g, '$1 ');
                cc_num = cc_num.trim();
                return cc_num;
            });
            obj.updateTwoCreditCardToken('second');
        });
        jQuery(ccSecondExpMoElm).keyup(function( event ) {
            obj.updateTwoCreditCardToken('second');
        });
        jQuery(ccSecondExpYrElm).keyup(function( event ) {
            obj.updateTwoCreditCardToken('second');
        });
        jQuery(ccSecondCvvElm).keyup(function( event ) {
            obj.updateTwoCreditCardToken('second');
        });
        jQuery(ccSecondExpYrVisibileElm).keyup(function( event ) {
            var ccExpYr = '';
            if(jQuery(this).val().length == 1) {
                ccExpYr = '200' + jQuery(ccSecondExpYrVisibileElm).val();
            }

            if(jQuery(this).val().length == 2) {
                ccExpYr = '20' + jQuery(ccSecondExpYrVisibileElm).val();
            }
            jQuery(ccSecondExpYrElm).val(ccExpYr);
        });
        jQuery( "#pagseguro_cc_method .actions-toolbar .checkout" ).on("click", function() {
                obj.updateOneCreditCardToken();
        });
        jQuery( "#pagseguro_tef_method .actions-toolbar .checkout" ).on("click", function() {
                obj.updatePaymentHashes();
        });
        jQuery("#rm_pagseguro_cc_cc_installments").change(function( event ) {
            obj.updateInstallments();
        });
        jQuery("#rm_pagseguro_twocc_first_cc_installments").change(function( event ) {
            obj.updateTwoInstallments('first');
        });
        jQuery("#rm_pagseguro_twocc_second_cc_installments").change(function( event ) {
            obj.updateTwoInstallments('second');
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
    if (jQuery("input[name='payment[method]']:checked").val() === 'rm_pagseguro_twocc' ) {
        self.updateTwoCreditCardToken('first');
        self.updateTwoCreditCardToken('second');
    } else {
        self.updateOneCreditCardToken();
    }
}

RMPagSeguro.prototype.updateOneCreditCardToken = function() {
    var ccNum = jQuery('input[name="payment[ps_cc_number]"]').val().replace(/[^0-9\.]+/g, '');
    var ccExpMo = jQuery('input[name="payment[ps_cc_exp_month]"]').val().replace(/[^0-9\.]+/g, '');
    var ccExpYr = jQuery('input[name="payment[ps_cc_exp_year]"]').val().replace(/[^0-9\.]+/g, '');
    var ccCvv = jQuery('input[name="payment[ps_cc_cid]"]').val().replace(/[^0-9\.]+/g, '');
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
                    jQuery('#card-msg').html('Dados do cartão inválidos. Verifique número, data de validade e CVV.');
                }else if(undefined!=psresponse.errors["10001"]){
                    jQuery('#card-msg').html('Tamanho do cartão inválido.');
                }else if(undefined!=psresponse.errors["10006"]){
                    jQuery('#card-msg').html('Tamanho do CVV inválido.');
                }else if(undefined!=psresponse.errors["30405"]){
                    jQuery('#card-msg').html('Data de validade incorreta.');
                }else if(undefined!=psresponse.errors["30403"]){
                    this.updateSessionId(); //Se sessao expirar, atualizamos a session
                }else if(undefined!=psresponse.errors["11157"]){
                    jQuery('#card-cpf-msg').html('CPF inválido.');
                }else{
                    jQuery('#card-msg').html('Verifique os dados do cartão.');
                }
                console.error('Falha ao obter token do cartão.');
                console.log(psresponse.errors);
                errors = true;
            },
            complete: function(psresponse){
                 console.info('Token do cartão atualizado com sucesso.');
            }
        });
    }    
}

RMPagSeguro.prototype.updateAmount = function(cardLabel) {
    
    var orginalValue = parseFloat(this.grandTotal).toFixed(2);
    var orderAmount = String(orginalValue).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    orderAmount = orderAmount.replace(/[^0-9]/g, '');
    orderAmount = Number(orderAmount);

    var value = jQuery('input[name="payment[ps_'+ cardLabel +'_cc_amount]"]').val().replace(',','.');
    value = value.replace(/[^0-9]/g, '');
    value = Number(value);

    if (value >= orderAmount) {
        value = orderAmount - 1;
    }

    if (isNaN(value)) {
        value = 0;
    }

    var remaining = orderAmount - value;

    remaining = (remaining / 100).toFixed(2);
    value = (value / 100).toFixed(2);

    if (cardLabel == 'first') {        
        jQuery('input[name="payment[ps_second_cc_amount]"]').val(remaining.toString());
        jQuery('input[name="payment[pagseguropro_second_cc_amount]"]').val(remaining.toString());
    }
    if (cardLabel == 'second') {
        jQuery('input[name="payment[ps_first_cc_amount]"]').val(remaining.toString());
        jQuery('input[name="payment[pagseguropro_first_cc_amount]"]').val(remaining.toString());
    }
    jQuery('input[name="payment[ps_'+ cardLabel +'_cc_amount]"]').val(value.toString());
    jQuery('input[name="payment[pagseguropro_'+ cardLabel +'_cc_amount]"]').val(value.toString());
}

RMPagSeguro.prototype.setTwoInstallments = function (cardLabel) {
    var amount = jQuery('input[name="payment[ps_'+ cardLabel +'_cc_amount]"]').val().replace(',','.');

    this.getTwoInstallments(amount, this.installmentsQty, cardLabel);
    jQuery('#'+cardLabel+'-card-msg').html('');
}

RMPagSeguro.prototype.updateTwoCreditCardToken = function(cardLabel){
    var ccNum = jQuery('input[name="payment[ps_'+ cardLabel +'_cc_number]"]').val().replace(/[^0-9\.]+/g, '');
    var ccExpMo = jQuery('input[name="payment[ps_'+ cardLabel +'_cc_exp_month]"]').val().replace(/[^0-9\.]+/g, '');
    var ccExpYr = jQuery('input[name="payment[ps_'+ cardLabel +'_cc_exp_year]"]').val().replace(/[^0-9\.]+/g, '');
    var ccCvv = jQuery('input[name="payment[ps_'+ cardLabel +'_cc_cid]"]').val().replace(/[^0-9\.]+/g, '');
    var brandName = '';
    var self = this;

    if (cardLabel === 'first') {
        if(typeof this.lastFirstCcNum != "undefined" || ccNum != this.lastFirstCcNum){
            this.updateTwoBrand(cardLabel);
            if(typeof this.firstBrand != "undefined"){
                brandName = this.firstBrand.name;
            }
        }
    } else {
        if(typeof this.lastSecondCcNum != "undefined" || ccNum != this.lastSecondCcNum){
            this.updateTwoBrand(cardLabel);
            if(typeof this.secondBrand != "undefined"){
                brandName = this.secondBrand.name;
            }
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
                if (cardLabel === 'first') {
                    self.creditCardTokenFirst = psresponse.card.token;
                } else {
                    self.creditCardTokenSecond = psresponse.card.token;
                }
                self.updatePaymentHashes();                
            },
            error: function(psresponse){
                //TODO: get real message instead of trying to catch all errors in the universe
                if(undefined!=psresponse.errors["30400"]) {
                    jQuery('#'+cardLabel+'-card-msg').html('Dados do cartão inválidos. Verifique número, data de validade e CVV.');
                }else if(undefined!=psresponse.errors["10001"]){
                    jQuery('#'+cardLabel+'-card-msg').html('Tamanho do cartão inválido.');
                }else if(undefined!=psresponse.errors["10006"]){
                    jQuery('#'+cardLabel+'-card-msg').html('Tamanho do CVV inválido.');
                }else if(undefined!=psresponse.errors["30405"]){
                    jQuery('#'+cardLabel+'-card-msg').html('Data de validade incorreta.');
                }else if(undefined!=psresponse.errors["30403"]){
                    this.updateSessionId(); //Se sessao expirar, atualizamos a session
                }else if(undefined!=psresponse.errors["11157"]){
                    jQuery('#card-cpf-msg').html('CPF inválido.');
                }else{
                    jQuery('#'+cardLabel+'-card-msg').html('Verifique os dados do cartão.');
                }
                console.error('Falha ao obter token do cartão.');
                console.log(psresponse.errors);
                errors = true;
            },
            complete: function(psresponse){
                 console.info('Token do cartão atualizado com sucesso.');
            }
        });
    }

}

RMPagSeguro.prototype.updateBrand = function(){
    if (jQuery("input[name='payment[method]']:checked").val() === 'rm_pagseguro_twocc' ) {
        this.updateTwoBrand('first');
        this.updateTwoBrand('second');
    } else {
        this.updateOneBrand();
    }
}

RMPagSeguro.prototype.updateOneBrand = function(){
    var ccNum ='';
    if(jQuery('input[name="payment[ps_cc_number]"]').val()){
        var ccNum = jQuery('input[name="payment[ps_cc_number]"]').val().replace(/[^0-9\.]+/g, '');
    }
    var currentBin = ccNum.substring(0, 6);
    var flag = window.rmconfig.flag;
    var debug = window.rmconfig.debug;
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

RMPagSeguro.prototype.updateTwoBrand = function(cardLabel){
    var ccNum ='';
    if(jQuery('input[name="payment[ps_'+ cardLabel +'_cc_number]"]').val()){
        var ccNum = jQuery('input[name="payment[ps_'+ cardLabel +'_cc_number]"]').val().replace(/[^0-9\.]+/g, '');
    }
    var currentBin = ccNum.substring(0, 6);
    var flag = window.rmconfig.flag;
    var debug = window.rmconfig.debug;
    var self = this;

    if(ccNum.length >= 6){
        if (cardLabel === 'first') {
            if (typeof this.cardFirstBin != "undefined" && currentBin == this.cardFirstBin) {
                if(typeof this.firstBrand != "undefined"){
                    jQuery('.'+cardLabel+'_cc_number_visible').attr('style','background-image:url("https://stc.pagseguro.uol.com.br/public/img/payment-methods-flags/' +flag + '/' + this.firstBrand.name + '.png") !important');
                }
                return;
            }
        } else {
            if (typeof this.cardSecondBin != "undefined" && currentBin == this.cardSecondBin) {
                if(typeof this.secondBrand != "undefined"){
                    jQuery('.'+cardLabel+'_cc_number_visible').attr('style','background-image:url("https://stc.pagseguro.uol.com.br/public/img/payment-methods-flags/' +flag + '/' + this.secondBrand.name + '.png") !important');
                }
                return;
            }
        }
        if (cardLabel === 'first') {
            this.cardFirstBin = currentBin;
        } else {
            this.cardSecondBin = currentBin;
        }
        PagSeguroDirectPayment.getBrand({
            cardBin: currentBin,
            success: function(psresponse){
                if (cardLabel === 'first') {
                    self.firstBrand = psresponse.brand;
                } else {
                    self.secondBrand = psresponse.brand;
                }                
                if(flag != ''){
                    jQuery('.'+cardLabel+'_cc_number_visible').attr('style','background-image:url("https://stc.pagseguro.uol.com.br/public/img/payment-methods-flags/' +flag + '/' + psresponse.brand.name + '.png") !important');
                }
                self.setTwoInstallments(cardLabel);
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
            inputCcIsadmin.val(window.rmconfig.is_admin);
    }

    if (currentSelectedPayment == 'rm_pagseguro_twocc') {
        var inputCcSenderHash = jQuery('input[name="payment[pagseguropro_cc_senderhash]"]');
            inputCcSenderHash.val(this.senderHash);
        var inputFirstCcToken = jQuery('input[name="payment[pagseguropro_first_cc_cctoken]"]');
            inputFirstCcToken.val(this.creditCardTokenFirst);
        var inputFirstCcType = jQuery('input[name="payment[pagseguropro_first_cc_cctype]"]');
            inputFirstCcType.val((this.firstBrand)?this.firstBrand.name:'');
        var inputSecondCcToken = jQuery('input[name="payment[pagseguropro_second_cc_cctoken]"]');
            inputSecondCcToken.val(this.creditCardTokenSecond);
        var inputSecondCcType = jQuery('input[name="payment[pagseguropro_second_cc_cctype]"]');
            inputSecondCcType.val((this.secondBrand)?this.secondBrand.name:'');
        var inputCcIsadmin = jQuery('input[name="payment[pagseguropro_cc_isadmin]"]');
            inputCcIsadmin.val(window.rmconfig.is_admin);
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

            if(window.rmconfig.force_installments_selection == 1){
                parcelsDrop.append('<option value="" selected="selected">Selecione a quantidade de parcelas</option>');
            }

            for(var x=0; x < b.length; x++){
                var optionText = '';
                var optionVal = '';
                optionText = b[x].quantity + "x de R$" + b[x].installmentAmount.toFixed(2).toString().replace('.',',');
                optionText += (b[x].interestFree)?" sem juros":" com juros";
                if(window.rmconfig.show_total == 1){
                    optionText += " (total R$" + (b[x].installmentAmount*b[x].quantity).toFixed(2).toString().replace('.', ',') + ")";
                }
                optionVal = b[x].quantity + "|" + b[x].installmentAmount;
                // if(b[x].quantity == selectedInstallment){
                //     parcelsDrop.append('<option value="'+optionVal+'" selected>'+optionText+'</option>');
                // }else{
                    parcelsDrop.append('<option value="'+optionVal+'">'+optionText+'</option>');
                // }
            }
            if(window.rmconfig.force_installments_selection != 1){
                jQuery('#rm_pagseguro_cc_cc_installments option[selected="selected"]').each(
                    function() {
                        jQuery(this).removeAttr('selected');
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

RMPagSeguro.prototype.getTwoInstallments = function(grandTotal, selectedInstallment, cardLabel){
    var self = this;
    if (cardLabel === 'first') {
        if(typeof this.firstBrand == "undefined"){
            return;
        }
    } else if (cardLabel === 'second') {
        if(typeof this.secondBrand == "undefined"){
            return;
        }
    } else {
        return;
    }

    const brandName = (cardLabel === 'first')?this.firstBrand.name:this.secondBrand.name;
    
    if(typeof grandTotal == "undefined"){
       this.getGrandTotal();
    }

    PagSeguroDirectPayment.getInstallments({
        amount: grandTotal,
        brand: brandName,
        success: function(response) {
            var parcelsDrop = jQuery('#rm_pagseguro_twocc_'+ cardLabel +'_cc_installments');
            var value = document.querySelector('#rm_pagseguro_twocc_'+ cardLabel +'_cc_installments').selectedIndex;
            if (value < 0) { value = 0; }
            var b = response.installments[brandName];
            parcelsDrop.empty();

            if(window.rmconfig.force_installments_selection == 1){
                parcelsDrop.append('<option value="" selected="selected">Selecione a quantidade de parcelas</option>');
            }

            for(var x=0; x < b.length; x++){
                var optionText = '';
                var optionVal = '';
                optionText = b[x].quantity + "x de R$" + b[x].installmentAmount.toFixed(2).toString().replace('.',',');
                optionText += (b[x].interestFree)?" sem juros":" com juros";
                if(window.rmconfig.show_total == 1){
                    optionText += " (total R$" + (b[x].installmentAmount*b[x].quantity).toFixed(2).toString().replace('.', ',') + ")";
                }
                optionVal = b[x].quantity + "|" + b[x].installmentAmount;
                isSelected = (Number(value) == x)?" selected=\"selected\"":""
                parcelsDrop.append('<option value="'+optionVal+'"'+isSelected+'>'+optionText+'</option>');
            }
            parcelsDrop.prop('selectedIndex', value);
            let cardOld = 'second';
            if (cardLabel != cardOld) {
                self.setTwoInstallments(cardOld);
            }
        },
        error: function(response) {
            console.error('Error getting parcels:');
            console.error(response);
            let cardOld = 'second';
            if (cardLabel != cardOld) {
                self.setTwoInstallments(cardOld);
            }
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
            if(window.rmconfig.debug){
                console.debug('Installments Data updated successfully.');
                console.debug(installmentsData);
            }
        },
        error: function(response){
            if(window.rmconfig.debug){
                console.error('Failed to update Installments Data.');
                console.error(response);
            }
            return false;
        }
    });
}

RMPagSeguro.prototype.updateTwoInstallments = function(cardLabel){
    var url = this.storeUrl + 'pseguro/ajax/updateInstallments';    
    ccInstallment = jQuery('select[name="payment[ps_'+ cardLabel +'_cc_installments]"] option:selected').val();
    var arr = ccInstallment.split("|");    
    if (cardLabel == 'first') {
        this.firstInstallmentsQty = arr[0];
    } else {
        this.secondInstallmentsQty = arr[0];
    }
    
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
            if (response.error == true && window.rmconfig.debug) {
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

                if(window.rmconfig.debug){
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
