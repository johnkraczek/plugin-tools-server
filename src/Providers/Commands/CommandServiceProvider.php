<?php

namespace PluginToolsServer\Providers\Commands;

use PluginToolsServer\Commands\PTSCommands;
use PluginToolsServer\Providers\Provider;

class CommandServiceProvider implements Provider
{
    public function register()
    {
        if (! defined('WP_CLI') || ! WP_CLI) {
            return;
        }

        \WP_CLI::add_command('pts', PTSCommands::class);
    }
}
