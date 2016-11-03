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

/*
 * This file contains all functions needed to save information about the current
 * recording. Those information are the metadata, the current user, whether or not the 
 * recording is locked, ...
 * 
 * This is the list of functions that must be implemented for session module.
 */

include "config.inc";

/**
 * @implements
 * Saves metadata for later use
 * @param assoc_array $meta Metadata for the record
 *       $meta['course_name'] : the course id
 *       $meta['origin'] : where does the video come from (typically the classroom's name)
 *       $meta['title'] : title for the recording
 *       $meta['description'] : description for the recording
 *       $meta['record_type'] : type of the recording (typically cam | slide | camslide)
 *       $meta['moderation'] : private or public recording (bool true : false)
 *       $meta['author'] : Author's name
 *       $meta['netid'] : Author's id
 *       $meta['record_date'] : YYYY_MM_DD_HH\hmm (i.e 2014_01_01_00h00)
 * @return bool metadata as string or false on error
 */
function session_modulename_metadata_save($meta) {}

/**
 * @implements
 * Deletes metadata 
 */
function session_modulename_metadata_delete() {}

/**
 * @implements
 * Returns an associative array containing metadata for the current recording
 * 
 *       $meta['course_name'] : the course id
 *       $meta['origin'] : where does the video come from (typically the classroom's name)
 *       $meta['title'] : title for the recording
 *       $meta['description'] : description for the recording
 *       $meta['record_type'] : type of the recording (typically cam | slide | camslide)
 *       $meta['moderation'] : private or public recording (bool true : false)
 *       $meta['author'] : Author's name
 *       $meta['netid'] : Author's id
 *       $meta['record_date'] : YYYY_MM_DD_HH\hmm (i.e 2014_01_01_00h00)
 */
function session_modulename_metadata_get() {}

/**
 * @Implements
 * Returns the metadata as an xml string
 *       $meta['course_name'] : the course id
 *       $meta['origin'] : where does the video come from (typically the classroom's name)
 *       $meta['title'] : title for the recording
 *       $meta['description'] : description for the recording
 *       $meta['record_type'] : type of the recording (typically cam | slide | camslide)
 *       $meta['moderation'] : private or public recording (bool true : false)
 *       $meta['author'] : Author's name
 *       $meta['netid'] : Author's id
 *       $meta['record_date'] : YYYY_MM_DD_HH\hmm (i.e 2014_01_01_00h00)
 * @return type
 */
function session_modulename_metadata_xml_get(){}

/**
 * @implements
 * Saves the time when the recording has been init
 * @param type $init the time in seconds
 */
function session_modulename_initstarttime_set($init) {}

/**
 * @implements
 * Returns the time when the recording has been init
 */
function session_modulename_initstarttime_get() {}

/**
 * @implements
 * Saves the time when the recording is started
 * @param type $startrec_info
 */
function session_modulename_recstarttime_set($startrec_info) {}

/**
 * @implements
 * Returns the time when the recording is started
 */
function session_modulename_recstarttime_get() {}

/**
 * @implements
 * Saves the time of the last request
 * @global type $time the time in seconds
 * @param type $startrec_info
 */
function session_modulename_last_request_set($time = '') {}

/**
 * @implements
 * Returns the time of the last request
 * @global type $recstarttime_file
 * @return type
 */
function session_modulename_last_request_get() {}
//
// Exclusive lock and concurrency management
//

/**
 * @implements
 * Checks whether or not there is already a capture going on.
 * @return bool "true" if a capture is already going on, "false" otherwise 
 */
function session_modulename_is_locked() {}

/**
 * @implements
 * Applies a lock on the recorder, i.e. saves the user currently using the system.
 * @param string $username 
 * @return bool error status
 */
function session_modulename_lock($username) {}

/**
 * @implements
 * Removes the lock on the recorder
 * @return bool error status
 */
function session_modulename_unlock() {}

/**
 * @implements
 * Returns the username of the person currently using the recorder, or false if no user is currently using it
 */
function session_modulename_current_user_get() {}


?>
