<?php

namespace PluginToolsServer;
use \PluginToolsServer\Providers\Provider;
use \PluginToolsServer\Providers\ApiServiceProvider;
use \PluginToolsServer\Providers\AdminPageProvider;
use \PluginToolsServer\Providers\CommandServiceProvider;

class Plugin implements Provider
{ 
    protected function providers()
    {
        return [
            ApiServiceProvider::class,
            AdminPageProvider::class,
            CommandServiceProvider::class,
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
