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
require 'lib_curl.php';
require_once $basedir . '/lib_various.php';
include_once $basedir . '/lib_error.php';

/*
 * This file contains all functions related to the video capture from an Axis IP camera.
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
function capture_axiscam_init(&$pid, $meta_assoc) {
    global $axiscam_camstatus_file;

    // creates temporary directory for further use
    $asset = $meta_assoc['record_date'] . '_' . $meta_assoc['course_name'];
    $tmp_dir = capture_axiscam_tmpdir_get($asset);

    // saves recording metadata as xml file 
    axiscam_assoc_array2xml_file($meta_assoc, "$tmp_dir/_metadata.xml");

    unlink($axiscam_camstatus_file);

    if (capture_axiscam_status_get() == '') {
        capture_axiscam_status_set('open');
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
function capture_axiscam_start($asset) {
    global $axiscam_time_started_file;
    global $axiscam_last_request_file;
    global $axiscam_recorder_logs;
    global $axiscam_username;
    global $axiscam_password;
    global $axiscam_ip;
    global $axiscam_input_nb;
    global $php_cli_cmd;
    global $axiscam_cli_monitoring;
    global $axiscam_camstatus_file;

    // starts the recording on the ip camera in background task
    /*
     * Axis cam has been set up to record on SD Card when the digital input port 2 is triggered.
     * While the digital input port 2 is active, the camera is recording.
     * The following url enables the digital input port 2.
     * This trigger must be defined in axis setup > events > action rules 
     * Condition: 
     *      - input signal
     *      - Digital input port
     *      - Input 2   
     *      - Active : yes
     * Actions: 
     *      - Record video
     *      - while the rule is active
     */
    $url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/io/virtualinput.cgi?action=$axiscam_input_nb:/";
    curl_read_url($url);

    // saves start time in text file
    file_put_contents($axiscam_time_started_file, time());
    file_put_contents($axiscam_last_request_file, time());

    //update recording status
    $status = capture_axiscam_status_get();
    if ($status == "open") {
        capture_axiscam_status_set('recording');
        file_put_contents($axiscam_camstatus_file, "pending");  
   //     system("echo '$php_cli_cmd $axiscam_cli_monitoring $asset'| at now");
        system("$php_cli_cmd $axiscam_cli_monitoring $asset > /dev/null &");
    } else {
        capture_axiscam_status_set("error");
        error_last_message("capture_start: can't start recording because current status: $status");
        return false;
    }

    return true;
}

/**
 * @implements
 * Pauses the current recording
 */
