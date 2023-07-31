<?php

namespace PluginToolsServer\Services;

use Symfony\Component\Process\Process;
use PluginToolsServer\Services\Crypto;
use Bit3\GitPhp\GitRepository;
use PluginToolsServer\Services\RimRaf;

class BitbucketManager
{
    private $username;
    private $password;
    private $workspace;
    public $targetDir;

    public function __construct()
    {
        $settings = get_option(YDTB_PTOOLS_OPTIONS_SLUG);

        $this->username = $settings['bitbucket_username'];
        $this->password = Crypto::Decrypt($settings['bitbucket_password']);
        $this->workspace = $settings['bitbucket_workspace']; // workspace slug from bitbucket
        $this->targetDir = wp_upload_dir()['basedir']. '/plugin-tools-server';

    }

    public function getUser()
    {
        return $this->username;
    }

    public function getWorkspace()
    {
        return $this->workspace;
    }

    // ??? is this needed? ???
    // private function getPassword()
    // {
    //     return Crypto::Decrypt($this->password);
    // }

    public function getTargetDir()
    {
        return $this->targetDir;
    }

    public function cloneOrFetchRepositories()
    {
        $packages = [];
        // Define the fields to fetch
        $fields = 'size,pagelen,page,values.full_name,values.name,values.slug,values.links.clone,values.links.html';

        // Create the context for the request
        $context = stream_context_create([
            'http' => [
                'header' => 'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
            ],
        ]);

        $nextPage = true;
        $page = 1;
        // we need to globally set a few things for git.
        // set the credentials helper & the git user and email.
        //idempotent so we can call this function and it will handle if it is already been completed.
        $this->setGitConfig();

        while ($nextPage) {
            // Fetch repositories
            echo "Fetching Repositories in " . $this->workspace . "...\n";
            $response = file_get_contents("https://api.bitbucket.org/2.0/repositories/".$this->workspace."?pagelen=100&page=$page&fields=$fields", false, $context);
            $data = json_decode($response, true);

            echo "Found " . $data['size'] . " repositories.\n";

            foreach ($data['values'] as $repository) {
                // we want to veriry that we didnt recieve something bad.
                $slug = $this->validateSlug($repository['slug']);
                
                // Define the local path for the repo
                $localWorkDir = $this->targetDir  . '/' . $slug;
                $localGitDir = $localWorkDir . "/$slug.git";
                $localWorkTree = $localWorkDir  . '/' . $slug;

                // If the directory doesn't exist, create it
                if (!is_dir($localWorkDir)) {
                    echo "Cloning " . $repository['name'] . "...\n";
                    mkdir($localWorkTree, 0700, true);
                    // Clone the repository into the local work directory
                    $process = new Process(['git', 'clone', $repository['links']['clone'][0]['href'], $localWorkTree], null, ['GIT_USERNAME' => $this->username, 'GIT_PASSWORD' => $this->password]);
                    $process->run();
                    // move the .git directory to a separate directory so that we can overwrite it later.
                    $process = new Process(['git', 'init', '--separate-git-dir', $localGitDir], $localWorkTree);
                    $process->run();
                    // keep the .git file somewhere else so that we are able to copy it back later.
                    copy($localWorkTree . '/.git', $localGitDir.'/.gitBackup');
                } else {
                    // If the repository does exist, fetch the latest changes
                    echo "Pulling " . $repository['name'] . "...\n";
                    $process = new Process(['git', 'pull', '--all'], $localWorkTree, ['username' => $this->username, 'password' => $this->password]);
                }
                $packages[] = array("path"=>$localWorkTree, "full_name"=>$repository['full_name']);
            }

            $nextPage = isset($data['next']);
            $page++;
        }

        echo "Done.\n";
        return $packages;
    }

    public function generateComposerPackages($packages)
    {

        $packageoutput = new \stdClass();
        $packageoutput->packages = new \stdClass();
        $count = 1;
        foreach($packages as $package) {
            echo "Count: " . $count . " Package: " . $package['full_name'] . "\n";

            $plugin = $this->fetchPluginData($package);
            $packageoutput->packages->{$plugin['slug']} = $plugin[$plugin['slug']];
            $count ++;
        }
        echo "Writing packages.json...\n";
        file_put_contents($this->targetDir."/packages.json", json_encode($packageoutput));
    }

