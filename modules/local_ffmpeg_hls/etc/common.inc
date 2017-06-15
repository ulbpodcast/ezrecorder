<?php

if(isset($config_version) && $config_version != "2017031601")
    trigger_error("local_ffmpeg_hls config.inc file is not up to date", E_USER_WARNING);

$ffmpeg_features = array('recording', 'streaming');

$ffmpeg_basedir = dirname(__DIR__).'/';
$ffmpeg_movie_name = 'ffmpegmovie';

//we may want to move the next two to config if/when we got more than one possibility
$ffmpeg_download_protocol = "rsync"; // if you change the protocol, you might
// have to adapt the function 'capture_ffmpeg_info_get()' in 'lib_capture.php'
$ffmpeg_streaming_protocol = "http"; // if you change the protocol, you might
// have to adapt the function 'capture_ffmpeg_info_get()' in 'lib_capture.php'

$ffmpeg_monitoring_file = $ffmpeg_basedir.'var/_monitoring_pid';
$ffmpeg_monitoring_asset = $ffmpeg_basedir.'var/_monitoring_asset';
$ffmpeg_pid_file = $ffmpeg_basedir.'var/ffmpeg_pid';
// second pid is only used when multiple ffmpeg process are needed (for high and low stream)
$ffmpeg_pid2_file = $ffmpeg_basedir.'var/ffmpeg_pid2';
$ffmpeg_status_file = $ffmpeg_basedir.'var/status'; // Current recorder status (paused, recording, stopped,...)
$ffmpeg_recstatus_file = $ffmpeg_basedir.'var/rec_status'; // Current recording status (recording, stopped)
$ffmpeg_recorder_logs = $ffmpeg_basedir.'var/_log'; // Log file
$ffmpeg_streaming_info = $ffmpeg_basedir.'var/streaming'; // contains server and port number
$ffmpeg_capture_file = $ffmpeg_basedir.'var/screencapture.jpg'; // Camera screenshot
$process_result_filename = "process_result.txt";
$ffmpeg_capture_tmp_file = "$ffmpeg_basedir/var/pic_new.jpg"; // ffmpeg thumb output file
$ffmpeg_capture_transit_file = "$ffmpeg_basedir/var/transit_screencapture.jpg"; // tmp resized file


$ffmpeg_processing_tool = "ffmpeg"; 
$ffmpeg_processing_tools = array("ffmpeg");

$ffmpeg_cli_streaming = $ffmpeg_basedir.'cli_streaming_content_send.php';

$ffmpeg_script_cancel = $ffmpeg_basedir.'bash/ffmpeg_cancel';
$ffmpeg_script_init = $ffmpeg_basedir.'bash/ffmpeg_init';
$ffmpeg_script_start = $ffmpeg_basedir.'bash/ffmpeg_start';
$ffmpeg_script_cutlist = $ffmpeg_basedir.'bash/ffmpeg_cutlist';
$ffmpeg_script_stop = $ffmpeg_basedir.'bash/ffmpeg_stop';
$ffmpeg_script_finalize = $ffmpeg_basedir.'bash/ffmpeg_finalize';
$ffmpeg_script_process = $ffmpeg_basedir.'bash/ffmpeg_process.php';

$ffmpeg_input_source_list = array("DeckLink", "IntensityShuttle", "IntensityShuttleThunderbolt", "UltraStudioMiniRecorder", "avfoundation", "AV.io", "rtsp");


//variables used in bash scripts. A bash config file is created from this at module init.
$bash_env = array();

$bash_env['EZCASTDIR']=$ffmpeg_basedir;
$bash_env['RECORDDIR']=$ezrecorder_recorddir;
$bash_env['COMMON_MOVIE_NAME']=$ffmpeg_movie_name;
$bash_env['STATUS']=$ffmpeg_status_file;
$bash_env['STREAMING_INFO']=$ffmpeg_streaming_info;
$bash_env['CLASSROOM']=$classroom;
$bash_env['MAIL_TO']=$mailto_admins;
$bash_env['PHP_PATH']=$php_cli_cmd;
$bash_env['FFMPEG_PATH']=$ffmpeg_cli_cmd;
$bash_env['FFMPEG_MONO']=($ffmpeg_mono ? 1 : 0);
$bash_env['FFMPEG_PID']=$ffmpeg_pid_file;
$bash_env['FFMPEG_PID2']=$ffmpeg_pid2_file;
$bash_env['AVFOUNDATION_VIDEO_INTERFACE']=$avfoundation_video_interface;
$bash_env['AVFOUNDATION_AUDIO_INTERFACE']=$avfoundation_audio_interface;
$bash_env['DECKLINK_DEVICE']=$decklink_device;
$bash_env['DECKLINK_FORMAT_INDEX']=$decklink_format_index;

$bash_env['THUMBNAIL_PATH']=$ffmpeg_basedir."/var/pic_new.jpg";

//rstp uris. Used when source is set to rtsp.
$bash_env['FFMPEG_RTSP_MEDIA_HIGH_URI']=$ffmpeg_rtsp_media_high_uri;
$bash_env['FFMPEG_RTSP_MEDIA_LOW_URI']=$ffmpeg_rtsp_media_low_uri;

$bash_env['H264_PROFILE']=$h264_profile;
$bash_env['H264_PRESET']=$h264_preset;
