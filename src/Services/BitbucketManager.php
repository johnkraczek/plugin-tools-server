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
    private $silent;

    public function __construct($silent = false)
    {
        $settings = get_option(YDTB_PTOOLS_OPTIONS_SLUG);

        if (!$settings){
            return false;
        }

        $this->username = $settings['bitbucket_username'];
        $this->password = Crypto::Decrypt($settings['bitbucket_password']);
        $this->workspace = $settings['bitbucket_workspace']; // workspace slug from bitbucket
        $this->targetDir = wp_upload_dir()['basedir']. '/plugin-tools-server';
        $this->silent = $silent;
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
        $fields = 'size,pagelen,page,values.full_name,values.name,values.slug,values.links.clone,values.links.html';
    
        $context = stream_context_create([
            'http' => [
                'header' => 'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
            ],
        ]);
    
        $nextPage = true;
        $page = 1;
        $this->setGitConfig();
    
        while ($nextPage) {
            $this->logOutput("Fetching Repositories in " . $this->workspace . "...\n");
            $response = file_get_contents("https://api.bitbucket.org/2.0/repositories/".$this->workspace."?pagelen=100&page=$page&fields=$fields", false, $context);
            $data = json_decode($response, true);
    
            $this->logOutput("Found " . $data['size'] . " repositories.\n");
    
            foreach ($data['values'] as $repository) {
                $slug = $this->validateSlug($repository['slug']);
                $localWorkDir = $this->targetDir  . '/' . $slug;
                $localGitDir = $localWorkDir . "/$slug.git";
                $localWorkTree = $localWorkDir  . '/' . $slug;
    
                if (!is_dir($localWorkDir)) {
                    $this->logOutput("Cloning " . $repository['name'] . "...\n");
                    mkdir($localWorkTree, 0700, true);
                    $process = new Process(['git', 'clone', $repository['links']['clone'][0]['href'], $localWorkTree], null, ['GIT_USERNAME' => $this->username, 'GIT_PASSWORD' => $this->password]);
                    $process->run();
                    $process = new Process(['git', 'init', '--separate-git-dir', $localGitDir], $localWorkTree);
                    $process->run();
                    copy($localWorkTree . '/.git', $localGitDir.'/.gitBackup');
                } else {
                    $this->logOutput("Pulling " . $repository['name'] . "...\n");
                    $process = new Process(['git', 'pull', '--all'], $localWorkTree, ['username' => $this->username, 'password' => $this->password]);
                }
                $packages[] = array("path"=>$localWorkTree, "full_name"=>$repository['full_name']);
            }
    
            $nextPage = isset($data['next']);
            $page++;
        }
    
        $this->logOutput("Done.\n");
        return $packages;
    }
    
    private function logOutput($message)
    {
        if (!$this->silent) {
            echo $message;
        }
    }

    public function generateComposerPackages($packages)
    {
        $packageoutput = new \stdClass();
        $packageoutput->packages = new \stdClass();
        $count = 1;
        $this->logOutput("Generating packages.json...\n");

        // clear out the meta data so that if any packages have been removed, they will be removed from the meta data.
        update_option('pts_plugin_list_meta', []);

        foreach($packages as $package) {
            if (!$this->silent) {
                $this->logOutput("Count: " . $count . " Package: " . $package['full_name'] . "\n");
            }
    
            $plugin = $this->fetchPluginData($package);
            $packageoutput->packages->{$plugin['slug']} = $plugin[$plugin['slug']];
            $count ++;
        }
        if (!$this->silent) {
            $this->logOutput("Writing packages.json...\n");
        }
        file_put_contents($this->targetDir."/packages.json", json_encode($packageoutput));
    }
    
    private function fetchPluginData($package)
    {
        $plugin = [];
        $fullName = $package['full_name'];
        $path = $package['path'];
    
        $git = new GitRepository($path);
    
        $this->logOutput("Generating... " . $package['full_name'] ."\n");
    
        $remotes = $git->branch()->getNames();
        $tags = $git->tag()->getNames();
    
        list('slug'=>$composerSlug, 'type'=>$type, 'description'=>$description) = $this->getComposerPackageDetails($path);
    
        if ($composerSlug === false) {
            return false;
        }
    
        $this->generatePluginMetaData($tags, $path, $composerSlug);
    
        foreach($tags as $tag) {
            $plugin[$composerSlug][$tag] = [
                'name' => $composerSlug,
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
    
            $this->logOutput("Branch: " . $branch . " hash: " . $branchHash ."\n");
    
            $devBranch = 'dev-' . $branch;
            $plugin[$composerSlug][$devBranch] = [
                'name' => $composerSlug,
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
    
        $plugin['slug'] = $composerSlug;
    
        return $plugin;
    }
    
    private function generatePluginMetaData($tags, $path, $composerSlug)
    {

        $this->logOutput( "Generating Plugin Meta Data... " . $composerSlug . "\n");
        $this->logOutput( print_r($tags, true) . "\n");

        $count = count($tags);
        if ($count === 0) {
            $currentTag = 'master';
        } elseif ($count == 1) {
            $currentTag = $tags[0];
        } else {
            usort($tags, 'version_compare');
            $currentTag = end($tags);
        }
        $this->logOutput( "Current Tag: " . $currentTag . "\n");

        $process = new Process(['git', 'show', '-s', '--format=%ci', "$currentTag^{commit}"], $path);
        $process->run();

        // Check and handle if the process fails
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    
        // Return the output, trimming any whitespace at the end
        $lastPushed = trim($process->getOutput());

        $this->logOutput( "Last Pushed: " . $lastPushed . "\n");

        $pluginName = $this->getPluginName($path);

        $this->logOutput( "Plugin Name: " . $pluginName . "\n");

        $plugin['pts_meta'] = array(
            'name' => $pluginName,
            'currentVersion' => $currentTag,
            'lastPushed' => $lastPushed,
            'slug' => $composerSlug,
            'folder' => $path
        );

        // Get existing plugins data from database
        $existingData = get_option('pts_plugin_list_meta', []); // default to an empty array if option doesn't exist

        // Use the plugin's slug as a unique key and update its metadata
        $existingData[$composerSlug] = $plugin;

        // Save updated data back to the database
        update_option('pts_plugin_list_meta', $existingData);
    }
    
    private function getComposerPackageDetails($filePath)
    {
        $this->logOutput( "filePath... " . $filePath . "\n");

        if (($json = @file_get_contents($filePath . '/composer.json')) === false) {
            $error = error_get_last();
            $this->logOutput( "Unable to get package slug... " . $error['message']);
            return false;
        }
        
        // Decode the composer file.
        $json_data = json_decode($json, true);

        $this->logOutput( "Slug Name... " . $json_data['name'] . "\n");

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

    public function handlePluginUpdate($input, $composerSlug, $name, $version)
    {
        $temp_file_path = $this->targetDir . "/tempzip.zip"; // Create a temporary file

        // Check if the input is a valid URL
        if (filter_var($input, FILTER_VALIDATE_URL)) {
            // Fetching the plugin from the provided URL
            $response = wp_remote_get($input, array(
                'timeout'  => 300,
                'stream'   => true,  // Stream the response to the temporary file
                'filename' => $temp_file_path,
            ));
    
            if (is_wp_error($response)) {
                unlink($temp_file_path);  // Remove the temporary file
                throw new \Exception("Error fetching plugin from $input: " . $response->get_error_message());
            }
        } elseif (file_exists($input)) {
            // Handle the local file path
            if (!copy($input, $temp_file_path)) {
                throw new \Exception("Error copying local file from $input to $temp_file_path");
            }
            // Remove the original file after successful copy
            unlink($input);
        } else {
            throw new \Exception("The input is neither a valid URL nor a valid local file path.");
        }

        $pluginSlug = $this->getFolderNameFromZip($temp_file_path);

        if (!$this->isPackageSameAsFolderName($composerSlug, $pluginSlug)) {
            unlink($temp_file_path);  // Remove the temporary file
            //@todo: When this happens we want to push a notification into the UI. There are several different parts of the plugin that do background processing. Having a notification system would be nice. then when the user comes into the UI they can see different things that have happened, such as when a plugin update has come in, how it went etc. Right now I'm just checking the logs to see what happened.
            $this->logOutput("Plugin folder name does not match package name");
            $this->logOutput(print_r($composerSlug, true));
            $this->logOutput(print_r($pluginSlug, true));
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
            $longName = $this->getPluginName($temp_file_path);
            $this->logOutput("Long Name: " . $longName);

            // setup the new pluign.
            mkdir($localWorkTree, 0700, true);

            $composerJsonContent = $this->getComposerJson($temp_file_path, $pluginSlug);

            if ($composerJsonContent !== null) {
                
                $composerData = json_decode($composerJsonContent, true);

                if (isset($composerData['name'])) {
                    // Replace the 'name' property with the new value.
                    $composerData['name'] = $composerSlug;
                }
            
                $fileWriteResult = file_put_contents("$localWorkTree/composer.json", json_encode($composerData));

                if ($fileWriteResult === false) {
                    $this->logOutput("Failed to write composer to the file");
                } else {
                    $this->logOutput(" composer File written successfully");
                }
            } else {
                $this->logOutput("composer.json file does not exist in the zip");
                $this->create_composer_json($composerSlug, $name, 'wordpress-plugin', $localWorkTree);
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

            $this->logOutput("Remote URL: " . $gitHref);


            $process = new Process(['git', 'remote', 'add', 'origin', $gitHref], "$localWorkTree/");
            $process->run();
            $this->logOutput("done add origin");
            $this->logOutput(print_r($process->getOutput(), true));
            $this->logOutput(print_r($process->getErrorOutput(), true));


            $process = new Process(['git', 'push', '--set-upstream', 'origin', 'master'], "$localWorkTree/", ['GIT_USERNAME' => $this->username, 'GIT_PASSWORD' => $this->password]);
            $process->run();
            $this->logOutput("set upstream output:");
            $this->logOutput(print_r($process->getOutput(), true));
            $this->logOutput(print_r($process->getErrorOutput(), true));
            //git push -u origin master
            
            $process = new Process(['git', 'push', '-u', 'origin', 'master'], "$localWorkTree/", ['GIT_USERNAME' => $this->username, 'GIT_PASSWORD' => $this->password]);
            $process->run();
            $this->logOutput("push output");
            $this->logOutput(print_r($process->getOutput(), true));
            $this->logOutput(print_r($process->getErrorOutput(), true));

            mkdir($localGitDir, 0700, true);

            $process = new Process(['git', 'init', '--separate-git-dir', "$localGitDir/"], "$localWorkTree/");
            $process->run();
            $this->logOutput("Separate Git Dir:");
            $this->logOutput(print_r($process->getOutput(), true));
            $this->logOutput(print_r($process->getErrorOutput(), true));
            // keep the .git file somewhere else so that we are able to copy it back later.
            
            copy($localWorkTree . '/.git', $localGitDir.'/.gitBackup');
        }
        
        // first step is to copy the composer.json file
        $source = "$localWorkTree/composer.json";
        $destination = "$pluginWorkingDir/composer.json";
        
        if (!copy($source, $destination)) {
            $this->logOutput( "failed to copy $source...\n");
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
            $this->logOutput( 'extraction successful!');
        } else {
            $this->logOutput( 'failed to open zip!');
        }

        $source  = "$pluginWorkingDir/composer.json";
        $destination = "$localWorkTree/composer.json";

        // next we put back the composer.json file.
        if (!copy($source, $destination)) {
            $this->logOutput( "failed to copy $source...\n");
        }

        // next we copy the .git file back into the plugin folder.
        $source = "$localGitDir/.gitBackup";
        $destination =  "$localWorkTree/.git";
        
        if (!copy($source, $destination)) {
            $this->logOutput( "failed to copy $source...\n");
        }

        // next we remove the temp zip file.
        if (file_exists($temp_file_path)) {
            unlink($temp_file_path);
        }

        // get the plugin version from the composer.json file.
        $pluginVersion = $this->get_plugin_version("$localWorkTree");

        if ($pluginVersion != $version) {
            $this->logOutput("Plugin version does not match package version");
            $this->logOutput(print_r($pluginVersion, true));
            $this->logOutput(print_r($version, true));
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
        $this->logOutput("Git Tag");
        $this->logOutput(print_r($process->getOutput(), true));
        $this->logOutput(print_r($process->getErrorOutput(), true));

        $process = new Process(['git', 'push', 'origin', $version], "$localWorkTree/", ['GIT_USERNAME' => $this->username, 'GIT_PASSWORD' => $this->password]);
        $process->run();
        $this->logOutput("Push Tag");
        $this->logOutput(print_r($process->getOutput(), true));
        $this->logOutput(print_r($process->getErrorOutput(), true));
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
        //$pluginName = $this->extractPluginData(file_get_contents($file), "Plugin Name:");

        foreach (glob($plugin_path.'/*.php') as $file) {
            $version = $this->extractPluginData(file_get_contents($file), "Version: ");
            if ($version) {
                return $version;
            }
        }
        return false;
    }

    private function isPackageSameAsFolderName($composerSlug, $folderName)
    {

        if ($composerSlug == $folderName) {
            return true;
        }

        // Split the slug into parts
        $parts = explode('/', $composerSlug);
        
        // If slug is not in 'vendor/package' format, return false
        if (count($parts) < 2) {
            return false;
        }
        
        // Get the package name
        $packageName = $parts[1];
    
        // Compare the package name with the folder name
        return $packageName == $folderName;
    }

    public function create_composer_json($composerSlug, $name, $type, $path)
    {
        $data = array(
            "name" => $composerSlug,
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
        $this->logOutput("auth: " . $auth);
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
    
    private function getPluginName($path)
    {
        // Check if the path is a ZIP file
        if (pathinfo($path, PATHINFO_EXTENSION) == 'zip') {
            $zip = new \ZipArchive;
            $res = $zip->open($path);
            
            if ($res !== true) {
                throw new \Exception("Cannot open the ZIP file.");
            }
    
            // Go through each file in the ZIP archive
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                // If the file is in the root of the ZIP and has a .php extension, check its content for the plugin name
                if (substr_count($filename, '/') == 1 && pathinfo($filename, PATHINFO_EXTENSION) == 'php') {
                    $pluginName = $this->extractPluginData($zip->getFromIndex($i), "Plugin Name: ");
                    if ($pluginName) {
                        $zip->close();
                        return $pluginName;
                    }
                }
            }
            $zip->close();
        } else {
            // If not a ZIP file, then consider it as a directory and read the PHP files directly
            foreach (glob($path.'/*.php') as $file) {
                $pluginName = $this->extractPluginData(file_get_contents($file), "Plugin Name: ");
                if ($pluginName) {
                    return $pluginName;
                }
            }
        }
    
        // Return false if no plugin name is found
        return false;
    }
    
    private function extractPluginData($fileContent, $matchedString)
    {
        if (preg_match('/\* '.$matchedString.'\s*(.+)/', $fileContent, $matches)) {
            return trim($matches[1]);
        }
        return false;
    }

}
