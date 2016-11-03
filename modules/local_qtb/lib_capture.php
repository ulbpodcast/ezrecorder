<?php

/*
 * EZCAST EZrecorder
 *
 * Copyright (C) 2016 Université libre de Bruxelles
 *
 * Written by Michel Jansens <mjansens@ulb.ac.be>
 * 	      Arnaud Wijns <awijns@ulb.ac.be>
 *            Antoine Dewilde
 * UI Design by Julien Di Pietrantonio
 *
 * This software is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this software; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require 'config.inc';
require_once $basedir . '/lib_various.php';
require_once $basedir . '/lib_error.php';
require_once $basedir . '/common.inc';

$module_name = "capture_localqtb";

/*
 * This file contains all functions related to the video capture from an analog camera.
 * It implements the "recorder interface" which is used in web_index. 
 * the function annotated with the comment "@implements" are required to make 
 * sure the web_index.php can work properly.
 */

/**
 * @implements 
 * Initialize the recording settings.
 * This function should be called before the use of the camera.
 * This function should launch a background task to save time and keep syncro
 * between cam and slides (if both are available)
 * @param int $pid the process id of the background task
 * @param associative_array $meta_assoc Metadata related to the record (used in cli_monitoring.php)
 * @return boolean true if everything went well; false otherwise
 */
function capture_localqtb_init(&$pid, $meta_assoc, $asset) {
    global $logger;
    global $localqtb_script_qtbnew;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    $logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": called", array(__FUNCTION__), $asset);
    
    $tmp_dir = capture_localqtb_tmpdir_get($asset);

    // saves recording metadata as xml file 
    xml_assoc_array2file($meta_assoc, "$tmp_dir/_metadata.xml");

    // status of the current recording
    $status = capture_localqtb_status_get();
    if ($status == '') { // no status yet
        // qtbnew initializes QuickTime Broadcaster and runs a recording test
        // launched in background to save time (pid is returned to be handled by web_index.php)
        system("sudo -u $localqtb_username $localqtb_script_qtbnew >> $localqtb_recorder_logs 2>&1 & echo $! > $tmp_dir/pid");
        $pid = file_get_contents("$tmp_dir/pid");
        // error occured while launching QTB
        if (capture_localqtb_status_get() == 'launch_failure') {
            error_last_message("can't open because QTB failed to launch");
            $logger->log(EventType::TEST, LogLevel::ERROR, __FUNCTION__.": Can't open because QTB failed to launch", array(__FUNCTION__), $asset);
            return false;
        }
        // the recording is now 'open'
        capture_localqtb_status_set('open');
    } else {
        error_last_message("capture_init: can't open because current status: $status");
        $logger->log(EventType::TEST, LogLevel::ERROR, __FUNCTION__.": Can't open because current of status: $status", array(__FUNCTION__), $asset);
        return false;
    }

    $logger->log(EventType::TEST, LogLevel::INFO, __FUNCTION__.": Successfully initialized module", array("module:capture_localqtb_init"));
    return true;
}

/**
 * @implements
 * Launches the recording process 
 */
function capture_localqtb_start($asset) {
    global $logger;
    global $localqtb_script_qtbrec;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    $logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": called", array(__FUNCTION__), $asset);
    
    // qtbrec starts the recording in QTB
    // $pid is used in web_index.php
    system("sudo -u $localqtb_username $localqtb_script_qtbrec >> $localqtb_recorder_logs 2>&1 &");

    //update recording status
    $status = capture_localqtb_status_get();
    if ($status == "open") {
        capture_localqtb_status_set('recording');
    } else {
        capture_localqtb_status_set("error");
        error_last_message("capture_start: can't start recording because current status: $status");
        $logger->log(EventType::TEST, LogLevel::WARNING, __FUNCTION__.": Can't start recording because of current status: $status", array(__FUNCTION__), $asset);
        return false;
    }

    return true;
}

/**
 * @implements
 * Pauses the current recording
 */
