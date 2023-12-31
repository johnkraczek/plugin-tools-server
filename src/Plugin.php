<?php

namespace PluginToolsServer;

use \PluginToolsServer\Providers\Provider;
use \PluginToolsServer\Providers\Views\AdminPageProvider;
use \PluginToolsServer\Providers\Commands\CommandServiceProvider;
use \PluginToolsServer\Providers\Routes\Composer;
use \PluginToolsServer\Providers\Database\LicenseTable;
use \PluginToolsServer\Providers\Rest\RestRouter;

class Plugin implements Provider
{
    public function register()
    {
        $Providers = [
            AdminPageProvider::class,
            Composer::class,
            LicenseTable::class,
            CommandServiceProvider::class,
            RestRouter::class,
        ];

        foreach ($Providers as $provider) {
            (new $provider)->register();
        }
    }

    public function boot()
    {
        //
    }
}