    private function fetchPluginData($package)
    {
        $plugin = [];
        $fullName = $package['full_name'];
        $path = $package['path'];

        $git = new GitRepository($path);
        echo "Generating... " . $package['full_name'] ."\n";

        $remotes = $git->branch()->getNames();
        $tags = $git->tag()->getNames();

        list('slug'=>$slug, 'type'=>$type, 'description'=>$description) = $this->getComposerPackageDetails($path);

        if ($slug === false) {
            return false;
        }

        foreach($tags as $tag) {
            $plugin[$slug][$tag] = [
                'name' => $slug,
                'version' => $tag,
                'dist' => [
                    'type' => 'zip',
                    'url' => "https://bitbucket.org/{$fullName}/get/{$tag}.zip"
                ],
                'source' => [
                    'type' => 'git',
                    'url' => "git@bitbucket.org:{$fullName}.git",
                    'reference' => $tag
                ],
                'type' => $type,
                'description' => $description
            ];
        }
    
        foreach($remotes as $branch) {

            $branchHash = $this->getGitBranchHash($path, $branch);

            echo "Branch: " . $branch . " hash: " . $branchHash ."\n";
            $devBranch = 'dev-' . $branch;
            $plugin[$slug][$devBranch] = [
                'name' => $slug,
                'version' => $devBranch,
                'dist' => [
                    'type' => 'zip',
                    'url' => "https://bitbucket.org/{$fullName}/get/{$branch}.zip"
                ],
                'source' => [
                    'type' => 'git',
                    'url' => "git@bitbucket.org:{$fullName}.git",
                    'reference' => $branchHash
                ],
                'type' => $type,
                'description' => $description
            ];
        }
        $plugin['slug'] = $slug;
        return $plugin;
    }
    
    private function getComposerPackageDetails($filePath)
    {
        echo "filePath... " . $filePath . "\n";

        if (($json = @file_get_contents($filePath . '/composer.json')) === false) {
            $error = error_get_last();
            echo "Unable to get package slug... " . $error['message'];
            return false;
        }
        
        // Decode the composer file.
        $json_data = json_decode($json, true);

        echo "Slug Name... " . $json_data['name'] . "\n";

        $composerData = [
            'slug' => $json_data['name'],
            'type' => $json_data['type'],
            'description' => $json_data['description']
        ];

        return $composerData;
    }
    
    public function setGitConfig()
    {
        // this only needs to be set once.
        if(file_exists("~/.gitconfig")) {
            return;
        }

        // a hack because we need to set the git config for the user,
        // but we need a repo to call git global config commands in.
        // We make a bare repo, set the config, and then ?delete? the repo.
        $bareGit = $this->targetDir . '/git';

        mkdir($bareGit, 0700, true);

        $process = new Process(['git', 'init', '--bare'], $bareGit);
        $process->run();
        $helper = '!f() { sleep 0.5; echo "username=${GIT_USERNAME}"; echo "password=${GIT_PASSWORD}"; }; f';
        $process = new Process(['git', 'config', '--global', 'credential.helper', $helper], $bareGit);
        $process->run();

        //@todo we should set this in the interface.
        $process = new Process(['git', 'config', '--global', 'user.email', "john@kraczek.com"], $bareGit);
        $process->run();

        $process = new Process(['git', 'config', '--global', 'user.name', "John Kraczek"], $bareGit);
        $process->run();

        RimRaf::rrmdir($bareGit);
    }

    private function getGitBranchHash($path, $branch)
    {
        $process = new Process(['git', 'log', '-n', '1', $branch, '--pretty="%h'], $path);
        $process->run();
        return $process->getOutput();
    }