function capture_localqtb_pause($asset) {
    global $logger;
    global $module_name;
    global $localqtb_script_qtbpause;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    $logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": called", array(__FUNCTION__), $asset);
    
    // get status of the current recording
    $status = capture_localqtb_status_get();
    if ($status == 'recording') {
        // qtbpause pauses the recording in QTB
        system("sudo -u $localqtb_username $localqtb_script_qtbpause >> $localqtb_recorder_logs 2>&1 &");
        capture_localqtb_status_set('paused');
        $logger->log(EventType::TEST, LogLevel::INFO, __FUNCTION__.": Recording was paused", array(__FUNCTION__), $asset);
    } else {
        error_last_message("capture_pause: can't pause recording because current status: $status");
        $logger->log(EventType::TEST, LogLevel::ERROR, __FUNCTION__.": Can't pause recording because of current status: $status", array(__FUNCTION__), $asset);
        return false;
    }

    return true;
}

/**
 * @implements
 * Resumes the current paused recording
 */
function capture_localqtb_resume($asset) {
    global $logger;
    global $module_name;
    global $localqtb_script_qtbresume;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    $logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": called", array(__FUNCTION__), $asset);
    
    // get status of the current recording
    $status = capture_localqtb_status_get();
    if ($status == 'paused' || $status == 'stopped') {
        // qtbresume resumes the current recording
        system("sudo -u $localqtb_username $localqtb_script_qtbresume >> $localqtb_recorder_logs 2>&1 &");
        // sets the new status of the current recording
        capture_localqtb_status_set('recording');
        $logger->log(EventType::TEST, LogLevel::INFO, __FUNCTION__.": Recording was resumed", array(__FUNCTION__), $asset);
    } else {
        error_last_message("capture_resume: can't resume recording because current status: $status");
        $logger->log(EventType::TEST, LogLevel::WARNING, __FUNCTION__.": Can't resume recording because of current status: $status", array(__FUNCTION__), $asset);
        return false;
    }

    return true;
}

/**
 * @implements
 * Stops the current recording
 */
function capture_localqtb_stop(&$pid, $asset) {
    global $logger;
    global $module_name;
    global $localqtb_script_qtbpause;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    $logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": called", array(__FUNCTION__), $asset);
    
    $tmp_dir = capture_localqtb_tmpdir_get($asset);

    // get status of the current recording
    $status = capture_localqtb_status_get();
    $last_status = $status;
    if ($status == 'recording') {
        // pauses the current recording (while user chooses the way to publish the record)
        system("sudo -u $localqtb_username $localqtb_script_qtbpause >> $localqtb_recorder_logs 2>&1 & echo $! > $tmp_dir/pid");
        $pid = file_get_contents("$tmp_dir/pid");
        // set the new status for the current recording
        capture_localqtb_status_set('stopped');
    } else if ($status == 'paused') {
        capture_localqtb_status_set('stopped');
    } else {
        error_last_message("capture_stop: can't pause recording because current status: $status");
        $logger->log(EventType::TEST, LogLevel::WARNING, __FUNCTION__.": Can't stop recording because of current status: $status", array(__FUNCTION__), $asset);
        return false;
    }

    $logger->log(EventType::TEST, LogLevel::INFO, __FUNCTION__.": Recording was stopped. Last status was: $last_status", array(__FUNCTION__), $asset);
    return true;
}

/**
 * @implements
 * Ends the current recording and saves it as an archive
 */
function capture_localqtb_cancel($asset) {
    global $logger;
    global $module_name;
    global $localqtb_script_qtbcancel;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    $logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": called", array(__FUNCTION__), $asset);
    
    // get status of the current recording
    $status = capture_localqtb_status_get();
    if ($status == 'recording' || $status == 'stopped' || $status == 'paused' || $status == 'open' || $status == '') {
        // qtbcancel cancels the current recording, saves it in archive dir and stops the monitoring
        $cmd = 'sudo -u ' . $localqtb_username . ' ' . $localqtb_script_qtbcancel . ' ' . $asset . ' >> ' . $localqtb_recorder_logs . ' 2>&1';
        log_append('recording', "launching command: $cmd");
        $fpart = exec($cmd, $outputarray, $errorcode);
        $logger->log(EventType::TEST, LogLevel::INFO, __FUNCTION__.": Recording was cancelled", array(__FUNCTION__), $asset);
    } else {
        error_last_message("capture_cancel: can't cancel recording because current status: " . $status);
        $logger->log(EventType::TEST, LogLevel::WARNING, __FUNCTION__.": Can't cancel recording because of current status: $status", array(__FUNCTION__), $asset);
        return false;
    }

    return true;
}

