<?php

/*
 * This is a CLI script that finalizes the recording process 
 * for the enabled modules.
 * Called directly from ezcast sever, executed as $recorder_user (ezcast config)
 */

if($argc != 2) {
    echo "Wrong arg count";
    exit(1);
}

require_once 'global_config.inc';
require_once 'lib_various.php';

include_once $cam_lib;
include_once $slide_lib;

Logger::$print_logs = true;

global $service;
$service = true;

if($argc != 2) {
    echo "Wrong arg count";
    exit(1);
}
$asset = $argv[1];

$logger->log(EventType::RECORDER_UPLOAD_TO_EZCAST, LogLevel::DEBUG, __FILE__ . " called with args: $asset", array(__FILE__), $asset);

$ok = true;

if ($cam_enabled) {
    $fct = 'capture_' . $cam_module . '_finalize';
    $res_cam = $fct($asset);
    if(!$res_cam) {
        $logger->log(EventType::RECORDER_UPLOAD_TO_EZCAST, LogLevel::ERROR, "Cam finalization for module $cam_module failed", array(__FILE__), $asset);
        $ok = false;
    }
}

if ($slide_enabled) {
    $fct = 'capture_' . $slide_module . '_finalize';
    $res_slide = $fct($asset);
    if(!$res_slide) {
        $logger->log(EventType::RECORDER_UPLOAD_TO_EZCAST, LogLevel::ERROR, "Slide finalization for module $slide_module failed", array(__FILE__), $asset);
        $ok = false;
    }
}

if(!$ok)
    exit(2);

//move asset folder from upload_to_server to upload_ok dir
$ok = move_asset($asset, "upload_ok", true);
if(!$ok) {
    $logger->log(EventType::RECORDER_UPLOAD_TO_EZCAST, LogLevel::CRITICAL, "Could not move asset folder from upload_to_server to upload_ok dir (failed on local or on remote)", array(__FILE__), $asset);
    exit(3);
}
$asset_dir = get_asset_dir($asset, 'upload'); //update asset location for the remaining of the script

exit(0);