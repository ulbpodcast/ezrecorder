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
require_once __DIR__. "/info.php";
require_once "$basedir/lib_ffmpeg.php";
    
$remoteffmpeg_module_name = $module_name;

/* Run given command in remote recorder
 * set process pid in $pid if background.
 * Return system call return value. (so, 0 is ok)
 */
function remote_call($cmd, $remote_log_file = "/dev/null", $background = false, &$pid = 0) {
    global $remoteffmpeg_username;
    global $remoteffmpeg_ip;
    global $logger;
    global $remote_script_call;
    
    
    $pid_file = "/var/tmp/" . uniqid();
    
        
    $remote_cmd = "$cmd 2>&1 >> $remote_log_file";
    $local_cmd = "ssh -o ConnectTimeout=10 -o BatchMode=yes $remoteffmpeg_ip \"$remote_cmd\" 2>&1 > /dev/null"; //we don't want any local printing
    if($background) {
        if(!is_writable("/tmp")) {
            $logger->log(EventType::RECORDER_REMOTE_CALL, LogLevel::CRITICAL, "Cannot write pid file $pid_file", array(__FUNCTION__));
            return 999;
        }
        
        $local_cmd .= " & echo $! > $pid_file";
    }
        
    //$logger->log(EventType::TEST, LogLevel::EMERGENCY, "$local_cmd", array(__FUNCTION__));
    
    $return_val = 0;
    system("sudo -u $remoteffmpeg_username $remote_script_call $local_cmd", $return_val);
    
    if($return_val == 0 && $background) {
        $pid = trim(file_get_contents($pid_file));
        if($pid == false) {
            $logger->log(EventType::RECORDER_REMOTE_CALL, LogLevel::ERROR, "No pid file found at $pid_file", array(__FUNCTION__));
        }
    }
    
    if(file_exists($pid_file))
        unlink($pid_file);
    
    return $return_val;
}

function check_remote_file_existence($remote_file, &$return_val) {
    
    $cmd = "test -f $remote_file";
    $return_val = remote_call($cmd, "/dev/null");
    if ($return_val != 0) {
        return false;
    }

    return true;
}

//return true if remote config files are found
function validate_remote_install(&$return_val) {
   global $remoteffmpeg_config1;
   global $remoteffmpeg_config2;
   
   $return_val = 0;
   
   check_remote_file_existence($remoteffmpeg_config1, $return_val);
   if($return_val != 0) 
       return false;
   check_remote_file_existence($remoteffmpeg_config2, $return_val);
   if($return_val != 0) 
       return false;
   
   return true;
}

/**
 * @implements 
 * Initialize the camera settings.
 * This function should be called before the use of the camera
 * @param associate_array $meta_assoc metadata relative to the current recording
 */
function capture_remoteffmpeg_init(&$pid, $meta_assoc, $asset) {
    global $remoteffmpeg_script_init;
    global $remoteffmpeg_ip;
    global $remoteffmpeg_username;
    global $remoteffmpeg_streaming_info;
    global $remote_script_datafile_set;
    global $logger;
    global $remoteffmpeg_module_name;
    
    $return_val = 0;
    $success = validate_remote_install($return_val);
    if(!$success) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, "Remote recorder is inaccessible or not properly installed. Return val: $return_val", array(__FUNCTION__), $asset);
        return false;
    }

    $success = create_module_working_folders($remoteffmpeg_module_name, $asset);
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
    
    $working_dir = get_asset_module_folder($remoteffmpeg_module_name, $asset);
        
    //create remote asset folder first, to store the log files in there
    $cmd = "mkdir -p $working_dir";
    $return_val = remote_call($cmd, "/dev/null");
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Could not create remote asset folder. Return val: $return_val. Cmd: $cmd", array(__FUNCTION__), $asset);
        return false;
    }
            
    /* remote script call requires:
     * - the remote ip
     * - the absolute path to the logs file
     * - the remote script to execute
     */
    // '> /dev/null' discards output, '&' executes the process as background task and 'echo $!' returns the pid

    $streaming = $streaming_info !== false ? 'true' : 'false';
    $remote_log_file = $working_dir . '/init.log';
    $cmd = "$remoteffmpeg_script_init $asset $working_dir 1 $streaming";
    $return_val = remote_call($cmd, $remote_log_file, true, $pid);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Init command failed with return val $return_val. Cmd: $cmd", array(__FUNCTION__), $asset);
        capture_remoteffmpeg_status_set("launch_failure");
        return false;
    }

    capture_remoteffmpeg_status_set('open');
    $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::INFO, "Successfully initialized module (init script is still running in background at this point)", array(__FUNCTION__), $asset);
    
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
    global $remoteffmpeg_username;
    global $logger;
    global $remoteffmpeg_module_name;
    
    create_module_working_folders($remoteffmpeg_module_name, $asset);
    $working_dir = get_asset_module_folder($remoteffmpeg_module_name, $asset);
    $remote_log_file = $working_dir . '/start.log';
    $cut_list = ffmpeg_get_cutlist_file($remoteffmpeg_module_name, $asset);
    
    /* remote script call requires:
     * - the remote ip
     * - the absolute path to the logs file
     * - the remote script to execute
     * - optional args for the script to execute
     */
    $cmd = "$remoteffmpeg_script_start $asset $working_dir $cut_list";
    $return_val = remote_call($cmd, $remote_log_file);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_START, LogLevel::ERROR,"Recording start failed at command execution with return code $return_val. Command: $cmd", array(__FUNCTION__), $asset);
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
    global $remoteffmpeg_module_name;

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
    
    $working_dir = get_asset_module_folder($remoteffmpeg_module_name, $asset);
    $remote_log_file = $working_dir . '/cutlist.log';
    $cmd = "$remoteffmpeg_script_cutlist $action $asset";
    $return_val = remote_call($cmd, $remote_log_file);
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
 * Stops the current recording (stop mark in cutlist, not real stop)
 */
