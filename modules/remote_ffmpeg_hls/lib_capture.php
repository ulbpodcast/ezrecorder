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
require_once __DIR__. "/info.php";
require_once "$basedir/lib_ffmpeg.php";

$remoteffmpeg_module_name = $module_name;

/* Run given command in remote recorder
 * set process pid in $pid if background.
 * Return system call return value. (so, 0 is ok)
 */
function remote_call($cmd, $remote_log_file = "/dev/null", $background = false, &$pid = 0) {
    global $remote_recorder_username;
    global $remote_recorder_ip;
    global $logger;
    global $remote_script_call;


    $pid_file = "/var/tmp/" . uniqid();


    $remote_cmd = "$cmd 2>&1 >> $remote_log_file";
    $local_cmd = "ssh -o ConnectTimeout=10 -o BatchMode=yes $remote_recorder_ip \"$remote_cmd\" 2>&1 > /dev/null"; //we don't want any local printing
    if($background) {
        if(!is_writable("/tmp")) {
            $logger->log(EventType::RECORDER_REMOTE_CALL, LogLevel::CRITICAL, "Cannot write pid file $pid_file", array(__FUNCTION__));
            return 999;
        }

        $local_cmd .= " & echo $! > $pid_file";
    }

    //$logger->log(EventType::TEST, LogLevel::EMERGENCY, "$local_cmd", array(__FUNCTION__));

    $return_val = 0;
    system("sudo -u $remote_recorder_username $remote_script_call $local_cmd", $return_val); //FIXME: $remote_recorder_username is used as a local username... This is the case in several place in this file

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

/* Return true if remote fill exists
Also returns exact command return value by reference
 *  */
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
   global $remote_recorder_username;
   global $remote_recorder_ip;
   global $logger;

   if($remote_recorder_username == "" | $remote_recorder_ip == "") {
       $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, '$remote_recorder_username or $remote_recorder_ip not properly configured', array(__FUNCTION__));
       return false;
   }

   $return_val = 0;

   check_remote_file_existence($remoteffmpeg_config1, $return_val);
   if($return_val != 0) {
       $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, "Could not find $remoteffmpeg_config1 on remote recorder", array(__FUNCTION__));
       return false;
   }

   check_remote_file_existence($remoteffmpeg_config2, $return_val);
   if($return_val != 0) {
       $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, "Could not find $remoteffmpeg_config2 on remote recorder", array(__FUNCTION__));
       return false;
   }

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
    global $remote_recorder_ip;
    global $remote_recorder_username;
    global $remoteffmpeg_streaming_info;
    global $remote_script_datafile_set;
    global $logger;
    global $remoteffmpeg_module_name;

    file_put_contents('/home/arwillame/test/txt1.txt','YEAH'.PHP_EOL,FILE_APPEND);


    $return_val = 0;
    $success = validate_remote_install($return_val);
    if(!$success) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, "Remote recorder is inaccessible or not properly installed. Return val: $return_val", array(__FUNCTION__), $asset);
        return false;
    }
    file_put_contents('/home/arwillame/test/txt1.txt','1'.PHP_EOL,FILE_APPEND);
    $success = create_module_working_folders($remoteffmpeg_module_name, $asset);
    if(!$success) {
        $processUser = posix_getpwuid(posix_geteuid());
        $name = $processUser['name'];
        $logger->log(EventType::TEST, LogLevel::DEBUG, "current user: $name", array(__FUNCTION__), $asset);
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, "Could not create ffmpeg working folder. Check parent folder permissions ?", array(__FUNCTION__), $asset);
        return false;
    }

    // status of the current recording, should be empty
    /* Buggy check, fixme
    $status = capture_remoteffmpeg_status_get();
    if ($status != '') { // has a status
        error_last_message("capture_init: can't open because of current status: $status");
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::WARNING, "Current status is: '$status' at init time, this shouldn't happen. Try to continue anyway.", array(__FUNCTION__), $asset);
    }
    */
    file_put_contents('/home/arwillame/test/txt1.txt','2'.PHP_EOL,FILE_APPEND);

    $streaming_info = capture_remoteffmpeg_info_get('streaming', $asset);
    if ($streaming_info !== false) {
      file_put_contents('/home/arwillame/test/txt.txt','3'.PHP_EOL,FILE_APPEND);

        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::DEBUG, "Streaming is enabled", array(__FUNCTION__), $asset);

        $xml = xml_assoc_array2metadata($streaming_info);
        // put the xml string in a metadata file on the remote mac mini
        system("sudo -u $remote_recorder_username $remote_script_datafile_set $remote_recorder_ip " . escapeshellarg($xml) . " $remoteffmpeg_streaming_info &");
    }
    file_put_contents('/home/arwillame/test/txt1.txt','4'.PHP_EOL,FILE_APPEND);

    $working_dir = get_asset_module_folder($remoteffmpeg_module_name, $asset);

    //create remote asset folder first, to store the log files in there
    $cmd = "mkdir -p $working_dir";
    $return_val = remote_call($cmd, "/dev/null");
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Could not create remote asset folder. Return val: $return_val. Cmd: $cmd", array(__FUNCTION__), $asset);
        file_put_contents('/home/arwillame/test/txt1.txt','5'.PHP_EOL,FILE_APPEND);

        return false;
    }

    /* remote script call requires:
     * - the remote ip
     * - the absolute path to the logs file
     * - the remote script to execute
     */
    // '> /dev/null' discards output, '&' executes the process as background task and 'echo $!' returns the pid
    file_put_contents('/home/arwillame/test/txt1.txt','6'.PHP_EOL,FILE_APPEND);

    $streaming = $streaming_info !== false ? 'true' : 'false';
    $remote_log_file = $working_dir . '/init.log';
    $cmd = "$remoteffmpeg_script_init $asset $working_dir 1 $streaming";
    $return_val = remote_call($cmd, $remote_log_file, true, $pid);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Init command failed with return val $return_val. Cmd: $cmd", array(__FUNCTION__), $asset);
        capture_remoteffmpeg_status_set("launch_failure");
        file_put_contents('/home/arwillame/test/txt1.txt','7'.PHP_EOL,FILE_APPEND);

        return false;
    }
    file_put_contents('/home/arwillame/test/txt1.txt','8'.PHP_EOL,FILE_APPEND);

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
        capture_remoteffmpeg_recstatus_set('recording');
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
    $remote_log_file = $working_dir . '/pause_resume.log';
    $cmd = "$remoteffmpeg_script_cutlist $action $asset";
    $return_val = remote_call($cmd, $remote_log_file);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::ERROR, "Setting recording $asset failed. Command: $cmd", array(__FUNCTION__), $asset);
        return false;
    }

    $set_status = $pause ? 'paused' : 'recording';
    capture_remoteffmpeg_status_set($set_status);
    capture_remoteffmpeg_recstatus_set($set_status);
    $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::INFO, "Recording was $set_status'd by user", array(__FUNCTION__), $asset);

    echo "OK";
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
    $remote_log_file = $working_dir . '/stop.log';
    $cmd = "$remoteffmpeg_script_cutlist stop $asset";
    $return_val = remote_call($cmd, $remote_log_file);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::ERROR, "Record stopping failed with return val $return_val and cmd: $cmd", array(__FUNCTION__), $asset);
        return false;
    }

    capture_remoteffmpeg_status_set('stopped');
    capture_remoteffmpeg_recstatus_set('');

    $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::DEBUG, "Recording was stopped by user", array(__FUNCTION__), $asset);

    return true;
}

