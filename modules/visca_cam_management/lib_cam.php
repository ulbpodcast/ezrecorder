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


include_once "lib_visca.php";
include_once "config.inc";
$ptzposdir = __DIR__ . "/ptzposdir";

/**
 * @implements
 * Returns an array of the preset positions
 * @global string $ptzposdir
 * @return array
 */
function cam_visca_ptz_posnames_get() {
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
function cam_visca_ptz_pos_save($name) {
    global $ptzposdir, $imagesnap_cmd;
    global $visca_basedir;
    //transform name into a safe filename
    $filename = str_toalnum($name);
    $ptzfilepath = $ptzposdir . "/" . $filename . ".ptz";
//first get actual position

    openSocket($sock);
    sendCommand($sock, "get_zoom_value", $answer1, $zoom, $ret2, $ret3);
    //$ret1 zoom
    sendCommand($sock, "get_pantilt_position", $answer2, $pan, $tilt, $ret3);

    $zoom = trim($zoom);
    $pan = trim($pan);
    $tilt = trim($tilt);

    // list($pan,$tilt,$zoom)=array(1,2,3);
    if ($anwser1 != "OK" && $answer2 != "OK") {
        //print "saving ptz $name to $ptzfilepath\n";
        $fp = fopen($ptzfilepath, "w");
        if ($fp) {
            fwrite($fp, "$pan,$tilt,$zoom");
            fclose($fp);
            //try to save a picture of the position
            $picmtime = filemtime("./cam_snaps/pic.jpg");
            if (time() - $picmtime < 5) {
                //preview picture is recent to save it along the .ptz file
                copy("./cam_snaps/pic.jpg", "$ptzposdir/$filename.jpg");
                print "$name saved with a picture\n";
            } else {
                //old pic -> imagesnap not launch in repetitive move
                //try take one snap
                system("$imagesnap_cmd $visca_basedir/cam_snaps/pic.jpg");
//                system("$imagesnap_cmd /Library/WebServer/Documents/visca/cam_snaps/pic.jpg");
                $picmtime = filemtime("../cam_snaps/pic.jpg");
                if (time() - $picmtime < 2) {
                    //preview picture is recent to save it along the .ptz file
                    copy("../cam_snaps/pic.jpg", "$ptzposdir/$filename.jpg");
                    print "$name saved with a picture\n";
                } else {
                    print "$name saved without picture\n";
                }
                return 0;
            }
        }
        else
            return -1;
    }
    closeSocket($sock);
}

/**
 * @implements
 * Deletes the given position
 * @global string $ptzposdir
 * @param type $name
 */
function cam_visca_ptz_pos_delete($name) {
    global $ptzposdir;
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
function cam_visca_ptz_move($name) {
    global $ptzposdir;
    $name = str_toalnum($name);
    $string = file($ptzposdir . "/" . $name . ".ptz");
    list($pan, $tilt, $zoom) = explode(",", trim($string[0]));
//    print "pan:$pan tilt:$tilt zoom:$zoom\n";
    openSocket($sock);
    // $speedcalc = $speed - floor($ret1 / ($zoom_max/$speed));
    //$speedstring = " ".$speedcalc." ".$speedcalc;
    $pan_speed = 8;
    $tilt_speed = 8;
    $anser = sendCommand($sock, "set_zoom_value $zoom", $answer, $res1, $res2, $res3);
    $answer = sendCommand($sock, "set_pantilt_absolute_position $pan_speed $tilt_speed $pan $tilt", $res1, $res2, $res3, $res4);

    closeSocket($sock);
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
