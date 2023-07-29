<?php

namespace PluginToolsServer;

use \PluginToolsServer\Providers\Provider;
use \PluginToolsServer\Providers\Rest\SettingsRestAPIPRovider;
use \PluginToolsServer\Providers\Rest\PluginUpdateAPI;
use \PluginToolsServer\Providers\Views\AdminPageProvider;
use \PluginToolsServer\Providers\Commands\CommandServiceProvider;
use \PluginToolsServer\Providers\Routes\Composer;
use \PluginToolsServer\Providers\Database\LicenseTable;

class Plugin implements Provider
{

    public function register()
    {

        $Providers = [
            SettingsRestAPIPRovider::class,
            AdminPageProvider::class,
            Composer::class,
            LicenseTable::class,
            CommandServiceProvider::class,
            PluginUpdateAPI::class,
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
