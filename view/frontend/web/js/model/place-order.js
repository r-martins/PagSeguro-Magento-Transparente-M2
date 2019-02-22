define(
    [
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'jquery', 'jquery/ui',
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

                   $(document).scrollTop(0);
                   fullScreenLoader.stopLoader();


                }
            );
        };
    }
);