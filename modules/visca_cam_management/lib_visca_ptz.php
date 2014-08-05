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


//goto position if camera doesn like the ptz_set_absolute_position
function emulate_set_pantilt_absolute_position($fp, $command) {
    $debug = false;
    list($cmd, $pan_speed, $tilt_speed, $goto_pan, $goto_tilt) = explode(" ", $command);
    if ($debug)
        print "emulating $cmd $pan_speed $tilt_speed $goto_pan $goto_tilt<br>";
    $speed_dec = 6;
    //$pan_speed=15;
    //$tilt_speed=15;
    $pan_resol = 50;
    $tilt_resol = 50;
    $direction = "";
    $pan_move = "x";
    $tilt_move = "x";
    $nbdirchange = 0;
    while ($nbdirchange < 40) {
        //check position
        //openSocket($fp);
        sendCommand($fp, "get_pantilt_position", $answer, $pan, $tilt, $ret3);
        //closeSocket($fp);
//  if($debug)print "get_pantilt_position pan: $pan tilt: $tilt<br>\n";
        $pan_dist = abs($goto_pan - $pan);
        if ($pan_dist < $pan_resol) {
            //we're there in pan
            $pan_move = "";
            $old_pan_move = "";
        } else {
            //we're still not near enough
            $old_pan_move = $pan_move;
            if ($goto_pan - $pan > 0)
                $pan_move = "right";
            if ($goto_pan - $pan < 0)
                $pan_move = "left";
            if ($old_pan_move != $pan_move && $old_pan_move != "x") {
                //we went too far so go back with a slower speed
                $pan_speed-=$speed_dec;
                $pan_speed = 1;
                if ($pan_speed < 1)
                    $pan_speed = 1;
                if ($debug)
                    print "Pan Speed:$pan_speed <br>\n";
            }
        }
        $tilt_dist = abs($goto_tilt - $tilt);
        if ($tilt_dist < $tilt_resol) {
            //we're there in tilt
            $tilt_move = "";
            $old_tilt_move = "";
        } else {
            $old_tilt_move = $tilt_move;
            if ($goto_tilt - $tilt > 0)
                $tilt_move = "up";
            if ($goto_tilt - $tilt < 0)
                $tilt_move = "down";
            if ($old_tilt_move != $tilt_move && $old_tilt_move != "x") {
                //we went too far to go back with at a slower speed
                $tilt_speed-=$speed_dec;
                $tilt_speed = 1;
                if ($tilt_speed < 1)
                    $tilt_speed = 1;
                if ($debug)
                    print "Tilt Speed:$tilt_speed <br>\n";
            }
        }
        $old_direction = $direction;
        $direction = $tilt_move . $pan_move;
        //print "direction:$direction<br>\n";
        if ($direction == "") {
            $ptz_cmd = "set_pantilt_stop " . $pan_speed . " " . $tilt_speed;
            //openSocket($fp);
            sendCommand($fp, $ptz_cmd, $answer, $ret1, $ret2, $ret3);
            //closeSocket($fp);
            if ($debug)
                print "destination reached<br>\n";
            return true;
        }
        if ($direction != $old_direction) {

            $nbdirchange+=1;
            //first stop pantilt
            //$ptz_cmd="set_pantilt_stop ".$pan_speed." ".$tilt_speed;
            //  sendCommand($fp, $ptz_cmd, $answer, $ret1, $ret2, $ret3);
            //the continue in other direction
            $ptz_cmd = "set_pantilt_" . $tilt_move . $pan_move . " " . $pan_speed . " " . $tilt_speed;
            if ($debug)
                print "direction change:$direction command:$ptz_cmd<br>\n";
            //openSocket($fp);
            sendCommand($fp, $ptz_cmd, $answer, $ret1, $ret2, $ret3);
            //closeSocket($fp);
        }
    }//end while
    if ($debug)
        print "out of while runaway command:$ptz_cmd<br>\n";
    $ptz_cmd = "set_pantilt_stop " . $pan_speed . " " . $tilt_speed;
    sendCommand($fp, $ptz_cmd, $answer, $ret1, $ret2, $ret3);
    return false;
}

?>