/**
 * @implements
 * Ends the current recording and saves it as an archive
 */
function capture_remoteffmpeg_cancel($asset = null) {
    global $remoteffmpeg_script_cancel;
    global $logger;
    global $remoteffmpeg_module_name;

    // cancels the current recording, saves it in archive dir and stops the monitoring
    if($asset != null) {
        $working_dir = get_asset_module_folder($remoteffmpeg_module_name, $asset);
        $remote_log_file = $working_dir . '/cancel.log';
        $cmd = "$remoteffmpeg_script_cancel $asset";
    } else {
        $cmd = $remoteffmpeg_script_cancel;
        $remote_log_file = "/dev/null";
    }

    $return_val = remote_call($cmd, $remote_log_file);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_CANCEL, LogLevel::ERROR, "Record cancel script start failed with return val $return_val and cmd: $cmd", array(__FUNCTION__), $asset);
        return false;
    }

    //update (clear) status
    capture_remoteffmpeg_recstatus_set('');
    $logger->log(EventType::RECORDER_CANCEL, LogLevel::INFO, "Recording was cancelled", array(__FUNCTION__));
    $logger->log(EventType::RECORDER_CANCEL, LogLevel::DEBUG, "Cancel backtrace: " . print_r(debug_backtrace(),true), array(__FUNCTION__));

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
    capture_remoteffmpeg_recstatus_set('');

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
        $logger->log(EventType::RECORDER_FINALIZE, LogLevel::ERROR, "Finalisation failed with return val $return_val. Cmd: $cmd", array(__FUNCTION__), $asset);
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
    global $remote_recorder_ip;
    global $remote_script_thumbnail_create;
    global $remote_recorder_username;
    global $logger;

    $remote_thumb_file = "$remoteffmpeg_basedir/var/pic_new.jpg";
    //if no image or image is old get a new screencapture
    if (!file_exists($remoteffmpeg_capture_file) || (time() - filemtime($remoteffmpeg_capture_file) > 1)) {
        $cmd = "sudo -u $remote_recorder_username $remote_script_thumbnail_create $remote_recorder_ip $remote_thumb_file $remoteffmpeg_capture_tmp_file";
        $return_val = 0;
        system($cmd, $return_val);

        //if command failed or remote script did not actually create image file
        if ($return_val != 0 || (time() - filemtime($remoteffmpeg_capture_tmp_file) > 60)) {
            //print "could not take a screencapture";
            copy("./nopic.jpg", "$remoteffmpeg_capture_file");
            if($return_val != 0)
                $logger->log(EventType::TEST, LogLevel::ERROR, "Could not get remote thumbnail file $remote_thumb_file (writing to $remoteffmpeg_capture_tmp_file). Permission problem?", array(__FUNCTION__));

        } else {
            //copy screencapture to actual snap
            $status = capture_remoteffmpeg_status_get();
            if ($status == 'recording') {
                $status = capture_remoteffmpeg_recstatus_get();
            }

            //invalid status in rec_status ?
            if($status == '') {
                $status = 'open';
            }

            $ok = image_resize($remoteffmpeg_capture_tmp_file, $remoteffmpeg_capture_transit_file, 235, 157, $status, false);
            if($ok)
                rename($remoteffmpeg_capture_transit_file, $remoteffmpeg_capture_file);
            else {
                copy("./nopic.jpg", $remoteffmpeg_capture_file);
            }
        }
    }
    return file_get_contents($remoteffmpeg_capture_file);
}