function capture_remoteffmpeg_stop(&$pid, $asset) {
    global $remoteffmpeg_script_cutlist;
    global $logger;
    global $remoteffmpeg_module_name;

    $pid = 0; //pid not used, we don't background anything in there
    
    $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::NOTICE, "Stopping ffmpeg capture", array(__FUNCTION__), $asset);
        
    // check current recording status
    $status = capture_remoteffmpeg_status_get();
    if ($status != 'recording' && $status != "paused") {
        error_last_message("capture_stop: can't stop recording because current status: $status");
        $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::WARNING, "Can't stop recording because current status: $status", array(__FUNCTION__), $asset);
        return true; //not really an error, we're trying to stop when already stopped
    }
    
    $working_dir = get_asset_module_folder($remoteffmpeg_module_name, $asset);
    $remote_log_file = $working_dir . '/cutlist.log';
    $cmd = "$remoteffmpeg_script_cutlist stop $asset";
    $return_val = remote_call($cmd, $remote_log_file);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::ERROR, "Record stopping failed with return val $return_val and cmd: $cmd", array(__FUNCTION__), $asset);
        return false;
    }
    
    capture_remoteffmpeg_status_set('stopped');
    capture_remoteffmpeg_rec_status_set('');
    
    $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::DEBUG, "Recording was stopped by user", array(__FUNCTION__), $asset);

    return true;
}

/**
 * @implements
 * Ends the current recording and saves it as an archive
 */
function capture_remoteffmpeg_cancel($asset) {
    global $remoteffmpeg_script_cancel;
    global $logger;
    global $remoteffmpeg_module_name;

    // cancels the current recording, saves it in archive dir and stops the monitoring
    $working_dir = get_asset_module_folder($remoteffmpeg_module_name, $asset);
    $remote_log_file = $working_dir . '/cancel.log';
    $cmd = "$remoteffmpeg_script_cancel $asset";
    $return_val = remote_call($cmd, $remote_log_file);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_CANCEL, LogLevel::ERROR, "Record cancel script start failed with return val $return_val and cmd: $cmd", array(__FUNCTION__), $asset);
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
    global $remoteffmpeg_processing_tool;
    global $remoteffmpeg_processing_tools;
    global $slide_file_name;
    global $remoteffmpeg_module_name;
    global $logger;
    
    /*
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
     * 
     */
    
    if (!in_array($remoteffmpeg_processing_tool, $remoteffmpeg_processing_tools))
        $remoteffmpeg_processing_tool = $remoteffmpeg_processing_tools[0];

    // saves recording in processing dir and start processing
    $process_dir = get_asset_module_folder($remoteffmpeg_module_name, $asset);
    $remote_log_file = "$process_dir/stop.log"; //!! NOT OK if remote path is not the same as on this recorder
    $cmd = "$remoteffmpeg_script_stop $asset $slide_file_name";
    $return_val = remote_call("$remoteffmpeg_script_stop $asset $slide_file_name", $remote_log_file, true, $pid);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::CRITICAL, "FFMPEG stop script launch failed. Return val $return_val. Cmd: $cmd", array(__FUNCTION__), $asset);
        $pid = 0;
        return false;
    }
    
   //$logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::EMERGENCY, "REMOTE PROCESS PID FILE $pid", array(__FUNCTION__), $asset);

    
    //update (clear) status
    capture_remoteffmpeg_status_set('');
    capture_remoteffmpeg_rec_status_set('');

    // should be saved in Movies/local_processing/<date+hour>/
    // combine cam and slide:
    // Make sure 'at' command is activated

    return true;
}

