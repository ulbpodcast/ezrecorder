<?php

/** Non user defined global variables should go here. 
 * Put here values that depends on global_config.inc values, else put them in common_static.inc
 * This files is included at the end of global_config.inc
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

date_default_timezone_set($recorder_timezone);

$ezcast_logs_url = "$ezcast_manager_url/recorder/logs.php";
$last_log_sent_get_url = "$ezcast_logs_url?action=last_log_sent&source=$classroom";
$log_push_url = "$ezcast_logs_url?action=push_logs"; //followed by json array
$ezcast_submit_url = "$ezcast_manager_url/recorder/index.php"; // URL to EZmanager server

$logger = new RecorderLogger($log_database_file, $last_log_sent_get_url, $classroom);

require_once("custom_error_handling.php");
