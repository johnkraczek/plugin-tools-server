<?php

namespace PluginToolsServer\Services;

use Symfony\Component\Process\Process;
use Bit3\GitPhp\GitRepository;

class BitbucketManager
{
    private $username;
    private $password;
    private $workspace;
    private $targetDir;

    public function __construct($username, $password, $workspace, $targetDir)
    {
        $this->username = $username;
        $this->password = $password;
        $this->workspace = $workspace; // workspace slug from bitbucket
        $this->targetDir = $targetDir; // parent directory for all of the repos
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
                    echo "Fetching " . $repository['name'] . "...\n";
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
        file_put_contents( $this->targetDir."/packages.json", json_encode($packageoutput));
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
        $helper = '!f() { sleep 0.1; echo "username=${GIT_USERNAME}"; echo "password=${GIT_PASSWORD}"; }; f';
        $process = new Process(['git', 'config', '--global', 'credential.helper', $helper], $bareGit);
        $process->run();

        // we should set this in the interface.
        $process = new Process(['git', 'config', '--global', 'user.email', "john@kraczek.com"], $bareGit);
        $process->run();

        $process = new Process(['git', 'config', '--global', 'user.name', "John Kraczek"], $bareGit);
        $process->run();

        // maybe clean up the bare repo?:
        // $process = new Process(['rm', '-rf', $bareGit], $bareGit);
        // $process->run();
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
}
