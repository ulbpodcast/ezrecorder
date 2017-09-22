<?php

require 'etc/config.inc';
require_once __DIR__ . '/../../global_config.inc';
require_once $basedir . '/common.inc';
require_once $basedir . '/lib_various.php';
require_once __DIR__ . '/../../create_bash_configs.php';
require_once $basedir . '/lib_error.php';
require_once $basedir . '/lib_model.php';
require_once __DIR__ . '/info.php';
require_once "$basedir/lib_ffmpeg.php";

/*
 * This file contains all functions related to the video capture using ffmpeg
 * It implements the "recorder interface" which is used in web_index.
 * the function annotated with the comment "@implements" are required to make
 * sure the web_index.php can work properly.
 */

//we can't use $ffmpeg_module_name directly as it may be overriden when including other module info.php
$ffmpeg_module_name = $module_name;

function init_streaming($asset, &$meta_assoc) {
    global $logger;
    global $ffmpeg_streaming_quality;
    global $ezcast_submit_url;
    global $ffmpeg_cli_streaming;
    global $php_cli_cmd;
    global $ffmpeg_streaming_info;
    global $ffmpeg_module_name;
    
    if (file_exists($ffmpeg_streaming_info))
        unlink($ffmpeg_streaming_info);

    $working_dir = get_asset_module_folder($ffmpeg_module_name, $asset);
    
    //if streaming is enabled, write it in '/var/streaming' ($ffmpeg_streaming_info) so that we may get the information later
    $streaming_info = capture_ffmpeg_info_get('streaming', $asset);
    if ($streaming_info !== false) {
        // defines that the streaming is enabled
        // It must be done before calling $ffmpeg_script_init (for preparing low and high HLS streams)
        file_put_contents($ffmpeg_streaming_info, var_export($streaming_info, true));
    } else {
        return true;
    }
    
    // streaming is enabled, we send a request to EZmanager to
    // init the streamed asset
    $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::DEBUG, "Streaming is enabled", array(__FUNCTION__), $asset);

    $post_array = $streaming_info;
    $post_array['action'] = 'streaming_init';
    $result = server_request_send($ezcast_submit_url, $post_array);
    $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::DEBUG, "Sent request for streaming with data " . print_r($post_array, true), array(__FUNCTION__), $asset);
 
    if (strpos($result, 'Curl error') !== false) {
        // an error occured with CURL
        $meta_assoc['streaming'] = 'false';
        unlink($ffmpeg_streaming_info);
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Curl failed to send request to server. Request: ". print_r($post_array, true) .". Result: $result", array(__FUNCTION__), $asset);
    }
    
    $course_name = $meta_assoc['course_name'];
    
    //not used $result = unserialize($result);
    // executes the command for sending TS segments to EZmanager in background
    // for low and high qualities
    $return_val_high = 0;
    $return_val_low = 0;
    if (strpos($ffmpeg_streaming_quality, 'high') !== false) {
        system("$php_cli_cmd $ffmpeg_cli_streaming $course_name " . $streaming_info['asset'] . " high &> $working_dir/stream_send_high.log &", $return_val_high);
    }
    if (strpos($ffmpeg_streaming_quality, 'low') !== false) {
        system("$php_cli_cmd $ffmpeg_cli_streaming $course_name " . $streaming_info['asset'] . " low &> $working_dir/stream_send_low.log &", $return_val_low);
    }
    if($return_val_high != 0 || $return_val_low != 0) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Failed to start background process. High return code: $return_val_high. Low return code: $return_val_low.", array(__FUNCTION__), $asset);
        return false;
    }
    
    return true;
}

function capture_ffmpeg_validate_environment(&$error_str) {
    global $ffmpeg_basedir;
    
    // -- Check if bash files are executables
    /* FIXME: This trigger on actually executable files... why?
    $dir = "$ffmpeg_basedir/bash/";
    $dir_content = scandir($dir);
    
    foreach($dir_content as $file ) {
        //ignore . .. and hidden files
        if($file[0] == '.')
            continue;
        
        if(!is_executable($file)) {
            $error_str = "$file is not executable ($dir)";
            return false;
        }
    }
     */
    // --
    
    // What else?
    
    return true;
}

