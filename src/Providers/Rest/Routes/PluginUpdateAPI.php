<?php

namespace PluginToolsServer\Providers\Rest\Routes;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PluginToolsServer\Providers\Database\LicenseTable;
use PluginToolsServer\Providers\Provider;
use PluginToolsServer\Services\PluginDownloadJob;
use PluginToolsServer\Providers\Rest\Permission\RestPermission;
use PluginToolsServer\Services\BitbucketManager;

class PluginUpdateAPI implements Provider
{
    private PluginDownloadJob $downloadJob;

    public function register()
    {
        $this->downloadJob = new PluginDownloadJob();

        add_action('rest_api_init', function () {
            register_rest_route('pt-server/v1', '/pk', [
                'methods' => 'POST',
                'callback' => array( $this, 'handleProductKey' )
            ]);
        });
           
        add_action('rest_api_init', function () {
            register_rest_route('pt-server/v1', '/update', [
                'methods' => 'POST',
                'callback' => array( $this, 'updateSinglePlugin' ),
                'permission_callback' => array( new RestPermission, 'getPermissionCallback' )
            ]);
        });

        add_action('rest_api_init', function () {
            register_rest_route('pt-server/v1', '/upload-plugin', [
                'methods' => 'POST',
                'callback' => array( $this, 'postInternalPlugin' ),
                'permission_callback' => array( new RestPermission, 'getPermissionCallback' )
            ]);
        });

        add_action('rest_api_init', function () {
            register_rest_route('pt-server/v1', '/complete-upload-plugin', [
                'methods' => 'POST',
                'callback' => array( $this, 'completeInternalPlugin' ),
                'permission_callback' => array( new RestPermission, 'getPermissionCallback' )
            ]);
        });
        
        $this->AttemptLimit = 5;
        $this->AttemptWindow = 10*60; // 10 minutes
    }

    public function handleProductKey(\WP_REST_Request $request)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $attempts = get_transient("PTS_SERVER_$ip") ?: 0;

        // Extract the product key from the request
        $product_key = $request->get_header('product-key');

        if (!$product_key) {
            return new \WP_Error('missing_product_key', 'The product key is missing', ['status' => 400]);
        }

        $domain = $_SERVER['HTTP_HOST'];

        // Validate the product key and get the permissions
        $result = LicenseTable::validate_and_update_license_key($product_key, $domain);

        // If the product key is invalid, return an error response
        if ($result['valid'] === false) {

            //failed attempts are tracked.
            set_transient("myplugin_attempts_$ip", $attempts + 1, $this->AttemptWindow);

            if ($attempts + 1 >= $this->AttemptLimit) {
                return new \WP_Error('too_many_attempts', 'Too many attempts, please try again later.', ['status' => 429]);
            }
            return new \WP_Error('invalid_product_key', 'The product key provided is invalid', ['status' => 400]);
        }

        // If the product key is valid, generate a JWT and return it
        $secret_key = get_option('PTS_JWT_SECRET_KEY');

        //@todo: allow the permissions to be set in the database
        // $permission = $result['permission'];
        $permission = 'rw';

        $issuedAt   = time();
        $expire     = $issuedAt + 3600;

        $payload = [
            "iat" => $issuedAt,                    // Issued at: time when the token was generated
            "iss" => get_site_url(),               // Issuer
            "exp" => $expire,                      // Expiration time
            "permission" => $permission
        ];

        $jwt = JWT::encode($payload, $secret_key, 'HS256');