    public function validateSlug($slug)
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
            throw new Exception('Invalid slug: ' . $slug);
        }
        return $slug;
    }

    public function handlePluginUpdate($url, $slug, $name, $version)
    {
        $temp_file_path = $this->targetDir."/tempzip.zip"; // Create a temporary file
        $response = wp_remote_get($url, array(
            'timeout'  => 300,
            'stream'   => true,  // Stream the response to the temporary file
            'filename' => $temp_file_path,
        ));
    
        if (is_wp_error($response)) {
            unlink($temp_file_path);  // Remove the temporary file
            throw new \Exception("Error fetching plugin from $url: " . $response->get_error_message());
        }
    
        if (200 != wp_remote_retrieve_response_code($response)) {
            unlink($temp_file_path);  // Remove the temporary file
            throw new \Exception("Unexpected response fetching plugin from $url: " . wp_remote_retrieve_response_message($response));
        }

        $pluginSlug = $this->getFolderNameFromZip($temp_file_path);

        if (!$this->isPackageSameAsFolderName($slug, $pluginSlug)) {
            unlink($temp_file_path);  // Remove the temporary file
            //@todo: When this happens we want to push a notification into the UI. There are several different parts of the plugin that do background processing. Having a notification system would be nice. then when the user comes into the UI they can see different things that have happened, such as when a plugin update has come in, how it went etc. Right now I'm just checking the logs to see what happened.
            error_log("Plugin folder name does not match package name");
            error_log(print_r($slug, true));
            error_log(print_r($pluginSlug, true));
            throw new \Exception("Plugin folder name does not match package name");
        }

        // we to check if this plugin has previously been downloaded.
        $pluginWorkingDir = $this->targetDir."/$pluginSlug";

        $localGitDir = "$pluginWorkingDir/$pluginSlug.git";
        $localWorkTree = "$pluginWorkingDir/$pluginSlug";

        // if we are not tracking this plugin, we need to make a workspace for it.
        if (!file_exists($pluginWorkingDir)) {

            $this->setGitConfig();

            // we need to get the long name of the plugin.
            $longName = $this->getPluginLongNameFromZip($temp_file_path, $pluginSlug);
            error_log("Long Name: " . $longName);

            // setup the new pluign.
            mkdir($localWorkTree, 0700, true);

            $composerJsonContent = $this->getComposerJson($temp_file_path, $pluginSlug);

            if ($composerJsonContent !== null) {
                $fileWriteResult = file_put_contents("$localWorkTree/composer.json", $composerJsonContent);

                if ($fileWriteResult === false) {
                    // echo 'Failed to write to the file';
                    error_log("Failed to write composer to the file");
                } else {
                    // echo 'File written successfully';
                    error_log(" composer File written successfully");
                }
            } else {
                // echo 'composer.json file does not exist in the zip';
                error_log("composer.json file does not exist in the zip");
                $this->create_composer_json($slug, $name, 'wordpress-plugin', $localWorkTree);
            }

            // next we create the file folder.
            $process = new Process(['git', 'init'], "$localWorkTree/");
            $process->run();
            $process = new Process(['git', 'add', '*'], "$localWorkTree/");
            $process->run();
            $process = new Process(['git', 'commit', '-m', 'Initial commit'], "$localWorkTree/");
            $process->run();

            // next we need to make a repo at bitbucket that we can push this to.
            $gitHref = $this->createBitbucketRepo($pluginSlug, $longName);

            error_log("Remote URL: " . $gitHref);


            $process = new Process(['git', 'remote', 'add', 'origin', $gitHref], "$localWorkTree/");
            $process->run();
            error_log("done add origin");
            error_log(print_r($process->getOutput(), true));
            error_log(print_r($process->getErrorOutput(), true));


            $process = new Process(['git', 'push', '--set-upstream', 'origin', 'master'], "$localWorkTree/", ['GIT_USERNAME' => $this->username, 'GIT_PASSWORD' => $this->password]);
            $process->run();
            error_log("set upstream output:");
            error_log(print_r($process->getOutput(), true));
            error_log(print_r($process->getErrorOutput(), true));
            //git push -u origin master
            
            $process = new Process(['git', 'push', '-u', 'origin', 'master'], "$localWorkTree/", ['GIT_USERNAME' => $this->username, 'GIT_PASSWORD' => $this->password]);
            $process->run();
            error_log("push output");
            error_log(print_r($process->getOutput(), true));
            error_log(print_r($process->getErrorOutput(), true));

            mkdir($localGitDir, 0700, true);

            $process = new Process(['git', 'init', '--separate-git-dir', "$localGitDir/"], "$localWorkTree/");
            $process->run();
            error_log("Separate Git Dir:");
            error_log(print_r($process->getOutput(), true));
            error_log(print_r($process->getErrorOutput(), true));
            // keep the .git file somewhere else so that we are able to copy it back later.
            
            copy($localWorkTree . '/.git', $localGitDir.'/.gitBackup');
        }
        
        // first step is to copy the composer.json file
        $source = "$localWorkTree/composer.json";
        $destination = "$pluginWorkingDir/composer.json";
        
        if (!copy($source, $destination)) {
            echo "failed to copy $source...\n";
        }

        // next we remove the previous plugin folder.
        if (file_exists("$localWorkTree/")) {
            RimRaf::rrmdir("$localWorkTree/");
        }

        // next we unzip the new plugin folder.
        $zip = new \ZipArchive;
        $res = $zip->open($temp_file_path);
        if ($res === true) {
            $zip->extractTo("$pluginWorkingDir");
            $zip->close();
            echo 'extraction successful!';
        } else {
            echo 'failed to open zip!';
        }

        $source  = "$pluginWorkingDir/composer.json";
        $destination = "$localWorkTree/composer.json";

        // next we put back the composer.json file.
        if (!copy($source, $destination)) {
            echo "failed to copy $source...\n";
        }

        // next we copy the .git file back into the plugin folder.
        $source = "$localGitDir/.gitBackup";
        $destination =  "$localWorkTree/.git";
        
        if (!copy($source, $destination)) {
            echo "failed to copy $source...\n";
        }

        // next we remove the temp zip file.
        if (file_exists($temp_file_path)) {
            unlink($temp_file_path);
        }

        // get the plugin version from the composer.json file.
        $pluginVersion = $this->get_plugin_version("$localWorkTree/$pluginSlug.php");

        if ($pluginVersion != $version) {
            error_log("Plugin version does not match package version");
            error_log(print_r($pluginVersion, true));
            error_log(print_r($version, true));
            throw new \Exception("Plugin version does not match package version");
        }
        
        // next we add changes in the git repo.
        $process = new Process(['git', 'add', '*'], "$localWorkTree/");
        $process->run();

        $process = new Process(['git', 'commit', '-m', "Update plugin to version $version"], "$localWorkTree/");
        $process->run();

        $process = new Process(['git', 'push', 'origin', 'master'], "$localWorkTree/", ['GIT_USERNAME' => $this->username, 'GIT_PASSWORD' => $this->password]);
        $process->run();

        $process = new Process(['git', 'tag', $version], "$localWorkTree/");
        $process->run();
        error_log("Git Tag");
        error_log(print_r($process->getOutput(), true));
        error_log(print_r($process->getErrorOutput(), true));

        $process = new Process(['git', 'push', 'origin', $version], "$localWorkTree/", ['GIT_USERNAME' => $this->username, 'GIT_PASSWORD' => $this->password]);
        $process->run();
        error_log("Push Tag");
        error_log(print_r($process->getOutput(), true));
        error_log(print_r($process->getErrorOutput(), true));
    }

    private function getFolderNameFromZip($path)
    {
        $zip = new \ZipArchive;
        $res = $zip->open($path);
        if ($res === true) {
            for($i = 0; $i < $zip->numFiles; $i++) {
                $fileinfo = $zip->statIndex($i);
                // Check if it is a directory
                if(substr($fileinfo['name'], -1) == '/') {
                    // Close zip file
                    $zip->close();
                    // Return the directory name
                    return trim($fileinfo['name'], '/');
                }
            }
        }
        // Return empty string if no directory found
        return '';
    }

    private function get_plugin_version($plugin_path)
    {
        if(!function_exists('get_plugin_data')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
    
        $plugin_data = get_plugin_data($plugin_path);
        $plugin_version = $plugin_data['Version'];
    
        return $plugin_version;
    }

    private function isPackageSameAsFolderName($slug, $folderName)
    {
        // Split the slug into parts
        $parts = explode('/', $slug);
        
        // If slug is not in 'vendor/package' format, return false
        if (count($parts) < 2) {
            return false;
        }
        
        // Get the package name
        $packageName = $parts[1];
    
        // Compare the package name with the folder name
        return $packageName == $folderName;
    }

    public function create_composer_json($slug, $name, $type, $path)
    {
        $data = array(
            "name" => $slug,
            "description" => "A private repo tracking the $name plugin.",
            "type" => $type
        );
    
        // Convert array to JSON
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
        // Define path
        $file_path = $path . '/composer.json';
    
        // Write data to file
        if (!file_put_contents($file_path, $json)) {
            throw new Exception('Unable to write to file at: ' . $file_path);
        }
    }

    public function getComposerJson($zipPath, $slug)
    {
        $zip = new \ZipArchive;
    
        // Open the zip file
        if ($zip->open($zipPath) === true) {
            // Loop through each file in the zip
            for($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                // If this file is the composer.json within the slug directory, return its content
                if ($filename == $slug . '/composer.json') {
                    $stream = $zip->getStream($filename);
                    if (!$stream) {
                        throw new Exception('Could not read composer.json file in zip');
                    }
                    $content = stream_get_contents($stream);
                    fclose($stream);
                    $zip->close();
                    return $content;
                }
            }
            // Close the zip file
            $zip->close();
        } else {
            throw new Exception('Could not open zip file at: ' . $zipPath);
        }
        // No composer.json file found
        return null;
    }

    private function createBitbucketRepo(string $slug, string $longName)
    {
        $username = $this->username;
        $password = $this->password;
        $workspace = $this->workspace;
        $project = 'PLUG';
        $auth = base64_encode("$username:$password");

        // @TODO we need to make sure that the project exists. for now i just manally created the project.
        error_log("auth: " . $auth);
        $data = array(
            'name' => $longName,
            'full_name' => $longName,
            'scm' => 'git',
            'slug' => $slug,
            'project' => [
                'key' => $project,
            ],
            'is_private' => true,
        );
    
        $options = array(
            'http' => array(
                'header'  => [
                    "Content-type: application/json",
                    "Accept: application/json",
                    "Authorization: Basic " . base64_encode("$username:$password")
                ],
                'method'  => 'POST',
                'content' => json_encode($data),
            ),
        );
        
        $context = stream_context_create($options);
    
        $url = "https://api.bitbucket.org/2.0/repositories/$workspace/$slug";
        $response = file_get_contents($url, false, $context);
    
        if ($response === false) {
            throw new \Exception("Unable to create Bitbucket repository");
        }
    
        $responseData = json_decode($response, true);
    
        if (isset($responseData['links']['clone'])) {
            foreach ($responseData['links']['clone'] as $cloneLink) {
                if ($cloneLink['name'] === 'https') {
                    return $cloneLink['href'];
                }
            }
        }

        throw new \Exception("Unable to get clone links from repository response");
        
    }
    
    private function getPluginLongNameFromZip($zipFilePath, $slug)
    {
        $zip = new \ZipArchive;
        $res = $zip->open($zipFilePath);

        if ($res === true) {
            $fileContent = $zip->getFromName($slug . '/' . $slug . '.php');

            if ($fileContent !== false) {
                if (preg_match('/\* Plugin Name:\s*(.+)/', $fileContent, $matches)) {
                    return $matches[1];
                } else {
                    throw new \Exception("Cannot find the Plugin Name in the ZIP file.");
                }
            } else {
                throw new \Exception("Cannot find the file $slug/$slug.php in the ZIP file.");
            }

            $zip->close();
        } else {
            throw new \Exception("Cannot open the ZIP file.");
        }
    }

    

}
