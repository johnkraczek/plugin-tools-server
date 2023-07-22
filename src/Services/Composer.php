<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

function getPluginData($plugin){
    // Function that would replicate getPluginData functionality of the javascript code.
}

$client = new Client();
$workspace = 'ydtb-wp-packages'; // Workspace slug

$pagenum = 1;
$plugins = [];
do {
    try {
        $response = $client->get("https://api.bitbucket.org/2.0/repositories/{$workspace}", [
            'query' => ['page' => $pagenum, 'pagelen' => 100]
        ]);

        $data = json_decode($response->getBody(), true);
        $list = array_map('getPluginData', $data['values']);
        $next = array_key_exists('next', $data);

        $pagenum += 1;
        $plugins = array_merge($plugins, $list);
    } catch (RequestException $e) {
        echo $e->getMessage();
    }
} while ($next);

$packages = array_map(function($plugin){
    $composerPackage = [];
    $composerPackage['name'] = $plugin['full_name'];

    foreach($plugin['tags'] as $tag){
        $tagText = $tag;
        // You might need to write custom code or use a library to replicate semver.clean functionality in PHP.
        $tag = semver_clean($tag) ?? semver_clean($tag . '.0') ?? $tag;

        $composerPackage[$plugin['full_name']][$tag] = [
            'name' => $plugin['full_name'],
            'version' => $tag,
            'dist' => [
                'type' => 'zip',
                'url' => "https://bitbucket.org/{$plugin['name']}/get/{$tagText}.zip"
            ],
            'source' => [
                'type' => 'git',
                'url' => "git@bitbucket.org:{$plugin['name']}.git",
                'reference' => $tagText
            ],
            'type' => $plugin['type'],
            'description' => $plugin['description']
        ];
    }

    foreach($plugin['branches'] as $branch){
        $devBranch = 'dev-' . $branch['name'];
        $composerPackage[$plugin['full_name']][$devBranch] = [
            'name' => $plugin['full_name'],
            'version' => $devBranch,
            'dist' => [
                'type' => 'zip',
                'url' => "https://bitbucket.org/{$plugin['name']}/get/{$branch['name']}.zip"
            ],
            'source' => [
                'type' => 'git',
                'url' => "git@bitbucket.org:{$plugin['name']}.git",
                'reference' => $branch['hash']
            ],
            'type' => $plugin['type'],
            'description' => $plugin['description']
        ];
    }
    return $composerPackage;
}, $plugins);

$packageJSON = ['packages' => []];
foreach($packages as $package){
    $name = $package['name'];
    $packageJSON['packages'][$name] = $package[$name];
}

try {
    file_put_contents('packages.json', json_encode($packageJSON));
    echo "File successfully written to packages.json";
} catch (Exception $e) {
    echo $e->getMessage();
}
