<?php

namespace PluginToolsServer\Providers\Rest;

use PluginToolsServer\Providers\Provider;
use PluginToolsServer\Providers\Rest\PTSRestProvider;
use PluginToolsServer\Services\BitbucketManager;

class PluginUpdateAPI implements Provider
{

    protected $bitbucketManager;

    public function register()
    {

        add_action('rest_api_init', function () {
            register_rest_route('pt-server/v1', '/plugins', [
                'methods' => 'GET',
                'callback' => array( $this, 'fetchAllPlugins' ),
                'permission_callback' => array( PTSRestProvider::class, 'getPermissionCallback' )
            ]);
        });
           


    }

    public function fetchAllPlugins(\WP_REST_Request $request){
        // do the work to fetch the plugin data. 
        $this->bitbucketManager = new BitbucketManager();
        $this->bitbucketManager->fetchAllPlugins();

        $pluginData = $this->bitbucketManager->getPluginDataForFrontEnd();

        return new \WP_REST_Response($pluginData, 200);
    }
}
