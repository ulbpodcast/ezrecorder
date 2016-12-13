<?php

require_once __DIR__.'/config.inc';
require_once __DIR__.'/lib_tools.php';
require_once __DIR__.'/../../../global_config.inc';

if($argc < 2) {
    $logger->log(EventType::TEST, LogLevel::ERROR, "Invalid parameters count", array(basename(__FILENAME__)), $asset_name);
    echo "Usage: ". $argv[0] . " <get_id> [params]" . PHP_EOL;
    exit(1);
}

$get_id = $argv[1];
//next argv's are parameters

switch($get_id) {
    case "process_result":
        $asset = $argv[2];
        return 0;
    default:
        $logger->log(EventType::TEST, LogLevel::ERROR, "Unknown get_id value $get_id", array(basename(__FILENAME__)), $asset_name);
        exit(2);
}