/**
 * @implements
 * Initialize the recording settings.
 * This function launch a background task and return the pid of it by reference.
 * @param int $pid the process id of the background task. This is updated to process pid if function is successfull
 * @param associative_array $meta_assoc Metadata related to the record (used in cli_monitoring.php)
 * @return boolean true if everything went well; false otherwise
 */
function capture_ffmpeg_init(&$pid, $meta_assoc, $asset) {
    global $logger;
    global $ffmpeg_script_init;
    global $ffmpeg_recorder_logs;
    global $ezrecorder_username;
    global $ffmpeg_input_source;
    global $bash_env;
    global $ffmpeg_basedir;
    global $ffmpeg_module_name;
    
    $success = capture_ffmpeg_validate_environment($error_str);
    if(!$success) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, "Could not init module because of environment error: $error_str", array(__FUNCTION__), $asset);
        return false;
    }
    
    //prepare bash variables
    $success = create_bash_configs($bash_env, $ffmpeg_basedir . "etc/localdefs");
    if (!$success) {
        file_put_contents($ffmpeg_recorder_logs, "capture_ffmpeg_init: ERROR: Unable to create bash variables file\n", FILE_APPEND);
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, "Unable to create bash variables file", array(__FUNCTION__), $asset);
        return false;
    }

    $success = create_module_working_folders($ffmpeg_module_name, $asset);
    if(!$success) {
        $processUser = posix_getpwuid(posix_geteuid());
        $name = $processUser['name'];
        $logger->log(EventType::TEST, LogLevel::DEBUG, "current user: $name", array(__FUNCTION__), $asset);
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, "Could not create ffmpeg working folder. Check parent folder permissions ?", array(__FUNCTION__), $asset);
        return false;
    }
    
    // status of the current recording, should be empty
    /* Buggy check, fixme
    $status = capture_ffmpeg_status_get();
    if ($status != '') { // has a status
        error_last_message("capture_init: can't open because of current status: $status");
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::WARNING,"Current status is: '$status' at init time, this shouldn't happen. Try to continue anyway.", array(__FUNCTION__), $asset);
    }
     */

    $asset_dir = get_asset_dir($asset, "local_processing");
        
    // script_init initializes FFMPEG and launches the recording
    // in background to save time (pid is returned to be handled by web_index.php)
    $working_dir = get_asset_module_folder($ffmpeg_module_name, $asset);
    $log_file = $working_dir . '/init.log';
    $init_pid_file = "$working_dir/init.pid";
    $return_val = 0;
    $cmd = "sudo -u $ezrecorder_username $ffmpeg_script_init $asset $ffmpeg_input_source $working_dir 1 > $log_file 2>&1 & echo $! > $init_pid_file";
    system($cmd, $return_val);
    if($return_val) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Init command failed with return val: $return_val. Cmd: $cmd", array(__FUNCTION__), $asset);
        capture_ffmpeg_status_set("launch_failure");
        return false;
    }
    $pid = file_get_contents($init_pid_file);

    // init the streaming
    init_streaming($asset, $meta_assoc);

    $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::INFO, "Successfully initialized module (init script is still running in background at this point)", array(__FUNCTION__), $asset);
    return true;
}

//Not yet used, use this when we got a proper separation of cam and slides status
function capture_ffmpeg_troubleshoot() {
    global $bash_env;
    
    $troubleshoot_output = 'local_ffmpeg_hls troubleshoot: ' . PHP_EOL;
    
    $cam_ip = $bash_env['FFMPEG_RTSP_MEDIA_HIGH_URI'];
    
    $return_val = 0;
    exec('ping -c 1 -W 1 '. $cam_ip, $output, $return_val); 
    if($return_val == 0) {
        $troubleshoot_output .= "Camera ping ($cam_ip) succeeded". PHP_EOL;
    } else {
        $troubleshoot_output .= "/!\ Camera ping ($cam_ip) failed. Output: $output". PHP_EOL;
    }
}

