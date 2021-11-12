define([
    'mage/utils/wrapper'
], function (wrapper) {
    'use strict';

    let checkoutConfig = window.checkoutConfig;

    return function (target) {
        if (!checkoutConfig || !checkoutConfig.isFirecheckout) {
            return target;
        }

        target.setPaymentMethods = wrapper.wrap(
            target.setPaymentMethods,
            function (originalAction, methods) {
                if (this.doNotUpdate) {
                    // Do not update payments after place order was pressed
                    //
                    // This method is called after shipping information save:
                    // @see Checkout/view/frontend/web/js/model/shipping-save-processor/default::done
                    return;
                }

                originalAction(methods);
            }
        );

        return target;
    };
});
