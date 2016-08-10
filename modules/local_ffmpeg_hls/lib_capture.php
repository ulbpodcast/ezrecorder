<?php

require 'etc/config.inc';
require_once __DIR__ . '/../../global_config.inc';
require_once $basedir . '/common.inc';
require_once $basedir . '/lib_various.php';
require_once 'create_bash_configs.php';
include_once $basedir . '/lib_error.php';
require_once $basedir . '/lib_model.php';

$module_name = "capture_ffmpeg";
/*
 * This file contains all functions related to the video capture using ffmpeg
 * It implements the "recorder interface" which is used in web_index.
 * the function annotated with the comment "@implements" are required to make
 * sure the web_index.php can work properly.
 */

function create_working_dir($dir) {
    global $ezrecorder_username;
    
    $ok = true;
    if(!file_exists($dir)) {
        $ok = mkdir("$dir", 0777, true) && $ok; //mode is not set ??
        $ok = chmod("$dir", 0777) && $ok;
    }
    return $ok;
}

function create_ffmpeg_working_folders($asset) {
    global $logger;
    global $module_name;
    
    $dir = capture_ffmpeg_get_asset_ffmpeg_folder($asset);
    $ok = true;
    
    $ok = create_working_dir($dir) && $ok;
    
    if(!$ok) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, __FUNCTION__.": Error while creating ffmpeg working folders (probably permissions). Main folder: $dir", array("module",$module_name), $asset);
    }
    return $ok;
}

//get ffmpeg cutlist file for asset
function capture_ffmpeg_get_cutlist($asset) {
    return capture_ffmpeg_get_asset_ffmpeg_folder($asset) . '/' . '_cut_list';
}

//get ffmpeg working folder for asset. Folder can be both in local_processing and upload_to_server
function capture_ffmpeg_get_asset_ffmpeg_folder($asset) {
    return get_asset_dir($asset) . '/ffmpeg/';
}

//get log file for given asset
function capture_ffmpeg_get_log_file($asset) {
    return capture_ffmpeg_get_asset_ffmpeg_folder($asset) . '/_log';
}

/**
 * @implements
 * Initialize the recording settings.
 * This function should be called before the use of the camera.
 * This function should launch a background task to save time and keep syncro
 * between cam and slides (if both are available)
 * @param int $pid the process id of the background task. This is updated to process pid if function is successfull
 * @param associative_array $meta_assoc Metadata related to the record (used in cli_monitoring.php)
 * @return boolean true if everything went well; false otherwise
 */
