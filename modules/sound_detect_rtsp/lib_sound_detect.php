<?php

require_once __DIR__."/../local_ffmpeg_hls/etc/config.inc";
require_once __DIR__.'/../../global_config.inc';
require_once "$basedir/docs/lib_sound_detect_interface.php";

/**
 * Detect current sound volume using avfoundation
 */
class sound_detect_rtsp implements sound_detect {
    
    //private $avfoundation_index = 0;
    private $remote_ip = "";
    //private $remote_username = "";
    
    public function __construct() {
        //global $vu_meter_avfoundation_index;
        //global $vu_meter_avfoundation_remote_username;
        //global $vu_meter_avfoundation_remote_ip;
        global $ffmpeg_rtsp_media_high_uri;
        
        //$this->avfoundation_index = $vu_meter_avfoundation_index;
        //$this->remote_ip = $vu_meter_avfoundation_remote_ip;
        //$this->remote_username = $vu_meter_avfoundation_remote_username;
        $this->remote_ip = "rtsp://10.103.155.21:554/2";
        //$ffmpeg_rtsp_media_high_uri;
    }
    
    public function available() {
        return true;
    }
    
    //return mean volume, or false on failure
    public function mean_volume_get($asset) {
        global $timeout_script;
        global $ffmpeg_cli_cmd;
        global $logger;
        global $ezrecorder_username;
       
        //$audio_interface = $this->avfoundation_index;
        /*if (strtoupper(php_uname('s'))==='LINUX'){
			$cmd = "$timeout_script 10 $ffmpeg_cli_cmd -t 0.1 -f alsa -i \":$audio_interface\" -af 'volumedetect' -f null /dev/null 2>&1";
		}else{
			$cmd = "$timeout_script 10 $ffmpeg_cli_cmd -t 0.1 -f avfoundation -i \":$audio_interface\" -af 'volumedetect' -f null /dev/null 2>&1";
		}*/
        
        //$cmd = "$timeout_script 10 $ffmpeg_cli_cmd -t 0.1 -f rtsp -rtsp_transport tcp -thread_queue_size 127 -i $this->remote_ip -af 'volumedetect' -f null /dev/null 2>&1";
        $cmd = "$timeout_script 3 $ffmpeg_cli_cmd -t 0.1 -f rtsp -rtsp_transport tcp -i $this->remote_ip -af 'volumedetect' -f null /dev/null 2>&1";
       // if($this->remote_ip && $this->remote_username)
       //     $cmd = "sudo -u ".$ezrecorder_username." ssh -o ConnectTimeout=10 -o BatchMode=yes ". $this->remote_username.'@'.$this->remote_ip." \"$cmd\"";
        
//        echo $cmd;
        $returncode = 0;
        $cmdoutput = "";
        exec($cmd, $cmdoutput, $returncode);
        //$file = "/tmp/cmd.log";
        //file_put_contents($file, $cmdoutput);
        //$file = "/tmp/cmd1.log";
        //file_put_contents($file, $returncode);


        if($returncode != 0) {
            $logger->log(EventType::RECORDER_SOUND_DETECTION, LogLevel::ERROR, "Failed to run detect sound command: $cmd ", array(__FUNCTION__));
            return false;
        }

        $sound_info = new FileSoundInfo();
        $ok = extract_volumes_from_ffmpeg_output($cmdoutput, $sound_info->mean_volume, $sound_info->max_volume);
        if($ok === false) 
            return false;

        return $sound_info->mean_volume;
    }
}
