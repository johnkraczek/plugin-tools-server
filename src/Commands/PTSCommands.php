<?php

namespace PluginToolsServer\Commands;

use PluginToolsServer\Services\BitbucketManager;
use PluginToolsServer\Services\Crypto;
use PluginToolsServer\Services\RimRaf;

class PTSCommands extends \WP_CLI_Command
{
    public function __construct()
    {
        parent::__construct();
        $wpUploadsDir = wp_upload_dir()['basedir'];
        $this->targetDir = $wpUploadsDir . '/plugin-tools-server';
        $this->settings = get_option(YDTB_PTOOLS_OPTIONS_SLUG);

        $this->bitbucketManager = new BitbucketManager(
            $this->settings['bitbucket_username'],
            Crypto::Decrypt($this->settings['bitbucket_password']),
            $this->settings['bitbucket_workspace'],
            $this->targetDir
        );
    }

    public function fetchAll()
    {
        $this->bitbucketManager->cloneOrFetchRepositories();
    }

    public function getOptions()
    {

        echo "Bitbucket_User". $this->settings['bitbucket_username'] ."\n";
        echo "Bitbucket_Workspace". $this->settings['bitbucket_workspace'] ."\n";

        $password_decrypted = Crypto::Decrypt($this->settings['bitbucket_password']);
        echo "Bitbucket_AppPass: ". $password_decrypted . "\n";
    }

    public function removeRepos(){
        \WP_CLI::confirm( "Are you sure you want to remove the repos?" );
        RimRaf::rrmdir($this->targetDir);
    }

    public function generateComposer(){

        $packages = $this->bitbucketManager->cloneOrFetchRepositories();
        $this->bitbucketManager->generateComposerPackages($packages);
        

    }
}