function capture_ffmpeg_init(&$pid, $meta_assoc) {
    global $logger;
    global $module_name;
    global $ffmpeg_script_init;
    global $ffmpeg_recorder_logs;
    global $ezrecorder_username;
    global $ezcast_submit_url;
    global $ffmpeg_streaming_info;
    global $ffmpeg_streaming_quality;
    global $ffmpeg_input_source;
    global $php_cli_cmd;
    global $ffmpeg_cli_streaming;
    global $bash_env;
    global $ffmpeg_basedir;

    $asset = get_asset_name($meta_assoc['course_name'], $meta_assoc['record_date']);
    
    //$logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::DEBUG, __FUNCTION__.": called", array("module",$module_name), $asset, null);
    
    //prepare bash variables
    $success = create_bash_configs($bash_env, $ffmpeg_basedir . "etc/localdefs");
    if (!$success) {
        file_put_contents($ffmpeg_recorder_logs, "capture_ffmpeg_init: ERROR: Unable to create bash variables file\n", FILE_APPEND);
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, __FUNCTION__.": Unable to create bash variables file", array("module",$module_name));
        return false;
    }

    if (file_exists($ffmpeg_streaming_info))
        unlink($ffmpeg_streaming_info);

    $asset_dir = get_asset_dir($asset, "local_processing");
    if(!file_exists($asset_dir)) {
        $res = mkdir($asset_dir, 0777);
        if(!$res) {
            $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, "Could not create folder $asset_dir. Check parent permissions ?", array("module",$module_name));
            return false;
        }
    }
    
    // saves recording metadata as xml file
    $success = assoc_array2xml_file($meta_assoc, "$asset_dir/_metadata.xml");
    if(!$success) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, __FUNCTION__.": Can't init because _metadata writing failed", array("module",$module_name));
        return false;
    }

    // status of the current recording, should be empty
    $status = capture_ffmpeg_status_get();
    if ($status != '') { // has a status
        error_last_message("capture_init: can't open because current status: $status");
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, __FUNCTION__.": Can't init because current status: $status", array("module",$module_name));
        return false;
    }

    //if streaming is enabled, write it in '/var/streaming' ($ffmpeg_streaming_info) so that we may get the information later
    $streaming_info = capture_ffmpeg_info_get('streaming', $asset);
    if ($streaming_info !== false) {
        // defines that the streaming is enabled
        // It must be done before calling $ffmpeg_script_init (for preparing low and high HLS streams)
        file_put_contents($ffmpeg_streaming_info, var_export($streaming_info, true));
    }
    
    create_ffmpeg_working_folders($asset); //try to continue anyway if this fails
    
    // script_init initializes FFMPEG and launches the recording
    // in background to save time (pid is returned to be handled by web_index.php)
    $working_dir = capture_ffmpeg_get_asset_ffmpeg_folder($asset);
    $log_file = capture_ffmpeg_get_log_file($asset);
    
    system("sudo -u $ezrecorder_username $ffmpeg_script_init $asset $ffmpeg_input_source $working_dir 1 >> $log_file 2>&1 & echo $! > $working_dir/init_pid");
    $pid = file_get_contents("$working_dir/init_pid");
    
    // error occured while launching FFMPEG
    if (capture_ffmpeg_status_get() == 'launch_failure') {
        error_last_message("can't open because FFMPEG failed to launch");
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, __FUNCTION__.": Can't init because FFMPEG failed to launch", array("module",$module_name));
        return false;
    }
    // the recording is now 'open'
    capture_ffmpeg_status_set('open');
    $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::DEBUG, __FUNCTION__.": FFMPEG status set to 'open'", array("module",$module_name));

    // init the streaming
    if ($streaming_info !== false) {
        // streaming is enabled, we send a request to EZmanager to
        // init the streamed asset
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::DEBUG, __FUNCTION__.": Streaming is enabled", array("module",$module_name));

        $post_array = $streaming_info;
        $post_array['action'] = 'streaming_init';
        $result = server_request_send($ezcast_submit_url, $post_array);

        if (strpos($result, 'Curl error') !== false) {
            // an error occured with CURL
            $meta_assoc['streaming'] = 'false';
            unlink($ffmpeg_streaming_info);
            $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, __FUNCTION__.": Curl failed to send request to server: $result", array("module",$module_name));
        }
        //not used $result = unserialize($result);
        // executes the command for sending TS segments to EZmanager in background
        // for low and high qualities
        if (strpos($ffmpeg_streaming_quality, 'high') !== false) {
            exec("$php_cli_cmd $ffmpeg_cli_streaming " . $meta_assoc['course_name']
                    . " " . $meta_assoc['record_date'] . " high > /dev/null &", $output, $errno);
        }
        if (strpos($ffmpeg_streaming_quality, 'low') !== false) {
            exec("$php_cli_cmd $ffmpeg_cli_streaming " . $meta_assoc['course_name']
                    . " " . $meta_assoc['record_date'] . " low > /dev/null &", $output, $errno);
        }
    }

    $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::INFO, __FUNCTION__.": Successfully initialized module (init script is still running in background at this point)", array("module",$module_name));
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
        $troubleshoot_output .= "/!\ Camera ping ($cam_ip) failed". PHP_EOL;
    }
}

/**
 * @implements
 * Launches the recording process
 */
