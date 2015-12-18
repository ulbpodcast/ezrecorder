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

$clifmle_features = array('recording');
$clifmle_ip = $ezrecorder_ip;
$clifmle_username = $ezrecorder_username;

$clifmle_basedir = $modules_basedir . '/cli_fmle/';
$clifmle_recorddir = $ezrecorder_recorddir;
$clifmle_movie_name = 'clifmlemovie';

$clifmle_monitoring_file = $clifmle_basedir.'var/_monitoring_pid';
$clifmle_status_file = $clifmle_basedir.'var/status'; // Current recorder status (paused, recording, stopped,...)
$clifmle_recorder_logs = $clifmle_basedir.'var/_log'; // Log file
$clifmle_capture_file = $clifmle_basedir.'var/screencapture.jpg'; // Camera screenshot

$clifmle_processing_tool = "ffmpeg"; 
$clifmle_processing_tools = array("ffmpeg");
$ffmpegpath = "/usr/local/bin/ffmpeg";
$clifmle_mono = true; // determines whether the input audio channel is mono or stereo

$clifmle_upload_dir = $clifmle_recorddir . 'upload_to_server/';

$clifmle_script_cancel = $clifmle_basedir.'bash/fmle_cancel';
$clifmle_script_start = $clifmle_basedir.'bash/fmle_start';
$clifmle_script_pause = $clifmle_basedir.'bash/fmle_stop_rec';
$clifmle_script_resume = $clifmle_basedir.'bash/fmle_rec';
$clifmle_script_stop = $clifmle_basedir.'bash/fmle_stop';
$clifmle_script_finalize = $clifmle_basedir.'bash/fmle_finalize';
$clifmle_script_thumbnail = $clifmle_basedir.'bash/fmle_thumbnail';
 
$clifmle_download_protocol = "rsync"; // if you change the protocol, you might
// have to adapt the function 'capture_localfmle_download_info_get()' in 'lib_capture.php'

?>
