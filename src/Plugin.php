<?php

namespace PluginToolsServer;
use \PluginToolsServer\Providers\Provider;
use \PluginToolsServer\Providers\Rest\SettingsRestAPIPRovider;
use \PluginToolsServer\Providers\Views\AdminPageProvider;
use \PluginToolsServer\Providers\Commands\CommandServiceProvider;
use \PluginToolsServer\Providers\Routes\Composer;
use \PluginToolsServer\Providers\Database\LicenseTable;

class Plugin implements Provider
{ 
    protected function providers()
    {
        return [
            SettingsRestAPIPRovider::class,
            AdminPageProvider::class,
            CommandServiceProvider::class,
            Composer::class,
            LicenseTable::class,
        ];
    }

    public function register()
    {
        foreach ($this->providers() as $service) {
            (new $service)->register();
        }
    }

    public function boot()
    {
        //
    }
}