function capture_ffmpeg_start($asset) {
    global $logger;
    global $module_name;
    global $ffmpeg_script_start;
    global $ffmpeg_recorder_logs;
    global $ezrecorder_username;
    global $ffmpeg_input_source;

    //$logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": called", array("module",$module_name));
    
    create_ffmpeg_working_folders($asset);
    $log_file = capture_ffmpeg_get_log_file($asset);
    $working_dir = capture_ffmpeg_get_asset_ffmpeg_folder($asset);
    $cut_list = capture_ffmpeg_get_cutlist($asset);
    
    // adds time in the cutlist
    $return_code = 0;
    system("sudo -u $ezrecorder_username $ffmpeg_script_start $asset $working_dir $ffmpeg_input_source $cut_list >> $log_file 2>&1 &", $return_code);
    if($return_code != 0) {
        $logger->log(EventType::RECORDER_START, LogLevel::INFO,"Recording start failed at $ffmpeg_script_start execution with return code $return_code.", array("module",$module_name, "capture_ffmpeg_start"));
        return false;
    }
    
    //TODO: The status has probably not changed yet since the last process in started in background
    //update recording status
    $status = capture_ffmpeg_status_get();
    if ($status == "open") {
        capture_ffmpeg_status_set('recording');
        $logger->log(EventType::RECORDER_START, LogLevel::INFO, __FUNCTION__.": User started recording", array("module",$module_name));
    } else {
        capture_ffmpeg_status_set("error");
        error_last_message("capture_start: can't start recording because current status: $status");
        $logger->log(EventType::RECORDER_START, LogLevel::ERROR, __FUNCTION__.": Recording could not be started. Status: $status", array("module",$module_name));
        return false;
    }

    //$logger->log(EventType::RECORDER_START, LogLevel::DEBUG, __FUNCTION__.": Status at function end: $status", array("module",$module_name));

    return true;
}

//pause or resume recording, by writing in the cutlist.
// @param $action string Only valid inputs are "pause" or "resume"
function capture_ffmpeg_pause_resume($action, $asset) {
    $pause = $action == "pause";
    $resume = $action == "resume";
    
    if(!$pause && !$resume)
        return false;
    
    global $logger;    
    global $module_name;
    global $ffmpeg_script_cutlist;
    global $ezrecorder_username;
    
    // get status of the current recording
    $status = capture_ffmpeg_status_get();
    if(   ($pause  && $status != 'recording')
       || ($resume && $status != 'paused' && $status != 'stopped')  ) {
        error_last_message("capture_pause: can't $action recording because current status: $status");
        $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::WARNING, __FUNCTION__.": Can't $action recording because current status: $status", array("module",$module_name));
        return false;
    }
    
    $return_val = 0;
    $cutlist_file = capture_ffmpeg_get_cutlist($asset);
    $log_file = capture_ffmpeg_get_log_file($asset);
    $cmd = "sudo -u $ezrecorder_username $ffmpeg_script_cutlist $action $cutlist_file >> $log_file 2>&1 &";
    system($cmd, $return_val);
    if($return_val != 0) {
        echo "$cmd failed";
        $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::ERROR, "Setting recording $asset failed (file: $cutlist_file). Command: $cmd", array("module",$module_name), $asset);
        return false;
    }
    
    $set_status = $pause ? 'paused' : 'recording';
    capture_ffmpeg_status_set($set_status);
    $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::INFO, "Recording was $asset" . 'd by user', array("module",$module_name), $asset);
    echo "ok";
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
    global $module_name;
    global $ffmpeg_script_cutlist;
    global $ffmpeg_recorder_logs;
    global $ezrecorder_username;

    $pid = 0; //pid not used, we don't background anything in there
    
    //$logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": called", array("module",$module_name));
    
    $working_dir = capture_ffmpeg_get_asset_ffmpeg_folder($asset);

    // get status of the current recording
    $status = capture_ffmpeg_status_get();
    if ($status == 'recording' || $status == "paused") {
        // pauses the current recording (while user chooses the way to publish the record)
        $cut_list = capture_ffmpeg_get_cutlist($asset);
        $log_file = capture_ffmpeg_get_log_file($asset);
        $cmd = "sudo -u $ezrecorder_username $ffmpeg_script_cutlist stop $cut_list >> $log_file 2>&1";
        $return_var = 0;
        system($cmd, $return_var);
        if($return_var != 0) {
            $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::ERROR, "Record stopping failed: $cmd", array("module",$module_name));
            return false;
        }
        //$pid = file_get_contents("$tmp_dir/stop_pid");
        // set the new status for the current recording
        capture_ffmpeg_status_set('stopped');
        $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::INFO, __FUNCTION__.": Recording was stopped by user", array("module",$module_name));
    } else {
        error_last_message("capture_stop: can't stop recording because current status: $status");
        $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::WARNING, __FUNCTION__.": Can't stop recording because current status: $status", array("module",$module_name));
        return false;
    }

    return true;
}

