<?php

/*
 * This file contains all functions needed to save information about the current
 * recording. Those information are the metadata, the current user, whether or not the 
 * recording is locked, ...
 * 
 * Every function annotated with the mention "@implements" is required to make
 * sure the "web_index.php" works properly
 */

include "config.inc";
require_once __DIR__."/../../global_config.inc";
require_once "$basedir/lib_various.php";

/**
 * @implements
 * Saves metadata into a separate file ($metadata_file, defined in config.inc) for later use
 * @global string $metadata_file the (temporary) file that will contain the metadata
 * @param assoc_array $metadata_assoc_array Metadata for the record
 * @return bool error status 
 */
function session_xml_metadata_save($metadata_assoc_array) {
    global $metadata_file;
    global $logger;
    
    $processUser = posix_getpwuid(posix_geteuid());
    $name = $processUser['name'];
        
    //create and store recording properties
    $xml = xml_assoc_array2metadata($metadata_assoc_array);
    $res = file_put_contents($metadata_file, $xml);
    if(!$res) {
        $logger->log(EventType::RECORDER_METADATA, LogLevel::ERROR, __FUNCTION__.": Failed to save metadata to $metadata_file. Current user: $name. Probably a permission problem.", array("lib_recording_session"));
        return false;
    }
    $res = chmod($metadata_file, 0644);
    if(!$res) {
        //file is owned by podclient. Any solution ?
        $logger->log(EventType::TEST, LogLevel::WARNING, "Could not chmod file $metadata_file. Current user: $name", array("lib_recording_session"));
    }
    return $xml;
}

/**
 * @implements
 * Deletes metadata file 
 * @global string $metadata_file the (temporary) file that will contain the metadata
 */
function session_xml_metadata_delete() {
    global $metadata_file;
    if(file_exists($metadata_file))
        unlink($metadata_file);
}

/**
 * @implements
 * Returns an associative array containing metadata for the current recording
 */
function session_xml_metadata_get() {
    global $metadata_file;

    if (file_exists($metadata_file))
        return xml_file2assoc_array($metadata_file);
    else 
        return false;
}

/**
 * @Implements
 * Returns the metadata as an xml string
 * @global string $metadata_file
 * @return type
 */
function session_xml_metadata_xml_get(){
    global $metadata_file;
    return file_get_contents($metadata_file);
}

/**
 * @implements
 * Saves the time when the recording is started
 * @gparam type $init the current time in seconds
 */
function session_xml_initstarttime_set($init) {
    global $initstarttime_file;
    file_put_contents($initstarttime_file, $init);
}

/**
 * @implements
 * Returns the time when the recording is started
 * @return type
 */
function session_xml_initstarttime_get() {
    global $initstarttime_file;
    return file_get_contents($initstarttime_file);
}

/**
 * @implements
 * Saves the time when the recording is started
 * @global type $recstarttime_file
 * @param type $startrec_info
 */
function session_xml_recstarttime_set($startrec_info) {
    global $recstarttime_file;
    file_put_contents($recstarttime_file, $startrec_info);
}

/**
 * @implements
 * Returns the time when the recording is started
 * @global type $recstarttime_file
 * @return type
 */
function session_xml_recstarttime_get() {
    global $recstarttime_file;
    return file_get_contents($recstarttime_file);
}

/**
 * @implements
 * Saves the time of the last request
 * @global type $recstarttime_file
 * @param type $startrec_info
 */
function session_xml_last_request_set($time = '') {
    global $last_request_file;
    if ($time == ''){
        $time = time();
    }
    file_put_contents($last_request_file, $time);
}

/**
 * @implements
 * Returns the time of the last request
 * @global type $recstarttime_file
 * @return type
 */
function session_xml_last_request_get() {
    global $last_request_file;
    return file_get_contents($last_request_file);
}

//
// Exclusive lock and concurrency management
//

/**
 * @implements
 * Checks whether or not there is already a capture going on.
 * @return bool "true" if a capture is already going on, "false" otherwise 
 */
function session_xml_is_locked($username = null) {
    global $lock_file;

    if(!file_exists($lock_file))
        return false;
    
    if($username == null)
        return true;
    
    $content = file_get_contents($lock_file);
    return trim($content) == $username;
}

/**
 * @implements
 * Applies a lock on the recorder, i.e. saves the user currently using the system.
 * @param string $username 
 * @return bool error status
 */
function session_xml_lock($username) {
    global $lock_file;
    global $logger;
    
    if (session_xml_is_locked()) {
        $logger->log(EventType::TEST, LogLevel::ERROR, "Cannot lock recorder, recorder already is use", array(__FUNCTION__));
        return false;
    }
    if (empty($username)) {
        $logger->log(EventType::TEST, LogLevel::ERROR, "Empty user name given", array(__FUNCTION__));
        return false;
    }

    $res = file_put_contents($lock_file, $username);
    if($res == false) {
        $logger->log(EventType::TEST, LogLevel::ALERT, "Could not write lock file $lock_file", array(__FUNCTION__));
        return false;
    }
    
    return true;
}

/**
 * @implements
 * Removes the lock on the recorder
 * @return bool error status
 */
function session_xml_unlock() {
    global $lock_file;

    if(file_exists($lock_file))
        unlink($lock_file); 
    
    return true;
}

/**
 * @implements
 * Returns the username of the person currently using the recorder, or false if no user is currently using it
 */
function session_xml_current_user_get() {
    global $lock_file;

    if (!session_xml_is_locked())
        return false;

    return file_get_contents($lock_file);
}