/**
 * @implements
 * Launches the recording process
 */
function capture_ffmpeg_start($asset) {
    global $ffmpeg_script_start;
    global $ezrecorder_username;
    global $ffmpeg_input_source;
    global $logger;
    global $ffmpeg_module_name;
    
    //$logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": called", array(__FUNCTION__), $asset);
    
    create_module_working_folders($ffmpeg_module_name, $asset);
    $working_dir = get_asset_module_folder($ffmpeg_module_name, $asset);
    $log_file = $working_dir . '/start.log';
    $cut_list = ffmpeg_get_cutlist_file($ffmpeg_module_name, $asset);
    
    $cmd = "sudo -u $ezrecorder_username $ffmpeg_script_start $asset $working_dir $ffmpeg_input_source $cut_list >> $log_file 2>&1";
    
    $return_code = 0;
    system($cmd, $return_code);
    if($return_code != 0) {
        $logger->log(EventType::RECORDER_START, LogLevel::ERROR,"Recording start failed at command execution with return code $return_code. Command: $cmd.", array(__FUNCTION__), $asset);
        return false;
    }
    
    //update recording status
    $status = capture_ffmpeg_status_get();
    if ($status == "open") {
        capture_ffmpeg_status_set('recording');
        capture_ffmpeg_recstatus_set('recording');
        $logger->log(EventType::RECORDER_START, LogLevel::INFO, "User started recording, recording start mark set", array(__FUNCTION__), $asset);
    } else {
        capture_ffmpeg_status_set("error");
        error_last_message("Can't start recording because current status: $status");
        $logger->log(EventType::RECORDER_START, LogLevel::ERROR, "Recording could not be started because of current status: $status", array(__FUNCTION__), $asset);
        return false;
    }
    
    return true;    
}

//pause or resume recording, by writing in the cutlist.
// @param $action string Only valid inputs are "pause" or "resume"
function capture_ffmpeg_pause_resume($action, $asset) {
    global $logger;    
    global $ffmpeg_script_cutlist;
    global $ezrecorder_username;
    global $ffmpeg_module_name;
    
    $pause = $action == "pause";
    $resume = $action == "resume";
    
    if(!$pause && !$resume)
        return false;
    
    // get status of the current recording
    $status = capture_ffmpeg_status_get();
    if(   ($pause  && $status != 'recording')
       || ($resume && $status != 'paused' && $status != 'stopped')  ) {
        error_last_message("capture_pause: can't $action recording because current status: $status");
        $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::WARNING, "Can't $action recording because current status: $status", array(__FUNCTION__), $asset);
        return false;
    }
    
    $return_val = 0;
    $working_dir = get_asset_module_folder($ffmpeg_module_name, $asset);
    $cutlist_file = ffmpeg_get_cutlist_file($ffmpeg_module_name, $asset);
    $log_file = $working_dir . '/pause_resume.log';
    //$cmd = "sudo -u $ezrecorder_username $ffmpeg_script_cutlist $action $cutlist_file >> $log_file 2>&1";
    $cmd = "sudo -u $ezrecorder_username $ffmpeg_script_cutlist $asset $action >> $log_file 2>&1";
    system($cmd, $return_val);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::ERROR, "Setting recording $asset failed (file: $cutlist_file). Command: $cmd", array(__FUNCTION__), $asset);
        return false;
    }
    
    $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::DEBUG, "Setting recording $asset failed (file: $cutlist_file). Command: $cmd", array(__FUNCTION__), $asset);
    
    $set_status = $pause ? 'paused' : 'recording';
    capture_ffmpeg_status_set($set_status);
    capture_ffmpeg_recstatus_set($set_status);
    $set_status_str = $pause ? 'paused' : 'resumed';
    $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::INFO, "Recording was $set_status_str by user", array(__FUNCTION__), $asset);
    
    echo "OK";
    return true;
}

/**
 * @implements
 * Pauses the current recording
 */
function capture_ffmpeg_pause($asset) {
    return capture_ffmpeg_pause_resume('pause', $asset);
}

/**
 * @implements
 * Resumes the current paused recording
 */
