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
function capture_localqtb_init(&$pid, $meta_assoc) {
    global $localqtb_script_qtbnew;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    $asset = $meta_assoc['record_date'] . '_' . $meta_assoc['course_name'];

    $tmp_dir = capture_localqtb_tmpdir_get($asset);

    // saves recording metadata as xml file 
    assoc_array2xml_file($meta_assoc, "$tmp_dir/_metadata.xml");


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
            return false;
        }
        // the recording is now 'open'
        capture_localqtb_status_set('open');
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
function capture_localqtb_start($asset) {
    global $localqtb_script_qtbrec;
    global $localqtb_time_started_file;
    global $localqtb_last_request_file;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    // qtbrec starts the recording in QTB
    // $pid is used in web_index.php
    system("sudo -u $localqtb_username $localqtb_script_qtbrec >> $localqtb_recorder_logs 2>&1 &");

    // saves start time in text file
    file_put_contents($localqtb_time_started_file, time());
    file_put_contents($localqtb_last_request_file, time());

    //update recording status
    $status = capture_localqtb_status_get();
    if ($status == "open") {
        capture_localqtb_status_set('recording');
    } else {
        capture_localqtb_status_set("error");
        error_last_message("capture_start: can't start recording because current status: $status");
        return false;
    }

    return true;
}

/**
 * @implements
 * Pauses the current recording
 */
