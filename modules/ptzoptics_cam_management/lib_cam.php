<?php

include_once "config.inc";
require_once __DIR__ .'/ptzoptics_cgi.php';

$PRESET_FILE = __DIR__ . '/var/presets';

/**
 * @implements
 * Returns an array of the preset positions
 * @return array
 */
function cam_ptzoptics_posnames_get() {
    $presets = cam_ptzoptics_get_presets();
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
function cam_ptzoptics_pos_save($name) {
    global $cam_ip;
    
    $last_id = cam_ptzoptics_get_last_used_preset_id();
    if($last_id >= PTZOptics_CGI_API::PRESET_MAX)
        return -1;
    
    $new_preset_id = $last_id + 1;
    $presets = cam_ptzoptics_get_presets();
    
    //override already existing key
    if($presets) {
        $existing_key = array_search($name, $presets);
        if($existing_key !== false)
            $new_preset_id = $existing_key; //in this case
    } else {
        $presets = array();
    }
    
    //save in camera
    $api = new PTZOptics_CGI_API($cam_ip);
    $api->preset_save($new_preset_id);
    
    //save in our local list
    $presets[$new_preset_id] = $name;
    cam_ptzoptics_set_presets($presets);
    
    return 0;
}

/**
 * @implements
 * Deletes the given position
 * @param type $name
 */
function cam_ptzoptics_pos_delete($name) {
    $presets = cam_ptzoptics_get_presets();
    if(!$presets)
        return false;
    $existing_key = array_search($name, $presets);
    if($existing_key === false)
        return false;
        
    unset($presets[$existing_key]);
    cam_ptzoptics_set_presets($presets);
    return true;
}

/**
 * @implements
 * moves the camera to a given preset position
 * @param type $name the preset position
 */
function cam_ptzoptics_move($name) {
    global $cam_ip;
    
    $presets = cam_ptzoptics_get_presets();
    if(!$presets)
        return false;
    
    $existing_key = array_search($name, $presets);
    if($existing_key === false)
        return false;
    
    $api = new PTZOptics_CGI_API($cam_ip);
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

function cam_ptzoptics_get_last_used_preset_id() {
    $presets = cam_ptzoptics_get_presets();
    if(!$presets)
        return 0;
    return max(array_keys($presets));
}

//return associative array with presets
function cam_ptzoptics_get_presets() {
    global $PRESET_FILE;
    
    if(file_exists($PRESET_FILE)) {
        $string_data = file_get_contents($PRESET_FILE);
        return unserialize($string_data);
    }
    return false;
}

function cam_ptzoptics_set_presets($presets = array()) {
    global $PRESET_FILE;
    
    $string_data = serialize($presets);
    file_put_contents($PRESET_FILE, $string_data);
}