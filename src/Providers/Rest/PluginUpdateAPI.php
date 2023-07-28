<?php

namespace PluginToolsServer\Providers\Rest;

use Firebase\JWT\JWT;
use PluginToolsServer\Providers\Database\LicenseTable;
use PluginToolsServer\Providers\Provider;

class PluginUpdateAPI implements Provider
{
    public function register()
    {
        add_action('rest_api_init', function () {
            register_rest_route('pt-server/v1', '/pk', [
                'methods' => 'POST',
                'callback' => array( $this, 'handleProductKey' )
            ]);
        });
            
        $this->AttemptLimit = 5;
        $this->AttemptWindow = 10*60; // 10 minutes
    }

    public function handleProductKey(\WP_REST_Request $request)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $attempts = get_transient("PTS_SERVER_$ip") ?: 0;

        // Extract the product key from the request
        $product_key = $request->get_header('product-key');

        if (!$product_key) {
            return new \WP_Error('missing_product_key', 'The product key is missing', ['status' => 400]);
        }

        $domain = $_SERVER['HTTP_HOST'];

        // Validate the product key and get the permissions
        $result = LicenseTable::validate_and_update_license_key($product_key, $domain);

        // If the product key is invalid, return an error response
        if ($result['valid'] === false) {

            //failed attempts are tracked.
            set_transient("myplugin_attempts_$ip", $attempts + 1, $this->AttemptWindow);

            if ($attempts + 1 >= $this->AttemptLimit) {
                return new \WP_Error('too_many_attempts', 'Too many attempts, please try again later.', ['status' => 429]);
            }
            return new \WP_Error('invalid_product_key', 'The product key provided is invalid', ['status' => 400]);
        }

        // If the product key is valid, generate a JWT and return it
        $secret_key = get_option('PTS_JWT_SECRET_KEY');
        $permission = $result['permission'];

        $payload = [
            'product_key' => $product_key,
            'permissions' => $permission,
            'issued_at' => time(),
            'issuer' => get_bloginfo('url'),
        ];

        $jwt = JWT::encode($payload, $secret_key, 'HS256');

        return ['jwt' => $jwt];
    }

    public function get_plugin_permission()
    {
        
        //return current_user_can('manage_options');
    }

}
