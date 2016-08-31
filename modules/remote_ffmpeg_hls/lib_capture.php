<?php

/*
 * This file contains all functions related to the video slide capture from a remote mac
 * It implements the "recorder interface" which is used in web_index. 
 * the function annotated with the comment "@implements" are required to make 
 * sure the web_index.php can work properly.
 * 
 * ATTENTION: In order to make this library works, the module has to be 'installed' 
 * on the remote server. 
 */

require_once __DIR__."/config.inc";
require_once $basedir . "/lib_various.php";
require_once $basedir . "/lib_error.php";
require_once $basedir . '/modules/local_ffmpeg_hls/lib_capture.php';
require_once __DIR__. "info.php";
    

/**
 * Create bash & php configurations files on remote remote recorder
 * Return true on success
 * @param $return_val return value of the remote install script
*/
function capture_remoteffmpeg_install_remote_config(&$return_val, &$output = '') {
    global $remoteffmpeg_username;
    global $remoteffmpeg_script_install_config;
    global $remoteffmpeg_ip;
    global $remote_script_call;    
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_recorddir;
    
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs php $remoteffmpeg_script_install_config '$remoteffmpeg_recorddir'";
    $output = system($cmd, $return_val);
    
    return $return_val == 0;
}

/**
 * @implements 
 * Initialize the camera settings.
 * This function should be called before the use of the camera
 * @param associate_array $meta_assoc metadata relative to the current recording
 */
function capture_remoteffmpeg_init(&$pid, $meta_assoc, $asset) {
    global $remoteffmpeg_script_init;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_username;
    global $remoteffmpeg_streaming_info;
    global $remote_script_datafile_set;
    global $logger;
    global $module_name;
    
    $return_val = 0;
    $output = '';
    $success = capture_remoteffmpeg_install_remote_config($return_val, $output);
    if(!$success) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, "Install remote config failed with return value $return_val. Output: $output", array(__FUNCTION__), $asset);
        return false;
    }
    
    $success = create_module_working_folders($module_name, $asset);
    if(!$success) {
        $processUser = posix_getpwuid(posix_geteuid());
        $name = $processUser['name'];
        $logger->log(EventType::TEST, LogLevel::DEBUG, "current user: $name", array(__FUNCTION__), $asset);
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, "Could not create ffmpeg working folder. Check parent folder permissions ?", array(__FUNCTION__), $asset);
        return false;
    }
    
    // status of the current recording, should be empty
    $status = capture_remoteffmpeg_status_get();
    if ($status != '') { // has a status
        error_last_message("capture_init: can't open because of current status: $status");
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::WARNING, "Current status is: '$status' at init time, this shouldn't happen. Try to continue anyway.", array(__FUNCTION__), $asset);
    }
    
    $streaming_info = capture_remoteffmpeg_info_get('streaming', $asset);
    if ($streaming_info !== false) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::DEBUG, "Streaming is enabled", array(__FUNCTION__), $asset);
        
        $xml = xml_assoc_array2metadata($streaming_info);
        // put the xml string in a metadata file on the remote mac mini
        system("sudo -u $remoteffmpeg_username $remote_script_datafile_set $remoteffmpeg_ip " . escapeshellarg($xml) . " $remoteffmpeg_streaming_info &");
    }
    
    /* remote script call requires:
     * - the remote ip
     * - the absolute path to the logs file
     * - the remote script to execute
     */
    // '> /dev/null' discards output, '&' executes the process as background task and 'echo $!' returns the pid
    $working_dir = get_asset_module_folder($module_name, $asset);
    $streaming = $streaming_info !== false ? 'true' : 'false';
    $init_pid_file = "$working_dir/init.pid";
    $log_file = $working_dir . '/init.log';
    $return_val = 0;
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_init $asset $working_dir 1 $streaming > $log_file 2>&1 & echo $! > $init_pid_file";
    system($cmd, $return_val);
    if($return_val) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Init command failed with return val $return_val. Cmd: $cmd", array(__FUNCTION__), $asset);
        capture_remoteffmpeg_status_set("launch_failure");
        return false;
    }
    $pid = file_get_contents($init_pid_file);

    //TODO FIXME: we don't even check result of init here
    capture_remoteffmpeg_status_set('open');

    return true;
}

