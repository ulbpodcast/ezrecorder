<?php

/**
Stops the recording
Improvmenet idea: Maybe delay the stop? Will that cause possible problems with consecutives records?
*/

require_once __DIR__."/../etc/config.inc";
require_once "$basedir/lib_ffmpeg.php";
require_once __DIR__."/../lib_capture.php";

Logger::$print_logs = true;
$log_context = basename(__FILE__, '.php');

if ($argc != 2) {
    echo 'Usage: php stop.php <asset>' . PHP_EOL;
    $logger->log(EventType::RECORDER_SOUND_BACKUP, LogLevel::WARNING, basename(__FILE__) . " called with wrong arg count", array($log_context));
    exit(1);
}

$asset = $argv[1];

#stop recording 
$ok = stop_ffmpeg($sound_backup_pid_file);
if(!$ok)
    exit(2);

//try to move backup file to asset folder
/* Not working but not really important, commented for now
$process_dir = get_asset_module_folder($sound_backup_module_name, $asset);
$asset_dir = get_asset_dir($asset);
if($process_dir && $asset_dir) {
    rename($process_dir/$backup_filename, $asset_dir/$backup_filename);
}
 */

exit(0);
