define(['jquery'], function($) {
    'use strict';

    return function(targetWidget) {
        $.validator.addMethod(
            'validate-pagseguro-public-key',
            function(value, element) {
                return value.length === 35;
            },
            $.mage.__('Check your public key. It has 35 characters.')
        );
        return targetWidget;
    };
});