function capture_axiscam_pause($asset) {
    global $axiscam_username;
    global $axiscam_password;
    global $axiscam_ip;
    global $axiscam_input_nb;
    global $axiscam_camstatus_file;
    global $axiscam_monitoring_file;
    
    $tmp_dir = capture_axiscam_tmpdir_get($asset);
    // get status of the current recording
    $status = capture_axiscam_status_get();
    $last_record = capture_axiscam_last_record_get();
    if ($status == 'recording') {
        // stops the recording on the ip camera 
        /*
         * Axis cam has been set up to record on SD Card when the digital input port 2 is triggered.
         * While the digital input port 2 is active, the camera is recording.
         * The following url disables the digital input port 2.
         * This trigger must be defined in axis setup > events > action rules 
         * Condition: 
         *      - input signal
         *      - Digital input port
         *      - Input 2   
         *      - Active : yes
         * Actions: 
         *      - Record video
         *      - while the rule is active
         */
        $url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/io/virtualinput.cgi?action=$axiscam_input_nb:\\";
        curl_read_url($url);
        capture_axiscam_status_set('paused');
        unlink($axiscam_camstatus_file);
        $monitoring_pid = file_get_contents($axiscam_monitoring_file);
        unlink($axiscam_monitoring_file);
        system("kill -9 $monitoring_pid");
        
        file_put_contents("$tmp_dir/_records_ids", $last_record . PHP_EOL, FILE_APPEND);
        file_put_contents("$tmp_dir/paused", time());
        
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
function capture_axiscam_resume($asset) {
    global $axiscam_camstatus_file;
    global $php_cli_cmd;
    global $axiscam_cli_resume;

    // get status of the current recording
    $status = capture_axiscam_status_get();
    if ($status == 'paused' || $status == 'stopped') {
        
  
        // sets the new status of the current recording
        capture_axiscam_status_set('recording');
        file_put_contents($axiscam_camstatus_file, "pending");  
        system("$php_cli_cmd $axiscam_cli_resume $asset > /dev/null &");
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
function capture_axiscam_stop(&$pid, $asset) {
    global $axiscam_username;
    global $axiscam_password;
    global $axiscam_ip;
    global $axiscam_input_nb;
    global $axiscam_monitoring_file;
    global $axiscam_camstatus_file;

    $tmp_dir = capture_axiscam_tmpdir_get($asset);
    // get status of the current recording
    $status = capture_axiscam_status_get();
    if ($status == 'recording') {
        // stops the current recording (while user chooses the way to publish the record)
        $url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/io/virtualinput.cgi?action=$axiscam_input_nb:\\";
        curl_read_url($url);

        $monitoring_pid = file_get_contents($axiscam_monitoring_file);
        unlink($axiscam_monitoring_file);
        system("kill -9 $monitoring_pid");
        unlink($axiscam_camstatus_file);

        // set the new status for the current recording
        capture_axiscam_status_set('stopped');
        // adds the recording id to be downloaded
        $index = 0;
        do {
            $last_record = capture_axiscam_last_record_get();
            $index++;
        } while (!isset($last_record) && $index < 5);
        file_put_contents("$tmp_dir/_records_ids", $last_record . PHP_EOL, FILE_APPEND);
    } else if ($status == 'paused') {
        capture_axiscam_status_set('stopped');
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
function capture_axiscam_cancel($asset) {
    global $axiscam_username;
    global $axiscam_password;
    global $axiscam_ip;
    global $axiscam_input_nb;

    $tmp_dir = capture_axiscam_tmpdir_get($asset);

    // get status of the current recording
    $status = capture_axiscam_status_get();
    if ($status == 'recording' || $status == 'stopped' || $status == 'paused' || $status == 'open' || $status == '') {
        $url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/io/virtualinput.cgi?action=$axiscam_input_nb:\\";
        curl_read_url($url);

        system("rm -rf $tmp_dir");
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
function capture_axiscam_process($meta_assoc, &$pid) {
    global $axiscam_cli_process;

    $asset = $meta_assoc['record_date'] . '_' . $meta_assoc['course_name'];
    $tmp_dir = capture_axiscam_tmpdir_get($asset);

    // saves recording metadata in xml file
    axiscam_assoc_array2xml_file($meta_assoc, "$tmp_dir/_metadata.xml");

    $status = capture_axiscam_status_get();
    if ($status != 'recording' && $status != 'open') {

        $cmd = "/usr/bin/php $axiscam_cli_process $asset > /dev/null & echo $!";
        // returns the process id of the background task
        $pid = system($cmd);

        //update (clear) status
        capture_axiscam_status_set('');
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
 * @global type $axiscam_metadata_file
 * @global type $axiscam_script_qtbfinalize
 * @global type $axiscam_recorder_logs
 * @global type $dir_date_format
 */
function capture_axiscam_finalize($asset) {
    global $axiscam_script_finalize;
    global $axiscam_recorder_logs;
    global $dir_date_format;
    global $axiscam_module_username;

    $tmp_dir = capture_axiscam_tmpdir_get($asset);
    // retrieves course_name and record_date
    $meta_assoc = axiscam_xml_file2assoc_array("$tmp_dir/_metadata.xml");

    // launches finalization bash script
    $cmd = 'sudo -u ' . $axiscam_module_username . ' ' . $axiscam_script_finalize . ' ' . $meta_assoc['course_name'] . " " . $meta_assoc['record_date'] . ' >> ' . $axiscam_recorder_logs . ' 2>&1  & echo $!';
    log_append("finalizing: execute cmd '$cmd'");
    $res = exec($cmd, $output, $errorcode);
}

/**
 * @implements
 * Returns an associative array containing information required for downloading the movie
 * from the server
 * @global type $axiscam_ip
 * @global type $axiscam_download_protocol
 * @global type $axiscam_username
 * @return type
 */
function capture_axiscam_download_info_get($asset) {
    global $axiscam_module_ip;
    global $axiscam_download_protocol;
    global $axiscam_module_username;
    global $axiscam_upload_dir;

    $tmp_dir = capture_axiscam_tmpdir_get($asset);

    $meta_assoc = axiscam_xml_file2assoc_array("$tmp_dir/_metadata.xml");

    // rsync requires ssh protocol is set (key sharing) on the remote server
    $download_info_array = array("ip" => $axiscam_module_ip,
        "protocol" => $axiscam_download_protocol,
        "username" => $axiscam_module_username,
        "filename" => $axiscam_upload_dir . $meta_assoc['record_date'] . "_" . $meta_assoc['course_name'] . "/cam.mkv");
    return $download_info_array;
}

/**
 * @implements
 * Creates a thumbnail picture
 * @return string the contents of the image to display
 */
function capture_axiscam_thumbnail() {
    global $axiscam_basedir;
    global $axiscam_capture_file;
    global $axiscam_last_request_file;
    global $axiscam_username;
    global $axiscam_password;
    global $axiscam_ip;
    global $axiscam_camstatus_file;

    touch($axiscam_last_request_file);

    $minperiod = 5;

    // Camera screenshot
    $diff = time() - filemtime($axiscam_capture_file);
    if (!file_exists($axiscam_capture_file) || (time() - filemtime($axiscam_capture_file) > 3)) {
        //if no image or image is old get a new screecapture
        $url = "$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/jpg/image.cgi?resolution=320x180";
        curl_download_file($url, "$axiscam_basedir/var/pic_new.jpg");
        // $res = exec("sudo -u $axiscam_username $axiscam_script_qtbthumbnail $axiscam_basedir/var/pic_new.jpg 2>&1", $output_array, $return_code);
        if ((time() - filemtime("$axiscam_basedir/var/pic_new.jpg") > 3)) {
            //print "could not take a screencapture";
            copy("./nopic.jpg", $axiscam_capture_file);
        } else {
            //copy screencapture to actual snap
            image_resize("$axiscam_basedir/var/pic_new.jpg", "$axiscam_basedir/var/pic_new_www.jpg", 235, 157, $axiscam_camstatus_file);
            rename("$axiscam_basedir/var/pic_new_www.jpg", $axiscam_capture_file);
        }
    }
    return file_get_contents($axiscam_capture_file);
}

/**
 * @implements
 * Returns the current status of the recording 
 * Status may be "open", "recording", "paused", "stopped", "error"
 */
function capture_axiscam_status_get() {
    global $axiscam_status_file;

    if (!file_exists($axiscam_status_file))
        return '';

    return trim(file_get_contents($axiscam_status_file));
}

/**
 * @implements
 * Defines the status of the current recording
 */
function capture_axiscam_status_set($status) {
    global $axiscam_status_file;
    global $axiscam_last_request_file;

    file_put_contents($axiscam_status_file, $status);
    file_put_contents($axiscam_last_request_file, time());
}

/**
 * Returns time of creation of the recording file
 * Only used for local purposes
 */
function private_capture_axiscam_starttime_get() {
    global $axiscam_time_started_file;

    if (!file_exists($axiscam_time_started_file))
        return false;

    return trim(file_get_contents($axiscam_time_started_file));
}

/**
 * Returns time of last action
 * Only used for local purposes
 */
function private_capture_axiscam_lastmodtime_get() {
    global $axiscam_capture_file;

    return filemtime($axiscam_capture_file);
}

/**
 * Returns the list of all recordings stored on the SD card of the ip camera.
 * @global type $axiscam_ip
 * @global type $axiscam_username
 * @global type $axiscam_password
 * @return <array> the array containing all the recordings stored on the SD card
 * of the ip camera.
 * Each item of the array is an associative array representing a recording:
 *      [diskid] : the disk id [SD_DISK / SHARE]
 *      [recordingid] : the unique id for the current record
 *      [starttime] : the time when the recording has been started
 *      [starttimelocal] : the time when the recording has been started, depending on the system time
 *      [stoptime] : the time when the recording has been stopped
 *      [stoptimelocal] : the time when the recording has been stopped, depending on the system time
 *      [recordingtype] : triggered
 *      [eventid] : name of the event that has been triggered
 *      [eventtrigger] : type of event 
 *      [recordingstatus] : whether the recording is completed or ongoing
 *      [source]
 *      [locked]
 */
function capture_axiscam_recordlist_get($record_id = "all") {
    global $axiscam_ip;
    global $axiscam_username;
    global $axiscam_password;

    $url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/record/list.cgi?recordingid=$record_id";
    $xml_string = curl_read_url($url);
    if ($xml_string === false) return false;
    $xml = new SimpleXMLElement($xml_string);

    $records = array();
    $records = private_capture_xml_parse($xml, $records);
    if (count($records) == 1)
        return $records[0];
    return $records;
}

/**
 * Returns the id of the last record on the SD card of the ip camera
 * @return type
 */
function capture_axiscam_last_record_get() {
    $records = capture_axiscam_recordlist_get();
    return $records[0]['recordingid'];
}

function capture_axiscam_record_download($record_id, $asset) {
    global $axiscam_ip;
    global $axiscam_username;
    global $axiscam_password;
    global $mailto_admins;
    global $classroom;

    $max_try = 10;

    $tmp_dir = capture_axiscam_tmpdir_get($asset);
    $url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/record/download.cgi?recordingid=$record_id";
    while ($max_try > 0 && !curl_download_file($url, $tmp_dir . '/' . $record_id . '.zip')) {
        sleep(360);
        $max_try--;
    };


/*     $url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/record/download.cgi?recordingid=$record_id";
    while ($max_try > 0 && !file_exists($tmp_dir . '/' . $record_id . '.zip')) {
        file_put_contents($tmp_dir . "/_monitoring_log", "[" . date("Y-m-d H:i:s") . "] Downloading: Start downloading recording " . $record_id . PHP_EOL, FILE_APPEND);
        curl_download_file($url, $tmp_dir . '/' . $record_id . '.zip');
        if (!file_exists($tmp_dir . '/' . $record_id . '.zip')){
            file_put_contents($tmp_dir . "/_monitoring_log", "[" . date("Y-m-d H:i:s") . "] Downloading: Failed to download recording " . $record_id . PHP_EOL, FILE_APPEND);
            sleep(360);
        }
        $max_try--;
    }; */
    
    if ($max_try == 0)
        mail($mailto_admins, "Download error in $classroom", "Failed to download recording $record_id from Axis camera $axiscam_ip");
}

/**
 * Recursive function that extract all records from an xml file
 * @param type $xml
 * @param type $records
 * @return type
 */
function private_capture_xml_parse($xml, &$records) {
    $array = array();
    if (trim($xml->getName()) == 'recording') {
        foreach ($xml->attributes() as $name => $value) {
            $array[$name] = trim($value);
        }
        $records[] = $array;
    } else {
        foreach ($xml->children() as $name => $xmlchild) {
            private_capture_xml_parse($xmlchild, $records);
        }
    }
    return $records;
}

/**
 *
 * @param <type> $assoc_array
 * @return <xml_string>
 * @desc takes an assoc array and transform it in a xml metadata file
 */
function axiscam_assoc_array2xml_file($assoc_array, $axiscam_metadata_file) {
    $xmlstr = "<?xml version='1.0' standalone='yes'?>\n<metadata>\n</metadata>\n";
    $xml = new SimpleXMLElement($xmlstr);
    foreach ($assoc_array as $key => $value) {
        $xml->addChild($key, $value);
    }
    $xml_txt = $xml->asXML();
    file_put_contents($axiscam_metadata_file, $xml_txt);
    chmod($axiscam_metadata_file, 0644);
}

function axiscam_xml_file2assoc_array($meta_path) {
    $xml = simplexml_load_file($meta_path);
    if ($xml === false)
        return false;
    $assoc_array = array();
    foreach ($xml as $key => $value) {
        $assoc_array[$key] = (string) $value;
    }
    return $assoc_array;
}

function capture_axiscam_tmpdir_get($asset) {
    global $axiscam_basedir;

    $tmp_dir = $axiscam_basedir . '/var/' . $asset;
    if (!dir($tmp_dir))
        mkdir($tmp_dir, 0777, true);

    return $tmp_dir;
}

?>