//return 0 on success, any other value on failure (-1 if file not found, else error code from ffmpeg_stop)
function capture_ffmpeg_stop_result($asset) {
    $working_dir = capture_ffmpeg_get_asset_ffmpeg_folder($asset);
    $stop_status_file = "$working_dir/stop_status";
    if(file_exists($stop_status_file)) {
        $content = file_get_contents($stop_status_file);
        if($content == false)
            return -1;
        else if($content == "0")
            return 0;
        else
            return $content;
    }
        
    return -1; //failure by default
}

/**
 * @implements
 * Ends the current recording and saves it as an archive
 */
function capture_ffmpeg_cancel($asset) {
    global $logger;
    global $module_name;
    global $ffmpeg_script_cancel;
    global $ezrecorder_username;

    //$logger->log(EventType::RECORDER_CANCEL, LogLevel::DEBUG, __FUNCTION__.": called", array("module",$module_name, "capture_ffmpeg_cancel"));
    
    // cancels the current recording, saves it in archive dir and stops the monitoring
    $log_file = capture_ffmpeg_get_log_file($asset);
    $cmd = 'sudo -u ' . $ezrecorder_username . ' ' . $ffmpeg_script_cancel . ' ' . $asset . ' >> ' . $log_file . ' 2>&1 &';
    log_append('recording', "launching command: $cmd");
    $fpart = exec($cmd, $outputarray, $errorcode);
    $post_array = capture_ffmpeg_info_get('streaming', $asset);
    if ($post_array !== false) {
        // streaming enabled
        global $ezcast_submit_url;
        $post_array['action'] = 'streaming_close';
        $res = server_request_send($ezcast_submit_url, $post_array);
        if (strpos($res, 'error') !== false) {
            $logger->log(EventType::RECORDER_CANCEL, LogLevel::ERROR, __FUNCTION__.": An error occured while stopping streaming on the server", array("module",$module_name, "capture_ffmpeg_cancel"));
        }
    }
    capture_ffmpeg_recstatus_set('');
    $logger->log(EventType::RECORDER_CANCEL, LogLevel::INFO, __FUNCTION__.": Recording was cancelled", array("module",$module_name, "capture_ffmpeg_cancel"));

    return true;
}

/** 
 * @implements
 * @param type $asset
 * @return true on process success, false on failure or result not found
 */
function capture_ffmpeg_process_result($asset) {
    $working_dir = get_asset_dir($asset);
    $result_file = "$working_dir/process_result";
    if(!file_exists($result_file))
        return false;
    $result = file_get_contents($result_file);
    return $result != false && $result == "0";
}

/**
 * @implements
 * Processes the record before sending it to the server
 * @param assoc_array $metadata_assoc metadata relative to current recording
 */
