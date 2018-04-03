#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use Spatie\ImageOptimizer\OptimizerChainFactory;

$download_files = $process_files = $upload_files = FALSE;
$env = $site = $destination = $ssh_key = NULL;

// Configure the necessary parameters for each option of the command.
configure_parameters($download_files, $process_files, $upload_files, $env, $site, $destination, $ssh_key);

// Executes the configured processes.
execute($download_files, $process_files, $upload_files, $env, $site, $destination, $ssh_key);

function execute($download_files, $process_files, $upload_files, $env, $site, $destination, $ssh_key) {
  if (!$download_files && !$process_files && !$upload_files) {
    // Prevent the execution if no parameters are configured.
    return;
  }

  $started = time();
  println('Starting execution at ' . date('l jS \of F Y h:i:s A'), 1);
  if ($download_files) {
    download_files($env, $site, $destination, $ssh_key);
    println('Finished download at ' . date('l jS \of F Y h:i:s A'), 1);
  }

  if ($process_files) {
    $counter = process_files($destination);
    println('Finished ' . $counter . ' optimizations at ' . date('l jS \of F Y h:i:s A'), 1);
  }

  if ($upload_files) {
    println('Uploading files');
    upload_files($env, $site, $destination, $ssh_key);
  }
  $finished = time();
  println('Total time: ' . ($finished - $started) . ' seconds.', 1);
  println('Finished execution at ' . date('l jS \of F Y h:i:s A'), 1);
  println('');
}

function process_files($folder, $counter = 0) {
  $full_path = build_full_path($folder);
  foreach (scandir($folder) as $file) {
    if (in_array($file, ['.', '..'])) {
      continue;
    }

    println('Processing file ' . path_cleanup($full_path . $file), 2);

    if (is_dir($file)) {
      // Recursive process.
      $counter = process_files(path_cleanup($folder . '/' .$file), $counter);
    }
    else {
      optimize_file(path_cleanup($full_path . $file));
      $counter++;
    }
  }

  return $counter;
}

function optimize_file($file) {
  $optimizer = OptimizerChainFactory::create();

  try {
    $optimizer->optimize($file);
  }
  catch (Exception $e) {
    // Always log errors.
    println($e->getMessage(), 0);
  }
}

function download_files($env, $site, $destination, $ssh_key) {
  // See https://pantheon.io/docs/rsync-and-sftp/#rsync
  $download_command = "rsync -rLvz --size-only --ipv4 --progress -e '"
    // Create the known_hosts files on /dev/null to execute command without prompt questions.
    . "ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null "
    // Specify the SSH key to be used.
    . "-i $ssh_key "
    . "-p 2222'"
    // Ensure only image files are downloaded.
    . " --prune-empty-dirs --include \"*/\" --include=\"*.jpg\" --include=\"*.jpeg\" --include=\"*.png\" --include=\"*.gif\" --exclude=\"*\""
    . " $env.$site@appserver.$env.$site.drush.in:files/ ./$destination";

  execute_command($download_command);
}

function upload_files($env, $site, $destination, $ssh_key) {
  // See https://pantheon.io/docs/rsync-and-sftp/#rsync
  $upload_command = "rsync -rLvz --size-only --ipv4 --progress -e '"
    // Create the known_hosts files on /dev/null to execute command without prompt questions.
    . "ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null "
    // Specify the SSH key to be used.
    . "-i $ssh_key "
    . "-p 2222' ./$destination/. --temp-dir=~/tmp/ $env.$site@appserver.$env.$site.drush.in:files/";

  execute_command($upload_command);
}

function build_full_path($path) {
  return shell_exec('pwd') . '/' . $path . '/';
}

function path_cleanup($path) {
  return str_replace("\n", '', $path);
}

function execute_command($command) {
  $output = [];
  $status = NULL;
  exec($command, $output, $status);
}

function get_option($config, $option) {
  if (isset($options[$opt])) {
    return $options[$opt];
  }

  println("The parameter $option was not specified.");
}

function println($message, $msg_level = 1) {
  $longopt = [
    // Verbose output, for all the info.
    'verbose',
    // Silence all output.
    'quiet',
  ];

  $config = getopt('', $longopt);
  $log_level = 1;
  if (isset($config['quiet'])) {
    $log_level = 0;
  }
  elseif (isset($config['verbose'])) {
    $log_level = 2;
  }

  if ($log_level >= $msg_level) {
    $color = "\033[0m";
    if ($msg_level = 0) {
      $color = "\033[31m" . "[Error]\033[0m";
    }
    print "\n$color$message";
  }
}

function configure_parameters(&$download_files, &$process_files, &$upload_files, &$env, &$site, &$destination, &$ssh_key) {
  $longopt = [
    // Pantheon environment. Usually dev, test, or live.
    'env:',
    // Site UUID from dashboard URL: https://dashboard.pantheon.io/sites/<UUID>
    'site:',
    // Local folder where the images will be stored.
    'destination:',
    // Path to the SSH key used for identification on Pantheon.
    'ssh:',
    // Downloads the files from the Pantheon environment.
    'download::',
    // Executes the optimization on the downloaded files.
    'process',
    // Reuploads the images that were downloaded.
    'upload',
  ];

  $config = getopt('', $longopt);

  $download_files = isset($config['download']) ? TRUE : FALSE;
  $process_files = isset($config['process']) ? TRUE : FALSE;
  $upload_files = isset($config['upload']) ? TRUE : FALSE;

  if ($download_files || $upload_files) {
    $env = $config['env'];
    $site = $config['site'];
    $destination = $config['destination'];
    $ssh_key = $config['ssh'];
  }
  elseif ($process_files) {
    $destination = $config['destination'];
  }
}
