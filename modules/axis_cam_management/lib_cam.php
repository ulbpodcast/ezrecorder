<?php
/*
 * EZCAST EZrecorder
 *
 * Copyright (C) 2016 Université libre de Bruxelles
 *
 * Written by Michel Jansens <mjansens@ulb.ac.be>
 * 	      Arnaud Wijns <awijns@ulb.ac.be>
 *            Antoine Dewilde
 * UI Design by Julien Di Pietrantonio
 *
 * This software is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this software; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

//include_once "lib_curl.php";
include_once "config.inc";
$ptzposdir = dirname(__FILE__) . "/ptzposdir";

/**
 * @implements
 * Returns an array of the preset positions
 * @global string $ptzposdir
 * @return array
 */
function cam_axis_ptz_posnames_get() {
    global $ptzposdir;
    $ptznames = array();
    if (is_dir($ptzposdir)) {
        if ($dh = opendir($ptzposdir)) {
            while (($file = readdir($dh) ) !== false) {
                if (substr($file, -4) == ".ptz") {
                    //its a ptz file so put it in the list
                    $name = str_replace(".ptz", "", $file);
                    array_push($ptznames, $name);
                }
            }
            closedir($dh);
        }
    }
    return $ptznames;
}

/**
 * @implements
 * Saves the current position of the camera
 * @global string $ptzposdir
 * @global type $imagesnap_cmd
 * @param type $name the name of the position
 * @return int
 */
function cam_axis_ptz_pos_save($name) {
    global $ptzposdir;
    global $axiscam_username;
    global $axiscam_password;
    global $axiscam_ip;
    
    //transform name into a safe filename
    $filename = str_toalnum($name);
    $ptzfilepath = $ptzposdir . "/" . $filename . ".ptz";

    // saves the current position in the ip camera
    $url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/com/ptzconfig.cgi?setserverpresetname=$name&home=no&camera=1";
    curl_read_url($url);
    // gets information on the current position
    $url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/com/ptz.cgi?query=position";
    $ptz = curl_read_url($url);

    if ($ptz != "") {
        //print "saving ptz $name to $ptzfilepath\n";
        $fp = fopen($ptzfilepath, "w");
        if ($fp) {
            fwrite($fp, $ptz);
            fclose($fp);
            //try to save a picture of the position        
            $url = "$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/jpg/image.cgi?resolution=320x180";
            curl_download_file($url, "$ptzposdir/$filename.jpg");
                print "$name saved with a picture\n";
        }
        else
            return -1;
    }
}

/**
 * @implements
 * Deletes the given position
 * @global string $ptzposdir
 * @param type $name
 */
function cam_axis_ptz_pos_delete($name) {
    global $ptzposdir;
    global $axiscam_username;
    global $axiscam_password;
    global $axiscam_ip;
    
    // removes the position saved in the camera
    $url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/com/ptzconfig.cgi?camera=1&removeserverpresetname=$name";
    curl_read_url($url);
    
    //remove coordinate file
    $filepath = $ptzposdir . "/" . $name . ".ptz";
    if (is_file($filepath))
        unlink($filepath);
    //remove image file
    $filepath = $ptzposdir . "/" . $name . ".jpg";
    if (is_file($filepath))
        unlink($filepath);
}

/**
 * @implements
 * moves the visca cam to a given preset position
 * @global string $ptzposdir path to the preset positions
 * @param type $name the preset position
 */
function cam_axis_ptz_move($name) {
    global $axiscam_username;
    global $axiscam_password;
    global $axiscam_ip;
    
    $name = str_toalnum($name);
    
    $url = "http://$axiscam_username:$axiscam_password@$axiscam_ip/axis-cgi/com/ptz.cgi?gotoserverpresetname=$name";
    curl_read_url($url);
    
}

function str_toalnum($string) {
    $toalnum = "";
    for ($idx = 0; $idx < strlen($string); $idx++)
        if (ctype_alnum($string[$idx]))
            $toalnum.=$string[$idx];
        else
            $toalnum.="_";
    return $toalnum;
}

?>