function capture_ffmpeg_process($asset, &$pid) {
    global $logger;
    global $module_name;
    global $ffmpeg_script_stop;
    global $ffmpeg_processing_tool;
    global $ffmpeg_processing_tools;
    global $ezrecorder_username;
    global $php_cli_cmd;

    //$logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::DEBUG, __FUNCTION__.": called", array("module",$module_name));
    
    $tmp_dir = capture_ffmpeg_tmpdir_get($asset);

    if (!in_array($ffmpeg_processing_tool, $ffmpeg_processing_tools))
        $ffmpeg_processing_tool = $ffmpeg_processing_tools[0];

    $status = capture_ffmpeg_status_get();
    if ($status != 'recording' && $status != 'open') {
        // saves recording in processing dir and processes it
        $process_dir = capture_ffmpeg_get_asset_ffmpeg_folder($asset);
        $pid_file = "$process_dir/process_pid";
        $log_file = "$process_dir/stop.log";
        $cmd = "sudo -u $ezrecorder_username $ffmpeg_script_stop $asset >> $log_file 2>&1  & echo $! > $pid_file";
        log_append('recording', "launching command: $cmd");
        // returns the process id of the background task
        $return_val = 0;
        system($cmd, $return_val);
        if($return_val != 0) {
            $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::CRITICAL, "FFMPEG stop script launch failed with return code $return_val", array("module",$module_name));
            $pid = 0;
            return false;
        }
        $pid = file_get_contents($pid_file);

        $post_array = capture_ffmpeg_info_get('streaming', $asset);
        if ($post_array !== false) {
            global $ezcast_submit_url;
            $post_array['action'] = 'streaming_close';
            $res = server_request_send($ezcast_submit_url, $post_array);
            if (strpos($res, 'error') !== false) {
                $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::ERROR, __FUNCTION__.": An error occured while starting streaming on the server", array("module",$module_name));
            }
        }

        //update (clear) status
        capture_ffmpeg_status_set('');
        capture_ffmpeg_recstatus_set('');
    } else {
        error_last_message("capture_stop: can't start recording process because of current status: $status");
        $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::ERROR, __FUNCTION__.": Can't start recording process because of current status: $status", array("module",$module_name));
        $pid = 0;
        return false;
    }

    //should be saved in get_asset_dir($asset, "local_processing");
    //combine cam and slide:
    //one need to activate at on the mac:
    //	vi /System/Library/LaunchDaemons/com.apple.atrun.plisto
    //	change Disabled tag value from <true /> to <false/>
    //   	launchctl unload -F /System/Library/LaunchDaemons/com.apple.atrun.plist
    //  	launchctl load -F /System/Library/LaunchDaemons/com.apple.atrun.plist

    $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::DEBUG, __FUNCTION__.": Processing successfully started", array("module",$module_name));
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
    global $module_name;
    global $ffmpeg_script_finalize;
    global $ffmpeg_recorder_logs;
    global $ezrecorder_username;

    //$logger->log(EventType::RECORDER_FINALIZE, LogLevel::DEBUG, __FUNCTION__.": called", array("module",$module_name));
    
    $asset_dir = get_upload_to_server_dir($asset);
    $metadata_file = "$asset_dir/_metadata.xml";
    // retrieves course_name and record_date
    $meta_assoc = xml_file2assoc_array($metadata_file); 
    if($meta_assoc == false) {
        $logger->log(EventType::RECORDER_FINALIZE, LogLevel::ERROR, "Couldn't get metadata file $metadata_file for finalization", array("module",$module_name));
        return false;
    }
    
    // launches finalization bash script
    $asset_name = get_asset_name( $meta_assoc['course_name'], $meta_assoc['record_date']);
    $log_file = get_asset_dir($asset) . '/ffmpeg/finalize.log';
    $cmd = 'sudo -u ' . $ezrecorder_username . ' ' . $ffmpeg_script_finalize . " $asset_name >> " . $log_file . ' 2>&1';
    log_append("finalizing: execute cmd '$cmd'");
    $return_val = 0;
    $output = system($cmd, $return_val);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_FINALIZE, LogLevel::ERROR, __FUNCTION__.": Finalisation failed with error code $return_val and output $output", array("module",$module_name));
        return false;
    }
    
    $logger->log(EventType::RECORDER_FINALIZE, LogLevel::DEBUG, __FUNCTION__.": Successfully finished finalization", array("module",$module_name));
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
    global $module_name;
    global $ezrecorder_ip;
    global $ffmpeg_download_protocol;
    global $ffmpeg_streaming_protocol;
    global $ffmpeg_streaming_quality;
    global $ezrecorder_username;
    global $classroom;
    global $cam_module;
    global $logger;
    
    switch ($action) {
        case 'download':
            $filename = get_upload_to_server_dir($asset) . "/cam.mov";
            if(!file_exists($filename)) {
                $logger->log(EventType::RECORDER_INFO_GET, LogLevel::DEBUG, "info_get: download: No camera file found, no info to give. File: $filename.", array("module",$module_name));
                return false; //invalid file
            }
            
            // rsync requires ssh protocol is set (key sharing) on the remote server
            $download_info_array = array("ip" => $ezrecorder_ip,
                "protocol" => $ffmpeg_download_protocol,
                "username" => $ezrecorder_username,
                "filename" => $filename);
            return $download_info_array;
        case 'streaming':
            include_once 'info.php';
            
            if ($ffmpeg_streaming_quality == 'none')
                return false;
            
            $asset_dir = get_local_processing_dir($asset);
            if(!file_exists($asset_dir)) {
                $logger->log(EventType::RECORDER_INFO_GET, LogLevel::DEBUG, "info_get: streaming: No asset dir found, no info to give. File: $asset_dir.", array("module",$module_name));
                return false;
            }
            
            $meta_assoc = xml_file2assoc_array("$asset_dir/_metadata.xml");
    
            $module_type = (($cam_module == $module_name) ? 'cam' : 'slide');
            // streaming is disabled if it has not been enabled by user
            // or if the module type is not of record type
            if ($meta_assoc['streaming'] === 'false' || ($meta_assoc['record_type'] !== 'camslide' && $meta_assoc['record_type'] != $module_type))
                return false;
            
            $streaming_info_array = array(
                "ip" => $ezrecorder_ip,
                "protocol" => $ffmpeg_streaming_protocol,
                "album" => $meta_assoc['course_name'],
                "asset" => $meta_assoc['record_date'],
                "record_type" => $meta_assoc['record_type'],
                "module_type" => $module_type,
                "module_quality" => $ffmpeg_streaming_quality,
                "classroom" => $classroom,
                "netid" => $meta_assoc['netid'],
                "author" => $meta_assoc['author'],
                "title" => $meta_assoc['title']);
            
            return $streaming_info_array;
    }
}

