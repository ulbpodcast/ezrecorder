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

/*
 * This is the list of functions that must be implemented for camera management module
 */

/**
 * @implements 
 * Initialize the recording settings.
 * This function should be called before the use of the camera.
 * This function should launch a background task to save time and keep syncro
 * between cam and slides (if both are available)
 * @param int $pid the process id of the background task (completed by the function itself)
 * @param associative_array $meta_assoc Metadata related to the record 
 *       $meta['course_name'] : the course id
         $meta['origin'] : where does the video come from (typically the classroom's name)
         $meta['title'] : title for the recording
         $meta['description'] : description for the recording
         $meta['record_type'] : type of the recording (typically cam | slide | camslide)
         $meta['moderation'] : private or public recording (bool true : false)
         $meta['author'] : Author's name
         $meta['netid'] : Author's id
         $meta['record_date'] : YYYY_MM_DD_HH\hmm (i.e 2014_01_01_00h00)
 * @return boolean true if everything went well; false otherwise
 */
function capture_modulename_init(&$pid, $meta_assoc) {};

/**
 * @implements
 * Launches the recording process 
 * @param string $asset the unique id for video : YYYY_MM_DD_hh\hmm_COURSE_ID (i.e 2014_01_01_00h00_COURSE_ID)
 * @return boolean true if everything went well; false otherwise
 */
function capture_modulename_start($asset) {};

/**
 * @implements
 * Pauses the current recording
 * @param string $asset the unique id for video : YYYY_MM_DD_hh\hmm_COURSE_ID (i.e 2014_01_01_00h00_COURSE_ID)
 * @return boolean true if everything went well; false otherwise
 */
function capture_modulename_pause($asset) {};

/**
 * @implements
 * Resumes the current paused recording
 * @param string $asset the unique id for video : YYYY_MM_DD_hh\hmm_COURSE_ID (i.e 2014_01_01_00h00_COURSE_ID)
 * @return boolean true if everything went well; false otherwise
 */
function capture_modulename_resume($asset) {};

/**
 * @implements
 * Stops the current recording
 * This function should launch a background task to save time and keep syncro
 * between cam and slides (if both are available)
 * @param int $pid the process id of the background task (completed by the function itself)
 * @param string $asset the unique id for video : YYYY_MM_DD_hh\hmm_COURSE_ID (i.e 2014_01_01_00h00_COURSE_ID)
 * @return boolean true if everything went well; false otherwise
 */
function capture_modulename_stop(&$pid, $asset) {};

/**
 * @implements
 * Ends the current recording and saves it as an archive
 * @param string $asset the unique id for video : YYYY_MM_DD_hh\hmm_COURSE_ID (i.e 2014_01_01_00h00_COURSE_ID)
 * @return boolean true if everything went well; false otherwise
 */
function capture_modulename_cancel($asset) {};

/** 
 * @implements
 * @param type $asset
 * @return true on process success, false on failure or result not found
 */
function capture_ffmpeg_process_result($asset) {}

/**
 * @implements
 * Processes the record before sending it to the server
 * @param int $pid the process id of the background task (completed by the function itself)
 * @param associative_array $meta_assoc Metadata related to the record 
 *       $meta['course_name'] : the course id
         $meta['origin'] : where does the video come from (typically the classroom's name)
         $meta['title'] : title for the recording
         $meta['description'] : description for the recording
         $meta['record_type'] : type of the recording (typically cam | slide | camslide)
         $meta['moderation'] : private or public recording (bool true : false)
         $meta['author'] : Author's name
         $meta['netid'] : Author's id
         $meta['record_date'] : YYYY_MM_DD_HH\hmm (i.e 2014_01_01_00h00)
 * @return boolean true if everything went well; false otherwise
 */
function capture_modulename_process($asset, &$pid) {};

/**
 * @implements
 * Finalizes the recording after it has been uploaded to the server.
 * The finalization consists in archiving video files in a specific dir
 * and removing all temp files used during the session.
 * @param int $pid the process id of the background task (completed by the function itself)
 * @param associative_array $meta_assoc Metadata related to the record 
 *       $meta['course_name'] : the course id
         $meta['origin'] : where does the video come from (typically the classroom's name)
         $meta['title'] : title for the recording
         $meta['description'] : description for the recording
         $meta['record_type'] : type of the recording (typically cam | slide | camslide)
         $meta['moderation'] : private or public recording (bool true : false)
         $meta['author'] : Author's name
         $meta['netid'] : Author's id
         $meta['record_date'] : YYYY_MM_DD_HH\hmm (i.e 2014_01_01_00h00)
 * @return boolean true if everything went well; false otherwise
 */
function capture_modulename_finalize($asset) {};

/**
 * @implements
 * Returns an associative array containing information required for the given action
 * @param string $action the action we want information about ('streaming' | 'download')
 * @param string $asset the unique id for video : YYYY_MM_DD_hh\hmm_COURSE_ID (i.e 2014_01_01_00h00_COURSE_ID)
 * @return boolean true if everything went well; false otherwise
 */
function capture_modulename_info_get($action, $asset) {};

/**
 * @implements
 * Creates a thumbnail picture
 * @return string the contents of the image to display
 */
function capture_modulename_thumbnail() {};

/**
 * @implements
 * Returns the current status of the recording 
 * Status may be "open", "recording", "paused", "stopped", "error"
 */
function capture_modulename_status_get() {};

/**
 * @implements
 * Defines the status of the current recording
 */
function capture_modulename_status_set($status) {};

/**
 * @implements
 * Returns the features offered by the module
 */
function capture_modulename_features_get(){};
?>
