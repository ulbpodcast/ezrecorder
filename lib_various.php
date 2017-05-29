<?php

require_once "global_config.inc";
require_once "lib_install.php";

/**
 * Resizes an image and adds a status on it
 * @param type $input source image
 * @param type $output destination file
 * @param type $maxwidth 
 * @param type $maxheight
 * @param type $status could be a path to a file containing the status if status_file is set to true;
 * the status [recording / pending / error / ...] otherwise
 * @param type $status_file $status expects a path to a file if true; a status if false
 */
function image_resize($input, $output, $maxwidth, $maxheight, $status, $status_file = true) {

    $img_path = array();
    $img_path['broadcasting'] = __DIR__ . '/img/broadcasting.png';
    $img_path['connection'] = __DIR__ . '/img/connection.png';
    $img_path['error'] = __DIR__ . '/img/error.png';
    $img_path['pending'] = __DIR__ . '/img/pending.png';

    $img = imagecreatefromjpeg($input);
//or imagecreatefrompng,imagecreatefromgif,etc. depending on user's uploaded file extension

    $width = imagesx($img); //get width and height of original image
    $height = imagesy($img);

//determine which side is the longest to use in calculating length of the shorter side, since the longest will be the max size for whichever side is longest.    
    if ($height > $width) {
        $ratio = $maxheight / $height;
        $newheight = $maxheight;
        $newwidth = $width * $ratio;
        $writex = round(($maxwidth - $newwidth) / 2);
        $writey = 0;
    } else {
        $ratio = $maxwidth / $width;
        $newwidth = $maxwidth;
        $newheight = $height * $ratio;
        $writex = 0;
        $writey = round(($maxheight - $newheight) / 2);
    }

    $newimg = imagecreatetruecolor($maxwidth, $maxheight);

//Since you probably will want to set a color for the letter box do this
//Assign a color for the letterbox to the new image, 
//since this is the first call, for imagecolorallocate, it will set the background color
//in this case, black rgb(0,0,0)
    imagecolorallocate($newimg, 0, 0, 0);

    $palsize = ImageColorsTotal($img);  //Get palette size for original image
    for ($i = 0; $i < $palsize; $i++) { //Assign color palette to new image
        $colors = ImageColorsForIndex($img, $i);
        ImageColorAllocate($newimg, $colors['red'], $colors['green'], $colors['blue']);
    }

    imagecopyresized($newimg, $img, $writex, $writey, 0, 0, $newwidth, $newheight, $width, $height);

    if ($status_file) {
        if (file_exists($status)) {
            $module_status = file_get_contents($status_file);
        }
    } else {
        $module_status = $status;
    }

    $img_status = null;
    switch ($module_status) {
        case "recording":
            $img_status = imagecreatefrompng($img_path['broadcasting']);
            break;
        case "connection problem":
            $img_status = imagecreatefrompng($img_path['connection']);
            break;
        case "stopped":
            $img_status = imagecreatefrompng($img_path['error']);
            break;
        case "pending":
            $img_status = imagecreatefrompng($img_path['pending']);
            break;
        default: 
            trigger_error("Invalid status '$module_status' to ".__FUNCTION__, E_USER_WARNING);
        case "open":
        case "paused":
            break;
    }
    if($img_status != null)
        imagecopymerge($newimg, $img_status, 5, 130, 0, 0, 225, 25, 75);

    imagejpeg($newimg, $output); //$output file is the path/filename where you wish to save the file.  
//Have to figure that one out yourself using whatever rules you want.  Can use imagegif() or imagepng() or whatever.
    return true;
}

/**
 *
 * @param <type> $assoc_array
 * @return <xml_string>
 * @desc takes an assoc array and transform it in a xml metadata file
 */
function xml_assoc_array2file($assoc_array, $metadata_file) {
    global $logger;
    
    $xml_txt = xml_assoc_array2metadata($assoc_array);
    $result = file_put_contents($metadata_file, $xml_txt);
    if($result == false) {
        $logger->log(EventType::TEST, LogLevel::ERROR, "Couldn't write metadata file $metadata_file: $xml_txt", array(__FUNCTION__));
        return false;
    }

    chmod($metadata_file, 0644);
    return true;
}

