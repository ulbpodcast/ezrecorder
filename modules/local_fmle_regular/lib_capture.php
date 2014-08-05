<?php
/*
 * EZCAST EZrecorder
 *
 * Copyright (C) 2014 Université libre de Bruxelles
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
include_once $basedir . '/lib_error.php';

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
function capture_localfmle_init(&$pid, $meta_assoc) {
    global $localfmle_script_init;
    global $localfmle_recorder_logs;
    global $localfmle_username;

    $asset = $meta_assoc['record_date'] . '_' . $meta_assoc['course_name'];

    $tmp_dir = capture_localfmle_tmpdir_get($asset);

    // saves recording metadata as xml file 
    assoc_array2xml_file($meta_assoc, "$tmp_dir/_metadata.xml");


    // status of the current recording
    $status = capture_localfmle_status_get();
    if ($status == '') { // no status yet
        // script_init initializes Flash Media Live Encoder and runs a recording test
        // launched in background to save time (pid is returned to be handled by web_index.php)
        system("sudo -u $localfmle_username $localfmle_script_init >> $localfmle_recorder_logs 2>&1 & echo $! > $tmp_dir/pid");
        $pid = file_get_contents("$tmp_dir/pid");
        // error occured while launching QTB
        if (capture_localfmle_status_get() == 'launch_failure') {
            error_last_message("can't open because FMLE failed to launch");
            return false;
        }
        // the recording is now 'open'
        capture_localfmle_status_set('open');
    } else {
        error_last_message("capture_init: can't open because current status: $status");
        return false;
    }

    return true;
}

/**
 * @implements
 * Launches the recording process 
 */
function capture_localfmle_start($asset) {
    global $localfmle_script_start;
    global $localfmle_time_started_file;
    global $localfmle_last_request_file;
    global $localfmle_recorder_logs;
    global $localfmle_username;

    // starts the recording in FMLE
    // $pid is used in web_index.php
    system("sudo -u $localfmle_username $localfmle_script_start >> $localfmle_recorder_logs 2>&1 &");

    // saves start time in text file
    file_put_contents($localfmle_time_started_file, time());
    file_put_contents($localfmle_last_request_file, time());

    //update recording status
    $status = capture_localfmle_status_get();
    if ($status == "open") {
        capture_localfmle_status_set('recording');
    } else {
        capture_localfmle_status_set("error");
        error_last_message("capture_start: can't start recording because current status: $status");
        return false;
    }

    return true;
}

/**
 * @implements
 * Pauses the current recording
 */
function capture_localfmle_pause($asset) {
    global $localfmle_script_action;
    global $localfmle_recorder_logs;
    global $localfmle_username;

    // get status of the current recording
    $status = capture_localfmle_status_get();
    if ($status == 'recording') {
        // qtbpause pauses the recording in QTB
        system("sudo -u $localfmle_username $localfmle_script_action >> $localfmle_recorder_logs 2>&1 &");
        capture_localfmle_status_set('paused');
    } else {
        error_last_message("capture_pause: can't pause recording because current status: $status");
        return false;
    }

    return true;
}

/**
 * @implements
 * Resumes the current paused recording
 */
function capture_localfmle_resume($asset) {
    global $localfmle_script_action;
    global $localfmle_recorder_logs;
    global $localfmle_username;

    // get status of the current recording
    $status = capture_localfmle_status_get();
    if ($status == 'paused' || $status == 'stopped') {
        // qtbresume resumes the current recording
        system("sudo -u $localfmle_username $localfmle_script_action >> $localfmle_recorder_logs 2>&1 &");
        // sets the new status of the current recording
        capture_localfmle_status_set('recording');
    } else {
        error_last_message("capture_resume: can't resume recording because current status: $status");
        return false;
    }

    return true;
}

/**
 * @implements
 * Stops the current recording
 */
