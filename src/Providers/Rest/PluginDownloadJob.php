<?php

namespace PluginToolsServer\Providers\Rest;

use PluginToolsServer\Services\BitbucketManager;

class PluginDownloadJob extends \WP_Async_Request {
    
        protected $action = 'plugin_download_job';
    
        protected $data = array();
    
        protected $bitbucketManager;
    
        public function __construct() {
            parent::__construct();
            $this->bitbucketManager = new BitbucketManager();
        }
    
        protected function handle() {

            $url= $_POST['plugin_url'];
            $slug= $_POST['plugin_slug'];
            $name= $_POST['plugin_name'];
            $version= $_POST['plugin_version'];

            $this->bitbucketManager->handlePluginUpdate($url, $slug, $name, $version);
        }
}