        return ['jwt' => $jwt];
    }

    public function updateSinglePlugin(\WP_REST_Request $request)
    {
        $token = $request->get_header('Authorization');
        $secret_key = get_option('PTS_JWT_SECRET_KEY');
        
        try {
            $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
            $permission = $decoded->permission;
            if ($permission !== 'rw') {
                return new \WP_Error('rest_forbidden', 'You do not have permission to do that.', array('status' => 403));
            }
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return new \WP_Error('rest_forbidden', 'Tampered Token', array('status' => 403));
        } catch (\Firebase\JWT\ExpiredException $e) {
            return new \WP_Error('rest_forbidden', 'Your token has expired', array('status' => 403));
        } catch (\Exception $e) {
            return new \WP_Error('rest_forbidden', 'You are not allowed to do that.', array('status' => 403));
        }

        // we have validated that there are no errors with the token, now we can get the data
        $data = $request->get_json_params();
 
        // Validate and sanitize the URL
        if (!filter_var($data['pluginURL'], FILTER_VALIDATE_URL)) {
            return new \WP_Error('validation_error', 'Invalid URL', array('status' => 400));
        } else {
            $pluginURL = esc_url_raw($data['pluginURL']);
        }

        // Validate and sanitize the plugin slug (vendor/plugin)
        if ($data['pluginSlug'] === null) {
            return new \WP_Error('validation_error', 'Slug is required', array('status' => 400));
        }

        if (!preg_match('/^[a-z0-9]+(\/[a-z0-9-]+)?$/i', $data['pluginSlug'])) {
            return new \WP_Error('validation_error', 'Invalid plugin slug. Expected format: vendor/plugin', array('status' => 400));
        }
        $pluginSlug = $data['pluginSlug'];
        
        // Validate and sanitize the plugin name
        if (strlen($data['pluginName']) > 30) {
            return new \WP_Error('validation_error', 'Plugin name should be less than 30 characters', array('status' => 400));
        }
        $pluginName = sanitize_text_field($data['pluginName']);


        // Validate the version (no sanitization, as version strings may legitimately contain non-alphanumeric characters)
        if (!preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(-(0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(\.(0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*)?(\+[0-9a-zA-Z-]+(\.[0-9a-zA-Z-]+)*)?$/', $data['pluginVersion'])) {
            return new \WP_Error('validation_error', 'Invalid version. Expected format: X.Y.Z', array('status' => 400));
        }

        $pluginVersion = $data['pluginVersion'];

        $this->downloadJob->data([
         'plugin_url' => $pluginURL,
         'plugin_slug' => $pluginSlug,
         'plugin_name' => $pluginName,
         'plugin_version' => $pluginVersion
        ]);
       
        $this->downloadJob->dispatch();
        return new \WP_REST_Response(array(
            'result' => 'success',
            'message' => 'Plugin update job dispatched.'
        ), 200);
    }

    public function postInternalPlugin(\WP_REST_Request $request)
    {
        // Ensure there's a file uploaded
        if (empty($_FILES) || !isset($_FILES['file'])) {
            return new WP_Error('no_file_uploaded', 'No file was uploaded.', array('status' => 400));
        }
    
        $file = $_FILES['file'];
    
        // Check for the file type
        if ($file['type'] !== 'application/zip') {
            return new WP_Error('invalid_file_type', 'Only zip files are allowed.', array('status' => 400));
        }
    
        $plugin_info = $this->isValidWordPressPlugin($file['tmp_name']);

        if ($plugin_info === false) {
            return new \WP_Error('invalid_plugin', 'The uploaded file is not a valid WordPress plugin.', array('status' => 400));
        }
        
        // If validation was successful, $plugin_info is an array with 'slug' and 'name' keys.
        $slug = $plugin_info['slug'];
        $name = $plugin_info['name'];
        $uploadVersion = $plugin_info['version'];

        // Here you would move the uploaded file to a safe location, using move_uploaded_file() or similar functions.
        $uploads_info = wp_upload_dir();

        // Determine the base directory for uploads.
        $base_dir = $uploads_info['basedir'];

        // Create your custom directory path.
        $plugin_tools_server_dir = $base_dir . '/plugin-tools-server';

        // Check if your custom directory doesn't exist.
        if (!file_exists($plugin_tools_server_dir."/tmp/")) {
            // Try to create the directory. This will also create nested directories as required.
            wp_mkdir_p($plugin_tools_server_dir."/tmp/");
        }

        // Define the destination path for the uploaded file.
        $destination_path = $plugin_tools_server_dir . $file['tmp_name'];

        move_uploaded_file($file['tmp_name'], $destination_path);
    
        // Calculate file size in a friendly format
        $filesize = $this->formatSizeUnits(filesize($destination_path));
    
        // check if there is a plugin with the same slug

        $pluginDir = "$plugin_tools_server_dir/$slug/$slug";

        $newPlugin = true;
        $result = null;
        $verCheck = null;

        if (file_exists($pluginDir)) {
            $newPlugin = false;
            $currentPlugin = $this->getPluginDetails($pluginDir);

            if ($currentPlugin === false) {
                return new WP_Error('invalid_plugin_directory', 'There was a problem looking up the plugin with that slug.', array('status' => 400));
            }

            if (version_compare($uploadVersion, $currentPlugin['version'], '>')) {
                $verCheck = [
                    "status" => true,
                    "message" => "Upload version is greater than Current Version",
                    "currentVersion" => $currentPlugin['version'
                ]] ;
            } else {
                $verCheck = [
                    "status" => false,
                    "message" => "Upload version is less than or equal to Current Version",
                    "currentVersion" => $currentPlugin['version'
                ]] ;
            }
        }

        $response = [
            'status' => 'success',
            'message' => 'File processed successfully.',
            'data' => [
                'filesize' => $filesize,
                'processedAt' => current_time('mysql'),
                'destination_path' => $destination_path,
                'slug' => $slug,
                'name' => $name,
                'newversion' => $uploadVersion,
                'newPlugin' => $newPlugin,
                'pluginDetails' => $result,
                'versionCheck' => $verCheck
            ]
        ];
    
        return new \WP_REST_Response($response, 200);
    }

    public function comleteInternalPlugin(\WP_REST_Request $request)
    {
        $data = $request->get_params()['data'];
        
        $bitbucket = new BitbucketManager(true);

        if (!$bitbucket->initalized){
            throw new \Exception('Bitbucket Settings not configured.');
        }

        if (!file_exists($data['destination_path'])) {
            throw new \Exception('File does not exist.');
        }

        $bitbucket->handlePluginUpdate($data['destination_path'], $data['composerSlug'], $data['name'], $data['newversion']);
        $packages = $bitbucket->cloneOrFetchRepositories();
        $bitbucket->generateComposerPackages($packages);

        $response = [
            'status' => 'success',
            'message' => 'Plugin processed successfully.',
        ];
        return new \WP_REST_Response($response, 200);
    }
    
    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        return $bytes;
    }
    
    private function isValidWordPressPlugin($zipPath)
    {
        // Create a new zip object
        $zip = new \ZipArchive;
        
        // Open the zip file
        if ($zip->open($zipPath) === false) {
            return false;
        }

        $entry = $zip->getNameIndex(0);
        $pos = strpos($entry, '/');
        $slug = substr($entry, 0, $pos);

        // Loop through each file in the zip
        for ($i = 0; $i < $zip->numFiles; $i++) {
            // Get the filename of the current file
            $filename = $zip->getNameIndex($i);

            if (substr_count($filename, '/') !== 1  || substr($filename, -1) === '/') {
                continue;
            }

            // Check if the file is a PHP file in the root directory of the plugin
            $path_parts = pathinfo($filename);
            if ($path_parts['extension'] == 'php') {
                // Get the contents of the file
                $contents = $zip->getFromName($filename);
    
                // Check if the contents include the plugin name and version
                if (preg_match('/Plugin Name:\s*(.*)/', $contents, $name_matches) &&
                    preg_match('/Version:\s*(.*)/', $contents, $version_matches)) {
                    // Close the zip file
                    $zip->close();
                        
                    // Return the slug (folder name), plugin name, and version
                    return [
                        'slug' => $slug,
                        'name' => trim($name_matches[1]),
                        'version' => trim($version_matches[1]),
                    ];
                }
            }
        }
    
        // Close the zip file
        $zip->close();
        
    
        // If we reach here, the file is not a valid WordPress plugin
        return false;
    }

    //todo: this has duplicate functionality with the bitbucket manager class, it does the same thing.

    private function getPluginDetails($folder_path)
    {

        // Ensure path ends with a directory separator
        if (substr($folder_path, -1) !== DIRECTORY_SEPARATOR) {
            $folder_path .= DIRECTORY_SEPARATOR;
        }
    
        // Read files in the specified directory
        $files = scandir($folder_path);
    
        // Filter for PHP files in the root directory of the plugin
        $php_files = array_filter($files, function ($file) use ($folder_path) {
            return is_file($folder_path . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php';
        });
    
        foreach ($php_files as $filename) {
            $contents = file_get_contents($folder_path . $filename);
    
            // Check if the contents include the plugin name and version
            if (preg_match('/Plugin Name:\s*(.*)/', $contents, $name_matches) &&
                preg_match('/Version:\s*(.*)/', $contents, $version_matches)) {
    
                // Return the slug (folder name), plugin name, and version
                return [
                    'name' => trim($name_matches[1]),
                    'version' => trim($version_matches[1]),
                ];
            }
        }
    
        return false;
    }
    

}
