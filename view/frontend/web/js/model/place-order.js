/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'jquery', 'jquery/ui',
        'Magento_Checkout/js/checkout-data'
    ],
    function (storage, errorProcessor, fullScreenLoader,$,checkoutsession) {
        'use strict';

        return function (serviceUrl, payload, messageContainer) {
            fullScreenLoader.startLoader();
          
            return storage.post(
                serviceUrl, JSON.stringify(payload)
            ).fail(
                function (response) { 
                  
                   if(jQuery(response=="")){
                   //alert(checkoutsession);
                   console.log(response.responseText);
                      alert(JSON.stringify(response.responseText));
                     // if (!$.sessionStorage.isSet('mage-cache-sessid')) {
                     //        $.sessionStorage.set('mage-cache-sessid', true);
                     //        storage.removeAll();
                     //    }
                    alert('Payment Capture error');
                    fullScreenLoader.stopLoader();
                      //jQuery('.payment-methods .step-title').after('<div class="message message-error error"><div data-ui-id="checkout-cart-validationmessages-message-error" data-bind="text: $data">Payment Capture error</div></div>');
                    }
                   else{
                     // alert('Payment Capture error');
                      alert(messageContainer.errorMessages._latestValue);
                   // jQuery('.payment-methods .step-title').after('<div class="message message-error error"><div data-ui-id="checkout-cart-validationmessages-message-error" data-bind="text: $data">'+messageContainer.errorMessages._latestValue+'</div></div>');   
                   }

                    setTimeout(function() {
                    $(".message-error.error").hide('blind', {}, 500)
                   }, 3000);
                    errorProcessor.process(response, messageContainer);
                    fullScreenLoader.stopLoader();
                }
            );
        };
    }
);
