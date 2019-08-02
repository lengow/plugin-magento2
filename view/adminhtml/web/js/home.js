require(['jquery'], function( $ ) {
    $(document).ready(function () {
        var syncLink = $('#lengow_sync_link').val();
        var isoCode = $('#lengow_lang_iso').val();

        $('#lengow-container').hide();
        $('<iframe id="lengow-iframe">', {
            id:  'lengow-iframe',
            frameborder: 0,
            scrolling: 'yes'
        }).appendTo('#lengow-iframe-container');

        var syncIframe = document.getElementById('lengow-iframe');
        var href = $('#lengow-iframe-container').attr('data-href');
        if (syncIframe) {
            syncIframe.onload = function () {
                $.ajax({
                    url: href,
                    method: 'POST',
                    data: {
                        action: 'get_sync_data',
                        form_key: FORM_KEY
                    },
                    dataType: 'json',
                    success: function (data) {
                        var targetFrame = document.getElementById("lengow-iframe").contentWindow;
                        targetFrame.postMessage(data, '*');
                    }
                });
            };
            if (syncLink) {
                // syncIframe.src = '//cms.lengow.io/sync/';
                // syncIframe.src = '//cms.lengow.net/sync/';
                syncIframe.src = '//cms.lengow.rec/sync/';
                // syncIframe.src = '//cms.lengow.dev/sync/';
            } else {
                // syncIframe.src = '//cms.lengow.io/';
                // syncIframe.src = '//cms.lengow.net/';
                syncIframe.src = '//cms.lengow.rec/';
                // syncIframe.src = '//cms.lengow.dev/';
            }
            syncIframe.src = syncIframe.src+'?lang='+isoCode+'&clientType=magento';
            $('#lengow-iframe').show();
        }

        window.addEventListener("message", receiveMessage, false);

        function receiveMessage(event) {
            switch (event.data.function) {
                case 'sync':
                    // store lengow information into Magento :
                    // account_id
                    // access_token
                    // secret_token
                    $.ajax({
                        method: 'POST',
                        data: {action: 'sync', data: event.data.parameters, form_key: FORM_KEY},
                        dataType: 'script'
                    });
                    break;
                case 'sync_and_reload':
                    // store lengow information into Magento and reload it
                    // account_id
                    // access_token
                    // secret_token
                    $.ajax({
                        method: 'POST',
                        data: {action: 'sync', data: event.data.parameters, form_key: FORM_KEY},
                        dataType: 'script',
                        success: function() {
                            location.reload();
                        }
                    });
                    break;
                case 'reload':
                    // reload the parent page (after sync is ok)
                    location.reload();
                    break;
                case 'cancel':
                    // reload Dashboard page
                    var hrefCancel = location.href.replace('?isSync=true', '');
                    window.location.replace(hrefCancel);
                    break;
            }
        }
    });
});