/**
 * @implements
 * Launch the recording process 
 * TODO: duplicate code, 
 */
function capture_remoteffmpeg_start($asset) {
    global $remoteffmpeg_script_start;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_username;
    global $logger;
    global $module_name;
    
    create_module_working_folders($module_name, $asset);
    $working_dir = get_asset_module_folder($module_name, $asset);
    $log_file = $working_dir . '/start.log';
    
    /* remote script call requires:
     * - the remote ip
     * - the absolute path to the logs file
     * - the remote script to execute
     * - optional args for the script to execute
     */
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_start $asset $working_dir $cut_list  >> $log_file 2>&1";
    
    $return_code = 0;
    system($cmd, $return_code);
    if($return_code != 0) {
        $logger->log(EventType::RECORDER_START, LogLevel::INFO,"Recording start failed at command execution with return code $return_code. Command: $cmd.", array(__FUNCTION__), $asset);
        return false;
    }
    
    //update recording status
    $status = capture_remoteffmpeg_status_get();
    if ($status == "open") {
        capture_remoteffmpeg_status_set('recording');
        $logger->log(EventType::RECORDER_START, LogLevel::INFO, "User started recording, recording start mark set", array(__FUNCTION__), $asset);
    } else {
        capture_remoteffmpeg_status_set("error");
        error_last_message("Can't start recording because current status: $status");
        $logger->log(EventType::RECORDER_START, LogLevel::ERROR, "Recording could not be started because of current status: $status", array(__FUNCTION__), $asset);
        return false;
    }

    return true;
}

//pause or resume recording, by writing in the cutlist.
// @param $action string Only valid inputs are "pause" or "resume"
function capture_remoteffmpeg_pause_resume($action, $asset) {
    global $logger;    
    global $remoteffmpeg_script_cutlist;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_username;
    global $module_name;

    $pause = $action == "pause";
    $resume = $action == "resume";
    
    if(!$pause && !$resume)
        return false;
    
    // get status of the current recording
    $status = capture_remoteffmpeg_status_get();
    if(   ($pause  && $status != 'recording')
       || ($resume && $status != 'paused' && $status != 'stopped')  ) {
        error_last_message("capture_pause: can't $action recording because current status: $status");
        $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::WARNING, "Can't $action recording because current status: $status", array(__FUNCTION__), $asset);
        return false;
    }
    
    $return_val = 0;
    $working_dir = get_asset_module_folder($module_name, $asset);
    $log_file = $working_dir . '/cutlist.log';
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_cutlist $asset $action >> $log_file 2>&1";
    system($cmd, $return_val);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::ERROR, "Setting recording $asset failed. Command: $cmd", array(__FUNCTION__), $asset);
        return false;
    }
    
    $set_status = $pause ? 'paused' : 'recording';
    capture_remoteffmpeg_status_set($set_status);
    $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::INFO, "Recording was $set_status'd by user", array(__FUNCTION__), $asset);
    return true;    
}

/**
 * @implements
 * Pauses the current recording
 */
function capture_remoteffmpeg_pause($asset) {
    return capture_remoteffmpeg_pause_resume('pause', $asset);
}

/**
 * @implements
 * Resumes the current paused recording
 */
function capture_remoteffmpeg_resume($asset) {
    return capture_remoteffmpeg_pause_resume('resume', $asset);
}

/**
 * @implements
 * Stops the current recording
 */
function capture_remoteffmpeg_stop(&$pid, $asset) {
    global $remoteffmpeg_script_cutlist;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_username;
    global $logger;
    global $module_name;

    $pid = 0; //pid not used, we don't background anything in there
    
    $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::NOTICE, "Stopping ffmpeg capture", array(__FUNCTION__), $asset);
        
    // check current recording status
    $status = capture_remoteffmpeg_status_get();
    if ($status != 'recording' && $status != "paused") {
        error_last_message("capture_stop: can't stop recording because current status: $status");
        $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::WARNING, "Can't stop recording because current status: $status", array(__FUNCTION__), $asset);
        return false;
    }
    
    $working_dir = get_asset_module_folder($module_name, $asset);
    $log_file = $working_dir . '/cutlist.log';
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_cutlist $asset stop >> $log_file 2>&1";
    $return_var = 0;
    system($cmd, $return_var);
    if($return_var != 0) {
        $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::ERROR, "Record stopping failed: $cmd", array(__FUNCTION__), $asset);
        return false;
    }
    
    capture_remoteffmpeg_status_set('stopped');
    $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::DEBUG, __FUNCTION__.": Recording was stopped by user", array(__FUNCTION__), $asset);

    return true;
}

