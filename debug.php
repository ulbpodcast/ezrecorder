<?php 


require_once "global_config.inc";
require_once "modules/remote_ffmpeg_hls/lib_capture.php";
require_once "lib_model.php";
require_once "logger_sync_daemon.php";
require_once "lib_ffmpeg.php";

Logger::$print_logs = true;
$debug_mode = true;

if($argc == 1)
{
	echo "No test ID provided" . PHP_EOL;
	return false;
}

$testID = $argv[1];

switch($testID)
{
case "slide_info_get":
	$asset = $argv[2];

	$post_array = capture_remoteffmpeg_info_get('download', $asset);
	echo "RESULT: " . PHP_EOL;
	print_r($post_array);
	break;
case "slide_status_get":
	$res = capture_remoteffmpeg_status_get();
	echo "Result: $res" . PHP_EOL;
	break;	
case "stop_current_record":
        stop_current_record(false);
        break;
case "slide_post_process":
        $asset = $argv[2];
        $fct = 'capture_' . $slide_module . '_process';
        $slide_pid = "";
        $success = $fct($asset, $slide_pid);
        break;
case "log_sync":
        $daemon = new LoggerSyncDaemon(); 
        $res = $daemon->run();
        break;
case "movie_join_parts":
        $movies_path = $argv[2];
        $output = $argv[3];
        movie_join_parts($movies_path, "ffmpegmovie", $output);
        break;
default:
	echo "Invalid test" .PHP_EOL;
	break;
}