function capture_ffmpeg_resume($asset) {
    return capture_ffmpeg_pause_resume('resume', $asset);
}

/**
 * @implements
 * Mark recording end (recording continues in the background for now)
 */
function capture_ffmpeg_stop(&$pid, $asset) {
    global $logger;
    global $ffmpeg_script_cutlist;
    global $ezrecorder_username;
    global $ffmpeg_module_name;
    
    $pid = 0; //pid not used, we don't background anything in there
    
    $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::NOTICE, "Stopping ffmpeg capture", array(__FUNCTION__), $asset);
    
    // check current recording status
    $status = capture_ffmpeg_status_get();
    if ($status != 'recording' && $status != "paused") {
        error_last_message("capture_stop: can't stop recording because current status: $status");
        $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::WARNING, "Can't stop recording because current status: $status", array(__FUNCTION__), $asset);
        return true; //not really an error, we're trying to stop when already stopped
    }
    
    // pauses the current recording (while user chooses the way to publish the record)
    $working_dir = get_asset_module_folder($ffmpeg_module_name, $asset);
    $log_file = $working_dir . '/stop.log';
    //$cmd = "sudo -u $ezrecorder_username $ffmpeg_script_cutlist stop $cut_list >> $log_file 2>&1";
    $cmd = "sudo -u $ezrecorder_username $ffmpeg_script_cutlist $asset stop >> $log_file 2>&1";
    $return_var = 0;
    system($cmd, $return_var);
    if($return_var != 0) {
        $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::ERROR, "Record stopping failed: $cmd", array(__FUNCTION__), $asset);
        return false;
    }

    // set the new status for the current recording
    capture_ffmpeg_status_set('stopped');
    capture_ffmpeg_recstatus_set('');
    
    $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::DEBUG, "Recording was stopped by user", array(__FUNCTION__), $asset);

    stop_streaming($asset);

    return true;
}

function stop_streaming($asset) {
    global $logger;
    global $ezcast_submit_url;
    
    $post_array = capture_ffmpeg_info_get('streaming', $asset);
    if ($post_array !== false) {
        // streaming enabled
        $post_array['action'] = 'streaming_close';
        $res = server_request_send($ezcast_submit_url, $post_array);
        if (strpos($res, 'error') !== false) {
            $logger->log(EventType::RECORDER_CANCEL, LogLevel::ERROR, __FUNCTION__.": An error occured while stopping streaming on the server", array(__FUNCTION__), $asset);
            return false;
        }
       return true;
    }
    
    $logger->log(EventType::RECORDER_CANCEL, LogLevel::NOTICE, "Successfully sent stop streaming request", array(__FUNCTION__), $asset);
    return true;
}

/**
 * @implements
 * Ends the current recording and saves it as an archive
 */
function capture_ffmpeg_cancel($asset = null) {
    global $logger;
    global $ffmpeg_script_cancel;
    global $ezrecorder_username;
    global $ffmpeg_module_name;

    // cancels the current recording, saves it in archive dir and stops the monitoring
    if($asset != null) {
        $working_dir = get_asset_module_folder($ffmpeg_module_name, $asset);
        $log_file = $working_dir . '/cancel.log';
        $cmd = "sudo -u $ezrecorder_username $ffmpeg_script_cancel $asset >> $log_file 2>&1";
    } else {
        $cmd = "sudo -u $ezrecorder_username $ffmpeg_script_cancel";
    }
    $return_val = 0;
    system($cmd, $return_val);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_CANCEL, LogLevel::ERROR, "Record cancel script start failed: $cmd", array(__FUNCTION__), $asset);
        return false;
    }
    
    $logger->log(EventType::RECORDER_CANCEL, LogLevel::INFO, "Recording was cancelled. ", array(__FUNCTION__));
    $logger->log(EventType::RECORDER_CANCEL, LogLevel::DEBUG, "Cancel backtrace: " . print_r(debug_backtrace(),true), array(__FUNCTION__));
    
    capture_ffmpeg_recstatus_set('');
    
    if($asset == null)
        return true;
    
    stop_streaming($asset);
    
    //Create a "CANCELLED file in asset dir just to make it more clear"
    $asset_dir = get_asset_dir($asset);
    $cancelled_file = "$asset_dir/CANCELED";
    file_put_contents($cancelled_file, "");
    
    return true;
}

