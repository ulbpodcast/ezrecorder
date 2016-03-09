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

$ffmpeg_features = array('recording', 'streaming');
$ffmpeg_ip = $ezrecorder_ip;
$ffmpeg_username = $ezrecorder_username;
$ffmpeg_force_quit_url = "$ezrecorder_url/index.php?action=recording_force_quit"; 
// used in cli_monitoring.php to stop the recording after a timeout

$ffmpeg_basedir = dirname(__FILE__).'/';
$ffmpeg_recorddir = $ezrecorder_recorddir;
$ffmpeg_moviesdir = $ffmpeg_recorddir.'ffmpeg_hls';
$ffmpeg_movie_name = 'ffmpegmovie';

$ffmpeg_monitoring_file = $ffmpeg_basedir.'var/_monitoring_pid';
$ffmpeg_status_file = $ffmpeg_basedir.'var/status'; // Current recorder status (paused, recording, stopped,...)
$ffmpeg_recstatus_file = $ffmpeg_basedir.'var/rec_status'; // Current recording status (recording, stopped)
$ffmpeg_recorder_logs = $ffmpeg_basedir.'var/_log'; // Log file
$ffmpeg_streaming_info = $ffmpeg_basedir.'var/streaming'; // contains server and port number
$ffmpeg_capture_file = $ffmpeg_basedir.'var/screencapture.jpg'; // Camera screenshot

$ffmpeg_processing_tool = "ffmpeg"; 
$ffmpeg_processing_tools = array("ffmpeg");
$ffmpegpath = "/usr/local/bin/ffmpeg";
$ffmpeg_mono = true; // determines whether the input audio channel is mono or stereo

$ffmpeg_upload_dir = $ffmpeg_recorddir . 'upload_to_server/';

$ffmpeg_cli_streaming = $ffmpeg_basedir.'cli_streaming_content_send.php';

$ffmpeg_script_cancel = $ffmpeg_basedir.'bash/ffmpeg_cancel';
$ffmpeg_script_init = $ffmpeg_basedir.'bash/ffmpeg_init';
$ffmpeg_script_start = $ffmpeg_basedir.'bash/ffmpeg_start';
$ffmpeg_script_cutlist = $ffmpeg_basedir.'bash/ffmpeg_cutlist';
$ffmpeg_script_stop = $ffmpeg_basedir.'bash/ffmpeg_stop';
$ffmpeg_script_finalize = $ffmpeg_basedir.'bash/ffmpeg_finalize';

$ffmpeg_download_protocol = "rsync"; // if you change the protocol, you might
// have to adapt the function 'capture_ffmpeg_info_get()' in 'lib_capture.php'
$ffmpeg_streaming_protocol = "http"; // if you change the protocol, you might
// have to adapt the function 'capture_ffmpeg_info_get()' in 'lib_capture.php'
$ffmpeg_input_source = "UltraStudioMiniRecorder"; // specifies the input source (IntensityShuttle | UltraStudioMiniRecorder | avfoundation | ...)
// you might have to adapt the 'bash/ffmpeg_init' script
$ffmpeg_streaming_quality = "highlow"; // quality enabled for live stream (highlow | high | low | none)


?>