/**
 *
 * @param <type> $assoc_array
 * @return <xml_string>
 * @desc takes an assoc array and transform it in a xml metadata string
 */
function xml_assoc_array2metadata($assoc_array) {
    $xmlstr = "<?xml version='1.0' standalone='yes'?>\n<metadata>\n</metadata>\n";
    $xml = new SimpleXMLElement($xmlstr);
    foreach ($assoc_array as $key => $value) {
        $xml->addChild($key,  str_replace('&','&amp;',$value));
    }
    $xml_txt = $xml->asXML();
    return $xml_txt;
}


/**
 * Open an xml file and return its content as an assoc array
 * @global type $debug_mode
 * @param type $meta_path
 * @return boolean
 */
function xml_file2assoc_array($meta_path) {
    $xml = simplexml_load_file($meta_path);
    if ($xml === false)
        return false;
    
    $assoc_array = array();
    foreach ($xml as $key => $value) {
        $assoc_array[$key] = (string) $value;
    }
    return $assoc_array;
}

// sends an associative array to a server via CURL
// return true on success, error http code on failure
function server_request_send($server_url, $post_array) {
    global $logger;
    global $basedir;

    $ch = curl_init($server_url);
    curl_setopt($ch, CURLOPT_POST, 1); //activate POST parameters
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_array);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //don't send answer to stdout but in returned string
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,30); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 6000); //timeout in seconds
    $res = curl_exec($ch);

    $curlinfo = curl_getinfo($ch);
    curl_close($ch);
    file_put_contents("$basedir/var/curl.log", var_export($curlinfo, true) . PHP_EOL . $res, FILE_APPEND);
    if ($res === false) {//error
        $http_code = isset($curlinfo['http_code']) ? $curlinfo['http_code'] : false;
        $logger->log(EventType::RECORDER_REQUEST_TO_MANAGER, LogLevel::ERROR, "Curl failed to POST data to $server_url. Http code: $http_code", array(__FUNCTION__));

        return "Curl error. Http code: $http_code";
    }
    
    //$logger->log(EventType::RECORDER_REQUEST_TO_MANAGER, LogLevel::DEBUG, "server_request_send $server_url, result= $res", array(__FUNCTION__));

    //All went well send http response in stderr to be logged
    //fputs(STDERR, "curl result: $res", 2000);

    return $res;
}

// determines if a process is running or not
function is_process_running($pid) {
    if (!isset($pid) || $pid == '' || $pid == 0)
        return false;
    exec("ps $pid", $output, $result);
    return count($output) >= 2;
}

function get_pid_from_file($filePath) {
    if(!file_exists($filePath))
        return false;
    
    $handle = fopen($filePath, "r");
    if($handle == false)
        return false;
    
    $pid = fgets($handle);
    fclose($handle);
    return $pid;
}

function debug_to_console($data) {
    if(is_array($data) || is_object($data)) {
            echo("<script>console.log('PHP: ".json_encode($data)."');</script>");
    } else {
            echo("<script>console.log('PHP: ".$data."');</script>");
    }
}

function get_asset_name($course_name, $record_date) {
    return $record_date . '_' . $course_name;
}

/* step == "upload" or "local_processing" or "upload_ok" or "" 
    Empty step will return first found, or local_processing dir if folder was not found
 * This function does not check folder existence
 *  */
function get_asset_dir($asset, $step = '') {
    if(!$asset)
        return false;
    
    switch ($step) {
        case "upload_ok":
            return get_upload_ok_dir($asset);
        case "upload":
        case "upload_to_server":
            return get_upload_to_server_dir($asset);
        case "local_processing":
            return get_local_processing_dir($asset);
        case '':
            $dir = get_upload_to_server_dir($asset);
            if(!file_exists($dir))
                $dir = get_upload_ok_dir($asset);
            if(!file_exists($dir))
                $dir = get_local_processing_dir($asset);
            
            return $dir;
        default:
            return false;
    }
}