/**
 * @implements
 * Finalizes the recording after it has been uploaded to the server.
 * The finalization consists in archiving video files in a specific dir
 * and removing all temp files used during the session.
 * @global type $remoteffmpeg_ip
 * @global type $remoteffmpeg_script_qtbfinalize
 * @global type $remote_script_datafile_get
 */
function capture_remoteffmpeg_finalize($asset) {
    global $remoteffmpeg_script_finalize;
    global $logger;
    global $remoteffmpeg_module_name;

    // launches finalization remote bash script
    $working_dir = get_asset_module_folder($remoteffmpeg_module_name, $asset, 'upload');
    $remote_log_file = $working_dir . '/finalize.log';
    $cmd = "$remoteffmpeg_script_finalize $asset";
    $return_val = remote_call($cmd, $remote_log_file);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_FINALIZE, LogLevel::ERROR, "Finalisation failed with return val $return_val", array(__FUNCTION__), $asset);
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
    global $remoteffmpeg_module_name;
    
    switch ($action) {
        case 'download':
            $filename = $remoteffmpeg_upload_dir . '/' . $asset . "/slide.mov";
            
            $cmd = "ssh -o ConnectTimeout=10 $remoteffmpeg_username@$remoteffmpeg_ip 'test -e $filename'";
            $return_val = 0;
            system($cmd, $return_val);

            if($return_val != 0)  {
                $logger->log(EventType::RECORDER_INFO_GET, LogLevel::ERROR, "info_get: download: No slide file found. This may be because the file is missing or because it has yet to be processed. File: $filename. Cmd: $cmd", array(__FUNCTION__), $asset);
            }

            //Todo: check file existence on remote server
            
            // rsync requires ssh protocol is set (key sharing) on the remote server
            $download_info_array = array("ip" => $remoteffmpeg_ip,
                "protocol" => $remoteffmpeg_download_protocol,
                "username" => $remoteffmpeg_username,
                "filename" => $filename);
            return $download_info_array;
        case 'streaming':
            //TODO check asset dir existence on remote server
            
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
            $module_type = (($cam_module == $remoteffmpeg_module_name) ? 'cam' : 'slide'); 
            if ($meta_assoc['streaming'] === 'false' || ($meta_assoc['record_type'] !== 'camslide' && $meta_assoc['record_type'] != $module_type))
                return false;
            
            $streaming_info_array = array(
                "ip" => $remoteffmpeg_ip, 
                "submit_url" => $ezcast_submit_url,
                "protocol" => $remoteffmpeg_streaming_protocol,
                "course" => $meta_assoc['course_name'],
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
        default:
            return false;
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
        $logger->log(EventType::RECORDER_SET_STATUS, LogLevel::DEBUG, "Status set to ".$status, array(__FUNCTION__));
    } else {
        $logger->log(EventType::RECORDER_SET_STATUS, LogLevel::ERROR, "Failed to set status to $status. Cmd: $cmd", array(__FUNCTION__));
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
    global $logger;
    
    $status = "'$status'";
    
    $cmd = "sudo -u $remoteffmpeg_username $remote_script_datafile_set $remoteffmpeg_ip $status $remoteffmpeg_rec_status_file";
    system($cmd, $return_val);
    if($return_val == 0) {
        $logger->log(EventType::RECORDER_SET_STATUS, LogLevel::DEBUG, "REC Status set to ".$status, array(__FUNCTION__));
    } else {
        $logger->log(EventType::RECORDER_SET_STATUS, LogLevel::ERROR, "Failed to set REC status to $status. Cmd: $cmd", array(__FUNCTION__));
    }
}
