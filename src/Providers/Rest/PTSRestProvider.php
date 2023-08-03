<?php

namespace PluginToolsServer\Providers\Rest;

use \PluginToolsServer\Providers\Provider;
use \PluginToolsServer\Providers\Rest\SettingsRestAPIPRovider;
use \PluginToolsServer\Providers\Rest\PluginUpdateAPI;


class PTSRestProvider implements Provider {
    

    public function register()
    {

        $Providers = [
            SettingsRestAPIPRovider::class,
            PluginUpdateAPI::class,
        ];

        foreach ($Providers as $provider) {
            (new $provider)->register();
        }
    }

    public static function getPermissionCallback($request)
    {
        // Get the nonce from the request header.
        $nonce = $request->get_header('X-WP-Nonce');
    
        // If the nonce is missing, return an error.
        if (empty($nonce)) {
            return new WP_Error('rest_forbidden', 'Nonce missing', array('status' => 403));
        }
    
        // Verify the nonce. The action is passed as an argument to this function.
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_forbidden', 'Invalid nonce', array('status' => 403));
        }
    
        // Check if the current user has the 'manage_options' capability.
        if (!current_user_can('manage_options')) {
            return new WP_Error('rest_forbidden', 'User does not have the necessary capabilities', array('status' => 403));
        }
    
        return true;
    }
}