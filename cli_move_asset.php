<?php
/*
 * Move asset to another processing folder 
 */

require_once __DIR__.'/global_config.inc';
require_once __DIR__.'/lib_various.php';

global $service;
$service = true;

Logger::$print_logs = true;

if ($argc != 3) {
    echo 'Usage: cli_move_asset.php <asset_name> <target_folder>' . PHP_EOL .
         'Target folder can be "local_processing", "upload_to_server" or "upload_ok"' . PHP_EOL;
    exit(1);
}

$asset = $argv[1];
$target = $argv[2];

$ok = move_asset($asset, $target, false);
        
exit($ok ? 0 : 1);