//get module working folder for asset. Folder can be both in local_processing and upload_to_server
function get_asset_module_folder($module, $asset, $step = '') {
    $asset_dir = get_asset_dir($asset, $step);
    if($asset_dir == false)
        return false;
    
    return "$asset_dir/$module/";
}

function get_local_processing_dir($asset = '') {
    global $ezrecorder_recorddir;

    return $ezrecorder_recorddir . '/local_processing/' . $asset . '/';
}

function get_upload_to_server_dir($asset = '') {
    global $ezrecorder_recorddir;

    return $ezrecorder_recorddir . '/upload_to_server/' . $asset . '/';
}

function get_upload_ok_dir($asset = '') {
    global $ezrecorder_recorddir;

    return $ezrecorder_recorddir . '/upload_ok/' . $asset . '/';
}

function create_working_dir($dir) {
    global $logger;
    
    $ok = true;
    if(!file_exists($dir)) {
        $ok = mkdir($dir, 0777, true); //mode is not set ??
        if($ok)
            chmod($dir, 0777);
        else
            $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::WARNING, "Failed to create dir $dir", array(__FUNCTION__));
    }
    return $ok;
}

function create_module_working_folders($module_name, $asset) {
    global $logger;
    
    $dir = get_asset_module_folder($module_name, $asset, 'local_processing');
    
    $ok = create_working_dir($dir);
    
    if(!$ok) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Error while creating ffmpeg working folders (probably permissions). Main folder: $dir", array(__FUNCTION__), $asset);
    }
    return $ok;
}


// Various function to handle record types as integer or strings
class RecordType {
    const CAM      = 0x1;
    const SLIDE    = 0x2;
        
    /**
     * Compose record type string for given cam/slide int options
     * @param integer $type_int
     * @return <slide|cam|camslide> or false
     */
    static function to_string($type_int) {
        if($type_int & self::CAM && $type_int & self::SLIDE)
            return "camslide";
        else if ($type_int & self::CAM)
            return "cam";
        else if ($type_int & self::SLIDE)
            return "slide";
        else
            return false;
    }
    
    /**
     * Compose record type string for given cam/slide bool options
     * Dunno how to name this function, feel free to change
     * @param boolean $cam
     * @param boolean $slide
     * @return <slide|cam|camslide> or false
     */
    static function to_string_for_options($cam, $slide) {
        if ($cam && $slide)
            return "camslide";
        elseif ($cam)
            return "cam";
        elseif ($slide)
            return "slide";
        else
            return false;
    }

    /** Get Record type as an integer for given cam/slide options
     * 
     * @param type $cam
     * @param type $slide
     * @return integer type 
     */
    static function to_int_for_options($cam, $slide) {
        $type = 0;
        if($cam)
            $type |= self::CAM;
        if($slide)
            $type |= self::SLIDE;

        return $type;
    }
    
    /**
     * Return record type int from string
     * @param string $camslide
     * @return integer
     */
    static function to_int_from_string($camslide) {
        $type = 0;
        if(strpos($camslide,"cam")!==false)
            $type |= self::CAM;
        if(strpos($camslide,"slide")!==false)
            $type |= self::SLIDE;

        return $type;
    }
}

/* Return allowed RecordType to be used on this recorder 
   @return allowed types as an integer
 *  */
function get_allowed_record_type() {
    global $cam_enabled;
    global $slide_enabled;
    
    $allowed = 0;
    if($cam_enabled)
        $allowed |= RecordType::CAM;
    if($slide_enabled)
        $allowed |= RecordType::SLIDE;
        
    return $allowed;
}

/**
 * Return allowed record type for given type, or false if not any
 * Example: Giving "camslide" to this function will return "cam" if slide is disabled.
 * 
 * @param string $record_type_str <slide|cam|camslide>
 * @param string $allowed_types types as an integer (RecordType) <1|2|3>
 * @return <slide|cam|camslide> or false
 */
