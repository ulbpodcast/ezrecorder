<?php

require_once __DIR__.'/config_sample.inc';
require_once(__DIR__."/../../../lib_install.php");
require_once(__DIR__."/../../../global_config.inc");

echo PHP_EOL . 
     "***************************************" . PHP_EOL;
echo "* Installation of remote_ffmpeg_cutlist remote module    *" . PHP_EOL;
echo "***************************************" . PHP_EOL;

echo "Creating config.inc" . PHP_EOL;

$config = file_get_contents(__DIR__ . "/config_sample.inc");

echo "Please enter now the requested values: " . PHP_EOL;

$value = "";
while(!in_array($value,$remoteffmpeg_input_source_list)) {
    $sources = "(";
    foreach($remoteffmpeg_input_source_list as $type) {
       $sources .=  $type . ",";
    }
    $sources = rtrim($sources, ",");
    $sources .= ")";
    
    $value = read_line("Input source, valid values are: $sources [default: '$remoteffmpeg_input_source']: ");
    if ($value != "")
        $remoteffmpeg_input_source = $value;
    else
        break; //keep default
}
 
$value = read_line("Path to the local video repository on this Mac [default: '$remoteffmpeg_recorddir']: ");
if ($value != "")
    $remoteffmpeg_recorddir = $value; 
    
if (!is_dir($remoteffmpeg_recorddir . '/ffmpeg_hls')){
    mkdir($remoteffmpeg_recorddir . '/ffmpeg_hls', 0755, true);
}

$h264_preset = "medium";
$h264_profile = "main";

$value = read_line("H264 preset [default: '$h264_preset']: ");
if ($value != "")
    $h264_preset = $value; 

$value = read_line("H264 preset [default: '$h264_profile']: ");
if ($value != "")
    $h264_profile = $value; 


$avfoundation_video_interface = 0;
$avfoundation_audio_interface = 1;
$decklink_format_index = 14;
$decklink_device = "UltraStudio Mini Recorder";
$decklink_volume = 1;

switch($remoteffmpeg_input_source)
{
    case "avfoundation":
    case "AV.io":
        echo "* Configuration of avfoundation audio and video interfaces indexes" . PHP_EOL;
        echo "If needed, you can list them with 'ffmpeg -f avfoundation -list_devices true -i \"\"'" .PHP_EOL;
        $value = read_line("avfoundation video interface [default: '$avfoundation_video_interface']:");
         if ($value != "")
           $avfoundation_video_interface = $value;
        //else keep default
         
        $value = read_line("avfoundation audio interface [default: '$avfoundation_audio_interface']:");
        if ($value != "")
           $avfoundation_audio_interface = $value;
        //else keep default
        break;
    case "IntensityShuttle":
    case "IntensityShuttleThunderbolt":
    case "UltraStudioMiniRecorder":
        echo "Input source '$remoteffmpeg_input_source' is deprecated and only kept for compatibility. You should use DeckLink instead." . PHP_EOL;
        break;
    case "DeckLink":
        echo "* Configuration of decklink format index" . PHP_EOL;
        echo "If needed, you can list decklink device with command: ffmpeg -f decklink -list_devices 1 -i dummy" .PHP_EOL;
        echo "Then list available formats with command: ffmpeg -f decklink -list_formats 1 -i 'Device Name'" .PHP_EOL;
        echo "Example:  ffmpeg -f decklink -list_formats 1 -i 'UltraStudio Mini Recorder'" .PHP_EOL;
        
        $value = read_line("Device name [default: '$decklink_device']:");
         if ($value != "")
           $decklink_device = $value;
         
        $value = read_line("Format index [default: '$decklink_format_index']:");
        if ($value != "")
           $decklink_format_index = $value;
        
        $value = read_line("Decklink volume modifier [default: '$decklink_volume']:");
        if ($value != "")
           $decklink_volume = $value;
        
        break;
        case "rtsp":
            $value = read_line("rtsp high stream uri [default: '$ffmpeg_rtsp_media_high_uri']:");
            if ($value != "")
               $ffmpeg_rtsp_media_high_uri = $value;
           //else keep default

            $value = read_line("rtsp low stream uri (used only with streaming) [default: '$ffmpeg_rtsp_media_low_uri']:");
            if ($value != "")
               $ffmpeg_rtsp_media_low_uri = $value;
           //else keep default
        break;
    default:
        break;
}

$config = preg_replace('/\$ffmpeg_rtsp_media_high_uri = (.+);/', '\$ffmpeg_rtsp_media_high_uri = "' . $ffmpeg_rtsp_media_high_uri . '";', $config);
$config = preg_replace('/\$ffmpeg_rtsp_media_low_uri = (.+);/', '\$ffmpeg_rtsp_media_low_uri = "' . $ffmpeg_rtsp_media_low_uri . '";', $config);

$config = preg_replace('/\$remoteffmpeg_input_source = (.+);/', '\$remoteffmpeg_input_source = "' . $remoteffmpeg_input_source . '";', $config);
$config = preg_replace('/\$remoteffmpeg_recorddir = (.+);/', '\$remoteffmpeg_recorddir = "' . $remoteffmpeg_recorddir . '";', $config);
file_put_contents("$remoteffmpeg_basedir/config.inc", $config);

echo PHP_EOL . "Changing values in bash/localdefs" . PHP_EOL;

$bash_file = file_get_contents("$remoteffmpeg_basedir/bash/localdefs_sample");
$bash_file = str_replace("!PATH", $remoteffmpeg_basedir, $bash_file);
$bash_file = str_replace("!RECORD_PATH", $remoteffmpeg_recorddir, $bash_file);
$bash_file = str_replace("!CLASSROOM", $classroom, $bash_file);
$bash_file = str_replace("!MAIL_TO", $mailto_admins, $bash_file);
$bash_file = str_replace("!INPUT_SOURCE", $remoteffmpeg_input_source, $bash_file);
$bash_file = str_replace("!PHP_PATH", $php_cli_cmd, $bash_file);
$bash_file = str_replace("!FFMPEG_PATH", $ffmpeg_cli_cmd, $bash_file);
$bash_file = str_replace("!H264_PROFILE", $h264_profile, $bash_file);
$bash_file = str_replace("!H264_PRESET", $h264_preset, $bash_file);
$bash_file = str_replace("!AVFOUNDATION_VIDEO_INTERFACE", $avfoundation_video_interface, $bash_file);
$bash_file = str_replace("!AVFOUNDATION_AUDIO_INTERFACE", $avfoundation_audio_interface, $bash_file);
$bash_file = str_replace("!DECKLINK_DEVICE", "\"$decklink_device\"", $bash_file);
$bash_file = str_replace("!DECKLINK_FORMAT_INDEX", $decklink_format_index, $bash_file);
$bash_file = str_replace("!DECKLINK_VOLUME", $decklink_volume, $bash_file);

$bash_file = str_replace("!HIGH_URI", $ffmpeg_rtsp_media_high_uri, $bash_file);
$bash_file = str_replace("!LOW_URI", $ffmpeg_rtsp_media_high_uri, $bash_file);

file_put_contents("$remoteffmpeg_basedir/bash/localdefs", $bash_file);

system("chmod -R 755 $remoteffmpeg_basedir/bash");
