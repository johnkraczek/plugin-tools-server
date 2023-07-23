<?php

namespace PluginToolsServer\Providers\Rest;
use PluginToolsServer\Providers\Provider;
use PluginToolsServer\Services\Crypto;

class SettingsRestAPIPRovider implements Provider
{


    public function register()
    {
        add_action('rest_api_init', function () {
            register_rest_route('pt-server/v1', '/settings', [
                'methods' => 'GET',
                'callback' => array( $this, 'get_settings_options' ),
                'permission_callback' => array( $this, 'get_plugin_permission' )
            ]);

            register_rest_route('pt-server/v1', '/settings', [
                'methods' => 'POST',
                'callback' => array( $this, 'post_settings_update' ),
                'permission_callback' => array( $this, 'get_plugin_permission' )
            ]);

            register_rest_route('pt-server/v1', '/settings', [
                'methods' => 'DELETE',
                'callback' => array( $this, 'delete_settings_options' ),
                'permission_callback' => array( $this, 'get_plugin_permission' )
            ]);
        });
    }


    public function get_settings_options(\WP_REST_Request $request)
    {
        $settings = get_option(YDTB_PTOOLS_OPTIONS_SLUG);

        $password_decrypted = Crypto::Decrypt($settings['bitbucket_password']);

        $plugin_tools_options = array(
            'bitbucket_username' => $settings['bitbucket_username'],
            'bitbucket_workspace' => $settings['bitbucket_workspace'],
            'bitbucket_password' => str_repeat("*", strlen($password_decrypted)),
        );

        return new \WP_REST_Response($plugin_tools_options, 200);
    }

    public function post_settings_update(\WP_REST_Request $request)
    {

        // "{"bitbucket_password":"abc123","bitbucket_username":"johnkraczek","bitbucket_workspace":"ydtb-wp-packages"}"
        
        if ($request->is_json_content_type()) {
            $data = $request->get_json_params();

            file_put_contents(YDTB_PTOOLS_SERVER_PATH."/test_log2.txt", json_encode($data), FILE_APPEND);

            $updated_options = array();

            if ( array_key_exists('bitbucket_password', $data)){
                $updated_options['bitbucket_password']= Crypto::Encrypt($data['bitbucket_password']);
            } else {
                $settings = get_option(YDTB_PTOOLS_OPTIONS_SLUG);
                $updated_options['bitbucket_password']= $settings['bitbucket_password'];
            }

            $updated_options['bitbucket_username']= filter_var($data['bitbucket_username'], FILTER_SANITIZE_STRING);
            $updated_options['bitbucket_workspace']= filter_var($data['bitbucket_workspace'], FILTER_SANITIZE_STRING);

            file_put_contents(YDTB_PTOOLS_SERVER_PATH."/test_log.txt", json_encode($updated_options), FILE_APPEND);

            update_option(YDTB_PTOOLS_OPTIONS_SLUG, $updated_options);

            // return our response.
            return new \WP_REST_Response(
                array(
                'message'=>'Settings Stored',
                'options'=>get_option(YDTB_PTOOLS_OPTIONS_SLUG)),
                200
            );
        }
        return new \WP_REST_Response(array('message'=>'Must be a JSON object'), 422);
    }

    public function delete_settings_options(\WP_REST_Request $request)
    {
        $settings = delete_option(YDTB_PTOOLS_OPTIONS_SLUG);
        return new \WP_REST_Response($settings, 200);
    }

    public function get_plugin_permission()
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
