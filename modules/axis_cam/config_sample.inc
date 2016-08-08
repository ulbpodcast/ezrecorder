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

require_once dirname(__FILE__).'/../../global_config.inc';

$axiscam_features = array('recording');
$axiscam_ip = "127.0.0.1"; // static IP address of Axis camera
$axiscam_module_ip = $ezrecorder_ip;  
$axiscam_username = "root"; // username of axis camera
$axiscam_password = "";  // password of Axis camera
$axiscam_input_nb = 1; // Digital input port used to trigger the recording [1..4]

$axiscam_basedir = $modules_basedir . '/axis_cam/';
$axiscam_recorddir = $ezrecorder_recorddir;

$axiscam_time_started_file = $axiscam_basedir.'var/_first_started'; // The file containing timestamp of creation
$axiscam_monitoring_file = $axiscam_basedir.'var/_monitoring_pid';
$axiscam_status_file = $axiscam_basedir.'var/status'; // Current recorder status (paused, recording, stopped,...)
$axiscam_camstatus_file = $axiscam_basedir.'var/cam_status'; // Current camera status 
$axiscam_last_request_file = $axiscam_basedir.'var/_last_request'; // Time of this file says when the last refresh happened
$axiscam_recorder_logs = $axiscam_basedir.'var/_log'; // Log file
$axiscam_capture_file = $axiscam_basedir.'var/screencapture.jpg'; // Camera screenshot

$axiscam_upload_dir = $axiscam_recorddir . 'upload_to_server/';

$axiscam_script_finalize = $axiscam_basedir.'bash/finalize';
$axiscam_script_process = $axiscam_basedir.'bash/process_recording';
$axiscam_cli_process = $axiscam_basedir.'/cli_download_process.php';
$axiscam_cli_monitoring = $axiscam_basedir.'/cli_monitoring.php';
$axiscam_cli_resume = $axiscam_basedir.'/cli_resume_record.php';

$axiscam_download_protocol = "rsync"; // if you change the protocol, you might
// have to adapt the function 'capture_axiscam_download_info_get()' in 'lib_capture.php'

$axiscam_pause_duration = 15; // minimum duration in seconds for pause between 2 recordings
$ffmpegpath = "/usr/local/bin/ffmpeg";


?>
