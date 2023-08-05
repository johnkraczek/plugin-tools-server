<?php

namespace PluginToolsServer\Providers\Rest\Permission;

class RestPermission {

    public function getPermissionCallback()
    {
        if (!current_user_can('manage_options')) {
            return new \WP_Error(
                'rest_forbidden',
                esc_html__('You do not have permissions to access this endpoint.'),
                array('status' => 401)
            );
        }
        return true;
    }
    
}