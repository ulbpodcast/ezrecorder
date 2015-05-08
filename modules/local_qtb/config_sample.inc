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

$localqtb_features = array('recording');
$localqtb_ip = $ezrecorder_ip;
$localqtb_username = $ezrecorder_username;
$localqtb_force_quit_url = "$ezrecorder_url/index.php?action=recording_force_quit"; 
// used in cli_monitoring.php to stop the recording after a timeout

$localqtb_basedir = $modules_basedir . '/local_qtb/';
$localqtb_recorddir = $ezrecorder_recorddir;
$localqtb_movie_name = 'qtbmovie';

$localqtb_monitoring_file = $localqtb_basedir.'var/_monitoring_pid';
$localqtb_status_file = $localqtb_basedir.'var/status'; // Current recorder status (paused, recording, stopped,...)
$localqtb_recorder_logs = $localqtb_basedir.'var/_log'; // Log file
$localqtb_capture_file = $localqtb_basedir.'var/screencapture.jpg'; // Camera screenshot

$localqtb_processing_tool = "ffmpeg"; // [ffmpeg | pcastaction]
$localqtb_processing_tools = array("ffmpeg", "pcastaction");
$ffmpegpath = "/usr/local/bin/ffmpeg";
$localqtb_mono = true; // determines whether the input audio channel is mono or stereo

$localqtb_upload_dir = $localqtb_recorddir . 'upload_to_server/';

$localqtb_script_qtbcancel = $localqtb_basedir.'bash/qtbcancel';
$localqtb_script_qtbnew = $localqtb_basedir.'bash/qtbnew';
$localqtb_script_qtbrec = $localqtb_basedir.'bash/qtbrec';
$localqtb_script_qtbpause = $localqtb_basedir.'bash/qtbpause';
$localqtb_script_qtbresume = $localqtb_basedir.'bash/qtbresume';
$localqtb_script_qtbstop = $localqtb_basedir.'bash/qtbstop';
$localqtb_script_qtbfinalize = $localqtb_basedir.'bash/qtbfinalize';
$localqtb_script_qtbthumbnail = $localqtb_basedir.'bash/screencapturecrop';

$localqtb_qtbstartmovierec = $localqtb_basedir.'applescript/qtbstartmovierec.scpt'; //used in cli_monitoring.php
$localqtb_qtbposition = $localqtb_basedir.'applescript/positionqtb.scpt'; //used in cli_monitoring.php
$localqtb_download_protocol = "rsync"; // if you change the protocol, you might
// have to adapt the function 'capture_localqtb_download_info_get()' in 'lib_capture.php'

?>
