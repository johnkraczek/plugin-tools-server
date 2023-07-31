<?php

namespace PluginToolsServer\Services;

/**
 * To be able to authenticate with bitbucket we are required to have a username and password. While its not ideal to store these in the database, we will encrypt them using the WP salt as the key. This will make it harder for someone to get the password, but not impossible. If you have a better way of doing this, please let me know. ;)
 * Things I have thought about: Using SSH Keys, but then this php user would need to have access to the private key. Either way something needs to be stored in the database... The benefit here is that this password is the App Password and has limits to what it can do.
 */

class Crypto {
    static function Encrypt($data, $key="")
    {
        // if no salt provided, then use the default WP salt
        // if ($key == ""){
        //     $key = wp_salt("AUTH_SALT");
        // }
        $key = "12345678901234567890123456789012";

        // Generate an initialization vector
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        // Encrypt the data using AES 256 encryption in CBC mode using our encryption key and initialization vector.
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        // The $iv is just as important as the key for decrypting, so save it with our encrypted data using a unique separator (::)
        return base64_encode($encrypted . '::' . $iv);
    }
     
    static function Decrypt($data, $key="")
    {

        $key = "12345678901234567890123456789012";
        
        // if no salt provided, then use the default WP salt
        // if ($key == ""){
        //     $key = wp_salt("AUTH_SALT");
        // }
        // To decrypt, split the encrypted data from our IV - our unique separator used was "::"
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
    }

    static function generate_license_key($length = 24) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}