/**
 * @implements
 * Returns an associative array containing information required for given action
 * @return type
 */
function capture_remoteffmpeg_info_get($action, $asset = '') {
    global $remote_recorder_ip;
    global $external_remote_recorder_ip;
    global $remoteffmpeg_download_protocol;
    global $remoteffmpeg_streaming_protocol;
    global $remote_recorder_username;
    global $remoteffmpeg_upload_dir;
    global $remoteffmpeg_streaming_quality;
    global $ezcast_submit_url;
    global $classroom;
    global $cam_module;
    global $logger;
    global $remoteffmpeg_module_name;
    global $ezrecorder_username;

    $ip = $remote_recorder_ip;
    if($external_remote_recorder_ip != "")
        $ip = $external_remote_recorder_ip;

    switch ($action) {
        case 'download':
            $filename = $remoteffmpeg_upload_dir . '/' . $asset . "/slide.mov";
            $return_val = 0; //unused
            if(!check_remote_file_existence($filename, $return_val))
                $logger->log(EventType::RECORDER_INFO_GET, LogLevel::ERROR, "info_get: download: No slide file found. This may be because the file is missing or because it has yet to be processed. Or maybe ssh command failed? File: $filename", array(__FUNCTION__), $asset);

            // rsync requires ssh protocol is set (key sharing) on the remote server
            $download_info_array = array("ip" => $ip,
                "protocol" => $remoteffmpeg_download_protocol,
                "username" => $remote_recorder_username,
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
                "ip" => $ip,
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
    global $remote_recorder_ip;
    global $remoteffmpeg_status_file;
    global $remote_script_datafile_get;
    global $remote_recorder_username;

    $cmd = "sudo -u $remote_recorder_username $remote_script_datafile_get $remote_recorder_ip $remoteffmpeg_status_file";
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
    global $remote_recorder_ip;
    global $remoteffmpeg_status_file;
    global $remote_script_datafile_set;
    global $remote_recorder_username;
    global $logger;

    $return_val = 0;
    $cmd = "sudo -u $remote_recorder_username $remote_script_datafile_set $remote_recorder_ip '$status' $remoteffmpeg_status_file";
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

function capture_remoteffmpeg_recstatus_get() {
    global $remote_recorder_ip;
    global $remoteffmpeg_rec_status_file;
    global $remote_script_datafile_get;
    global $remote_recorder_username;
    global $logger;

    $cmd = "sudo -u $remote_recorder_username $remote_script_datafile_get $remote_recorder_ip $remoteffmpeg_rec_status_file";
    $res = exec($cmd, $output, $errorcode);
    if ($errorcode) {
        $logger->log(EventType::TEST, LogLevel::ERROR, "Failed to fetch status file from remote recorder", array(__FUNCTION__));
        return '';
    }

    return trim($res);
}

function capture_remoteffmpeg_recstatus_set($status) {
    global $remote_recorder_ip;
    global $remoteffmpeg_rec_status_file;
    global $remote_script_datafile_set;
    global $remote_recorder_username;
    global $logger;

    $return_val = 0;
    $cmd = "sudo -u $remote_recorder_username $remote_script_datafile_set $remote_recorder_ip '$status' $remoteffmpeg_rec_status_file";
    system($cmd, $return_val);
    if($return_val == 0) {
        $logger->log(EventType::RECORDER_SET_STATUS, LogLevel::DEBUG, "REC Status set to ".$status, array(__FUNCTION__));
    } else {
        $logger->log(EventType::RECORDER_SET_STATUS, LogLevel::ERROR, "Failed to set REC status to $status. Cmd: $cmd", array(__FUNCTION__));
    }
}

/**
 * @implements
 * @param type $asset
 * @return true on process success, false on failure or result not found
 */
/* Disabled: BUGGY for now. Is process result file properly created?
 * It also seems post_process does not properly wait on slide processing.
 * See "!! Slides processing ($slide_pid) NOT running at this point" error. Maybe it's just not put in background there.
 *
 */
function capture_remoteffmpeg_process_result($asset) {
    global $remote_recorder_ip;
    global $remote_script_datafile_get;
    global $remote_recorder_username;
    global $remoteffmpeg_module_name;
    global $process_result_filename;
    global $logger;

    $working_dir = get_asset_module_folder($remoteffmpeg_module_name, $asset);
    $result_file = "$working_dir/$process_result_filename";
    $cmd = "sudo -u $remote_recorder_username $remote_script_datafile_get $remote_recorder_ip $result_file";
    $errorcode = 0;
    $result = exec($cmd, $output, $errorcode);
    if ($errorcode != 0) {
        return false;
    }

    if($result)
        $result = trim($result);

    $success = $result !== false && $result == "0";
    $logger->log(EventType::RECORDER_FFMPEG_STOP, LogLevel::DEBUG, "File was found ($result_file), contain: $result. Returning success: " . ($success ? "true" : "false"), array(__FUNCTION__));
    return $success;
}