function capture_localfmle_stop(&$pid, $asset) {
    global $localfmle_script_action;
    global $localfmle_recorder_logs;
    global $localfmle_username;

    $tmp_dir = capture_localfmle_tmpdir_get($asset);

    // get status of the current recording
    $status = capture_localfmle_status_get();
    if ($status == 'recording') {
        // pauses the current recording (while user chooses the way to publish the record)
        system("sudo -u $localfmle_username $localfmle_script_action >> $localfmle_recorder_logs 2>&1 & echo $! > $tmp_dir/pid");
        $pid = file_get_contents("$tmp_dir/pid");
        // set the new status for the current recording
        capture_localfmle_status_set('stopped');
    } else if ($status == 'paused') {
        capture_localfmle_status_set('stopped');
    } else {
        error_last_message("capture_stop: can't pause recording because current status: $status");
        return false;
    }

    return true;
}

/**
 * @implements
 * Ends the current recording and saves it as an archive
 */
function capture_localfmle_cancel($asset) {

    global $localfmle_script_cancel;
    global $localfmle_recorder_logs;
    global $localfmle_username;

    // get status of the current recording
    $status = capture_localfmle_status_get();
    if ($status == 'recording' || $status == 'stopped' || $status == 'paused' || $status == 'open' || $status == '') {
        // qtbcancel cancels the current recording, saves it in archive dir and stops the monitoring
        $cmd = 'sudo -u ' . $localfmle_username . ' ' . $localfmle_script_cancel . ' ' . $asset . ' >> ' . $localfmle_recorder_logs . ' 2>&1';
        log_append('recording', "launching command: $cmd");
        $fpart = exec($cmd, $outputarray, $errorcode);
    } else {
        error_last_message("capture_cancel: can't cancel recording because current status: " . $status);
        return false;
    }

    return true;
}

/**
 * @implements
 * Processes the record before sending it to the server
 * @param assoc_array $metadata_assoc metadata relative to current recording
 */
