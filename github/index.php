<?php
error_reporting(E_ALL);
date_default_timezone_set('America/Los_Angeles');
$headers = apache_request_headers();

define('GITHUB_SECRET', "jrGVzvKIFuB2s7pg26LRSYcuhYMlvlD5");

function is_dir_empty($dir)
{
    if (!is_readable($dir)) return null;
    $handle = opendir($dir);
    while (false !== ($entry = readdir($handle))) {
        if ($entry !== '.' && $entry !== '..') { // <-- better use strict comparison here
            return false;
        }
    }
    closedir($handle); // <-- always clean up! Close the directory stream
    return true;
}

class Log{
    private $recusive = false;
    public function r($bool=true){
        $this->recusive = !$bool;

        return $this;
    }
    public function info(){
        $args = func_get_args();
        if($this->recusive){
            foreach($args as $arg){
                if(is_object($arg) || is_array($arg)){
                    echo '<pre>';
                    print_r($arg);
                    echo '<pre>';
                }else{
                    echo $arg;
                }
            }
            echo "\n";
        }else{
            echo join(" ", $args) . "\n";
        }

        return $this;
    }

    static function error(){

    }
}

$log = new Log();

$repo_map = array(
    'sbdev' => array(
        'dev' => array(
            'dir' => '/var/www/dev.sbdev/',
            'clone_url' => 'git@github.com:scbowler/sbdev.git',
            'remote' => 'origin',
        ),
        'master' => array(
            'dir' => '/var/www/sbdev/',
            'clone_url' => 'git@github.com:scbowler/sbdev.git',
            'remote' => 'origin',
            'hooks'=>array(
                'pre'=>function($payload){
                    $base = $payload['pull_request']['base']['ref'];
                    $head = $payload['pull_request']['head']['ref'];
                    $action = $payload['action'];

                    if($action != 'closed'){
                        return false;
                    }elseif($base != 'master' || $head != 'dev'){
                        return false;
                    }else{
                        return true;
                    }
                }
            )
        )
    )
);

if (!isset($_POST['payload'])) {
    $log->info('Payload doesnt exist try to change content content-type');
    exit;
}

$raw_payload = file_get_contents('php://input');

// Calculate hash based on payload and the secret
$payloadHash = hash_hmac('sha1', $raw_payload, GITHUB_SECRET);

// security check -  validate the payload based on the secret
if ($headers['X-Hub-Signature'] != 'sha1=' . $payloadHash) {
    $log->info('Error Validating payload with provided secret');
    exit;
} else {
    $log->info('Payload has been validated');
}


$payload = json_decode($_POST['payload'], TRUE);

//get pull request base branch

//check if payload has a pull request
if (!isset($payload['pull_request'])) {
    $log->r(true)->info(array('success' => false, 'data' => 'Payload not supported.' . __LINE__))->r(false);
    exit;
}

$base_branch = $payload['pull_request']['base']['ref'];
$git_repo_name = $payload['repository']['name'];

$log->info($git_repo_name);

//check if payload has references in repo map
if(!isset($repo_map[$git_repo_name]) && !isset($repo_map[$git_repo_name][$base_branch])){
    $log->info("Payload info doesnt match repo map");
    exit;
}

$actionObj = $repo_map[$git_repo_name][$base_branch];

//check for hooks
$hooks = (isset($actionObj['hooks']))?$actionObj['hooks']:false;

//check that request is a pull request
// check that repo name is an accepted repo to handle
if ($headers['X-GitHub-Event'] == 'pull_request'
    && $payload['action'] === 'closed'
    && isset($repo_map[$git_repo_name])
) {
//used for push requests

    if($hooks && isset($hooks['pre'])){
        $log->info("Hook has been defined");
        if(!$hooks['pre']($payload)){
            $log->info("Error passing pre hook validation");
            exit;
        }
    }

    $branch = $base_branch;
    $dir = $actionObj['dir'];
    $clone_url = $actionObj['clone_url'];
    $remote = $actionObj['remote'];

    try {
        //change the directory from the repo map
        chdir($dir);

        if (is_writable($dir)) {
            $log->info($dir, ' is writeable');
            if (is_dir_empty($dir)) {
                $log->info($dir, ' is empty');
                //directory is empty we should clone down the repo
                exec(sprintf('git clone %s %s', $clone_url, $dir), $output, $r);
            }
            //check current branch by returning branches from the command line into $branches variable
            exec('git branch', $branches);
            $log->r()->info('current branch : ', $branches);
            $on_correct_branch = false;
            foreach ($branches as $val) {
                if (strpos($val, $branch) !== false
                    && strpos($val, "*") !== false
                ) {
                    $on_correct_branch = true;
                    break;
                }
            }
            if (!$on_correct_branch) {
                $log->info('not on the correct branch attempt to checkout ' . $branch);
                //current branch isn't the correct branch
                exec('git checkout master', $output, $r);
                log("checkout master branch output :", $output);
                exec(sprintf('git branch -D %s', $branch));
                exec(sprintf('git checkout -b %s %s/%s', $branch, $remote, $branch), $output, $r);
                $log->r()->info("output for checkout branch : ", $branch, " : ", $output);
            }

            $log->info('pulling latest changes into ' . $branch);
            //pull latest changes
            exec('git pull', $output, $r);

            //change premissions on the .git directory
            exec('chmod -R 0770 .git');
        } else {
            $log->r(false)->info($dir, ' is not writeable');
        }


        $log->r(false)->info("output : ", $output);
        $log->info("output : ", $r);
    } catch (Exception $e) {
        $log->info($e->getMessage());
    }
} else {
    $log->info(sprintf('Either the request wasnt a pull request (%s) or repository (%s) that made this call isnt defined in the repo map', $headers['X-GitHub-Event'], $git_repo_name));
}

