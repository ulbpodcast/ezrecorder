<?php

require_once __DIR__ . "/etc/config.inc";
require_once __DIR__ . '/../../global_config.inc';
require_once $basedir . '/lib_various.php';
require_once __DIR__ . '/../../create_bash_configs.php';

$sound_backup_module_name = "sound_backup";

function capture_sound_backup_init(&$pid, $meta_assoc, $asset) {
    global $logger;
    global $ezrecorder_username;
    global $sound_backup_module_name;
    global $sound_backup_script_init;
    
    $success = create_module_working_folders($sound_backup_module_name, $asset);
    if(!$success) {
        $logger->log(EventType::RECORDER_SOUND_BACKUP, LogLevel::CRITICAL, "Could not create working folder. Check parent folder permissions ?", array(__FUNCTION__), $asset);
        return false;
    }
    
    // status of the current recording, should be empty
    $status = capture_ffmpeg_status_get();
    if ($status != '') { // has a status
        error_last_message("capture_init: can't open because of current status: $status");
        $logger->log(EventType::RECORDER_SOUND_BACKUP, LogLevel::WARNING,"Current status is: '$status' at init time, this shouldn't happen. Try to continue anyway.", array(__FUNCTION__), $asset);
    }

    // script_init initializes FFMPEG and launches the recording
    // in background to save time (pid is returned to be handled by web_index.php)
    $working_dir = get_asset_module_folder($sound_backup_module_name, $asset);
    $log_file = $working_dir . '/init.log';
    $init_pid_file = "$working_dir/init.pid";
    $return_val = 0;
    $cmd = "sudo -u $ezrecorder_username $sound_backup_script_init $asset $working_dir > $log_file 2>&1 & echo $! > $init_pid_file";
    system($cmd, $return_val);
    if($return_val) {
        $logger->log(EventType::RECORDER_SOUND_BACKUP, LogLevel::ERROR, "Init command failed with return val: $return_val. Cmd: $cmd", array(__FUNCTION__), $asset);
        capture_ffmpeg_status_set("launch_failure");
        return false;
    }


    $logger->log(EventType::RECORDER_SOUND_BACKUP, LogLevel::INFO, "Successfully initialized module (init script is still running in background at this point)", array(__FUNCTION__), $asset);
    return true;
}

function capture_sound_backup_start($asset) {
    //nothing do to
}

function capture_sound_backup_pause($asset) {
    //nothing to do
}

function capture_sound_backup_resume($asset) {
    //nothing to do
}

function capture_sound_backup_stop(&$pid, $asset) {
  //nothing to do
}

function capture_sound_backup_cancel($asset) {
    //just stop it
    capture_sound_backup_process($asset, 0);
}

function capture_sound_backup_process_result($asset) {
    //not really relevant, nothing to do
    return true;
}

function capture_sound_backup_process($asset, &$pid) {
    global $logger;
    global $sound_backup_script_stop;
    global $ezrecorder_username;
    global $sound_backup_module_name;
    $pid = 0;
    
    //just stop the record
    $process_dir = get_asset_module_folder($sound_backup_module_name, $asset);
    $pid_file = "$process_dir/stop_pid.txt";
    $log_file = "$process_dir/stop.log";
    $cmd = "sudo -u $ezrecorder_username $sound_backup_script_stop $asset >> $log_file 2>&1 & echo $! > $pid_file";
    // returns the process id of the background task
    $return_val = 0;
    system($cmd, $return_val);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_SOUND_BACKUP, LogLevel::CRITICAL, "Sound backup stop script launch failed with return code $return_val. Cmd: $cmd", array(__FUNCTION__), $asset);
        return false;
    }
    
    return true;
}

function capture_sound_backup_finalize($asset) {
     //nothing to do
}

function capture_sound_backup_info_get($action, $asset) {
    return false;
}

function capture_sound_backup_thumbnail() {
    return false;
}

function capture_sound_backup_status_get() {
    return false;
}

function capture_sound_backup_status_set($status) {
    return false;
}

function capture_sound_backup_features_get() {
    return false;
}
