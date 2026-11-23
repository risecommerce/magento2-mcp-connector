/**
 * @author    Risecommerce <info@risecommerce.com>
 * @copyright Copyright (c) Risecommerce, Ltd. (https://risecommerce.com)
 * @license   MIT
 */
define(['jquery'], function ($) {
    'use strict';

    return function () {
        var $mode = $('#risecommerce_mcp_oauth_client_auth_mode');
        if ($mode.length === 0 || $mode.data('mcpAuthModeInit')) {
            return;
        }
        $mode.data('mcpAuthModeInit', true);

        var fieldSelector = 'tr, .field, .admin__field';
        var $service = $('#risecommerce_mcp_oauth_client_service_admin_user_id').closest(fieldSelector);
        var $allowedUsers = $('#risecommerce_mcp_oauth_client_allowed_admin_user_ids').closest(fieldSelector);
        var $allowedRoles = $('#risecommerce_mcp_oauth_client_allowed_admin_role_ids').closest(fieldSelector);

        function sync() {
            if ($mode.val() === 'shared') {
                $service.show();
                $allowedUsers.hide();
                $allowedRoles.hide();
            } else {
                $service.hide();
                $allowedUsers.show();
                $allowedRoles.show();
            }
        }

        $mode.on('change', sync);
        sync();
    };
});