/**
 * @implements
 * Ends the current recording and saves it as an archive
 */
function capture_remoteffmpeg_cancel($asset) {
    global $remoteffmpeg_script_cancel;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_username;
    global $logger;
    global $module_name;

    // cancels the current recording, saves it in archive dir and stops the monitoring
    $working_dir = get_asset_module_folder($module_name, $asset);
    $log_file = $working_dir . '/cancel.log';
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_cancel $asset >> $log_file 2>&1";
    system($cmd, $return_val);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_CANCEL, LogLevel::ERROR, "Record cancel script start failed: $cmd", array(__FUNCTION__), $asset);
        return false;
    }
    
    //update (clear) status
    capture_remoteffmpeg_rec_status_set('');
    $logger->log(EventType::RECORDER_CANCEL, LogLevel::INFO, __FUNCTION__.": Recording was cancelled", array(__FUNCTION__));

    return true;
}

/**
 * @implements
 * Processes the record before sending it to the server
 */
function capture_remoteffmpeg_process($asset, &$pid) {
    global $remoteffmpeg_script_stop;
    global $remoteffmpeg_ip;
    global $remote_script_call;
    global $remoteffmpeg_recorder_logs;
    global $remoteffmpeg_processing_tool;
    global $remoteffmpeg_processing_tools;
    global $remoteffmpeg_username;
    global $slide_file_name;
    global $module_name;

    $status = capture_remoteffmpeg_status_get();
    // If record is still going on at this point, try to stop it (should not happen, this is a security)
    if ($status == 'recording' || $status == 'open') {
        $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::WARNING, "Function called while status was still $status. Try to stop it.", array(__FUNCTION__), $asset);
        stop_current_record(false);
    }
    
    $status = capture_remoteffmpeg_status_get();
    if ($status == 'recording' || $status == 'open') {
        error_last_message("Can't start recording process because of current status: $status");
        $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::CRITICAL, "Can't start recording process because of current status: $status", array(__FUNCTION__), $asset);
        $pid = 0;
        return false;
    }
    
    if (!in_array($remoteffmpeg_processing_tool, $remoteffmpeg_processing_tools))
        $remoteffmpeg_processing_tool = $remoteffmpeg_processing_tools[0];

    // saves recording in processing dir and start processing
    $process_dir = get_asset_module_folder($module_name, $asset);
    $pid_file = "$process_dir/stop_pid.txt";
    $log_file = "$process_dir/stop.log";
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_stop $asset $slide_file_name >> $log_file 2>&1 & echo $! > $pid_file";
    $return_val = 0;
    system($cmd, $return_val);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::CRITICAL, "FFMPEG stop script launch failed with return code $return_val", array(__FUNCTION__), $asset);
        $pid = 0;
        return false;
    }
    $pid = file_get_contents($pid_file);
    
    
    //update (clear) status
    capture_remoteffmpeg_status_set('');
    capture_remoteffmpeg_rec_status_set('');

    // should be saved in Movies/local_processing/<date+hour>/
    // combine cam and slide:
    // one need to activate at on the mac:
    //	vi /System/Library/LaunchDaemons/com.apple.atrun.plisto
    //	change Disabled tag value from <true /> to <false/>
    //   	launchctl unload -F /System/Library/LaunchDaemons/com.apple.atrun.plist
    //  	launchctl load -F /System/Library/LaunchDaemons/com.apple.atrun.plist

    return true;
}

/**
 * @implements
 * Finalizes the recording after it has been uploaded to the server.
 * The finalization consists in archiving video files in a specific dir
 * and removing all temp files used during the session.
 * @global type $remoteffmpeg_ip
 * @global type $remoteffmpeg_script_qtbfinalize
 * @global type $remoteffmpeg_recorder_logs
 * @global type $remote_script_datafile_get
 * @global type $remote_script_call
 */
