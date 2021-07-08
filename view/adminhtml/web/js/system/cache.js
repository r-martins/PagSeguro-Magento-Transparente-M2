define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'jquery-ui-modules/widget'
], function($, confirm) {

    function askForActionConfirmation(element) {
        var msg = $(element).attr("data-confirm-msg");
        var url = $(element).attr("data-flush-cache-url");

        if(confirm({
            "title": false,
            "content": msg,
            "actions": {
                confirm: function() { location.href = url; }
            }
        }));
    }

    return function(config, element) {
        $(element).on("click", function() { askForActionConfirmation(element); });
    };
});