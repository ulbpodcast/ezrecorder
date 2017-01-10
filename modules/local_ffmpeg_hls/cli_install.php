<?php

$conf_file = __DIR__."/etc/config.inc";
if(file_exists($conf_file) && filesize($conf_file) > 0)
    require_once $conf_file;
else
    require_once __DIR__.'/etc/config_sample.inc';

require_once(__DIR__."/../../lib_install.php");
require_once(__DIR__."/../../global_config.inc");

echo PHP_EOL . 
     "*******************************************" . PHP_EOL;
echo "* Installation of local_ffmpeg_hls module *" . PHP_EOL;
echo "*******************************************" . PHP_EOL;

echo "Creating etc/config.inc" . PHP_EOL;

$config = file_get_contents("$ffmpeg_basedir/etc/config_sample.inc");

$value = "";
while(!in_array($value,$ffmpeg_input_source_list)) {
    $sources = "(";
    foreach($ffmpeg_input_source_list as $type) {
       $sources .=  $type . ",";
    }
    $sources = rtrim($sources, ",");
    $sources .= ")";
    
    $value = read_line("Input source, valid values are: $sources [default: '$ffmpeg_input_source']: ");
    if ($value != "")
        $ffmpeg_input_source = $value;
    else
        break; //keep default
}
unset($value);

switch($ffmpeg_input_source)
{
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
    case "avfoundation":
    case "AV.io":
        echo "* Configuration of avfoundation audio and video interfaces indexes" . PHP_EOL;
        echo "If needed, you can list them with 'ffmpeg -f avfoundation -list_devices true -i \"\"'" .PHP_EOL;
        $value = read_line("avfoundation video interface [default: '$avfoundation_video_interface']:");
         if ($value != "")
           $avfoundation_video_interface = $value;
        //else keep default
         
        $value = read_line("avfoundation video interface [default: '$avfoundation_audio_interface']:");
        if ($value != "")
           $avfoundation_audio_interface = $value;
        //else keep default
        break;
    case "IntensityShuttle":
    case "IntensityShuttleThunderbolt":
    case "UltraStudioMiniRecorder":
        echo "Input source '$ffmpeg_input_source' is deprecated and only kept for compatibility. You should use DeckLink instead." . PHP_EOL;
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
        break;
    default:
        break;
}


$config = preg_replace('/\$ffmpeg_input_source = (.+);/', '\$ffmpeg_input_source = "' . $ffmpeg_input_source . '";', $config);
$config = preg_replace('/\$ffmpeg_rtsp_media_high_uri = (.+);/', '\$ffmpeg_rtsp_media_high_uri = "' . $ffmpeg_rtsp_media_high_uri . '";', $config);
$config = preg_replace('/\$ffmpeg_rtsp_media_low_uri = (.+);/', '\$ffmpeg_rtsp_media_low_uri = "' . $ffmpeg_rtsp_media_low_uri . '";', $config);
$config = preg_replace('/\$avfoundation_video_interface = (.+);/', '\$avfoundation_video_interface = "' . $avfoundation_video_interface . '";', $config);        
$config = preg_replace('/\$avfoundation_audio_interface = (.+);/', '\$avfoundation_audio_interface = "' . $avfoundation_audio_interface . '";', $config);
$config = preg_replace('/\$decklink_device = (.+);/', '\$decklink_device = "' . $decklink_device . '";', $config);
$config = preg_replace('/\$decklink_format_index = (.+);/', '\$decklink_format_index = "' . $decklink_format_index . '";', $config);

file_put_contents("$ffmpeg_basedir/etc/config.inc", $config);

$perms_file = file_get_contents("$ffmpeg_basedir/setperms_sample.sh");
$perms_file = str_replace("!USER", $ezrecorder_username, $perms_file);
$perms_file = str_replace("!WEB_USER", $ezrecorder_web_user, $perms_file);
file_put_contents("$ffmpeg_basedir/setperms.sh", $perms_file);

system("chmod -R 755 $ffmpeg_basedir/bash");
chmod("$ffmpeg_basedir/setperms.sh", 0755);
echo "Enter sudo password for executing setperms.sh ." . PHP_EOL;
system("sudo $ffmpeg_basedir/setperms.sh");
