/**
 * PagSeguro Transparente para Magento
 * @author Ricardo Martins <pagseguro-transparente@ricardomartins.net.br>
 * @link http://bit.ly/pagseguromagento
 * @version 2.1.0
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
        //                                    Please enter credit card data to calculate
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
        var ccExpMoElm = jQuery('select[name="payment[ps_cc_exp_month]"]');
        var ccExpYrElm = jQuery('select[name="payment[ps_cc_exp_year]"]');
        var ccCvvElm = jQuery('input[name="payment[ps_cc_cid]"]');
        var cpf = jQuery('input[name="payment[ps_cc_cpf]"]');
        var boletocpf = jQuery('input[name="payment[pagseguro_boleto_cpf]"]');
        

        jQuery(ccNumElm).keyup(function( event ) {
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
        
        jQuery( "#pagseguro_cc_method .actions-toolbar .checkout" ).on("click", function() { 
			if(cpf.val()!=''){
				obj.updateCreditCardToken();
			}	
		});
		
		jQuery( "#pagseguro_boleto_method .actions-toolbar .checkout" ).on("click", function() { 
			if(boletocpf.val()!=''){
				obj.updatePaymentHashes();
			}	
		});
        
        jQuery("#rm_pagseguro_cc_cc_installments").change(function( event ) {
            obj.updateInstallments();
        });
        
    }catch(e){
        console.error('Unable to add greeting to cards. ' + e.message);
    }

}

RMPagSeguro.prototype.updateCreditCardToken = function(){
     var ccNum = jQuery('input[name="payment[ps_cc_number]"]').val().replace(/^\s+|\s+$/g,'');
    var ccExpMo = jQuery('select[name="payment[ps_cc_exp_month]"]').val().replace(/^\s+|\s+$/g,'');
    var ccExpYr = jQuery('select[name="payment[ps_cc_exp_year]"]').val().replace(/^\s+|\s+$/g,'');
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
                jQuery('#card-brand').html('<img src="https://stc.pagseguro.uol.com.br/public/img/payment-methods-flags/' +flag + '/' + this.brand.name + '.png" alt="' + this.brand.name + '" title="' + this.brand.name + '"/>');
            }
            return;
        }
        this.cardBin = ccNum.substring(0, 6); 
        PagSeguroDirectPayment.getBrand({
            cardBin: currentBin,
            success: function(psresponse){
                self.brand = psresponse.brand;
                jQuery('#card-brand').html(psresponse.brand.name);
                if(flag != ''){
                    jQuery('#card-brand').html('<img src="https://stc.pagseguro.uol.com.br/public/img/payment-methods-flags/' +flag + '/' + psresponse.brand.name + '.png" alt="' + psresponse.brand.name + '" title="' + psresponse.brand.name + '"/>');
                }
                jQuery('#card-brand').addClass(psresponse.brand.name.replace(/[^a-zA-Z]*!/g,''));
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
    var self = this;
    var url = self.storeUrl +'pseguro/ajax/updatePaymentHashes';
    var boletocpf = jQuery('input[name="payment[pagseguro_boleto_cpf]"]').val();
    var cpf = jQuery('input[name="payment[ps_cc_cpf]"]').val();
    
    if(boletocpf!='' && boletocpf != undefined){
		var boletocpf = jQuery('input[name="payment[pagseguro_boleto_cpf]"]').val();
		var paymentHashes = {
			"payment[sender_hash]": this.senderHash,
			"ownerdata[boleto_cpf]": boletocpf,
		};
	}
	
	if(cpf !='' && cpf != undefined){
		var ccOwner = jQuery('input[name="payment[ps_cc_owner]"]').val();
		var ccOwnerBirthDay = jQuery('input[name="payment[ps_cc_owner_birthday_day]"]').val();
		var ccOwnerBirthMonth = jQuery('input[name="payment[ps_cc_owner_birthday_month]"]').val();
		var ccOwnerBirthYear = jQuery('input[name="payment[ps_cc_owner_birthday_year]"]').val();
		var cpf = jQuery('input[name="payment[ps_cc_cpf]"]').val();
		var paymentHashes = {
			"payment[sender_hash]": this.senderHash,
			"payment[credit_card_token]": this.creditCardToken,
			"payment[cc_type]": (this.brand)?this.brand.name:'',
			"payment[is_admin]": this.config.is_admin,
			"ownerdata[credit_card_owner]": ccOwner,
			"ownerdata[credit_card_birthday]":ccOwnerBirthDay,
			"ownerdata[credit_card_birthmonth]":ccOwnerBirthMonth,
			"ownerdata[credit_card_birthyear]":ccOwnerBirthYear,
			"ownerdata[credit_card_cpf]": cpf,
		};	
	}
    
    jQuery.ajax({
        url: url,
        type: 'POST',
        data: paymentHashes,
        success: function(response){
            if(self.config.debug){
                console.debug('Hashes updated successfully.');
                console.debug(paymentHashes);
            }
        },
        error: function(response){
            if(self.config.debug){
                console.error('Failed to update session hashes.');
                console.error(response);
            }
            return false;
        }
    });
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
                parcelsDrop.append('<option value="">Selecione a quantidade de parcelas</option>');
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
                if(b[x].quantity == selectedInstallment){
                    parcelsDrop.append('<option value="'+optionVal+'" selected>'+optionText+'</option>');
                }else{
                    parcelsDrop.append('<option value="'+optionVal+'">'+optionText+'</option>');
                }
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