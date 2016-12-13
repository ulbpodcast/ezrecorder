<?php

// used by cli_monitoring.php when a recording has timed out
$remoteffmpeg_basedir = __DIR__.'/';
$remoteffmpeg_movie_name = "ffmpegmovie";
$remoteffmpeg_monitoring_file = $remoteffmpeg_basedir . 'var/_monitoring_pid';
$remoteffmpeg_rec_status_file = $remoteffmpeg_basedir . 'var/rec_status';
$remoteffmpeg_status_file = $remoteffmpeg_basedir . 'var/status';
$remoteffmpeg_streaming_info = $remoteffmpeg_basedir . 'var/streaming.xml';
$remoteffmpeg_cli_streaming = $remoteffmpeg_basedir . 'cli_streaming_content_send.php';
$remoteffmpeg_pid_file = $remoteffmpeg_basedir.'var/ffmpeg_pid';
$ffmpeg_movie_name = 'ffmpegmovie';

$remoteffmpeg_input_source_list = array("IntensityShuttle", "UltraStudioMiniRecorder", "avfoundation", "AV.io");
