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
function capture_localqt_init(&$pid, $meta_assoc) {
    global $localqt_script_qtnew;
    global $localqt_recorder_logs;
    global $localqt_username;

    $asset = $meta_assoc['course_name'] . '_' . $meta_assoc['record_date'];
    $tmp_dir = capture_localqt_tmpdir_get($asset);
    // saves recording metadata as xml file 
    xml_assoc_array2file($meta_assoc, "$tmp_dir/_metadata.xml");


    // status of the current recording
    $status = capture_localqt_status_get();
    if ($status == '') { // no status yet
        // qtnew initializes QuickTime and runs a recording test
        // launched in background to save time (pid is returned to be handled by web_index.php)
        system("sudo -u $localqt_username $localqt_script_qtnew >> $localqt_recorder_logs 2>&1 & echo $! > $tmp_dir/pid");
        $pid = file_get_contents("$tmp_dir/pid");

        // error occured while launching QuickTime
        if (capture_localqt_status_get() == 'launch_failure') {
            error_last_message("can't open because QTB failed to launch");
            return false;
        }
        // the recording is now 'open'
        capture_localqt_status_set('open');
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
function capture_localqt_start($asset) {
    global $localqt_script_qtrec;
    global $localqt_recorder_logs;
    global $localqt_username;


    // qtrec starts the recording in QuickTime
    // $pid is used in web_index.php
    system("sudo -u $localqt_username $localqt_script_qtrec >> $localqt_recorder_logs 2>&1 &");

    //update recording status
    $status = capture_localqt_status_get();
    if ($status == "open") {
        capture_localqt_status_set('recording');
    } else {
        capture_localqt_status_set("error");
        error_last_message("capture_start: can't start recording because current status: $status");
        return false;
    }

    return true;
}

/**
 * @implements
 * Pauses the current recording
 */
function capture_localqt_pause($asset) {
    global $localqt_script_qtpause;
    global $localqt_recorder_logs;
    global $localqt_username;

    // get status of the current recording
    $status = capture_localqt_status_get();
    if ($status == 'recording') {
        // qtpause pauses the recording in QuickTime
        system("sudo -u $localqt_username $localqt_script_qtpause >> $localqt_recorder_logs 2>&1 &");
        capture_localqt_status_set('paused');
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
function capture_localqt_resume($asset) {
    global $localqt_script_qtresume;
    global $localqt_recorder_logs;
    global $localqt_username;

    // get status of the current recording
    $status = capture_localqt_status_get();
    if ($status == 'paused' || $status == 'stopped') {
        // qtresume resumes the current recording
        system("sudo -u $localqt_username $localqt_script_qtresume >> $localqt_recorder_logs 2>&1 &");
        // sets the new status of the current recording
        capture_localqt_status_set('recording');
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
function capture_localqt_stop(&$pid, $asset) {
    global $localqt_script_qtpause;
    global $localqt_recorder_logs;
    global $localqt_username;

    $tmp_dir = capture_localqt_tmpdir_get($asset);

    // get status of the current recording
    $status = capture_localqt_status_get();
    if ($status == 'recording') {
        // pauses the current recording (while user chooses the way to publish the record)
        system("sudo -u $localqt_username $localqt_script_qtpause >> $localqt_recorder_logs 2>&1 & echo $! > $tmp_dir/pid");
        $pid = file_get_contents("$tmp_dir/pid");
        // set the new status for the current recording
        capture_localqt_status_set('stopped');
    } else if ($status == 'paused') {
        capture_localqt_status_set('stopped');
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
function capture_localqt_cancel($asset) {

    global $localqt_script_qtcancel;
    global $localqt_recorder_logs;
    global $localqt_username;

    // get status of the current recording
    $status = capture_localqt_status_get();
    if ($status == 'recording' || $status == 'stopped' || $status == 'paused' || $status == 'open' || $status == '') {
        // qtbcancel cancels the current recording, saves it in archive dir and stops the monitoring
        $cmd = 'sudo -u ' . $localqt_username . ' ' . $localqt_script_qtcancel . ' ' . $asset . ' >> ' . $localqt_recorder_logs . ' 2>&1';
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
function capture_localqt_process($meta_assoc, &$pid) {
    global $localqt_script_qtstop;
    global $localqt_recorder_logs;
    global $localqt_username;

    $asset = $meta_assoc['course_name'] . '_' . $meta_assoc['record_date'];
    $tmp_dir = capture_localqt_tmpdir_get($asset);

    // saves recording metadata in xml file
    xml_assoc_array2file($meta_assoc, "$tmp_dir/_metadata.xml");

    $status = capture_localqt_status_get();
    if ($status != 'recording' && $status != 'open') {
        // saves recording in processing dir and processes it
        // launched in background to save time 
        $cmd = 'sudo -u ' . $localqt_username . ' ' . $localqt_script_qtstop . ' ' . $meta_assoc['course_name'] . ' ' . $meta_assoc['record_date'] . ' >> ' . $localqt_recorder_logs . ' 2>&1  & echo $! > ' . "$tmp_dir/pid";
        log_append('recording', "launching command: $cmd");
        // returns the process id of the background task
        system($cmd);
        $pid = file_get_contents("$tmp_dir/pid");

        //update (clear) status
        capture_localqt_status_set('');
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
 * @global type $localqt_metadata_file
 * @global type $localqt_script_qtfinalize
 * @global type $localqt_recorder_logs
 * @global type $dir_date_format
 */
function capture_localqt_finalize($asset) {
    global $localqt_script_qtfinalize;
    global $localqt_recorder_logs;
    global $dir_date_format;
    global $localqt_username;

    $tmp_dir = capture_localqt_tmpdir_get($asset);
    // retrieves course_name and record_date
    $meta_assoc = xml_file2assoc_array("$tmp_dir/_metadata.xml");

    // launches finalization bash script
    $cmd = 'sudo -u ' . $localqt_username . ' ' . $localqt_script_qtfinalize . ' ' . $meta_assoc['course_name'] . " " . $meta_assoc['record_date'] . ' >> ' . $localqt_recorder_logs . ' 2>&1  & echo $!';
    log_append("finalizing: execute cmd '$cmd'");
    $res = exec($cmd, $output, $errorcode);
}

/**
 * @implements
 * Returns an associative array containing information required for given action
 * @global type $localqt_ip
 * @global type $localqt_download_protocol
 * @global type $localqt_username
 * @return type
 */
function capture_localqt_info_get($action, $asset = '') {
    global $localqt_ip;
    global $localqt_download_protocol;
    global $localqt_username;
    global $localqt_upload_dir;
    global $localqt_movie_name;

    switch ($action) {
        case 'download':
            $tmp_dir = capture_localqt_tmpdir_get($asset);

            $meta_assoc = xml_file2assoc_array("$tmp_dir/_metadata.xml");

            // rsync requires ssh protocol is set (key sharing) on the remote server
            $download_info_array = array("ip" => $localqt_ip,
                "protocol" => $localqt_download_protocol,
                "username" => $localqt_username,
                "filename" => $localqt_upload_dir . $meta_assoc['record_date'] . "_" . $meta_assoc['course_name'] . "/$localqt_movie_name.mov");
            return $download_info_array;
            break;
    }
}

/**
 * @implements
 * Creates a thumbnail picture
 * @return string the contents of the image to display
 */
function capture_localqt_thumbnail() {
    global $localqt_basedir;
    global $localqt_script_qtthumbnail;
    global $localqt_capture_file;
    global $localqt_username;


    // Camera screenshot
    $diff = time() - filemtime($localqt_capture_file);
    if (!file_exists($localqt_capture_file) || (time() - filemtime($localqt_capture_file) > 3)) {
        //if no image or image is old get a new screecapture
        $res = exec("sudo -u $localqt_username $localqt_script_qtthumbnail $localqt_basedir/var/pic_new.jpg 2>&1", $output_array, $return_code);
        if ((time() - filemtime("$localqt_basedir/var/pic_new.jpg") > 3)) {
            //print "could not take a screencapture";
            copy("./nopic.jpg", $localqt_capture_file);
        } else {
            //copy screencapture to actual snap
            copy("$localqt_basedir/var/pic_new.jpg", "$localqt_basedir/var/pic_new_www.jpg");
            rename("$localqt_basedir/var/pic_new_www.jpg", $localqt_capture_file);
        }
    }
    return file_get_contents($localqt_capture_file);
}

/**
 * @implements
 * Returns the current status of the recording 
 * Status may be "open", "recording", "paused", "stopped", "error"
 */
function capture_localqt_status_get() {
    global $localqt_status_file;

    if (!file_exists($localqt_status_file))
        return '';

    return trim(file_get_contents($localqt_status_file));
}

/**
 * @implements
 * Defines the status of the current recording
 */
function capture_localqt_status_set($status) {
    global $localqt_status_file;

    file_put_contents($localqt_status_file, $status);
}

/**
 * @implements
 * Returns an array containing the features offered by the module
 * @global type $localqt_features
 * @return type
 */
function capture_localqt_features_get() {
    global $localqt_features;
    return $localqt_features;
}


function capture_localqt_tmpdir_get($asset) {
    global $localqt_basedir;
    static $tmp_dir;

    $tmp_dir = $localqt_basedir . '/var/' . $asset;
    if (!file_exists($tmp_dir) && !dir($tmp_dir))
        mkdir($tmp_dir, 0777, true);

    return $tmp_dir;
}

?>