/**
 * @implements
 * Processes the record before sending it to the server
 * @param assoc_array $metadata_assoc metadata relative to current recording
 */
function capture_localqtb_process($asset, &$pid) {
    global $logger;
    global $module_name;
    global $localqtb_script_qtbstop;
    global $localqtb_recorder_logs;
    global $localqtb_processing_tool;
    global $localqtb_processing_tools;
    global $localqtb_username;

    $logger->log(EventType::TEST, LogLevel::DEBUG, "called", array(__FUNCTION__), $asset);
    
    $tmp_dir = capture_localqtb_tmpdir_get($asset);

    if (!in_array($localqtb_processing_tool, $localqtb_processing_tools))
        $localqtb_processing_tool = $localqtb_processing_tools[0];

    $status = capture_localqtb_status_get();
    if ($status != 'recording' && $status != 'open') {
        // saves recording in processing dir and processes it
        // launched in background to save time 
        $cmd = 'sudo -u ' . $localqtb_username . ' ' . $localqtb_script_qtbstop . ' ' . $asset . ' ' . $localqtb_processing_tool . ' >> ' . $localqtb_recorder_logs . ' 2>&1  & echo $! > ' . $tmp_dir . '/pid';
        log_append('recording', "launching command: $cmd");
        // returns the process id of the background task
        system($cmd);
        $pid = file_get_contents("$tmp_dir/pid");

        //update (clear) status
        capture_localqtb_status_set('');
    } else {
        error_last_message("capture_stop: can't process recording because current status: $status");
        $logger->log(EventType::TEST, LogLevel::WARNING, "Can't cancel process because of current status: $status", array(__FUNCTION__), $asset);
        return false;
    }

    //should be saved in Movies/local_processing/<date+hour>/
    //combine cam and slide:
    //one need to activate at on the mac:
    //	vi /System/Library/LaunchDaemons/com.apple.atrun.plisto
    //	change Disabled tag value from <true /> to <false/>
    //   	launchctl unload -F /System/Library/LaunchDaemons/com.apple.atrun.plist
    //  	launchctl load -F /System/Library/LaunchDaemons/com.apple.atrun.plist

    $logger->log(EventType::TEST, LogLevel::INFO, "Processing successfully started", array(__FUNCTION__), $asset);
    
    return true;
}

/**
 * @implements
 * Finalizes the recording after it has been uploaded to the server.
 * The finalization consists in archiving video files in a specific dir
 * and removing all temp files used during the session.
 * @global type $localqtb_script_qtbfinalize
 * @global type $localqtb_recorder_logs
 * @global type $dir_date_format
 */
function capture_localqtb_finalize($asset) {
    global $logger;
    global $module_name;
    global $localqtb_script_qtbfinalize;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    $logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": called", array(__FUNCTION__), $asset);
    
    // launches finalization bash script
    $cmd = 'sudo -u ' . $localqtb_username . ' ' . $localqtb_script_qtbfinalize . ' ' . $asset . ' >> ' . $localqtb_recorder_logs . ' 2>&1  & echo $!';
    log_append("finalizing: execute cmd '$cmd'");
    exec($cmd);
    $logger->log(EventType::TEST, LogLevel::INFO, __FUNCTION__.": Finished finalization", array(__FUNCTION__), $asset);
}


/**
 * @implements
 * Returns an associative array containing information required for given action
 * @global type $localqtb_ip
 * @global type $localqtb_download_protocol
 * @global type $localqtb_username
 * @return type
 */
