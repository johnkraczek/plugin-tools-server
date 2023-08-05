<?php

namespace PluginToolsServer\Providers\Views;

use PluginToolsServer\Providers\Provider;

class AdminPageProvider implements Provider
{
    public function register()
    {
        add_action('admin_menu', array($this, 'settings_menu_link'));
    }

    public function settings_menu_link()
    {
        $hook =  add_submenu_page('plugins.php', 'Plugin Tools Server', 'Plugin Tools Server', 'manage_options', 'plugin-tools-server', array($this, 'settings_page'));
        
        add_action('load-' . $hook, array( $this, 'load_assets' ));
    }

    public function load_assets()
    {
        $entrypoints_manifest = YDTB_PTOOLS_SERVER_PATH."dist/entrypoints.json";

        if (!$entrypoints_manifest) {
            throw new \Exception('Example: you must run `yarn build` before using this plugin.');
        }
        
        $entrypoints = json_decode(file_get_contents($entrypoints_manifest));
        
        foreach ($entrypoints->client->js as $js) {
            wp_enqueue_script(
                $js,
                YDTB_PTOOLS_SERVER_URL."dist/{$js}",
                $entrypoints->client->dependencies,
                false,
                true,
            );
        }
        foreach ($entrypoints->client->css as $css) {

            if(preg_match('/client\.([a-z0-9]*?)\./', $css, $matches)) {
                $hash = $matches[1];
                // Now the $hash variable contains the hash
            } else {
                $hash = false;
            }

            wp_enqueue_style(
                'plugin-tools-server',
                YDTB_PTOOLS_SERVER_URL."dist/{$css}",
                array(),
                $hash,
                'all',
            );
        }

        foreach ($entrypoints->client->js as $js) {
            if ($js === "js/client.js" || preg_match("/^js\/client\.[a-zA-Z0-9]+\.js$/", $js)) {
                wp_localize_script($js, 'pts', [
                    'rest' => esc_url_raw(rest_url())."pt-server/v1/",
                    'nonce' => wp_create_nonce('wp_rest'),
                    'root' => esc_url_raw(YDTB_PTOOLS_SERVER_URL),
                    "cssHash" => $hash
                ]);
            }
        }
    }

    public function settings_page()
    {
        echo('<div id="ydtb-plugin-tools-server-root" class="h-full" style="margin-right: 16px; margin-top: 10px;"></div>');
    }
}
