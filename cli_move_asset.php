<?php
/*
 * Move asset to another processing folder 
 */

require_once __DIR__.'/global_config.inc';
require_once __DIR__.'/lib_various.php';

global $service;
$service = true;

Logger::$print_logs = true;

if ($argc < 3 ||$argc >4 ) {
    echo 'Usage: cli_move_asset.php <asset_name> <target_folder> [remote]' . PHP_EOL .
         ' finds the asset in the usual directories and move it where specified'.PHP_EOL .'Target folder can be "local_processing", "upload_to_server" or "upload_ok"'. PHP_EOL.'remote option moves the remote asset as well' . PHP_EOL."example: php cli_move_asset.php 2017_10_12_12h36_PODC-I-001 local_processing remote".PHP_EOL;
    exit(1);
}

$asset = $argv[1];
$target = $argv[2];
if($argc==4 && $argv[3]=="remote")
  $remote=true;
 else
  $remote=false;   
 
$ok = move_asset($asset, $target, $remote);
        
exit($ok ? 0 : 1);