function capture_localqtb_info_get($action, $asset = '') {
    global $logger;
    global $localqtb_ip;
    global $localqtb_download_protocol;
    global $localqtb_username;
    global $localqtb_upload_dir;

    switch ($action) {
        case 'download':
            $filename = $localqtb_upload_dir . $asset . "/cam.mov";
            if(!file_exists($filename)) {
                $logger->log(EventType::RECORDER_INFO_GET, LogLevel::DEBUG, "info_get: download: No camera file found, no info to give. File: $filename.", array(__FUNCTION__), $asset);
                return false; //invalid file
            }
            
            // rsync requires ssh protocol is set (key sharing) on the remote server
            $download_info_array = array("ip" => $localqtb_ip,
                "protocol" => $localqtb_download_protocol,
                "username" => $localqtb_username,
                "filename" => $filename);
            return $download_info_array;
            break;
    }
}

/**
 * @implements
 * Creates a thumbnail picture
 * @return string the contents of the image to display
 */
function capture_localqtb_thumbnail() {
    global $logger;
    global $localqtb_basedir;
    global $localqtb_script_qtbthumbnail;
    global $localqtb_capture_file;
    global $localqtb_username;

    // Camera screenshot
    $diff = time() - filemtime($localqtb_capture_file);
    if (!file_exists($localqtb_capture_file) || (time() - filemtime($localqtb_capture_file) > 3)) {
        //if no image or image is old get a new screecapture
        $res = exec("sudo -u $localqtb_username $localqtb_script_qtbthumbnail $localqtb_basedir/var/pic_new.jpg 2>&1", $output_array, $return_code);
        if ((time() - filemtime("$localqtb_basedir/var/pic_new.jpg") > 3)) {
            //print "could not take a screencapture";
            copy("./nopic.jpg", $localqtb_capture_file);
        } else {
            //copy screencapture to actual snap
            copy("$localqtb_basedir/var/pic_new.jpg", "$localqtb_basedir/var/pic_new_www.jpg");
            rename("$localqtb_basedir/var/pic_new_www.jpg", $localqtb_capture_file);
        }
    }
    return file_get_contents($localqtb_capture_file);
}

/**
 * @implements
 * Returns the current status of the recording 
 * Status may be "open", "recording", "paused", "stopped", "error"
 */
function capture_localqtb_status_get() {
    global $localqtb_status_file;

    if (!file_exists($localqtb_status_file))
        return '';

    return trim(file_get_contents($localqtb_status_file));
}

/**
 * @implements
 * Defines the status of the current recording
 */
function capture_localqtb_status_set($status) {
    global $logger;
    global $module_name;
    global $localqtb_status_file;
    global $localqtb_last_request_file;

    file_put_contents($localqtb_status_file, $status);
    file_put_contents($localqtb_last_request_file, time());
    
    $logger->log(EventType::TEST, LogLevel::DEBUG, __FUNCTION__.": rectatus set to: '".$status . "'. Caller: " . debug_backtrace()[1]['function'], array(__FUNCTION__), $asset);
}

/**
 * @implements
 * updates the last_request time (for cli_monitoring)
 */
function capture_localqtb_last_request_set(){
    global $localqtb_last_request_file;
    
    touch($localqtb_last_request_file);
}

/**
 * Returns the last_request time
 * @global type $localqtb_last_request_file
 */
function capture_localqtb_last_request_get(){
    global $localqtb_last_request_file;
    
    return filemtime($localqtb_last_request_file);
}

/**
 * @implements
 * Returns an array containing the features offered by the module
 * @global type $localqtb_features
 * @return type
 */
function capture_localqtb_features_get() {
    global $localqtb_features;
    return $localqtb_features;
}

function capture_localqtb_tmpdir_get($asset) {
    global $localqtb_basedir;
    static $tmp_dir;

    $tmp_dir = $localqtb_basedir . '/var/' . $asset;
    if (!file_exists($tmp_dir) && !dir($tmp_dir))
        mkdir($tmp_dir, 0777, true);

    return $tmp_dir;
}

?>
