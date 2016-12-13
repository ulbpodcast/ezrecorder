<?php

require_once 'etc/config_sample.inc';
require_once(__DIR__."/../../lib_various.php");

echo PHP_EOL . 
     "*******************************************" . PHP_EOL;
echo "* Installation of local_ffmpeg_hls module *" . PHP_EOL;
echo "*******************************************" . PHP_EOL;

echo "Creating etc/config.inc" . PHP_EOL;

$config = file_get_contents("$ffmpeg_basedir/etc/config_sample.inc");

while(!in_array($value,$ffmpeg_input_source_list)) {
    $sources = "(";
    foreach($ffmpeg_input_source_list as $type) {
       $sources .= "," . $type;
    }
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
        echo "*Configuration of avfoundation audio and video interfaces indexes" . PHP_EOL;
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
    default:
        break;
}


$config = preg_replace('/\$ffmpeg_input_source = (.+);/', '\$ffmpeg_input_source = "' . $ffmpeg_input_source . '";', $config);
$config = preg_replace('/\$ffmpeg_rtsp_media_high_uri = (.+);/', '\$ffmpeg_rtsp_media_high_uri = "' . $ffmpeg_rtsp_media_high_uri . '";', $config);
$config = preg_replace('/\$ffmpeg_rtsp_media_low_uri = (.+);/', '\$ffmpeg_rtsp_media_low_uri = "' . $ffmpeg_rtsp_media_low_uri . '";', $config);
$config = preg_replace('/\$avfoundation_video_interface = (.+);/', '\$avfoundation_video_interface = "' . $avfoundation_video_interface . '";', $config);        
$config = preg_replace('/\$avfoundation_audio_interface = (.+);/', '\$avfoundation_audio_interface = "' . $avfoundation_audio_interface . '";', $config);

file_put_contents("$ffmpeg_basedir/etc/config.inc", $config);

$perms_file = file_get_contents("$ffmpeg_basedir/setperms_sample.sh");
$perms_file = str_replace("!USER", $ezrecorder_username, $perms_file);
$perms_file = str_replace("!WEB_USER", $ezrecorder_web_user, $perms_file);
file_put_contents("$ffmpeg_basedir/setperms.sh", $perms_file);

system("chmod -R 755 $ffmpeg_basedir/bash");
chmod("$ffmpeg_basedir/setperms.sh", 0755);
echo "Enter sudo password for executing setperms.sh ." . PHP_EOL;
system("sudo $ffmpeg_basedir/setperms.sh");