/**
 * @implements
 * Creates a thumbnail picture
 * @return string the contents of the image to display
 */
function capture_ffmpeg_thumbnail() {
    global $ffmpeg_basedir;
    global $ffmpeg_capture_file;

    // Camera screenshot
    $diff = time() - filemtime($ffmpeg_capture_file);
    if (!file_exists($ffmpeg_capture_file) || ($diff > 3)) {
        //if no image or image is old get a new screencapture
        if ((time() - filemtime("$ffmpeg_basedir/var/pic_new.jpg") > 3)) {
            //print "could not take a screencapture";
            copy("./nopic.jpg", $ffmpeg_capture_file);
        } else {
            //copy screencapture to actual snap
            $status = capture_ffmpeg_status_get();
            if ($status == 'recording') {
                $status = capture_ffmpeg_recstatus_get();
            }
            rename("$ffmpeg_basedir/var/pic_new.jpg", $ffmpeg_capture_file);
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
    global $module_name;
    global $ffmpeg_status_file;

    file_put_contents($ffmpeg_status_file, $status);
    $logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": Status set to ".$status, array("module",$module_name));
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
    global $module_name;
    global $ffmpeg_recstatus_file;

    file_put_contents($ffmpeg_recstatus_file, $status);
    $logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": rectatus set to: '".$status . "'. Caller: " . debug_backtrace()[1]['function'], array("module",$module_name));
}

function capture_ffmpeg_tmpdir_get($asset) {
    global $basedir;
    static $tmp_dir;

    $tmp_dir = $basedir . '/var/local_ffmpeg_hls/'. $asset;
    if (!file_exists($tmp_dir))
        mkdir($tmp_dir, 0777, true);

    return $tmp_dir;
}

?>
