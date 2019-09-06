require(['jquery', 'uiRegistry'], function ($, registry) {
    $(document).ready(function () {

        var lgwContainer = $('.lengow-connector');

        lgwContainer.on('click', '.lengow_import_orders-js', function () {
            var href = $(this).attr('data-href');
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
                    reloadInformations(data.informations, true);
                    // reload the grid
                    var grid = registry.get('lengow_order_listing.lengow_order_listing').source;
                    if (grid && typeof grid === 'object') {
                        var params = [];
                        grid.set('params.t ', Date.now());
                    }
                }
            }).fail( function () {
                $("#lengow_wrapper_timeout").show(0.25);
            });
        });

        lgwContainer.on('click', '.lgw_order_action_grid-js', function () {
            var href = $(this).attr('data-href'),
                lgwAction = $(this).attr('data-lgwAction'),
                orderLengowId = $(this).attr('data-lgwOrderId');
            $.ajax({
                url: href,
                method: 'POST',
                data: {
                    action: lgwAction,
                    order_lengow_id: orderLengowId,
                    form_key: FORM_KEY
                },
                showLoader: true,
                dataType: 'json',
                success: function (data) {
                    reloadInformations(data.informations, false);
                    // reload the grid
                    var grid = registry.get('lengow_order_listing.lengow_order_listing').source;
                    if (grid && typeof grid === 'object') {
                        var params = [];
                        grid.set('params.t ', Date.now());
                    }
                }
            });
        });
    });

    function reloadInformations(informations, showMessages) {
        var lengowWrapperMessage = $('#lengow_wrapper_messages');
        $("#lengow_order_with_error").html(informations.order_with_error);
        $("#lengow_order_to_be_sent").html(informations.order_to_be_sent);
        $("#lengow_last_importation").html(informations.last_importation);
        var all_messages = '';
        if (showMessages) {
            $.each(informations.messages, function (index, message) {
                all_messages += message + '<br/>';
            });
            lengowWrapperMessage.html(all_messages);
            lengowWrapperMessage.show(0.25);
        } else {
            lengowWrapperMessage.html(all_messages);
            lengowWrapperMessage.hide(0.25);
        }
    }
});