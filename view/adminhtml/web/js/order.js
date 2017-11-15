require(['jquery'], function ($) {
    $(document).ready(function () {

        /**
         * Show or not the product grid
         */
        $('.lengow-connector').on('click', '.lengow_import_orders-js', function () {
            var href = $(this).attr('data-href'),
                lengowWrapperMessage = $('#lengow_wrapper_messages');
            $.ajax({
                url: href,
                method: 'POST',
                data: {
                    action: 'import_all',
                    form_key: FORM_KEY
                },
                showLoader: true,
                context: $('.lgw-box'),
                dataType: 'json',
                success: function (data) {
                    $("#lengow_order_with_error").html(data.informations.order_with_error);
                    $("#lengow_order_to_be_sent").html(data.informations.order_to_be_sent);
                    $("#lengow_last_importation").html(data.informations.last_importation);
                    var all_messages = '';
                    $.each(data.informations.messages, function (index, message) {
                        all_messages += message + '<br/>';
                    });
                    lengowWrapperMessage.html(all_messages);
                    lengowWrapperMessage.show(0.25);
                    //reload the grid
                    var registry = require('uiRegistry');
                    registry.get('lengow_order_listing.lengow_order_listing').source.reload();
                }
            });
        });

    });
});