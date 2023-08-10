<?php

namespace PluginToolsServer\Providers\Rest;

use \PluginToolsServer\Providers\Provider;
use \PluginToolsServer\Providers\Rest\Routes\SettingsRestAPIPRovider;
use \PluginToolsServer\Providers\Rest\Routes\PluginUpdateAPI;
use \PluginToolsServer\Providers\Rest\Routes\PluginDataRestAPI;


class RestRouter implements Provider
{
    public function register()
    {
        $Providers = [
            SettingsRestAPIPRovider::class,
            PluginUpdateAPI::class,
            PluginDataRestAPI::class
        ];

        foreach ($Providers as $provider) {
            (new $provider)->register();
        }
    }
}
