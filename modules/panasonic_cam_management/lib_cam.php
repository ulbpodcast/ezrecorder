<?php

include_once "config.inc";
require_once __DIR__ .'/panasonic_cgi.php';
//if ($cam_enabled)



$PRESET_FILE = __DIR__ . '/var/presets';

/**
 * @implements
 * Returns an array of the preset positions
 * @return array
 */
function cam_panasonic_posnames_get() {
    $presets = cam_panasonic_get_presets();
    if($presets)
        return array_values($presets);
    else
        return array();
}

/**
 * @implements
 * Saves the current position of the camera
 * @param string $name the name of the position to save
 * @return int -1 if an error occured
 */
function cam_panasonic_pos_save($name) {
    global $cam_ip;
    global $cam_enabled;
    global $cam_module;
    global $ffmpeg_capture_file;
    global $ffmpeg_capture_tmp_file;
    global $ffmpeg_capture_transit_file;
    global $logger;

    global $web_basedir;
    
    
    $last_id = cam_panasonic_get_last_used_preset_id();
    if($last_id >= Panasonic_CGI_API::PRESET_MAX)
        return -1;
    
    $new_preset_id = $last_id + 1;
    $presets = cam_panasonic_get_presets();
    
    //override already existing key
    if($presets) {
        $existing_key = array_search($name, $presets);
        if($existing_key !== false)
            $new_preset_id = $existing_key; //in this case
    } else {
        $presets = array();
    }
    
    //save in camera (not yet implemented
    $api = new Panasonic_CGI_API($cam_ip);
    $api->preset_save($new_preset_id);
    
    


//save in our local list
    $presets[$new_preset_id] = $name;
    cam_panasonic_set_presets($presets);


    //try to install image too
//    if($cam_enabled) {
        
         require_once __DIR__ .'/../local_ffmpeg_hls/lib_capture.php';
//        $fct_capture_thumbnail = 'capture_' . $cam_module . '_thumbnail';
        $pic = capture_ffmpeg_thumbnail();
        $install_pic_ok = false;
        if($pic) {
            $ptz_pics_dir = "$web_basedir/ptzposdir/";
            $install_pic_ok = file_put_contents("$ptz_pics_dir/$name.jpg", $pic);
        }
//    }

    
    return 0;
}

/**
 * @implements
 * Deletes the given position
 * @param type $name
 */
function cam_panasonic_pos_delete($name) {
    $presets = cam_panasonic_get_presets();
    if(!$presets)
        return false;
    $existing_key = array_search($name, $presets);
    if($existing_key === false)
        return false;
        
    unset($presets[$existing_key]);
    cam_panasonic_set_presets($presets);
    return true;
}

/**
 * @implements
 * moves the camera to a given preset position
 * @param type $name the preset position
 */
function cam_panasonic_move($name) {
    global $cam_ip;
    
    $presets = cam_panasonic_get_presets();
    if(!$presets)
        return false;
    
    $existing_key = array_search($name, $presets);
    if($existing_key === false)
        return false;
    
    $api = new Panasonic_CGI_API($cam_ip);
    $api->preset_go_to($existing_key);
    return true;
}

//--- Internal function

/* 
Presets are represented in a associative array with structure :
 * $array = [
 *  id  => name
 *  id2 => name2
 *  ...
 * ]
 */

function cam_panasonic_get_last_used_preset_id() {
    $presets = cam_panasonic_get_presets();
    if(!$presets)
        return 0;
    return max(array_keys($presets));
}

//return associative array with presets
function cam_panasonic_get_presets() {
    global $PRESET_FILE;
    
    if(file_exists($PRESET_FILE)) {
        $string_data = file_get_contents($PRESET_FILE);
        return unserialize($string_data);
    }
    return false;
}

function cam_panasonic_set_presets($presets = array()) {
    global $PRESET_FILE;
    
    $string_data = serialize($presets);
    file_put_contents($PRESET_FILE, $string_data);
}


function test($cam_ip='10.10.12.3'){        
    //initialise class
    //$api = new Panasonic_CGI_API($cam_ip);

    // Go to preset number 2
    //$api->preset_go_to(2); //go to preset
    
    
    //move (param = pan & tilt)
    //$res=$api->move(2,2);     
  
//    Save ALL the presets in an array
//    $res=cam_panasonic_set_presets( array("1" => "PLANRAPPROCHE","2" => "PLAN LARGE"));
      
    
//    MOVE TO PRESET BY NAME
    $res=cam_panasonic_move('PLAN LARGE');
    
    
    
    return $res;
}