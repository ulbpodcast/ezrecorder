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

/*
 * This file contains all functions needed to save information about the current
 * recording. Those information are the metadata, the current user, whether or not the 
 * recording is locked, ...
 * 
 * Every function annotated with the mention "@implements" is required to make
 * sure the "web_index.php" works properly
 */

include "config.inc";

/**
 * @implements
 * Saves metadata into a separate file ($metadata_file, defined in config.inc) for later use
 * @global string $metadata_file the (temporary) file that will contain the metadata
 * @param assoc_array $metadata_assoc_array Metadata for the record
 * @return bool error status 
 */
function session_xml_metadata_save($metadata_assoc_array) {
    global $metadata_file;

    //create and store recording properties
    $xml = session_xml_assoc_array2metadata($metadata_assoc_array);
    file_put_contents($metadata_file, $xml);
    chmod($metadata_file, 0644);

    return true;
}

/**
 * @implements
 * Deletes metadata file 
 * @global string $metadata_file the (temporary) file that will contain the metadata
 */
function session_xml_metadata_delete() {
    global $metadata_file;
    unlink($metadata_file);
}

/**
 * @implements
 * Returns an associative array containing metadata for the current recording
 */
function session_xml_metadata_get() {
    global $metadata_file;

    if (file_exists($metadata_file))
        return session_xml_metadata2assoc_array($metadata_file);
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
 * @global type $recstarttime_file
 * @param type $startrec_info
 */
function session_xml_recstarttime_set($startrec_info) {
    global $recstarttime_file;
    file_put_contents($recstarttime_file, $startrec_info);
}

function session_xml_recstarttime_get() {
    global $recstarttime_file;
    return file_get_contents($recstarttime_file);
}

//
// Exclusive lock and concurrency management
//

/**
 * @implements
 * Checks whether or not there is already a capture going on.
 * @return bool "true" if a capture is already going on, "false" otherwise 
 */
function session_xml_is_locked() {
    global $lock_file;

    return file_exists($lock_file);
}

/**
 * @implements
 * Applies a lock on the recorder, i.e. saves the user currently using the system.
 * @param string $username 
 * @return bool error status
 */
function session_xml_lock($username) {
    global $lock_file;

    if (session_xml_is_locked()) {
        //  TODO 
        //        capture_last_error('Recorder is already in use');
        return false;
    }
    if (empty($username)) {
        //  TODO
        //        capture_last_error('capture_lock: empty username!');
        return false;
    }

    $res = file_put_contents($lock_file, $username);
    return true;
}

/**
 * @implements
 * Removes the lock on the recorder
 * @return bool error status
 */
function session_xml_unlock() {
    global $lock_file;

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

/**
 *
 * @param path $meta_path
 * @return assoc_array|false
 * @desc open a metadatafile (xml 1 level) and return all properties and values in an associative array
 */
function session_xml_metadata2assoc_array($meta_path) {
    $xml = simplexml_load_file($meta_path);
    if ($xml === false)
        return false;
    $assoc_array = array();
    foreach ($xml as $key => $value) {
        $assoc_array[$key] = (string) $value;
    }
    return $assoc_array;
}

/**
 *
 * @param <type> $assoc_array
 * @return <xml_string>
 * @desc takes an assoc array and transform it in a xml metadata string
 */
function session_xml_assoc_array2metadata($assoc_array) {
    $xmlstr = "<?xml version='1.0' standalone='yes'?>\n<metadata>\n</metadata>\n";
    $xml = new SimpleXMLElement($xmlstr);
    foreach ($assoc_array as $key => $value) {
        $xml->addChild($key, $value);
    }
    $xml_txt = $xml->asXML();
    return $xml_txt;
}

?>
