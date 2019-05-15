<?php

require_once __DIR__."/../local_ffmpeg_hls/etc/config.inc";
//require_once __DIR__.'/../../global_config.inc';
require_once __DIR__."/../remote_ffmpeg_hls/remote/config.inc";
require_once "$basedir/docs/lib_sound_detect_interface.php";

/**
 * Detect current sound volume using avfoundation
 */
class sound_detect_rtsp implements sound_detect {
    
    private $remote_ip = "";
    
    public function __construct() {
        global $ffmpeg_rtsp_media_high_uri;
        global $ffmpeg_rstp_media_low_uri;

        if (!empty($ffmpeg_rtsp_media_low_uri)) {
          $this->remote_ip = $ffmpeg_rtsp_media_low_uri;
        }else{
          $this->remote_ip = $ffmpeg_rtsp_media_high_uri;
        }
    }
    
    public function available() {
        return true;
    }
    
    //return mean volume, or false on failure
    public function mean_volume_get($asset) {
        global $timeout_script;
        global $ffmpeg_cli_cmd;
        global $logger;
       
        $cmd = "$timeout_script 10 $ffmpeg_cli_cmd -t 0.1 -f rtsp -rtsp_transport tcp -i $this->remote_ip -af 'volumedetect' -f null /dev/null 2>&1";
        
        $returncode = 0;
        $cmdoutput = "";
        exec($cmd, $cmdoutput, $returncode);

        if($returncode != 0) {
            $logger->log(EventType::RECORDER_SOUND_DETECTION, LogLevel::ERROR, "Failed to run detect sound command: $cmd ", array(__FUNCTION__));
            return false;
        }

        $sound_info = new FileSoundInfo();
        $ok = extract_volumes_from_ffmpeg_output($cmdoutput, $sound_info->mean_volume, $sound_info->max_volume);

        $file="/tmp/log.txt";
        $data="########\n".$cmd."\n\n".$cmdoutput."\n\n".$sound_info->mean_volume."\n\n"; 
        file_put_contents($file, $data); 

        if($ok === false) 
            return false;

        return $sound_info->mean_volume;
    }
}
