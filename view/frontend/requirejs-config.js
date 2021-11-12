var config = {
    map: {
        '*': {
            'Magento_Checkout/js/model/place-order':'RicardoMartins_PagSeguro/js/model/place-order'
        }
    },
    config: {
        // Disables the firecheckout mixin on payment service model of Magento Checkout.
        // This avoids bugs when coupon is applied / cancelled on checkout page.
        // By default, Firecheckout attempts to fill credit card form fields wit previous values
        // but this is implemented in a way that does not trigger necessary events
        mixins: {
            'Magento_Checkout/js/model/payment-service': {
                'Swissup_Firecheckout/js/mixin/model/payment-service-mixin': false,
                'RicardoMartins_PagSeguro/js/mixin/model/payment-service-firecheckout-fix': true
            }
        }
    }
};