/** 
 * @implements
 * @param type $asset
 * @return true on process success, false on failure or result not found
 */
function capture_ffmpeg_process_result($asset) {
    global $logger;
    global $process_result_filename;
    global $ffmpeg_module_name;
    
    $working_dir = get_asset_module_folder($ffmpeg_module_name, $asset);
    $process_result_file = "$working_dir/$process_result_filename";
    if(!file_exists($process_result_file)) {
        $logger->log(EventType::TEST, LogLevel::DEBUG, "Return false because file $process_result_file does not exists", array(__FUNCTION__));
        return false;
    }
    $result = file_get_contents($process_result_file);
    if($result) 
        $result = trim($result);
    
    $success = $result !== false && $result == "0";
    $logger->log(EventType::TEST, LogLevel::DEBUG, "File was found ($process_result_file), contain: $result. Returning success: " . ($success ? "true" : "false"), array(__FUNCTION__));
    return $success;
}

/**
 * @implements
 * Processes the record before sending it to the server
 * @param assoc_array $metadata_assoc metadata relative to current recording
 */
function capture_ffmpeg_process($asset, &$pid) {
    global $logger;
    global $ffmpeg_script_stop;
    global $ffmpeg_processing_tool;
    global $ffmpeg_processing_tools;
    global $ezrecorder_username;
    global $cam_file_name;
    global $ffmpeg_module_name;
    
    //any use for this ?
    if (!in_array($ffmpeg_processing_tool, $ffmpeg_processing_tools))
        $ffmpeg_processing_tool = $ffmpeg_processing_tools[0];

    /*
    $status = capture_ffmpeg_status_get();
    // If record is still going on at this point, try to stop it (should not happen, this is a security)
    if ($status == 'recording' || $status == 'open') {
        $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::WARNING, "Function called while status was still $status. Try to stop it.", array(__FUNCTION__), $asset);
        stop_current_record(false);
    }
    
    $status = capture_ffmpeg_status_get();
    if ($status == 'recording' || $status == 'open') {
        error_last_message("Can't start recording process because of current status: $status");
        $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::CRITICAL, "Can't start recording process because of current status: $status", array(__FUNCTION__), $asset);
        $pid = 0;
        return false;
    }
    */
       
    // saves recording in processing dir and start processing
    $process_dir = get_asset_module_folder($ffmpeg_module_name, $asset);
    $pid_file = "$process_dir/stop_pid.txt";
    $log_file = "$process_dir/stop.log";
    $cmd = "sudo -u $ezrecorder_username $ffmpeg_script_stop $asset $cam_file_name >> $log_file 2>&1 & echo $! > $pid_file";
    log_append('recording', "launching command: $cmd");
    // returns the process id of the background task
    $return_val = 0;
    system($cmd, $return_val);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::CRITICAL, "FFMPEG stop script launch failed with return code $return_val. Cmd: $cmd", array(__FUNCTION__), $asset);
        $pid = 0;
        return false;
    }
    $pid = file_get_contents($pid_file);

    //should be saved in get_asset_dir($asset, "local_processing");
    //combine cam and slide:
    //one need to activate at on the mac:
    //	vi /System/Library/LaunchDaemons/com.apple.atrun.plisto
    //	change Disabled tag value from <true /> to <false/>
    //   	launchctl unload -F /System/Library/LaunchDaemons/com.apple.atrun.plist
    //  	launchctl load -F /System/Library/LaunchDaemons/com.apple.atrun.plist

    $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::DEBUG, "Processing successfully started. Cmd: $cmd", array(__FUNCTION__), $asset);
    return true;
}

/**
 * @implements
 * Finalizes the recording after it has been uploaded to the server.
 * The finalization consists in archiving video files in a specific dir
 * and removing all temp files used during the session.
 * @global type $ffmpeg_script_qtbfinalize
 * @global type $ffmpeg_recorder_logs
 * @global type $dir_date_format
 */