function capture_remoteffmpeg_finalize($asset) {
    global $remoteffmpeg_ip;
    global $remoteffmpeg_script_finalize;
    global $remoteffmpeg_recorder_logs;
    global $remote_script_call;
    global $remoteffmpeg_username;
    global $logger;
    global $module_name;

    // launches finalization remote bash script
    $working_dir = get_asset_module_folder($module_name, $asset, 'upload');
    $log_file = $working_dir . '/finalize.log';
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_call $remoteffmpeg_ip $remoteffmpeg_recorder_logs $remoteffmpeg_script_finalize $asset >> $log_file 2>&1";
    $return_val = 0;
    $output = system($cmd, $return_val);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_FINALIZE, LogLevel::ERROR, "Finalisation failed with error code $return_val and output $output", array(__FUNCTION__), $asset);
        return false;
    }
    
    $logger->log(EventType::RECORDER_FINALIZE, LogLevel::DEBUG, "Successfully finished finalization", array(__FUNCTION__), $asset);
    return true;
}

/**
 * #implements
 * Creates a thumbnail picture
 */
function capture_remoteffmpeg_thumbnail() {
    global $remoteffmpeg_basedir;
    global $remoteffmpeg_capture_file;
    global $remoteffmpeg_capture_tmp_file;
    global $remoteffmpeg_capture_transit_file;
    global $remoteffmpeg_ip;
    global $remote_script_thumbnail_create;
    global $remoteffmpeg_username;


    $minperiod = 5;

    // Slide screenshot
    if (!file_exists($remoteffmpeg_capture_file) || (time() - filemtime($remoteffmpeg_capture_file) > 3)) {
        //if no image or image is old get a new screencapture
        $cmd = "sudo -u $remoteffmpeg_username $remote_script_thumbnail_create $remoteffmpeg_ip $remoteffmpeg_basedir/var/pic_new.jpg $remoteffmpeg_capture_tmp_file";
        $res = exec($cmd, $output_array, $return_code);
        if ((time() - filemtime($remoteffmpeg_capture_tmp_file) > 3)) {
            //print "could not take a screencapture";
            copy("./nopic.jpg", "$remoteffmpeg_capture_file");
        } else {
            //copy screencapture to actual snap
            $status = capture_remoteffmpeg_status_get();
            if ($status == 'recording') {
                $status = capture_remoteffmpeg_rec_status_get();
            }
            image_resize("$remoteffmpeg_capture_tmp_file", "$remoteffmpeg_capture_transit_file", 235, 157, $status, false);
            rename("$remoteffmpeg_capture_transit_file", "$remoteffmpeg_capture_file");
        }
    }
    return file_get_contents($remoteffmpeg_capture_file);
}

/**
 * @implements
 * Returns an associative array containing information required for given action
 * @global type $remoteffmpeg_ip
 * @global type $remoteffmpeg_download_protocol
 * @global type $remoteffmpeg_username
 * @return type
 */
