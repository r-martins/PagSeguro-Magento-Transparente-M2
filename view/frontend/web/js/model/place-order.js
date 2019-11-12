define(
    [
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'jquery',
        'Magento_Checkout/js/checkout-data',
        'Magento_Ui/js/model/messageList'
    ],
    function (storage, errorProcessor, fullScreenLoader,$,checkoutSession, globalMessageList) {
        'use strict';

        return function (serviceUrl, payload, messageContainer) {
            fullScreenLoader.startLoader();

            messageContainer = messageContainer || globalMessageList;
          
            return storage.post(
                serviceUrl, JSON.stringify(payload)
            ).fail(
                function (response) {

                   if(!response.responseJSON){

                       messageContainer.addErrorMessage({'message': 'Payment Capture error'});

                   }else{

                       errorProcessor.process(response, messageContainer);
                   }

                   if(payload.paymentMethod.method == 'rm_pagseguro_cc'){
                       var responseMessage = JSON.parse(response.responseText);
                       var errorMessage = '<div role="alert" class="message message-error error"><div data-ui-id="checkout-cart-validationmessages-message-error" data-bind="text: $data">'+responseMessage.message+'</div></div>';
                       $("#pagseguro_cc_method div.messages").html(errorMessage);
                   }

                   $(document).scrollTop(0);
                   fullScreenLoader.stopLoader();


                }
            );
        };
    }
);