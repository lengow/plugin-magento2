document.observe("dom:loaded", function() {

    $('lengow_import_orders').observe('click', function() {
        var url = $(this).readAttribute('data-href');
        new Ajax.Request(url,{
            method: 'post',
            parameters: {action: 'import_all', form_key: FORM_KEY},
            onSuccess: function(response) {
                lengowOrderGridJsObject.reload();
                reloadInformations(response);
                var responseJson = response.responseText.evalJSON();
                var all_messages = '';
                responseJson.messages.each(function(message) {
                    all_messages += message+'<br/>';
                });
                $('lengow_wrapper_messages').update(all_messages);
                $('lengow_wrapper_messages').appear({ duration: 0.250 });

            }
        });
    });

    if ($('lengow_migrate_fade') != null) {
        $('lengow_migrate_fade').observe('click', function() {
            var url = $(this).readAttribute('data-href');
            new Ajax.Request(url,{
                method: 'post',
                parameters: {action: 'migrate_button_fade', form_key: FORM_KEY},
                onSuccess: function(response) {
                    $('lengow_migrate_order').fade({ duration: 0.250 });
                }
            });
        });
    };

});

function makeLengowActions(url, action, orderLengowId) {
    new Ajax.Request(url,{
        method: 'post',
        parameters: {action: action, order_lengow_id: orderLengowId, form_key: FORM_KEY},
        onSuccess: function(response) {
            lengowOrderGridJsObject.reload();
            reloadInformations(response);
        }
    });
}

function reloadGrid(grid, current, transport) {
    grid.reload();
    var url = $('lengow_controller_url').readAttribute('data-href')
    new Ajax.Request(url,{
        method: 'post',
        parameters: {action: 'load_information', form_key: FORM_KEY},
        onSuccess: function(response) {
            reloadInformations(response);
        }
    });
}

function reloadInformations(response) {
    var responseJson = response.responseText.evalJSON();
    $('lengow_last_importation').update(responseJson.last_importation);
    $('lengow_order_with_error').update(responseJson.order_with_error);
    $('lengow_order_to_be_sent').update(responseJson.order_to_be_sent); 
}
