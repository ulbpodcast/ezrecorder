<?php 


require_once "global_config.inc";
require_once "modules/remote_ffmpeg_hls/lib_capture.php";
require_once "lib_model.php";

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
default:
	echo "Invalid test" .PHP_EOL;
	break;
}
