<?php

namespace PluginToolsServer\Commands;

use PluginToolsServer\Services\BitbucketManager;

class PTSCommands extends \WP_CLI_Command
{
    public function __construct()
    {
        parent::__construct();
    }

    public function fetchRepos()
    {
        $wpUploadsDir = wp_upload_dir()['basedir'];
        $targetDir = $wpUploadsDir . '/plugin-tools-server';

        $bitbucketManager = new BitbucketManager('username', 'token/apppass', 'ydtb-wp-packages', $targetDir);
        $bitbucketManager->cloneOrFetchRepositories();
    }

}