<?php

namespace PluginToolsServer\Providers\Rest;

use \PluginToolsServer\Providers\Provider;
use \PluginToolsServer\Providers\Rest\SettingsRestAPIPRovider;
use \PluginToolsServer\Providers\Rest\PluginUpdateAPI;
use \PluginToolsServer\Providers\Rest\PluginDataRestApi;

class PTSRestProvider implements Provider
{
    public function register()
    {
        $Providers = [
            SettingsRestAPIPRovider::class,
            PluginUpdateAPI::class,
            PluginDataRestApi::class
        ];

        foreach ($Providers as $provider) {
            (new $provider)->register();
        }
    }

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