function capture_localqtb_pause($asset) {
    global $localqtb_script_qtbpause;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    // get status of the current recording
    $status = capture_localqtb_status_get();
    if ($status == 'recording') {
        // qtbpause pauses the recording in QTB
        system("sudo -u $localqtb_username $localqtb_script_qtbpause >> $localqtb_recorder_logs 2>&1 &");
        capture_localqtb_status_set('paused');
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
function capture_localqtb_resume($asset) {
    global $localqtb_script_qtbresume;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    // get status of the current recording
    $status = capture_localqtb_status_get();
    if ($status == 'paused' || $status == 'stopped') {
        // qtbresume resumes the current recording
        system("sudo -u $localqtb_username $localqtb_script_qtbresume >> $localqtb_recorder_logs 2>&1 &");
        // sets the new status of the current recording
        capture_localqtb_status_set('recording');
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
function capture_localqtb_stop(&$pid, $asset) {
    global $localqtb_script_qtbpause;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    $tmp_dir = capture_localqtb_tmpdir_get($asset);

    // get status of the current recording
    $status = capture_localqtb_status_get();
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
        return false;
    }

    return true;
}

/**
 * @implements
 * Ends the current recording and saves it as an archive
 */
function capture_localqtb_cancel($asset) {

    global $localqtb_script_qtbcancel;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    // get status of the current recording
    $status = capture_localqtb_status_get();
    if ($status == 'recording' || $status == 'stopped' || $status == 'paused' || $status == 'open' || $status == '') {
        // qtbcancel cancels the current recording, saves it in archive dir and stops the monitoring
        $cmd = 'sudo -u ' . $localqtb_username . ' ' . $localqtb_script_qtbcancel . ' ' . $asset . ' >> ' . $localqtb_recorder_logs . ' 2>&1';
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
function capture_localqtb_process($meta_assoc, &$pid) {
    global $localqtb_script_qtbstop;
    global $localqtb_recorder_logs;
    global $localqtb_processing_tool;
    global $localqtb_processing_tools;
    global $localqtb_username;

    $asset = $meta_assoc['record_date'] . '_' . $meta_assoc['course_name'];
    $tmp_dir = capture_localqtb_tmpdir_get($asset);

    if (!in_array($localqtb_processing_tool, $localqtb_processing_tools))
        $localqtb_processing_tool = $localqtb_processing_tools[0];

    // saves recording metadata in xml file
    assoc_array2xml_file($meta_assoc, "$tmp_dir/_metadata.xml");

    $status = capture_localqtb_status_get();
    if ($status != 'recording' && $status != 'open') {
        // saves recording in processing dir and processes it
        // launched in background to save time 
        $cmd = 'sudo -u ' . $localqtb_username . ' ' . $localqtb_script_qtbstop . ' ' . $meta_assoc['course_name'] . ' ' . $meta_assoc['record_date'] . ' ' . $localqtb_processing_tool . ' >> ' . $localqtb_recorder_logs . ' 2>&1  & echo $! > ' . $tmp_dir . '/pid';
        log_append('recording', "launching command: $cmd");
        // returns the process id of the background task
        system($cmd);
        $pid = file_get_contents("$tmp_dir/pid");

        //update (clear) status
        capture_localqtb_status_set('');
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
 * @global type $localqtb_script_qtbfinalize
 * @global type $localqtb_recorder_logs
 * @global type $dir_date_format
 */
function capture_localqtb_finalize($asset) {
    global $localqtb_script_qtbfinalize;
    global $localqtb_recorder_logs;
    global $localqtb_username;

    $tmp_dir = capture_localqtb_tmpdir_get($asset);

    // retrieves course_name and record_date
    $meta_assoc = xml_file2assoc_array("$tmp_dir/_metadata.xml");

    // launches finalization bash script
    $cmd = 'sudo -u ' . $localqtb_username . ' ' . $localqtb_script_qtbfinalize . ' ' . $meta_assoc['course_name'] . " " . $meta_assoc['record_date'] . ' >> ' . $localqtb_recorder_logs . ' 2>&1  & echo $!';
    log_append("finalizing: execute cmd '$cmd'");
    $res = exec($cmd, $output, $errorcode);       
    
}

/**
 * @implements
 * Returns an associative array containing information required for downloading the movie
 * from the server
 * @global type $localqtb_ip
 * @global type $localqtb_download_protocol
 * @global type $localqtb_username
 * @return type
 */
function capture_localqtb_download_info_get($asset) {
    global $localqtb_ip;
    global $localqtb_download_protocol;
    global $localqtb_username;
    global $localqtb_upload_dir;

    $tmp_dir = capture_localqtb_tmpdir_get($asset);

    $meta_assoc = xml_file2assoc_array("$tmp_dir/_metadata.xml");

    // rsync requires ssh protocol is set (key sharing) on the remote server
    $download_info_array = array("ip" => $localqtb_ip,
        "protocol" => $localqtb_download_protocol,
        "username" => $localqtb_username,
        "filename" => $localqtb_upload_dir . $meta_assoc['record_date'] . "_" . $meta_assoc['course_name'] . "/cam.mov");
    return $download_info_array;
}

/**
 * @implements
 * Creates a thumbnail picture
 * @return string the contents of the image to display
 */
function capture_localqtb_thumbnail() {
    global $localqtb_basedir;
    global $localqtb_script_qtbthumbnail;
    global $localqtb_capture_file;
    global $localqtb_last_request_file;
    global $localqtb_username;

    touch($localqtb_last_request_file);

    $minperiod = 5;

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
    global $localqtb_status_file;
    global $localqtb_last_request_file;

    file_put_contents($localqtb_status_file, $status);
    file_put_contents($localqtb_last_request_file, time());
}

/**
 * Returns time of creation of the recording file
 * Only used for local purposes
 */
function private_capture_starttime_get() {
    global $localqtb_time_started_file;

    if (!file_exists($localqtb_time_started_file))
        return false;

    return trim(file_get_contents($localqtb_time_started_file));
}

/**
 * Returns time of last action
 * Only used for local purposes
 */
function private_capture_lastmodtime_get() {
    global $localqtb_capture_file;

    return filemtime($localqtb_capture_file);
}

/**
 *
 * @param <type> $assoc_array
 * @return <xml_string>
 * @desc takes an assoc array and transform it in a xml metadata file
 */
function assoc_array2xml_file($assoc_array, $localqtb_metadata_file) {
    $xmlstr = "<?xml version='1.0' standalone='yes'?>\n<metadata>\n</metadata>\n";
    $xml = new SimpleXMLElement($xmlstr);
    foreach ($assoc_array as $key => $value) {
        $xml->addChild($key, $value);
    }
    $xml_txt = $xml->asXML();
    file_put_contents($localqtb_metadata_file, $xml_txt);
    chmod($localqtb_metadata_file, 0644);
}

function xml_file2assoc_array($meta_path) {
    $xml = simplexml_load_file($meta_path);
    if ($xml === false)
        return false;
    $assoc_array = array();
    foreach ($xml as $key => $value) {
        $assoc_array[$key] = (string) $value;
    }
    return $assoc_array;
}

function capture_localqtb_tmpdir_get($asset) {
    global $localqtb_basedir;
    static $tmp_dir;
    
    $tmp_dir = $localqtb_basedir . '/var/' . $asset;
    if (!dir($tmp_dir))
        mkdir($tmp_dir, 0777, true);
    
    return $tmp_dir; 
}

?>
