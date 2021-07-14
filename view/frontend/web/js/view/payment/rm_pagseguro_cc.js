define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'rm_pagseguro_cc',
                component: 'RicardoMartins_PagSeguro/js/view/payment/method-renderer/rm_pagseguro_ccmethod'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
