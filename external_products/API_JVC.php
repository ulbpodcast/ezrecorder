<?php

//First draft of implementation of JVC cameras protocol
//Never tested in production at this point, may need adaptation

/*
error_reporting(E_ALL); 
ini_set( 'display_errors','1');

$api = new JVC_API("164.15.43.44", "jvc", "0000");
$api->debug = false;
echo "---------------------------------------------------------------" .PHP_EOL;
$api->auth();
echo "---------------------------------------------------------------" .PHP_EOL;
*/
//$api->get_system_info();
//$api->get_streaming_settings();
//$api->session_renewal();
//$api->set_streaming_server_settings_rtsp(0, "RTSP", "password");
//$api->set_streaming_server_settings_rtmp(1, "RTMP", "rtmp://10.0.2.100/play"); //marche pas ? ca met nimp dans l'url on dirait avec les /

//$api->get_cam_status();
//$api->set_streaming_ctrl(false);
//$api->set_current_streaming_server(0);
//$api->set_streaming_bitrate("5.0M");
//$api->get_streaming_server_settings(2);
//$api->get_available_streaming_resolution();
//$api->get_available_streaming_framerate("1280x720");
//$api->get_available_streaming_bitrate("RTSP", "1280x720", "25p");

//$api->set_streaming_resolution("1280x720");
//$api->set_streaming_framerate("25p");
//$api->set_streaming_bitrate("8.0M");
//echo "---------------------------------------------------------------" .PHP_EOL;
//$api->set_streaming_ctrl(false);

//$api->set_streaming_ctrl(true);
/*
$api->joystick_operation(JOYSTICK_PAN_DIRECTION::LEFT, 20, JOYSTICK_TILT_DIRECTION::STOP, 0);
sleep(2);
$api->joystick_operation(JOYSTICK_PAN_DIRECTION::RIGHT, 20, JOYSTICK_TILT_DIRECTION::STOP, 0);
sleep(2);
$api->joystick_operation(JOYSTICK_PAN_DIRECTION::STOP, 0, JOYSTICK_TILT_DIRECTION::STOP, 0);
for($i = 1; $i < 5; $i++) {
    $api->ptz_preset_move_to($i);
    sleep(4);
}

$api->ptz_preset_set(4);
sleep(2);
$api->ptz_preset_move_to(1);
sleep(2);
$api->ptz_preset_move_to(4);
*/


abstract class JOYSTICK_PAN_DIRECTION
{
    const LEFT = "Left";
    const RIGHT = "Right";
    const STOP = "Stop";
}

abstract class JOYSTICK_TILT_DIRECTION
{
    const UP = "Up";
    const DOWN = "Down";
    const STOP = "Stop";
}

//Improvement: auto auth if needed
//TODO: Check reponse http code
//TODO: Can curl throw exceptions?
class JVC_API {
    
    var $_ch = null;
    var $auth_url = null;
    var $api_url = null;
    var $username = null;
    var $password = null;
    var $cookie = null;
    var $debug = false;
    
    //constants
    static $streaming_types = array("UDP", "UDPN", "UDPL", "TCP", "RTSP", "ZIXIM", "ZIXIL", "RTMP");
    static $streaming_resolutions = array("1920x1080", "1440x1080", "1280x720", "720x480", "720x576", "640x360", "480x270");
    static $streaming_framerates = array("60p", "60i", "30p", "50p", "50i", "25p");
    static $streaming_bitrates = array("0.3M", "0.8M", "1.5M", "2.5M", "3.0M", "5.0M", "8.0M", "10M", "12M", "16M", "20M");
    
    const min_preset_index = 1;
    const max_preset_index = 100;
    const min_streaming_server_id = 0;
    const max_streaming_server_id = 3;
    
    function __construct($ip, $username, $password) {
        $this->_ch = curl_init();
        
        $this->auth_url = "$ip/api.php";
        $this->api_url = "$ip/cgi-bin/api.cgi";
        $this->username = $username;
        $this->password = $password;
        
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_ch, CURLOPT_HEADER, 1);
        curl_setopt($this->_ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($this->_ch, CURLOPT_VERBOSE, 1);
    }
    
    //TODO: check for failure
    function auth() {
        curl_setopt($this->_ch, CURLOPT_URL, $this->auth_url);
        echo "I'm sending first auth request ".PHP_EOL;
        $output = curl_exec($this->_ch);
        //var_dump(curl_getinfo($this->_ch, CURLINFO_HEADER_OUT));
        //var_dump($output);
        //echo PHP_EOL;
        $headers = self::get_headers_from_curl_response($output);
        //var_dump($headers);
        $authenticate_str = $headers["WWW-Authenticate"];
        echo PHP_EOL;
        echo "I received WWW-Authenticate with data: " . PHP_EOL;
        print_r($authenticate_str);
        echo PHP_EOL . PHP_EOL;
        
       //TODO: if ok:
        self::auth_step2();
    }
    
