<?php
/*
 * EZCAST EZrecorder
 *
 * Copyright (C) 2014 Université libre de Bruxelles
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

require(__DIR__ . '/../../external_products/ponvif/lib/class.ponvif.php');
include_once "config.inc";
$ptzposdir = __DIR__ . "/ptzposdir";

/**
 * Create and inititialize a new ponvif helper and return it
 * @return \ponvif
 */
function create_ponvif_helper()
{ 
    global $logger;
    global $onvifcam_username;
    global $onvifcam_password;
    global $onvifcam_ip;
    
    $ponvif=new ponvif();
    $ponvif->setUsername($onvifcam_username);
    $ponvif->setPassword($onvifcam_password);
    $ponvif->setIPAddress($onvifcam_ip);
    
    $ponvif->initialize();
    
    return $ponvif;
}

function get_ponvif_profile_token($ponvif)
{
    $sources=$ponvif->getSources();
    if ($ponvif->isFault($sources)) {
        error_log ("Error getting sources");
        return;
    }
    
    $profileToken=$sources[0][0]['profiletoken'];
    $profileToken2=str_replace(" ","_", $profileToken); //spaces in profile name should be replaced by underscore (at least for our Axis P5534 )
    
    return $profileToken2;
}

/**
 * @implements
 * Returns an array of the preset positions
 * @global string $ptzposdir
 * @return array
 */
function cam_onvif_ptz_posnames_get() {
    global $ptzposdir;
    global $logger;
    
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
function cam_onvif_ptz_pos_save($name) {
    global $logger;
    
    error_log("NYI");
    die();
}

/**
 * @implements
 * Deletes the given position
 * @global string $ptzposdir
 * @param type $name
 */
function cam_onvif_ptz_pos_delete($name) {
    global $logger;
    
    error_log("NYI");
    die();
}

/**
 * @implements
 * moves the visca cam to a given preset position
 * @global string $ptzposdir path to the preset positions
 * @param type $name the preset position
 */
function cam_onvif_ptz_move($PresetName) {
    global $logger;
    
    try {
        $ponvif = create_ponvif_helper();
        $profileToken = get_ponvif_profile_token($ponvif);
    } catch (Exception $e)
    {
        $logger->error("cam_onvif_ptz_move: error while trying to get ponvif object: " . $e->getMessage(), array("module","onvif_cam_management"));
        return false;
    }
    
    $ponvif->ptz_GotoPreset($profileToken,$PresetName,0.1,0.1,0.2);
    return true;
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
