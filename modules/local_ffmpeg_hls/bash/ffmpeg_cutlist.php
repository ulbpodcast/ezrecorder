<?php

require_once __DIR__."/../etc/config.inc";
require_once __DIR__."/../lib_capture.php";
require_once __DIR__."/../info.php";

if ($argc != 3) {
    echo 'Usage: php ffmpeg_custlist.php <asset> <action>' . PHP_EOL;
    $logger->log(EventType::RECORDER_FFMPEG_CUTLIST, LogLevel::WARNING, "Called with wrong arg count", array(basename(__FILE__)));
    exit(1);
}

$asset = $argv[1];
$action = $argv[2];

$timestamp = time();
$date_formatted = date("Y_m_d_H\hi", $timestamp);
$cutlist_str = "$action:$timestamp:$date_formatted".PHP_EOL;

$cutlist_file = ffmpeg_get_cutlist_file($module_name, $asset);
if(!$cutlist_file) {
    $logger->log(EventType::RECORDER_FFMPEG_CUTLIST, LogLevel::ERROR, "Could not get cutlist file for asset $asset and module $module_name", array(basename(__FILE__)), $asset);
    exit(2);
}
$ok = file_put_contents($cutlist_file, $cutlist_str, FILE_APPEND);
if(!$ok) {
    $logger->log(EventType::RECORDER_FFMPEG_CUTLIST, LogLevel::ERROR, "Could not write in cutlist file at $cutlist_file", array(basename(__FILE__)), $asset);
    exit(3);
}

exit(0);
        
/*
#!/bin/bash

# This script must be sudo authorized for _www to run as podclient

#include localdefs
source $(dirname $0)/../etc/localdefs

ACTION=$1
CUTLIST_FILE=$2

DATE=`date +%s`
if [ "$ACTION" == "resume" ] 
then 
DATE=$[ $DATE + 5 ]
fi

echo $ACTION:$DATE:`date +"%Y_%m_%d_%Hh%M"` >> $CUTLIST_FILE
 * */