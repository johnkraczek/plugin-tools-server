<?php

namespace PluginToolsServer\Providers\Rest;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PluginToolsServer\Providers\Database\LicenseTable;
use PluginToolsServer\Providers\Provider;
use PluginToolsServer\Providers\Rest\PluginDownloadJob;
use PluginToolsServer\Providers\Rest\PTSRestProvider;

class PluginUpdateAPI implements Provider
{
    private PluginDownloadJob $downloadJob;

    public function register()
    {
        $this->downloadJob = new PluginDownloadJob();

        add_action('rest_api_init', function () {
            register_rest_route('pt-server/v1', '/pk', [
                'methods' => 'POST',
                'callback' => array( $this, 'handleProductKey' )
            ]);
        });
           
        add_action('rest_api_init', function () {
            register_rest_route('pt-server/v1', '/update', [
                'methods' => 'POST',
                'callback' => array( $this, 'updateSinglePlugin' ),
                'permission_callback' => array( PTSRestProvider::class, 'getPermissionCallback' )
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

        //@todo: allow the permissions to be set in the database
        // $permission = $result['permission'];
        $permission = 'rw';

        $issuedAt   = time();
        $expire     = $issuedAt + 3600;

        $payload = [
            "iat" => $issuedAt,                    // Issued at: time when the token was generated
            "iss" => get_site_url(),               // Issuer
            "exp" => $expire,                      // Expiration time
            "permission" => $permission
        ];

        $jwt = JWT::encode($payload, $secret_key, 'HS256');

        return ['jwt' => $jwt];
    }

    public function updateSinglePlugin(\WP_REST_Request $request)
    {
        $token = $request->get_header('Authorization');
        $secret_key = get_option('PTS_JWT_SECRET_KEY');
        
        try {
            $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
            $permission = $decoded->permission;
            if ($permission !== 'rw') {
                return new \WP_Error('rest_forbidden', 'You do not have permission to do that.', array('status' => 403));
            }
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return new \WP_Error('rest_forbidden', 'Tampered Token', array('status' => 403));
        } catch (\Firebase\JWT\ExpiredException $e) {
            return new \WP_Error('rest_forbidden', 'Your token has expired', array('status' => 403));
        } catch (\Exception $e) {
            return new \WP_Error('rest_forbidden', 'You are not allowed to do that.', array('status' => 403));
        }

        // we have validated that there are no errors with the token, now we can get the data
        $data = $request->get_json_params();
 
        // Validate and sanitize the URL
        if (!filter_var($data['pluginURL'], FILTER_VALIDATE_URL)) {
            return new \WP_Error('validation_error', 'Invalid URL', array('status' => 400));
        } else {
            $pluginURL = esc_url_raw($data['pluginURL']);
        }

        // Validate and sanitize the plugin slug (vendor/plugin)
        if ($data['pluginSlug'] === null) {
            return new \WP_Error('validation_error', 'Slug is required', array('status' => 400));
        }

        if (!preg_match('/^[a-z0-9]+(\/[a-z0-9-]+)?$/i', $data['pluginSlug'])) {
            return new \WP_Error('validation_error', 'Invalid plugin slug. Expected format: vendor/plugin', array('status' => 400));
        } 
            $pluginSlug = $data['pluginSlug'];
        
        // Validate and sanitize the plugin name
        if (strlen($data['pluginName']) > 30) {
            return new \WP_Error('validation_error', 'Plugin name should be less than 30 characters', array('status' => 400));
        } 
            $pluginName = sanitize_text_field($data['pluginName']);


        // Validate the version (no sanitization, as version strings may legitimately contain non-alphanumeric characters)
        if (!preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(-(0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(\.(0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*)?(\+[0-9a-zA-Z-]+(\.[0-9a-zA-Z-]+)*)?$/', $data['pluginVersion'])) {
            return new \WP_Error('validation_error', 'Invalid version. Expected format: X.Y.Z', array('status' => 400));
        }

        $pluginVersion = $data['pluginVersion'];

        $this->downloadJob->data([
         'plugin_url' => $pluginURL,
         'plugin_slug' => $pluginSlug,
         'plugin_name' => $pluginName,
         'plugin_version' => $pluginVersion
        ]);
       
        $this->downloadJob->dispatch();  
        return new \WP_REST_Response(array(
            'result' => 'success',
            'message' => 'Plugin update job dispatched.'
        ), 200);
    }
}