function capture_ffmpeg_finalize($asset) {
    global $logger;
    global $ffmpeg_script_finalize;
    global $ezrecorder_username;
    global $ffmpeg_module_name;
    
    // launches finalization bash script
    $working_dir = get_asset_module_folder($ffmpeg_module_name, $asset, 'upload');
    $log_file = $working_dir . '/finalize.log';
    $cmd = "sudo -u $ezrecorder_username $ffmpeg_script_finalize $asset >> $log_file 2>&1";
    $return_val = 0;
    $output = system($cmd, $return_val);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_FINALIZE, LogLevel::ERROR, "Finalisation failed with error code $return_val and output: $output", array(__FUNCTION__), $asset);
        return false;
    }
    
    $logger->log(EventType::RECORDER_FINALIZE, LogLevel::DEBUG, "Successfully finished finalization", array(__FUNCTION__), $asset);
    return true;
}

/**
 * @implements
 * Returns an associative array containing information required for given action
 * @global type $ezrecorder_ip
 * @global type $ffmpeg_download_protocol
 * @global type $ezrecorder_username
 * @return info array or false if failure
 */
function capture_ffmpeg_info_get($action, $asset = '') {
    global $ezrecorder_ip;
    global $ffmpeg_download_protocol;
    global $ffmpeg_streaming_protocol;
    global $ffmpeg_streaming_quality;
    global $ezrecorder_username;
    global $classroom;
    global $cam_module;
    global $logger;
    global $ffmpeg_module_name;
    
    switch ($action) {
        case 'download':
            $filename = get_asset_dir($asset) . "/cam.mov";
            if(!file_exists($filename)) {
                $logger->log(EventType::RECORDER_INFO_GET, LogLevel::DEBUG, "info_get: download: No camera file found, no info to give. File: $filename.", array(__FUNCTION__), $asset);
                return false; //invalid file
            }
            
            // rsync requires ssh protocol is set (key sharing) on the remote server
            $download_info_array = array("ip" => $ezrecorder_ip,
                "protocol" => $ffmpeg_download_protocol,
                "username" => $ezrecorder_username,
                "filename" => $filename);
            return $download_info_array;
        case 'streaming':
            if ($ffmpeg_streaming_quality == 'none')
                return false;
            
            $asset_dir = get_asset_dir($asset);
            if(!file_exists($asset_dir)) {
                $logger->log(EventType::RECORDER_INFO_GET, LogLevel::DEBUG, "info_get: streaming: No asset dir found, no info to give. File: $asset_dir.", array(__FUNCTION__), $asset);
                return false;
            }
            
            $meta_assoc = xml_file2assoc_array("$asset_dir/_metadata.xml");
    
            $module_type = (($cam_module == $ffmpeg_module_name) ? 'cam' : 'slide');
            // streaming is disabled if it has not been enabled by user
            // or if the module type is not of record type
            if ($meta_assoc['streaming'] === 'false' || ($meta_assoc['record_type'] !== 'camslide' && $meta_assoc['record_type'] != $module_type))
                return false;
            
            $streaming_info_array = array(
                "ip" => $ezrecorder_ip,
                "protocol" => $ffmpeg_streaming_protocol,
                "course" => $meta_assoc['course_name'],
                "asset" => $meta_assoc['record_date'],
                "record_type" => $meta_assoc['record_type'],
                "module_type" => $module_type,
                "module_quality" => $ffmpeg_streaming_quality,
                "classroom" => $classroom,
                "netid" => $meta_assoc['netid'],
                "author" => $meta_assoc['author'],
                "title" => $meta_assoc['title']);
            
            return $streaming_info_array;
        default:
            return false;
    }
}

/**
 * @implements
 * Creates a thumbnail picture
 * @return string the contents of the image to display
 */