function capture_localfmle_process($meta_assoc, &$pid) {
    global $localfmle_script_stop;
    global $localfmle_recorder_logs;
    global $localfmle_processing_tool;
    global $localfmle_processing_tools;
    global $localfmle_username;

    $asset = $meta_assoc['record_date'] . '_' . $meta_assoc['course_name'];
    $tmp_dir = capture_localfmle_tmpdir_get($asset);

    if (!in_array($localfmle_processing_tool, $localfmle_processing_tools))
        $localfmle_processing_tool = $localfmle_processing_tools[0];

    // saves recording metadata in xml file
    assoc_array2xml_file($meta_assoc, "$tmp_dir/_metadata.xml");

    $status = capture_localfmle_status_get();
    if ($status != 'recording' && $status != 'open') {
        // saves recording in processing dir and processes it
        // launched in background to save time 
        $cmd = 'sudo -u ' . $localfmle_username . ' ' . $localfmle_script_stop . ' ' . $meta_assoc['course_name'] . ' ' . $meta_assoc['record_date'] . ' ' . $localfmle_processing_tool . ' >> ' . $localfmle_recorder_logs . ' 2>&1  & echo $! > ' . $tmp_dir . '/pid';
        log_append('recording', "launching command: $cmd");
        // returns the process id of the background task
        system($cmd);
        $pid = file_get_contents("$tmp_dir/pid");

        //update (clear) status
        capture_localfmle_status_set('');
    } else {
        error_last_message("capture_stop: can't stop recording because current status: $status");
        return false;
    }

    //should be saved in Movies/local_processing/<date+hour>/
    //combine cam and slide:
    //one need to activate at on the mac:
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
 * @global type $localfmle_script_qtbfinalize
 * @global type $localfmle_recorder_logs
 * @global type $dir_date_format
 */
function capture_localfmle_finalize($asset) {
    global $localfmle_script_finalize;
    global $localfmle_recorder_logs;
    global $localfmle_username;

    $tmp_dir = capture_localfmle_tmpdir_get($asset);

    // retrieves course_name and record_date
    $meta_assoc = xml_file2assoc_array("$tmp_dir/_metadata.xml");

    // launches finalization bash script
    $cmd = 'sudo -u ' . $localfmle_username . ' ' . $localfmle_script_finalize . ' ' . $meta_assoc['course_name'] . " " . $meta_assoc['record_date'] . ' >> ' . $localfmle_recorder_logs . ' 2>&1  & echo $!';
    log_append("finalizing: execute cmd '$cmd'");
    $res = exec($cmd, $output, $errorcode);       
    
}

/**
 * @implements
 * Returns an associative array containing information required for downloading the movie
 * from the server
 * @global type $localfmle_ip
 * @global type $localfmle_download_protocol
 * @global type $localfmle_username
 * @return type
 */
function capture_localfmle_download_info_get($asset) {
    global $localfmle_ip;
    global $localfmle_download_protocol;
    global $localfmle_username;
    global $localfmle_upload_dir;

    $tmp_dir = capture_localfmle_tmpdir_get($asset);

    $meta_assoc = xml_file2assoc_array("$tmp_dir/_metadata.xml");

    // rsync requires ssh protocol is set (key sharing) on the remote server
    $download_info_array = array("ip" => $localfmle_ip,
        "protocol" => $localfmle_download_protocol,
        "username" => $localfmle_username,
        "filename" => $localfmle_upload_dir . $meta_assoc['record_date'] . "_" . $meta_assoc['course_name'] . "/fmle_movie.f4v");
    return $download_info_array;
}

/**
 * @implements
 * Creates a thumbnail picture
 * @return string the contents of the image to display
 */
function capture_localfmle_thumbnail() {
    global $localfmle_basedir;
    global $localfmle_script_thumbnail;
    global $localfmle_capture_file;
    global $localfmle_last_request_file;
    global $localfmle_username;
    global $localfmle_status_file;

    touch($localfmle_last_request_file);

    $minperiod = 5;

    // Camera screenshot
    $diff = time() - filemtime($localfmle_capture_file);
    if (!file_exists($localfmle_capture_file) || (time() - filemtime($localfmle_capture_file) > 3)) {
        //if no image or image is old get a new screecapture
        $res = exec("sudo -u $localfmle_username $localfmle_script_thumbnail $localfmle_basedir/var/pic_new.jpg 2>&1", $output_array, $return_code);
        if ((time() - filemtime("$localfmle_basedir/var/pic_new.jpg") > 3)) {
            //print "could not take a screencapture";
            copy("./nopic.jpg", $localfmle_capture_file);
        } else {
            //copy screencapture to actual snap
            image_resize("$localfmle_basedir/var/pic_new.jpg", "$localfmle_basedir/var/pic_new_www.jpg", 235, 157, $localfmle_status_file);
            rename("$localfmle_basedir/var/pic_new_www.jpg", $localfmle_capture_file);
        }
    }
    return file_get_contents($localfmle_capture_file);
    
}

/**
 * @implements
 * Returns the current status of the recording 
 * Status may be "open", "recording", "paused", "stopped", "error"
 */
function capture_localfmle_status_get() {
    global $localfmle_status_file;

    if (!file_exists($localfmle_status_file))
        return '';

    return trim(file_get_contents($localfmle_status_file));
}

/**
 * @implements
 * Defines the status of the current recording
 */
function capture_localfmle_status_set($status) {
    global $localfmle_status_file;
    global $localfmle_last_request_file;

    file_put_contents($localfmle_status_file, $status);
    file_put_contents($localfmle_last_request_file, time());
}

/**
 * Returns time of creation of the recording file
 * Only used for local purposes
 */
function private_capture_localfmle_starttime_get() {
    global $localfmle_time_started_file;

    if (!file_exists($localfmle_time_started_file))
        return false;

    return trim(file_get_contents($localfmle_time_started_file));
}

/**
 * Returns time of last action
 * Only used for local purposes
 */
function private_capture_localfmle_lastmodtime_get() {
    global $localfmle_capture_file;

    return filemtime($localfmle_capture_file);
}


function capture_localfmle_tmpdir_get($asset) {
    global $localfmle_basedir;
    static $tmp_dir;
    
    $tmp_dir = $localfmle_basedir . '/var/' . $asset;
    if (!dir($tmp_dir))
        mkdir($tmp_dir, 0777, true);
    
    return $tmp_dir; 
}

?>
