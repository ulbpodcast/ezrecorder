<?php
/*
 * This is a CLI script that launches the local processing of the recordings 
 * By default, the data about the record to process is retrieved from the session module.
 * Alternatively, you can provide
 * Usage: cli_post_process.php [asset_name]
 * 
 */

global $service;
$service = true;

require_once 'global_config.inc';

require_once $cam_lib;
if($slide_enabled)
    require_once $slide_lib;
require_once $session_lib;
require_once 'lib_error.php';
require_once 'lib_various.php';
require_once 'lib_model.php';

Logger::$print_logs = true;

$asset = '';
$meta_assoc = false;
if(isset($argv[1]))
{
    $asset = $argv[1];
} else {
    //get session metadata to find last course
    $fct = "session_" . $session_module . "_metadata_get";
    $meta_assoc = $fct();
    if($meta_assoc == false) {
        $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::CRITICAL, "Could not get session metadata file, cannot continue", array("cli_post_process"));
        exit(1);
    }

    $record_date = $meta_assoc['record_date'];
    $course_name = $meta_assoc['course_name'];
    //$record_type = $meta_assoc['record_type'];

    $asset = get_asset_name($course_name, $record_date);
}

$asset_dir = get_asset_dir($asset);
if(!$asset_dir) {
    $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::CRITICAL, "Could not get asset directory for asset '$asset'. Is asset already sent to server?", array("cli_post_process"), $asset);
    exit(1);
}

$metadata_file = "$asset_dir/metadata.xml";
if (!file_exists($metadata_file)) {
    $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::CRITICAL, "Could not get asset metadata file from dir: $asset_dir, cannot continue", array("cli_post_process"), $asset);
    echo "Error: metadata file $metadata_file does not exist" . PHP_EOL;
    exit(1);
}

// Stopping and releasing the recorder

$logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::INFO, "Started videos post processing", array("cli_post_process"), $asset);

// if cam module is enabled
$cam_pid = 0;
if ($cam_enabled) {
    $fct = 'capture_' . $cam_module . '_process';
    $success = $fct($asset, $cam_pid);
    if(!$success) {
        $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::ERROR, "Cam post processing start failed, disabling camera.", array("cli_post_process"), $asset);
        $cam_enabled = false;
        $cam_pid = 0;
    } else if ($cam_pid == 0) {
        $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::WARNING, "Cam process was successfully started but did not provided a pid", array("cli_post_process"), $asset);
    }
}

// if slide module is enabled
$slide_pid = 0;
if ($slide_enabled) {
    $fct = 'capture_' . $slide_module . '_process';
    $success = $fct($asset, $slide_pid);
    if(!$success) {
        $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::ERROR, "Slide post processed start failed, disabling slides.", array("cli_post_process"), $asset);
        $slide_enabled = false;
        $slide_pid = 0;
    } else if ($slide_pid == 0) {
        $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::WARNING, "Slide process was successfully started but did not provided a pid", array("cli_post_process"), $asset);
    }
    
    if(!is_process_running($slide_pid)) {
        $logger->log(EventType::TEST, LogLevel::ERROR, "!! Slides processing ($slide_pid) NOT running at this point ", array("cli_post_process"), $asset);
    }
}

if(!$cam_pid && !$slide_pid) {
    $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::CRITICAL, "Both cam and slides post processing failed or disabled, stopping now.", array("cli_post_process"), $asset);
    exit(2);
}

// wait for local processing to finish
while ( ($cam_pid && is_process_running($cam_pid)) || ($slide_pid && is_process_running($slide_pid)) ) {
    sleep(0.5);
}

$cam_ok = true;
$slide_ok = true;
//check result
if ($cam_enabled) {
    $fct = 'capture_' . $cam_module . '_process_result';
    if(function_exists($fct)) { //all modules do not implement this yet
        $success = $fct($asset);
        if(!$success) {
            $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::ERROR, "Cam post processing failed or result not found. Continue anyway.", array("cli_post_process"), $asset);
            $cam_ok = false;
        } else {
            $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::DEBUG, "Cam post process function $fct returned $success.", array("cli_post_process"), $asset);
        }
    } else {
        $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::WARNING, "Cam module has not process_result function ($fct). Cannot check post process result for cam.", array("cli_post_process"), $asset);
    }
}

if ($slide_enabled) {
    $fct = 'capture_' . $slide_module . '_process_result';
    if(function_exists($fct)) { //all modules do not implement this yet
        $success = $fct($asset);
        if(!$success) {
            $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::ERROR, "Slides post processing failed or result not found. Continue anyway.", array("cli_post_process"), $asset);
            $slide_ok = false;
        }
    } else {
        $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::WARNING, "Slides module has not process_result function ($fct). Cannot check post process result for slide.", array("cli_post_process"), $asset);
    }
}

if($slide_ok && $cam_ok) {
    $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::INFO, "Finished successfully videos post processing.", array("cli_post_process"), $asset);
} else if (!(($slide_enabled && $slide_ok) || ($cam_enabled && $cam_ok))) {
    $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::CRITICAL, "Post processing: Both cam and slides are either disabled or failed (cam: ".($cam_ok ?'1':'0').", slide: ".($slide_ok ?'1':'0').") (1 = ok)", array("cli_post_process"), $asset);
    exit(1);
} else {
    $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::ERROR, "At least one module failed video post processing (cam: ".($cam_ok ?'1':'0').", slide: ".($slide_ok ?'1':'0').") (1 = ok). Trying to continue anyway.", array("cli_post_process"), $asset);
}
system("echo \"`date` : local processing finished for both cam and slide modules\" >> $basedir/var/finish");

//move asset folder from local_processing to upload_to_server dir
$upload_dir = get_asset_dir($asset, 'upload');
if($asset_dir != $upload_dir){
    $ok = rename($asset_dir, $upload_dir);
    if(!$ok) {
        $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::CRITICAL, "Could not move asset folder from local_processing to upload dir. ($asset_dir to $upload_dir)", array("cli_post_process"), $asset);
        // exit(1); //Commented for now: Before last ffmpeg modules updates, modules moved the asset themselves at the processing end, so this move may fail if we use older modules.
    }
    $asset_dir = get_asset_dir($asset, 'upload'); //update asset location for the remaining of the script
}


//start upload
global $cli_upload;
global $php_cli_cmd;


$logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::DEBUG, "Call to $cli_upload", array("cli_post_process"), $asset);

$return_val = 0;
$cmd = "$php_cli_cmd $cli_upload $asset > $asset_dir/upload.log";
system($cmd, $return_val);
if($return_val != 0) {
    $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::ERROR, "Could not start upload ($cli_upload), cli returned $return_val. Command: $cmd", array("cli_post_process"), $asset);
    exit(1);
}

echo "OK";

exit(0);