function validate_allowed_record_type($record_type_str, $allowed_types) {
    global $logger;
    
    //convert to int record type for operations, then back to string
    $record_type_int = RecordType::to_int_from_string($record_type_str);
    //get all allowed types from given record type
    $ok_types_int = $record_type_int & $allowed_types;
    $ok_type_str = RecordType::to_string($ok_types_int);
     
    /*
    $logger->log(EventType::TEST, LogLevel::DEBUG, "record_type_str: $record_type_str", array('controller'));
    $logger->log(EventType::TEST, LogLevel::DEBUG, "allowed_int: $allowed_int", array('controller'));
    $logger->log(EventType::TEST, LogLevel::DEBUG, "ok_types_int: $ok_types_int", array('controller'));
    $logger->log(EventType::TEST, LogLevel::DEBUG, "ok_type_str: $ok_type_str", array('controller'));
    */
    
    if($ok_type_str == false) {
        $logger->log(EventType::TEST, LogLevel::ERROR, "No valid given record type in $record_type_str ($record_type_int). Only allowed types are: $allowed_types", array(__FUNCTION__));
        return false;
    }
    
    //user asked for camslide but only part only one of those was valid
    if($record_type_str != $ok_type_str) {
        $logger->log(EventType::TEST, LogLevel::ERROR, "Only part of given record type $record_type_str ($record_type_int) was valid. Only allowed types are: $allowed_types", array(__FUNCTION__));
    }
    
    return $ok_type_str;
}

function get_asset_from_dir($dir) {
    $basename = basename($dir);
    $asset = substr($basename, 0, 16); //example of dir name : 2016_10_10_16h42_PODC-I-00...
    return $asset;
}

/* Valid targets are local_processing", "upload_to_server", "upload_ok"
 * 
 */
function move_asset($asset, $target, $move_on_remote = false) {
    global $logger;
    global $remote_recorder_ip;
    
    $valid_targets = array("local_processing", "upload_to_server", "upload_ok");
    if(!in_array($target, $valid_targets)) {
        $logger->log(EventType::TEST, LogLevel::ERROR, 'Invalid target folder give', array(__FUNCTION__), $asset);
        return false;
    }

    $current_dir = get_asset_dir($asset);
    if(!file_exists($current_dir)) {
        $logger->log(EventType::TEST, LogLevel::ERROR, "Could not find asset dir for asset $asset", array(__FUNCTION__), $asset);
        return false;
    }

    $target_dir = get_asset_dir($asset, $target);
    if($current_dir == $target_dir) {
        $logger->log(EventType::TEST, LogLevel::DEBUG, "Asset is already in target directory $target, nothing to do", array(__FUNCTION__), $asset);
        return true;
    }

    $ok = rename($current_dir, $target_dir);
    if(!$ok) {
        $logger->log(EventType::TEST, LogLevel::CRITICAL, "Could not move asset folder from $current_dir to $target_dir dir", array(__FUNCTION__), $asset);
        return false;
    }

    $logger->log(EventType::TEST, LogLevel::INFO, "Local asset moved from $current_dir to $target_dir dir", array(__FUNCTION__), $asset);
    
    if($remote_recorder_ip && $move_on_remote) { //move only if remote exists
        return move_remote_asset($asset, $target);
    } else {
        return true;
    }
}

function move_remote_asset($asset, $target) {
    global $ezrecorder_username;
    global $remote_recorder_ip;
    global $remote_recorder_username;
    global $move_asset_script;
    global $logger;
    
    $remote_cmd = "php $move_asset_script $asset $target";
    $local_cmd = "sudo -u $ezrecorder_username ssh -o ConnectTimeout=5 -o BatchMode=yes $remote_recorder_username@$remote_recorder_ip \"$remote_cmd\" 2>&1 > /dev/null"; //we don't want any local printing
    $return_val = 0;
    system($local_cmd, $return_val);
    if($return_val != 0) {
        $logger->log(EventType::TEST, LogLevel::ERROR, "Failed to move remote asset to target $target. Cmd: $local_cmd. Return val: $return_val", array(__FUNCTION__), $asset);
        return false;
    }
    $logger->log(EventType::TEST, LogLevel::INFO, "Remote asset moved from to $target folder", array(__FUNCTION__), $asset);
    return true;
}
