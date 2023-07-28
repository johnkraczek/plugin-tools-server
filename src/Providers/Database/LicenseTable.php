<?php
namespace PluginToolsServer\Providers\Database;

use PluginToolsServer\Providers\Provider;

class LicenseTable implements Provider
{

    public function register()
    {
        
        register_activation_hook(YDTB_PTOOLS_SERVER_PATH .'/PluginToolsServer.php', [$this,'create_license_table']);
        register_deactivation_hook(YDTB_PTOOLS_SERVER_PATH .'/PluginToolsServer.php', [$this,'maybe_remove_license_table']);
    }

    public function create_license_table()
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        $license_table_name = $wpdb->prefix . 'license_keys';
        $sql = "CREATE TABLE {$license_table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            license_key varchar(255) NOT NULL,
            expiry_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            permissions ENUM('rw', 'ro', 'ex') DEFAULT 'ro' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE (license_key)
        ) {$charset_collate};";

        dbDelta($sql);

        $domain_table_name = $wpdb->prefix . 'license_domains';
        $sql = "CREATE TABLE {$domain_table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            license_id mediumint(9) NOT NULL,
            domain varchar(255) NOT NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            check_count INT DEFAULT 0,
            PRIMARY KEY (id)
        ) {$charset_collate};";
        
        dbDelta($sql);

        // Try to add the foreign key
        $wpdb->query("ALTER TABLE {$domain_table_name} ADD CONSTRAINT `fk_license_id` FOREIGN KEY (license_id) REFERENCES {$license_table_name}(id) ON DELETE CASCADE");

    }

    public function maybe_remove_license_table()
    {
        $remove_on_deactivation = get_option('remove_license_table_on_deactivation', false);
        if($remove_on_deactivation) {
            global $wpdb;
            $license_table_name = $wpdb->prefix . 'license_keys';
            $domain_table_name = $wpdb->prefix . 'license_domains';
    
            // Delete all records from 'license_domains' table
            $wpdb->query("DELETE FROM $domain_table_name");
    
            // Now drop the 'license_domains' table
            $wpdb->query("DROP TABLE IF EXISTS $domain_table_name");
    
            // Now drop the 'license_keys' table
            $wpdb->query("DROP TABLE IF EXISTS $license_table_name");
        }
    }

    public static function add_license_key($license_key, $expiry_date)
    {
        global $wpdb;

        // Insert license key
        $wpdb->insert(
            $wpdb->prefix . 'license_keys',
            [
                'license_key' => $license_key,
                'expiry_date' => $expiry_date,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            [
                '%s',    // license_key
                '%s',    // expiry_date
                '%s',    // created_at
                '%s'     // updated_at
            ]
        );
    }

    public static function add_domain_to_license($license_key, $domain)
    {
        global $wpdb;

        // Get license ID from license key
        $license_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}license_keys WHERE license_key = %s",
            $license_key
        ));

        if($license_id) {
            // Insert domain
            $wpdb->insert(
                $wpdb->prefix . 'license_domains',
                [
                    'license_id' => $license_id,
                    'domain' => $domain,
                    'created_at' => current_time('mysql')
                ],
                [
                    '%d',    // license_id
                    '%s',    // domain
                    '%s'     // created_at
                ]
            );
        } else {
            // Handle case when no matching license key is found
        }
    }

    public static function validate_and_update_license_key($license_key, $domain)
    {
        global $wpdb;
        $license_table_name = $wpdb->prefix . 'license_keys';
        $license_row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$license_table_name} WHERE license_key = %s",
            $license_key
        ));

        if (!$license_row) {
            // License key not found
            return ['valid' => false, 'permission' => null];
        }

        $domain_table_name = $wpdb->prefix . 'license_domains';
        $domain_row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$domain_table_name} WHERE license_id = %d AND domain = %s",
            $license_row->id,
            $domain
        ));

        if (!$domain_row) {
            // Domain not found for this license key, insert new domain
            $wpdb->insert($domain_table_name, [
                'license_id' => $license_row->id,
                'domain' => $domain,
                'created_at' => current_time('mysql'),
                'check_count' => 1
            ]);
        } else {
            // Domain found, increment the check_count
            $wpdb->update($domain_table_name, [
                'check_count' => $domain_row->check_count + 1
            ], [
                'id' => $domain_row->id
            ]);
        }
        // License key found and domain updated, return the validation result and permission
        return ['valid' => true, 'permission' => $license_row->permissions];
    }
}
