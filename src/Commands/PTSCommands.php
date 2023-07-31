<?php

namespace PluginToolsServer\Commands;

use PluginToolsServer\Services\BitbucketManager;
use PluginToolsServer\Services\Crypto;
use PluginToolsServer\Services\RimRaf;
use PluginToolsServer\Providers\Database\LicenseTable;

class PTSCommands extends \WP_CLI_Command
{
    public function __construct()
    {
        parent::__construct();
        $this->bitbucketManager = new BitbucketManager();
    }

    public function fetchAll()
    {
        $this->bitbucketManager->cloneOrFetchRepositories();
    }

    public function getOptions()
    {
        echo "Bitbucket_User". $this->bitbucketManager->getUser() ."\n";
        echo "Bitbucket_Workspace". $this->bitbucketManager->getWorkspace() ."\n";
        //echo "Bitbucket_AppPass: ". $this->bitbucketManager->getPassword() . "\n";
    }

    public function removeRepos(){
        \WP_CLI::confirm( "Are you sure you want to remove the repos?" );
        RimRaf::rrmdir($this->bitbucketManager->getTargetDir());
    }

    public function generateComposer(){
        $packages = $this->bitbucketManager->cloneOrFetchRepositories();
        $this->bitbucketManager->generateComposerPackages($packages);
    }

    public function makeKey( ){
        echo "make key \n";
        $license_key = "pk_". Crypto::generate_license_key();
        echo $license_key . "\n";
        $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year'));
        LicenseTable::add_license_key($license_key, $expiry_date);
    }
}
