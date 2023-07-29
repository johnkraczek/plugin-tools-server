<?php

namespace PluginToolsServer\Providers\Routes;

use PluginToolsServer\Providers\Provider;

class Composer implements Provider
{
    public function register()
    {

        add_action('init', function () {
            add_rewrite_rule('^packages.json$', 'index.php?packages_json=1', 'top');
        });

        add_filter('query_vars', function ($vars) {
            $vars[] = 'packages_json';
            return $vars;
        });

        add_action('template_redirect', function () {
            $packages_json = intval(get_query_var('packages_json'));
            if ($packages_json) {
                // echo $wpUploadsDir.'/packages.json';
                $wpUploadsDir = wp_upload_dir()['basedir']. '/plugin-tools-server';
                // echo $wpUploadsDir;
                $json_file = $wpUploadsDir.'/packages.json';  // Specify your actual file path
                if (file_exists($json_file)) {
                    header('Content-Type: application/json');
                    readfile($json_file);
                } else {
                    status_header(404);
                    nocache_headers();
                }
                exit;
            }
        });
    }

}