function capture_remoteffmpeg_info_get($action, $asset = '') {
    global $remoteffmpeg_ip;
    global $remoteffmpeg_download_protocol;
    global $remoteffmpeg_streaming_protocol;
    global $remoteffmpeg_username;
    global $remoteffmpeg_upload_dir;
    global $remoteffmpeg_username;
    global $remoteffmpeg_streaming_quality;
    global $ezcast_submit_url;
    global $classroom;
    global $cam_module;
    global $logger;

    switch ($action) {
        case 'download':
            $filename = $remoteffmpeg_upload_dir . $asset . "/slide.mov";
            
            //Todo: check file existence on remote server
            
            // rsync requires ssh protocol is set (key sharing) on the remote server
            $download_info_array = array("ip" => $remoteffmpeg_ip,
                "protocol" => $remoteffmpeg_download_protocol,
                "username" => $remoteffmpeg_username,
                "filename" => $filename);
            return $download_info_array;
        case 'streaming':
            //TODO check asset dir existence on remote server
            
            include_once 'info.php';
            if ($remoteffmpeg_streaming_quality == 'none') 
                return false; 
            
             $asset_dir = get_asset_dir($asset);
            if(!file_exists($asset_dir)) {
                $logger->log(EventType::RECORDER_INFO_GET, LogLevel::DEBUG, "info_get: streaming: No asset dir found, no info to give. File: $asset_dir.", array(__FUNCTION__), $asset);
                return false;
            }
            
            $meta_assoc = xml_file2assoc_array("$asset_dir/_metadata.xml");
            
            // streaming is disabled if it has not been enabled by user
            // or if the module type is not of record type
            $module_type = (($cam_module == $module_name) ? 'cam' : 'slide'); 
            if ($meta_assoc['streaming'] === 'false' || ($meta_assoc['record_type'] !== 'camslide' && $meta_assoc['record_type'] != $module_type))
                return false;
            
            $streaming_info_array = array(
                "ip" => $remoteffmpeg_ip, 
                "submit_url" => $ezcast_submit_url,
                "protocol" => $remoteffmpeg_streaming_protocol,
                "album" => $meta_assoc['course_name'],
                "asset" => $meta_assoc['record_date'],
                "record_type" => $meta_assoc['record_type'],
                "module_type" => $module_type,
                "module_quality" => $remoteffmpeg_streaming_quality,
                "classroom" => $classroom,
                "netid" => $meta_assoc['netid'],
                "author" => $meta_assoc['author'],
                "title" => $meta_assoc['title']);
            return $streaming_info_array;
            break;
    }
}

/**
 * @implements
 * Returns the current status of the video slide
 * Status may be "open", "recording", "paused", "stopped", "error"
 */
function capture_remoteffmpeg_status_get() {
    global $remoteffmpeg_ip;
    global $remoteffmpeg_status_file;
    global $remote_script_datafile_get;
    global $remoteffmpeg_username;

    $cmd = "sudo -u $remoteffmpeg_username $remote_script_datafile_get $remoteffmpeg_ip $remoteffmpeg_status_file";
    $res = exec($cmd, $output, $errorcode);
    if ($errorcode) {
        return '';
    }

    return trim($res);
}

/**
 * @implements
 * Defines the status of the current video
 */
function capture_remoteffmpeg_status_set($status) {
    global $remoteffmpeg_ip;
    global $remoteffmpeg_status_file;
    global $remote_script_datafile_set;
    global $remoteffmpeg_username;
    global $logger;
    
    $return_val = 0;
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_datafile_set $remoteffmpeg_ip '$status' $remoteffmpeg_status_file";
    system($cmd, $return_val);
    if($return_val == 0) {
        $logger->log(EventType::TEST, LogLevel::DEBUG, "Status set to ".$status, array(__FUNCTION__));
    } else {
        $logger->log(EventType::TEST, LogLevel::ERROR, "Failed to set status to $status. Cmd: $cmd", array(__FUNCTION__));
    }
}

/**
 * @implements
 * Returns an array containing the features offered by the module
 * @global type $remoteffmpeg_features
 * @return type
 */
function capture_remoteffmpeg_features_get() {
    global $remoteffmpeg_features;
    global $remoteffmpeg_streaming_quality;

    if ($remoteffmpeg_streaming_quality == 'none') {
        if ($index = array_search('streaming', $remoteffmpeg_features) !== false) {
            unset($remoteffmpeg_features[$index]);
        }
    }
    return $remoteffmpeg_features;
}

function capture_remoteffmpeg_rec_status_get() {
    global $remoteffmpeg_ip;
    global $remoteffmpeg_rec_status_file;
    global $remote_script_datafile_get;
    global $remoteffmpeg_username;

    $cmd = "sudo -u $remoteffmpeg_username $remote_script_datafile_get $remoteffmpeg_ip $remoteffmpeg_rec_status_file";
    $res = exec($cmd, $output, $errorcode);
    if ($errorcode) {
        return '';
    }

    return trim($res);
}

function capture_remoteffmpeg_rec_status_set($status) {
    global $remoteffmpeg_ip;
    global $remoteffmpeg_rec_status_file;
    global $remote_script_datafile_set;
    global $remoteffmpeg_username;

    $cmd = "sudo -u $remoteffmpeg_username $remote_script_datafile_set $remoteffmpeg_ip '$status' $remoteffmpeg_rec_status_file";
    system($cmd, $return_val);
}
