<?php

namespace PluginToolsServer\Providers;

class AdminPageProvider implements Provider
{
    public function register()
    {
        add_action('admin_menu', array($this, 'settings_menu_link'));

    }

    public function settings_menu_link()
    {
        $hook =  add_submenu_page('plugins.php', 'Plugin Tools', 'Plugin Tools', 'manage_options', 'plugin-tools', array($this, 'settings_page'));
        
        add_action('load-' . $hook, array( $this, 'load_assets' ));
    }

    public function load_assets()
    {
        $entrypoints_manifest = YDTB_PTOOLS_PATH."/dist/entrypoints.json";

        if (!$entrypoints_manifest) {
            throw new \Exception('Example: you must run `yarn build` before using this plugin.');
        }
        
        $entrypoints = json_decode(file_get_contents($entrypoints_manifest));
        
        foreach ($entrypoints->client->js as $js) {
            // echo($js."<br>");
            // echo(YDTB_PTOOLS_URL."dist/{$js}<br>");
            wp_enqueue_script(
                $js,
                YDTB_PTOOLS_URL."dist/{$js}",
                $entrypoints->client->dependencies,
                false,
                true,
            );
        }
        foreach ($entrypoints->client->css as $css) {
            wp_enqueue_style(
                'adminPageCss',
                YDTB_PTOOLS_URL."dist/{$css}",
                array(),
                false,
                'all',
            );
        }

        wp_localize_script('js/client.js', 'ydtb', [
            'rest' => esc_url_raw(rest_url())."/ydtb-plugin-tools/v1/",
            'nonce' => wp_create_nonce('wp_rest'),
            'root' => esc_url_raw(YDTB_PTOOLS_URL)
        ]);
    }
    public function settings_page()
    {
        echo('<div id="ydtb-plugin-tools-root" class="h-full" style="margin-right: 16px; margin-top: 10px;"></div>');
    }
}
