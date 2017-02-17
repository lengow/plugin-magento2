(function( $ ) {
    $(document).ready(function () {

        changeGlobalSetting();
        changeStoreSetting();
        changeActionSetting();
        changeCronSetting();
        changeChecksumSetting();

        /* SWITCH TOGGLE */
        $('.lengow-connector').on('change', '.lgw-switch', function() {
            var check = $(this);
            check.toggleClass('checked');
            switch(check.attr('id')) {
                case 'lengow_see_global':
                   changeGlobalSetting();
                   break; 
                case 'lengow_see_store':
                    changeStoreSetting();
                    break;
                case 'lengow_see_action':
                    changeActionSetting();
                    break;
                case 'lengow_see_cron':
                    changeCronSetting();
                    break;
                case 'lengow_see_checksum':
                    changeChecksumSetting();
                    break;
            }
        });
        
        function changeGlobalSetting() {
            if ($('#lengow_see_global').hasClass('checked')) {
                $('#global-information').show();
            } else {
                console.log('hide');
                $('#global-information').hide();
            }
        }

        function changeStoreSetting() {
            if ($('#lengow_see_store').hasClass('checked')) {
                $('#store-information').show();
            } else {
                console.log('hide');
                $('#store-information').hide();
            }
        }

        function changeActionSetting() {
            if ($('#lengow_see_action').hasClass('checked')) {
                $('#lengow_action_grid').show();
            } else {
                console.log('hide');
                $('#lengow_action_grid').hide();
            }
        }

        function changeCronSetting() {
            if ($('#lengow_see_cron').hasClass('checked')) {
                $('#cron-information').show();
            } else {
                console.log('hide');
                $('#cron-information').hide();
            }
        }

        function changeChecksumSetting() {
            if ($('#lengow_see_checksum').hasClass('checked')) {
                $('#checksum-information').show();
            } else {
                console.log('hide');
                $('#checksum-information').hide();
            }
        }

    });
})(lengow_jquery);
