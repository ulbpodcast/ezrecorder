<?php

include_once('global_config.inc');
include_once('logger.php');
include_once('external_products/Psr/Log/LogLevel.php');
use Psr\Log\LogLevel;


// Logger
if($debugMode) {
  $logLevelThreshold = LogLevel::DEBUG;
} else {
  $logLevelThreshold = LogLevel::INFO;
}


if($debugMode) {
  ini_set("display_errors", 1);
  error_reporting(E_ALL); 
}
    
//this is required by the logger
date_default_timezone_set($recorder_timezone);
$logger = new Logger($databaseFile, $logLevelThreshold);
