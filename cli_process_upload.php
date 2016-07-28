<?php

/*
 * This is a CLI script that launches the local processing of the recordings 
 * and sends a request to podman to download the recordings
 * Usage: cli_process_upload.php
 * 
 */

require_once 'global_config.inc';

require_once $cam_lib;
require_once $slide_lib;
require_once $session_lib;
require_once 'lib_error.php';
require_once 'lib_various.php';

//get session metadata to find last course
$fct = "session_" . $session_module . "_metadata_get";
$meta_assoc = $fct();

if($meta_assoc == false) {
    $logger->log(EventType::UPLOAD_WRONG_METADATA, LogLevel::CRITICAL, "Could not get session metadata file, cannot continue", array("cli_process_upload"));
    return 1;
}

$record_date = $meta_assoc['record_date'];
$course_name = $meta_assoc['course_name'];
$record_type = $meta_assoc['record_type'];

$asset = $record_date . '_' . $course_name;
$tmp_dir = "$basedir/var/$asset/";
if (!file_exists($tmp_dir . "/metadata.xml")) {
    $logger->log(EventType::UPLOAD_WRONG_METADATA, LogLevel::CRITICAL, "Could not get temporary asset metadata file, cannot continue", array("cli_process_upload"), $asset);
    echo "Error: metadata file $basedir/var/$asset/metadata.xml does not exist" . PHP_EOL;
    //  die;
}

$fct = "session_" . $session_module . "_metadata_delete";
//debug UNCOMMENT THIS //$fct();

// Stopping and releasing the recorder

$logger->log(EventType::CAPTURE_POST_PROCESSING, LogLevel::INFO, "Started videos post processing", array("cli_process_upload"), $asset);
// if cam module is enabled
$cam_pid = 0;
if ($cam_enabled) {
    $fct = 'capture_' . $cam_module . '_process';
    $fct($meta_assoc, $cam_pid);
}
// if slide module is enabled
$slide_pid = 0;
if ($slide_enabled) {
    $fct = 'capture_' . $slide_module . '_process';
    $fct($meta_assoc, $slide_pid);
}

// wait for local processing to finish
while ($cam_pid && is_process_running($cam_pid) || $slide_pid && is_process_running($slide_pid)) {
    sleep(0.5);
}

$logger->log(EventType::CAPTURE_POST_PROCESSING, LogLevel::INFO, "Finished videos post processing", array("cli_process_upload"), $asset);

system("echo \"`date` : local processing finished for both cam and slide modules\" >> $basedir/var/finish");


////call EZcast server and tell it a recording is ready to download

$nb_retry = 500;

$cam_download_info = null;
if ($cam_enabled) {
    // get downloading information required by EZcast server
    $fct = 'capture_' . $cam_module . '_info_get';
    $cam_download_info = $fct('download', $asset);
}

$slide_download_info = null;
if ($slide_enabled) {
    // get downloading information required by EZcast server
    $fct = 'capture_' . $slide_module . '_info_get';
    $slide_download_info = $fct('download', $asset);
}

$logger->log(EventType::UPLOAD_TO_EZCAST, LogLevel::INFO, "Starting upload to ezcast", array("cli_process_upload"), $asset);

//try repeatedly to call EZcast server and send the right post parameters
$err = true;
while ($err && $nb_retry > 0) {
    $err = server_start_download($record_type, $record_date, $course_name, $cam_download_info, $slide_download_info);
    if ($err) {
        $logger->log(EventType::UPLOAD_TO_EZCAST, LogLevel::ERROR, "Upload (curl) failed, will retry later.", array("cli_process_upload"), $asset);
        log_append('EZcast_curl_call', "Will retry later: Error connecting to EZcast server ($ezcast_submit_url). curl error: $err \n");
        sleep(120);
    }//endif error
    $nb_retry--;
}//end while

if ($err) {
    $logger->log(EventType::UPLOAD_TO_EZCAST, LogLevel::CRITICAL, "Upload failed $nb_retry times, giving up", array("cli_process_upload"), $asset);
    log_append('EZcast_curl_call', "Giving up: Error connecting to EZcast server ($ezcast_submit_url). curl error: $err \n");
    sleep(120);
}

/**
 *
 * @param <slide|cam|camslide> $recording_type
 * @param <YYYY_MM_DD_HHhmm> $recording_date
 * @param <mnemonique> $course_name
 * @param <associative_array> $cam_download_info information relative to the downloading of cam movie
 * @param <associative_array> $slide_download_info information relative to the downloading of slide movie
 * @return false|error_code
 */
function server_start_download($record_type, $record_date, $course_name, $cam_download_info, $slide_download_info) {
    global $logger;
    
    //tells the server that a recording is ready to be downloaded
    global $ezcast_submit_url;
    global $tmp_dir;
    global $recorder_version;
    global $php_cli_cmd;

    $post_array['action'] = 'download';
    $post_array['record_type'] = $record_type;
    $post_array['record_date'] = $record_date;
    $post_array['course_name'] = $course_name;
    $post_array['php_cli'] = $php_cli_cmd;

    $post_array['metadata_file'] = $tmp_dir . "/metadata.xml";

    if (isset($cam_download_info) && count($cam_download_info) > 0) {
        $post_array['cam_info'] = serialize($cam_download_info);
    }

    if (isset($slide_download_info) && count($slide_download_info) > 0) {
        $post_array['slide_info'] = serialize($slide_download_info);
    }

    if (isset($recorder_version) && !empty($recorder_version)) {
        $post_array['recorder_version'] = $recorder_version;
    }
    
    $success = strpos(server_request_send($ezcast_submit_url, $post_array), 'Curl error') !== false;
    return $success;
}
