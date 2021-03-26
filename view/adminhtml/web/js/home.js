require(['jquery', "mage/url", "select2"], function ($) {

    $(document).ready(function () {

        var connectionContainer = $('#lgw-connection-content');

        connectionContainer.on('click', '.js-go-to-credentials', function () {
            var ajaxUrl = $('.js-go-to-credentials').data('href');
            var data = {form_key: window.FORM_KEY};
            $.post(ajaxUrl, data, function(response) {
                connectionContainer.html(response.output);
            });
        });

        connectionContainer.on('change', '.js-credentials-input', function() {
            var accessToken = $('input[name=lgwAccessToken]').val();
            var secret = $('input[name=lgwSecret]').val();
            if (accessToken !== '' && secret !== '') {
                $('.js-connect-cms')
                    .removeClass('lgw-btn-disabled')
                    .addClass('lgw-btn-green');
            } else {
                $('.js-connect-cms')
                    .addClass('lgw-btn-disabled')
                    .removeClass('lgw-btn-green');
            }
        });

        // check api credentials
        connectionContainer.on('click', '.js-connect-cms', function() {
            var accessToken = $('input[name=lgwAccessToken]');
            var secret = $('input[name=lgwSecret]');
            $(this).addClass('loading');
            accessToken.prop('disabled', true);
            secret.prop('disabled', true);
            var ajaxUrl = $(this).data('href');
            var data = {
                form_key: window.FORM_KEY,
                access_token: accessToken.val(),
                secret: secret.val(),
            };
            $.post(ajaxUrl, data, function(response) {
                connectionContainer.html(response.output);
            });
        });

        connectionContainer.on('click', '.js-go-to-catalog', function() {
            var retry = $(this).attr('data-retry') !== 'false';
            var ajaxUrl =  $(this).data('href');
            var data = {
                retry: retry,
                form_key: window.FORM_KEY,
            };
            $.post(ajaxUrl, data, function(response) {
                connectionContainer.html(response.output);
                $('#lgw-connection-container select').select2();
            });
        });

        // disable catalog option in select
        connectionContainer.on('change', '.js-catalog-linked', function() {
            var currentShopId = $(this).attr('name');
            // get all catalogs selected by shop
            var catalogSelected = [];
            var shopSelect = $('.js-catalog-linked');
            shopSelect.each(function() {
                var shopId = $(this).attr('name');
                var catalogIds = $(this).val();
                if (catalogIds !== null) {
                    $.each(catalogIds, function (key, value) {
                        catalogSelected.push({
                            shopId: shopId,
                            catalogId: value
                        })
                    });
                }
            });
            // disable catalog option for other shop
            shopSelect.each(function() {
                var shopId = $(this).attr('name');
                if (shopId !== currentShopId) {
                    var catalogLinked = [];
                    $.each(catalogSelected, function(key, value) {
                        if (value.shopId !== shopId) {
                            catalogLinked.push(value.catalogId);
                        }
                    });

                    var options = $(this).find('option');
                    options.each(function() {
                        if (catalogLinked.includes($(this).val())) {
                            $(this).attr('disabled', true);
                        } else {
                            $(this).attr('disabled', false);
                        }
                    });
                    $(this).select2();
                }
            });
        });

        // link catalog ids
        connectionContainer.on('click', '.js-link-catalog', function() {
            var catalogSelected = [];
            var shopSelect = $('.js-catalog-linked');
            shopSelect.each(function() {
                if ($(this).val() !== null) {
                    var catalogIds = $(this).val();
                    var catalogIdsCleaned = [];
                    $.each(catalogIds, function(key, value) {
                        catalogIdsCleaned.push(parseInt(value, 10))
                    })
                    catalogSelected.push({
                        shopId: parseInt($(this).attr('name'), 10),
                        catalogId: catalogIdsCleaned,
                    });
                }
            });
            $('.js-link-catalog').addClass('loading');
            shopSelect.prop('disabled', true );
            var ajaxUrl = $(this).data('href');
            var data = {
                catalogSelected: catalogSelected,
                form_key: FORM_KEY,
            };
            $.post(ajaxUrl, data, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    connectionContainer.html(response.output);
                }
            });
        });
    });
});