//exit;
//
//class Deploy
//{
//
//    /**
//     * A callback function to call after the deploy has finished.
//     *
//     * @var callback
//     */
//    public $post_deploy;
//
//    /**
//     * The name of the file that will be used for logging deployments. Set to
//     * FALSE to disable logging.
//     *
//     * @var string
//     */
//    private $_log = 'deployments.log';
//
//    /**
//     * The timestamp format used for logging.
//     *
//     * @link    http://www.php.net/manual/en/function.date.php
//     * @var     string
//     */
//    private $_date_format = 'Y-m-d H:i:sP';
//
//    /**
//     * The name of the branch to pull from.
//     *
//     * @var string
//     */
//    private $_branch = 'eric-test';
//
//    /**
//     * The name of the remote to pull from.
//     *
//     * @var string
//     */
//    private $_remote = 'origin';
//
//    /**
//     * The directory where your website and git repository are located, can be
//     * a relative or absolute path
//     *
//     * @var string
//     */
//    private $_directory;
//
//    /**
//     * Sets up defaults.
//     *
//     * @param  string $directory Directory where your website is located
//     * @param  array $data Information about the deployment
//     */
//    public function __construct($directory, $options = array())
//    {
//        // Determine the directory path
//        $this->_directory = realpath($directory) . DIRECTORY_SEPARATOR;
//
//        $available_options = array('log', 'date_format', 'branch', 'remote');
//
//        foreach ($options as $option => $value) {
//            if (in_array($option, $available_options)) {
//                $this->{'_' . $option} = $value;
//            }
//        }
//        $this->_log = getcwd() . '/' . $this->_log;
//        $this->log('Attempting deployment...');
//    }
//
//    /**
//     * Writes a message to the log file.
//     *
//     * @param  string $message The message to write
//     * @param  string $type The type of log message (e.g. INFO, DEBUG, ERROR, etc.)
//     */
//    public function log($message, $type = 'INFO')
//    {
//        if ($this->_log) {
//            // Set the name of the log file
//            $filename = $this->_log;
//
//            if (!file_exists($filename)) {
//                // Create the log file
//                file_put_contents($filename, '');
//
//                // Allow anyone to write to log files
//                chmod($filename, 0666);
//            }
//
//            if (is_writable($filename)) {
//                file_put_contents($filename, date($this->_date_format) . ' --- ' . $type . ': ' . $message . PHP_EOL, FILE_APPEND);
//            } else {
//                echo 'Cant write log to file';
//            }
//
//            // Write the message into the log file
//            // Format: time --- type: message
//
//        }
//    }
//
//    /**
//     * Executes the necessary commands to deploy the website.
//     */
//    public function execute()
//    {
//        try {
//            // Make sure we're in the right directory
//            exec('cd ' . $this->_directory, $output);
//            chdir($this->_directory);
//            $this->log('Changing working directory... ' . implode(' ', $output));
//            $this->log('cd ' . $this->_directory);
//
//            // Discard any changes to tracked files since our last deploy
//            exec('git reset --hard HEAD', $output);
//            $this->log('Reseting repository... ' . implode(' ', $output));
//            $this->log('git reset --hard HEAD');
//
//            // Update the local repository
//            exec('git pull ' . $this->_remote . ' ' . $this->_branch, $output);
//            $this->log('Pulling in changes... ' . implode(' ', $output));
//            $this->log('git pull ' . $this->_remote . ' ' . $this->_branch);
//
//            // Secure the .git directory
//            exec('chmod -R og-rx .git');
//            $this->log('Securing .git directory... ');
//            $this->log('chmod -R og-rx .git');
//
//            if (is_callable($this->post_deploy)) {
//                call_user_func($this->post_deploy, $this->_data);
//            }
//            $this->log('Deployment successful.');
//        } catch (Exception $e) {
//            $this->log($e->getMessage(), 'ERROR');
//        }
//    }
//
//}
//
//$repo_map = array(
//    'website' => '/opt/sandbox/dev.learningfuze.com/',
//);
//
//$git_data = json_decode($_POST['payload'], TRUE);
//
//$git_reponame = $git_data['repository']['name'];
//
//if (isset($repo_map[$git_reponame])) {
//    $deploy = new Deploy($repo_map[$git_reponame]);
//    $deploy->post_deploy = function () use ($deploy) {
//        global $git_data, $git_reponame;
//        echo 'inside post deploy';
//
//        try {
//            ob_start();
//            print_r($git_data['commits']);
//            $body = ob_get_clean();
//            $this->log($body);
//            mail('eric.johnson@learningfuze.com', 'Push from ' . $git_reponame, $body);
//        } catch (Exception $e) {
//            echo '<pre>';
//            print_r($e->getMessage());
//            echo '</pre>';
//        }
//
//    };
//
//    $deploy->execute();
//}