function capture_ffmpeg_thumbnail() {
    global $ffmpeg_capture_file;
    global $ffmpeg_capture_tmp_file;
    global $ffmpeg_capture_transit_file;
    
    // Camera screenshot
    $diff = time() - filemtime($ffmpeg_capture_file);
    if (!file_exists($ffmpeg_capture_file) || ($diff > 1)) { //if last used capture is more than 1 sec old
        if ((time() - filemtime($ffmpeg_capture_tmp_file) > 60)) { //if last ffmpeg thumbnail is older than 60 secs
            //print "could not take a screencapture";
            copy("./nopic.jpg", $ffmpeg_capture_file);
        } else { //use ffmpeg thumbnail to generate final thumbnail (resize + add status on it)
            //copy screencapture to actual snap
            $status = capture_ffmpeg_status_get();
            if ($status == 'recording') {
                $status = capture_ffmpeg_recstatus_get();
            }
             //invalid status in rec_status ?
            if($status == '') {
                $status = 'open';
            }
            
            $ok = image_resize($ffmpeg_capture_tmp_file, $ffmpeg_capture_transit_file, 235, 157, $status, false);
            if($ok) 
                rename($ffmpeg_capture_transit_file, $ffmpeg_capture_file);
            else {
                copy("./nopic.jpg", $ffmpeg_capture_file);
            }
        }
    }
    return file_get_contents($ffmpeg_capture_file);
}

/**
 * @implements
 * Returns the current status of the recording
 * Status may be "open", "recording", "paused", "stopped", "error"
 */
function capture_ffmpeg_status_get() {
    global $ffmpeg_status_file;

    if (!file_exists($ffmpeg_status_file))
        return '';

    return trim(file_get_contents($ffmpeg_status_file));
}

/**
 * @implements
 * Returns an array containing the features offered by the module
 * @global type $ffmpeg_features
 * @return type
 */
function capture_ffmpeg_features_get() {
    global $ffmpeg_features;
    global $ffmpeg_streaming_quality;

    if ($ffmpeg_streaming_quality == 'none') {
        if ($index = array_search('streaming', $ffmpeg_features) !== false) {
            unset($ffmpeg_features[$index]);
        }
    }

    return $ffmpeg_features;
}

/**
 * @implements
 * Defines the status of the current recording
 */
function capture_ffmpeg_status_set($status) {
    global $logger;
    global $ffmpeg_status_file;

    file_put_contents($ffmpeg_status_file, $status);
    $logger->log(EventType::TEST, LogLevel::DEBUG, "Status set to ".$status, array(__FUNCTION__));
}

/**
 * returns the real status of the current recording
 * @global type $ffmpeg_movie_name
 * @return string
 */
function capture_ffmpeg_recstatus_get() {
    global $ffmpeg_recstatus_file;

    if (!file_exists($ffmpeg_recstatus_file))
        return '';

    return trim(file_get_contents($ffmpeg_recstatus_file));
}

/**
 * sets the real status of the current recording
 * @global type $ffmpeg_status_file
 * @param type $status
 */
function capture_ffmpeg_recstatus_set($status) {
    global $logger;
    global $ffmpeg_recstatus_file;

    file_put_contents($ffmpeg_recstatus_file, $status);
    $logger->log(EventType::TEST, LogLevel::DEBUG, "rectatus set to: '".$status . "'. Caller: " . debug_backtrace()[1]['function'], array(__FUNCTION__));
}

function capture_ffmpeg_get_last_movies_dir($asset) {
    global $ffmpeg_movie_name;
    global $ffmpeg_module_name;
    
    $working_dir = get_asset_module_folder($ffmpeg_module_name, $asset);
    
    $scanned = scandir("$working_dir", SCANDIR_SORT_DESCENDING);
    if($scanned == false)
        return false;
    
    $subdir = false; //ffmpegmovie_* folder
    foreach($scanned as $value) {
        $dir = "$working_dir/$value";
        if(strpos($value, $ffmpeg_movie_name) !== false && is_dir($dir)) {
            $subdir = $dir;
            break;
        }
    }
    
    if($subdir === false) 
        return false;
    
    $high_folder = "$subdir/high";
    $low_folder = "$subdir/low";
    if(file_exists($high_folder))
        return $high_folder;
     if(file_exists($low_folder))
        return $low_folder;

    return false;
}