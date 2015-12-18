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

require_once dirname(__FILE__).'/../../../global_config.inc';

$ffmpeg_features = array('recording', 'streaming');

$ffmpeg_basedir = dirname(__DIR__).'/';
$ffmpeg_moviesdir = $ezrecorder_recorddir.'ffmpeg_hls';
$ffmpeg_movie_name = 'ffmpegmovie';

$ffmpeg_monitoring_file = $ffmpeg_basedir.'var/_monitoring_pid';
$ffmpeg_pid_file = $ffmpeg_basedir.'var/ffmpeg_pid';
// second pid is only used when multiple ffmpeg process are needed
$ffmpeg_pid2_file = $ffmpeg_basedir.'var/ffmpeg_pid2';
$ffmpeg_status_file = $ffmpeg_basedir.'var/status'; // Current recorder status (paused, recording, stopped,...)
$ffmpeg_recstatus_file = $ffmpeg_basedir.'var/rec_status'; // Current recording status (recording, stopped)
$ffmpeg_recorder_logs = $ffmpeg_basedir.'var/_log'; // Log file
$ffmpeg_streaming_info = $ffmpeg_basedir.'var/streaming'; // contains server and port number
$ffmpeg_capture_file = $ffmpeg_basedir.'var/screencapture.jpg'; // Camera screenshot

$ffmpeg_processing_tool = "ffmpeg"; 
$ffmpeg_processing_tools = array("ffmpeg");
$ffmpegpath = "/usr/local/bin/ffmpeg";
$ffmpeg_mono = true; // determines whether the input audio channel is mono or stereo

$ffmpeg_upload_dir = $ezrecorder_recorddir . 'upload_to_server/';

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
$ffmpeg_input_source = "UltraStudioMiniRecorder"; // specifies the input source (IntensityShuttle | UltraStudioMiniRecorder | avfoundation | rstp | ...)
// you might have to adapt the 'bash/ffmpeg_init' script
$ffmpeg_streaming_quality = "highlow"; // quality enabled for live stream (highlow | high | low | none)


//variables used in bash scripts. A bash config file is created from this at module init.
$bash_env = array();
$bash_env['EZCASTDIR']=$ffmpeg_basedir;
$bash_env['RECORDDIR']=$ezrecorder_recorddir;
$bash_env['MOVIESDIR']=$ffmpeg_moviesdir;
$bash_env['COMMON_MOVIE_NAME']=$ffmpeg_movie_name;
$bash_env['STATUS']=$ffmpeg_status_file;
$bash_env['STREAMING_INFO']=$ffmpeg_streaming_info;
$bash_env['CLASSROOM']=$classroom;
$bash_env['MAIL_TO']=$mailto_admins;
$bash_env['PHP_PATH']=$php_cli_cmd;
$bash_env['FFMPEG_PATH']=$ffmpegpath;
$bash_env['FFMPEG_MONO']=($ffmpeg_mono ? 1 : 0);
$bash_env['FFMPEG_PID']=$ffmpeg_pid_file;
$bash_env['FFMPEG_PID2']=$ffmpeg_pid2_file;

$bash_env['THUMBNAIL_PATH']=$ffmpeg_basedir."/var/pic_new.jpg";
$bash_env['FFMPEG_RTSP_MEDIA_HIGH_URI']="rtsp://xxx.xxx.xxx.xxx:xxx/xx";
$bash_env['FFMPEG_RTSP_MEDIA_LOW_URI']="rtsp://xxx.xxx.xxx.xxx:xxx/yy";
$bash_env['TMP_PID']=$ffmpeg_basedir."/var/tmp_pid";
        
?>