    function auth_step2() {
        echo "I'm sending auth request ".PHP_EOL;
        curl_setopt($this->_ch, CURLOPT_USERPWD,  $this->username . ":" . $this->password);
        curl_setopt($this->_ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        $output = curl_exec($this->_ch);
        /*
        echo "I'm sending: ".PHP_EOL;
        echo PHP_EOL;
        var_dump(curl_getinfo($this->_ch, CURLINFO_HEADER_OUT));
        echo PHP_EOL;
         */
        $headers = self::get_headers_from_curl_response($output);
        //var_dump($headers);
        //echo PHP_EOL;
        $cookie_temp = $headers["Set-Cookie"];
        $this->cookie = substr($cookie_temp, strlen("SessionID="));
        if(strlen($this->cookie) != 32) //always 32 ? Tested on PZ100 only.
            trigger_error("AUTH FAILED. Wrong cookie size. Cookie: ".$this->cookie, E_USER_ERROR);
        echo "Received cookie: ".$this->cookie . PHP_EOL;
    }
    
    function get_system_info() {
        echo "I'm fetching system infos" . PHP_EOL;
        return self::post_request('GetSystemInfo');
    }
    
    function get_streaming_settings() {
        echo "I'm fetching streaming settings" . PHP_EOL;
        return self::post_request('GetStreamingSettings');
    }
    
    function set_streaming_server_settings_rtsp($id, $alias, $password) {
        if($id < self::min_streaming_server_id || $id > self::max_streaming_server_id) {
            echo "Wrong ID $id given. Must be between " . self::min_streaming_server_id . ' and ' . self::max_streaming_server_id . PHP_EOL;
            return false;
        }
        echo "I'm setting streaming server rtsp settings" . PHP_EOL;
        $params = array( 
            "ID"        => $id,
            "Alias"     => $alias,
            "Password"  => $password,
        );
        return self::post_request('SetStreamingServerSettingsRTSP', $params);
    }
    
    function set_streaming_server_settings_rtmp($id, $alias, $dst_url, $stream_key = "livestreaming") {
        if($id < self::min_streaming_server_id || $id > self::max_streaming_server_id) {
            echo "Wrong ID $id given. Must be between " . self::min_streaming_server_id . ' and ' . self::max_streaming_server_id . PHP_EOL;
            return false;
        }
        echo "I'm setting streaming server rtsp settings" . PHP_EOL;
        $params = array( 
            "ID"        => $id,
            "Alias"     => $alias,
            "DstUrl"    => $dst_url,
            "StreamKey"  => $stream_key,
        );
        return self::post_request('SetStreamingServerSettingsRTMP', $params);
    }
    
    function get_streaming_server_settings($id) {
        if($id < self::min_streaming_server_id || $id > self::max_streaming_server_id) {
            echo "Wrong ID $id given. Must be between " . self::min_streaming_server_id . ' and ' . self::max_streaming_server_id . PHP_EOL;
            return false;
        }
        echo "I'm fetching streaming server settings" . PHP_EOL;
        $params = array( 
            "ID"        => $id,
        );
        return self::post_request('GetStreamingServerSettings', $params);
    }
    
    function session_renewal() {
        echo "Renewing session" . PHP_EOL;
        $params = array( 
            "Update"    => 1,
        );
        return self::post_request("SessionRenewal", $params);
    }
    
    function get_cam_status() {
        echo "Requesting cam status" . PHP_EOL;
        return self::post_request("GetCamStatus");
    }
    
    //start/stop streaming
    function set_streaming_ctrl($on = true) {
        $set_str = ($on ? "On" : "Off");
        echo "Request streaming $set_str" . PHP_EOL;
        $params = array( 
            "Streaming"    => $set_str,
        );
        return self::post_request("SetStreamingCtrl", $params);
    }
    
    function set_current_streaming_server($id) {
        if($id < self::min_streaming_server_id || $id > self::max_streaming_server_id) {
            echo "Wrong ID $id given. Must be between " . self::min_streaming_server_id . ' and ' . self::max_streaming_server_id . PHP_EOL;
            return false;
        }
        echo "I'm setting current streaming server to $id" . PHP_EOL;
        $params = array( 
            "ID"        => $id,
        );
        return self::post_request('SetCurrentStreamingServerID', $params);
    }
    
    function set_streaming_bitrate($bitrate = "5.0M") {
        if(!in_array($bitrate, self::$streaming_bitrates)) {
            echo "Wrong bitrate $bitrate given. Must be in " . print_r(self::$streaming_bitrates). PHP_EOL;
            return false;
        }
        echo "I'm setting current streaming bitrate to $bitrate" . PHP_EOL;
        $params = array( 
            "Bitrate"        => $bitrate,
        );
        return self::post_request('SetStreamingBitrate', $params);
    }
    
    function set_streaming_framerate($framerate) {
        if(!in_array($framerate, self::$streaming_framerates)) {
            echo "Wrong framerate $framerate given. Must be in " . var_dump(self::$streaming_framerates). PHP_EOL;
            return false;
        }
        echo "I'm setting current streaming framerate to $framerate" . PHP_EOL;
        $params = array( 
            "Framerate"        => $framerate,
        );
        return self::post_request('SetStreamingFramerate', $params);
    }
    
    function set_streaming_resolution($resolution) {
        if(!in_array($resolution, self::$streaming_resolutions)) {
            echo "Wrong resolution $resolution given. Must be in " . print_r(self::$streaming_resolutions). PHP_EOL;
            return false;
        }
        echo "I'm setting current streaming resolution to $resolution" . PHP_EOL;
        $params = array( 
            "Resolution"        => $resolution,
        );
        return self::post_request('SetStreamingResolution', $params);
    }
    
    function get_available_streaming_resolution() {
        return self::post_request('AvailableStreamingResolutionSettings');
    }
    
    function get_available_streaming_framerate($resolution) {
        if(!in_array($resolution, self::$streaming_resolutions)) {
            echo "Wrong resolution $resolution given. Must be in " . print_r(self::$streaming_resolutions). PHP_EOL;
            return false;
        }
        echo "I'm fetching available streaming framerate for resolution $resolution" . PHP_EOL;
        $params = array( 
            "Resolution"        => $resolution,
        );
        return self::post_request('AvailableStreamingFramerateSettings', $params);
    }
    
    function get_available_streaming_bitrate($type, $resolution, $framerate) {
        if(!in_array($type, self::$streaming_types)) {
            echo "Wrong type $type given. Must be in " . print_r(self::$streaming_types). PHP_EOL;
            return false;
        }
        if(!in_array($resolution, self::$streaming_resolutions)) {
            echo "Wrong resolution $resolution given. Must be in " . print_r(self::$streaming_resolutions). PHP_EOL;
            return false;
        }
        if(!in_array($framerate, self::$streaming_framerates)) {
            echo "Wrong framerate $framerate given. Must be in " . var_dump(self::$streaming_framerates). PHP_EOL;
            return false;
        }
        echo "I'm fetching available streaming bitrate for type $type, resolution $resolution and framerate $framerate" . PHP_EOL;
        $params = array( 
            "Type"              => $type,
            "Resolution"        => $resolution,
            "Framerate"         => $framerate
        );
        return self::post_request('AvailableStreamingBitrateSettings', $params);
    }
    
    //see JOYSTICK_PAN_DIRECTION and JOYSTICK_TILT_DIRECTION
    function joystick_operation($pan_direction, $pan_seed /*= 0*/, $tilt_direction /*= JOYSTICK_TILT_DIRECTION::STOP*/, $titl_speed/* = 0*/) {
        //TODO: arg validation
        
        echo "Joystick operation with pan direction $pan_direction, speed $pan_seed, tilt direction $tilt_direction, speed $titl_speed" . PHP_EOL;
        $params = array( 
            "PanDirection"     => $pan_direction,
            "PanSpeed"         => $pan_seed,
            "TiltDirection"    => $tilt_direction,
            "TiltSpeed"        => $titl_speed,
        );
        return self::post_request("JoyStickOperation", $params);
    }
    
    function ptz_preset_move_to($preset_index) {
        return self::ptz_preset($preset_index, 'Move');
    }
    
    function ptz_preset_set($preset_index) {
        return self::ptz_preset($preset_index, 'Set');
    }
    
    function ptz_preset_delete($preset_index) {
        return self::ptz_preset($preset_index, 'Delete');
    }
    
    private function ptz_preset($preset_index, $operation) {
        if($preset_index > self::max_preset_index || $preset_index < self::min_preset_index) {
            echo "Wrong preset index $preset_index given" . PHP_EOL;
            return false;
        }
        echo "Setting preset $preset_index" . PHP_EOL;
        $params = array( 
            "No"                => $preset_index,
            "Operation"         => $operation,
        );
        return self::post_request("SetPTZPreset", $params);
    }
    
    function get_headers_from_curl_response($response)
    {
        $headers = array();

        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0)
                $headers['http_code'] = $line;
            else
            {
                list ($key, $value) = explode(': ', $line);

                $headers[$key] = $value;
            }

        return $headers;
    }
    
    function post_request($command, $params = array(), $print = true) {
        $post_data = array(
            'Request' => array(
                'Command'   => $command,
                'SessionID' => $this->cookie,
                'Params'    => $params,
            )
        );
          
        curl_setopt($this->_ch, CURLOPT_URL, $this->api_url);
        curl_setopt($this->_ch, CURLOPT_POST,  1);
        curl_setopt($this->_ch, CURLOPT_HEADER, 0);
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        $out = curl_exec($this->_ch);
        if($this->debug) {
            echo "Received headers: " . PHP_EOL;
            print_r(curl_getinfo($this->_ch, CURLINFO_HEADER_OUT));
            echo PHP_EOL;
        }
        if($print) {
             echo "Decoded response" . PHP_EOL;
            $response = json_decode($out);
            print_r($response);
        }
        return $out;
    }
}