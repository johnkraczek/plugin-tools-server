<?php

namespace PluginToolsServer\Providers\Rest\Routes;

use PluginToolsServer\Providers\Provider;
use PluginToolsServer\Services\BitbucketManager;
use PluginToolsServer\Providers\Rest\Permission\RestPermission;


class PluginDataRestAPI implements Provider
{
    protected $bitbucketManager;

    public function register()
    {
        add_action('rest_api_init', function () {
            register_rest_route('pt-server/v1', '/plugins', [
                'methods' => 'GET',
                'callback' => array( $this, 'fetchAllPluginsMeta' ),
                'permission_callback' => array( new RestPermission, 'getPermissionCallback' )
            ]);

            register_rest_route('pt-server/v1', '/refresh', [
                'methods' => 'GET',
                'callback' => array( $this, 'refreshPluginData' ),
                'permission_callback' => array( new RestPermission, 'getPermissionCallback' )
            ]);
        });
    }

    public function fetchAllPluginsMeta(\WP_REST_Request $request)
    {
        // Get the plugin data from the database
        $pluginData = get_option('pts_plugin_list_meta', []);

        // Prepare an empty array to store the formatted plugin data
        $formattedPluginData = [];
    
        // Loop through each plugin's data
        foreach ($pluginData as $slug => $plugin) {
            // Prepare the data in the format required by the frontend
            $formattedPluginData[] = [
                'name' => $plugin['pts_meta']['name'],
                'currentVersion' => $plugin['pts_meta']['currentVersion'],
                'lastPushed' => $plugin['pts_meta']['lastPushed'],
                'slug' => $slug
            ];
        }
    
        // Return the data as a JSON-encoded string
        return new \WP_REST_Response($formattedPluginData, 200);
    }


    public function refreshPluginData(\WP_REST_Request $request)
    {
        $bitbucket = new BitbucketManager(true);

        if (!$bitbucket->initalized){
            return new \WP_Error(
                'Settings Error',
                esc_html__('Bitbucket settings not configured.'),
                array('status' => 412)
            );
        }
        $packages = $bitbucket->cloneOrFetchRepositories();
        $bitbucket->generateComposerPackages($packages);

        return $this->fetchAllPluginsMeta($request);
    }
}
