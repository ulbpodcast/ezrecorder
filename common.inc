<?php

/** All static variables of ezrecorder should go here
 *  */

include_once('global_config.inc');
require_once('logger_recorder.php');

if($debug_mode) {
  ini_set("display_errors", 1);
  error_reporting(E_ALL); 
}
    
$logger = new RecorderLogger($log_database_file);
//Uncomment this to print all log message to screen (may be useful to debug services or such)
Logger::$print_logs = true;