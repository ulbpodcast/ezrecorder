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

$logger->log(EventType::RECORDER_UPLOAD_TO_EZCAST, LogLevel::DEBUG, __FILE__ . " called with args: $asset", array("cli_upload_finished"), $asset);

$ok = true;

if ($cam_enabled) {
    $fct = 'capture_' . $cam_module . '_finalize';
    $res_cam = $fct($asset);
    if(!$res_cam) {
        $logger->log(EventType::RECORDER_UPLOAD_TO_EZCAST, LogLevel::ERROR, "Cam finalization for module $cam_module failed", array("cli_upload_finished"), $asset);
        $ok = false;
    }
}

if ($slide_enabled) {
    $fct = 'capture_' . $slide_module . '_finalize';
    $res_slide = $fct($asset);
    if(!$res_slide) {
        $logger->log(EventType::RECORDER_UPLOAD_TO_EZCAST, LogLevel::ERROR, "Slide finalization for module $slide_module failed", array("cli_upload_finished"), $asset);
        $ok = false;
    }
}

if(!$ok)
    exit(2);

$upload_to_server_dir = get_asset_dir($asset);
$upload_ok_dir = get_asset_dir($asset, 'upload_ok');
$ok = rename("$upload_to_server_dir", "$upload_ok_dir");
if(!$ok) {
    $logger->log(EventType::RECORDER_UPLOAD_TO_EZCAST, LogLevel::ERROR, "Could not move asset folder to upload_ok dir. ($upload_to_server_dir -> $upload_ok_dir) ", array("cli_upload_finished"), $asset);
    exit(3);
}

exit(0);