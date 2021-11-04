var config = {
    map: {
        '*': {
            'Magento_Checkout/js/model/place-order':'RicardoMartins_PagSeguro/js/model/place-order'
        }
    },
    config: {
        // Disables the firecheckout mixin on payment service model of Magento Checkout.
        // Its avoids bugs when coupon is applied / cancenled on checkout page.
        mixins: {
            'Magento_Checkout/js/model/payment-service': {
                'Swissup_Firecheckout/js/mixin/model/payment-service-mixin': false,
                'RicardoMartins_PagSeguro/js/mixin/model/firecheckout-fix': true
            }
        }
    }
};
