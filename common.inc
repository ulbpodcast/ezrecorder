<?php

/** All static variables of ezrecorder should go here
 *  */

require_once('global_config.inc');
require_once('logger_recorder.php');

if($debug_mode) {
    error_reporting(E_ALL); 
}

if($config_version != "20161003")
    trigger_error("global_config.inc file is not up to date", E_USER_WARNING);

//Uncomment this to print all log message to screen (may be useful to debug services or such)
//Logger::$print_logs = true;

$ezcast_logs_url = "$ezcast_manager_url/recorder/logs.php";
$last_log_sent_get_url = "$ezcast_logs_url?action=last_log_sent&source=$classroom";
$log_push_url = "$ezcast_logs_url?action=push_logs"; //followed by json array
$ezcast_submit_url = "$ezcast_manager_url/recorder/index.php"; // URL to EZmanager server
$ffmpeg_script_merge_movies = "$basedir/cli_merge_movies.php";
$recorder_logs = "$basedir/var/_log"; // Log file
$recorder_session = "$basedir/var/session";
$recorder_monitoring_pid = "$basedir/var/_timeout_monitoring_pid";
$nopic_file = "$basedir/nopic.jpg";
$modules_basedir = "$basedir/modules/";

// cli script for  uploading to server
$cli_upload = "$basedir/cli_upload_to_server.php";
// cli script for recording processing. Auto start $cli_upload afterwards
$cli_post_process = "$basedir/cli_post_process.php";
// cli script for monitoring the timeout on a recording
$cli_timeout_monitoring = "$basedir/cli_timeout_monitoring.php";

// logging
$log_database_file = "$basedir/var/logs.sqlite";
$cam_file_name = "cam.mov";
$slide_file_name = "slide.mov";

$logger = new RecorderLogger($log_database_file, $last_log_sent_get_url);

require_once("custom_error_handling.php");
