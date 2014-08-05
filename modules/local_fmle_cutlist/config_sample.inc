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

require_once dirname(__FILE__).'/../../global_config.inc';

$localfmle_ip = $ezrecorder_ip;
$localfmle_username = $ezrecorder_username;
$localfmle_force_quit_url = "$ezrecorder_url/index.php?action=recording_force_quit"; 
// used in cli_monitoring.php to stop the recording after a timeout

$localfmle_basedir = dirname(__FILE__).'/';
$localfmle_recorddir = $ezrecorder_recorddir;
$localfmle_movie_name = 'fmlemovie';

$localfmle_time_started_file = $localfmle_basedir.'var/_first_started'; // The file containing timestamp of creation
$localfmle_monitoring_file = $localfmle_basedir.'var/_monitoring_pid';
$localfmle_status_file = $localfmle_basedir.'var/status'; // Current recorder status (paused, recording, stopped,...)
$localfmle_recstatus_file = $localfmle_basedir.'var/rec_status'; // Current recording status (recording, stopped)
$localfmle_last_request_file = $localfmle_basedir.'var/_last_request'; // Time of this file says when the last refresh happened
$localfmle_recorder_logs = $localfmle_basedir.'var/_log'; // Log file
$localfmle_capture_file = $localfmle_basedir.'var/screencapture.jpg'; // Camera screenshot

$localfmle_processing_tool = "ffmpeg"; 
$localfmle_processing_tools = array("ffmpeg");
$ffmpegpath = "/usr/local/bin/ffmpeg";

$localfmle_upload_dir = $localfmle_recorddir . 'upload_to_server/';

$localfmle_script_cancel = $localfmle_basedir.'bash/fmle_cancel';
$localfmle_script_init = $localfmle_basedir.'bash/fmle_init';
$localfmle_script_start = $localfmle_basedir.'bash/fmle_start';
$localfmle_script_cutlist = $localfmle_basedir.'bash/fmle_cutlist';
$localfmle_script_stop = $localfmle_basedir.'bash/fmle_stop';
$localfmle_script_finalize = $localfmle_basedir.'bash/fmle_finalize';
$localfmle_script_thumbnail = $localfmle_basedir.'bash/fmle_thumbnail';

$localfmle_open = $localfmle_basedir.'applescript/fmle_open.scpt'; //used in cli_monitoring.php
$localfmle_action = $localfmle_basedir.'applescript/fmle_action.scpt'; //used in cli_monitoring.php
$localfmle_download_protocol = "rsync"; // if you change the protocol, you might
// have to adapt the function 'capture_localfmle_download_info_get()' in 'lib_capture.php'

?>
