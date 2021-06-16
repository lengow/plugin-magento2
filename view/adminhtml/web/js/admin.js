require(['jquery'], function( $ ) {
    $(document).ready(function () {
        // open upgrade plugin modal
        $('.js-upgrade-plugin-modal-open').on('click', function() {
            var modalBox = $('#upgrade-plugin');
            modalBox.show();
            setTimeout(function() {
                modalBox.addClass('is-open');
            }, 250);
        });

        // close upgrade plugin modal
        function closeUpgradePluginModal() {
            var modalBox = $('#upgrade-plugin.is-open');
            modalBox.removeClass('is-open');
            setTimeout(function() {
                modalBox.hide();
            }, 250);
        }
        $('.js-upgrade-plugin-modal-close').on('click', closeUpgradePluginModal);

        // when the user clicks anywhere outside of the modal, close it
        $(document).on('click', function(event) {
            if (!$(event.target).closest('.lgw-modalbox-content').length) {
                closeUpgradePluginModal();
            }
        });

        // hide the display of the modal for 7 days
        $('.js-upgrade-plugin-modal-remind-me').on('click', function() {
            var href = $(this).attr('data-href');
            var data = {
                action: 'remind_me_later',
                form_key: window.FORM_KEY,
            };
            $.getJSON(href, data, function() {
                var modalBox = $('#upgrade-plugin.is-open');
                modalBox.removeClass('is-open');
                setTimeout(function() {
                    $('.js-upgrade-plugin-modal-remind-me').hide();
                    modalBox.hide();
                }, 250);
            });
        });
    });
});
