<?php


class PTZOptics_CGI_API 
{
    var $api_url = null;
    var $_ch = null;
    var $debug = false;
    const PRESET_MAX = 255; //Not 100% sure about this one
    
    function __construct($ip) 
    {
        $this->_ch = curl_init();
        
        $this->api_url = "http://$ip/cgi-bin/ptzctrl.cgi";
        
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_ch, CURLOPT_HEADER, 0);
        curl_setopt($this->_ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($this->_ch, CURLOPT_VERBOSE, 1);
    }
    
    function send_action($action, $params = array()) 
    {
        if($this->debug) {
            echo "Sending action $action with params:" . PHP_EOL;
            print_r($params);
        }
        
        $url = $this->api_url . '/?ptzcmd&'.$action;
        foreach($params as $param) {
            $url .= '&'.$param;
        }
        return self::get_request($url);
    }
    
    function move($direction, $panspeed = 5, $tiltspeed = 5) 
    {
        if($this->debug) {
            echo "Moving to $direction with speed $panspeed : $tiltspeed" . PHP_EOL;
        }
        
        return self::send_action($direction, array($panspeed, $tiltspeed));
    }
    
    function stop() 
    {
        return self::send_action('ptzstop'); 
    }
    
    function move_home() 
    {
        return self::send_action('home'); 
    }
    
    function preset_go_to($i) 
    {
        if($i < 0 || $i > self::PRESET_MAX)
            return false;
        
        $url = $this->api_url . '/?ptzcmd&poscall&'.$i;
        return self::get_request($url);
    }
    
    function preset_save($i) 
    { 
        if($i < 0 || $i > self::PRESET_MAX)
            return false;
        
        $url = $this->api_url . '/?ptzcmd&posset&'.$i;
        return self::get_request($url);
    }
    
    function zoom_in($speed) 
    {
        return self::send_action('zoomin', array($speed)); 
    }
    
    function zoom_out($speed) 
    {
        return self::send_action('zoomout', array($speed)); 
    }
       
    function focus_in($speed) 
    {
        return self::send_action('focusin', array($speed)); 
    }
    
    function focus_out($speed) 
    {
        return self::send_action('focusout', array($speed)); 
    }
    
    function get_request($url) 
    {
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        $out = curl_exec($this->_ch);
        if($this->debug) {
            echo "Received headers: " . PHP_EOL;
            print_r(curl_getinfo($this->_ch, CURLINFO_HEADER_OUT));
            echo PHP_EOL;
        }
        return $out;
    }
}

/*
 * Some examples/testing
 * 
$api = new PTZOptics_CGI_API("10.0.2.253");
$api->debug = false;
 * 
$api->move("down");
sleep(5);
$api->stop();
$api->move("up");
sleep(5);
$api->stop();
$api->move_home();
*/

/*
 //save dummy positions
$api->move("right");
sleep(3);
$api->move("up");
sleep(3);
$api->preset_save(1);
$api->move("down");
sleep(6);
$api->preset_save(2);
$api->move("left");
sleep(6);
$api->preset_save(3);
$api->move("up");
sleep(6);
$api->preset_save(4);
$api->stop();
sleep(3);
$api->move_home();

//cycle between positions 1-4
$i = 1;
while(true) {
    echo "Going to $i..." . PHP_EOL;
    $api->preset_go_to($i);
    sleep(1);
    $i = ($i == 4) ? 1 : ++$i;
}
 */