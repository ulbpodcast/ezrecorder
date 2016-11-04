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

$emulate_set_pantilt=false;
require_once 'D70limits.php';
require_once 'lib_visca_ptz.php';
require_once 'config.inc';

function openSocket(&$fp) {
  $fp = fsockopen("localhost", 3376, $errno, $errstr);
  if (!$fp) {
    echo "$errstr ($errno)<br />\n";
    return false;
  } else {
    //get the welcome message
    stream_set_timeout($fp, 15);//timeout 15s
    $msg=fgets($fp, 128);
    if(strpos($msg,'VISCA')===false)
      return false;
     else
      return true;
  }
}

function closeSocket($fp) {
  fclose($fp);
}

function sendCommand($fp, $command, &$answer, &$ret1, &$ret2, &$ret3) {
    global $emulate_set_pantilt;
    global $classroom;
    global $mailto_admins;
    
 $debug=false;
  $answer = "";
  $ret1 = "";
  $ret2 = "";
  $ret3 = "";
//print "cmd:$command<br>";
  if($emulate_set_pantilt && substr($command,0,29)=="set_pantilt_absolute_position"){
      if($debug)print "calling emulate_set_pantilt_absolute_position(\"$command\") ";
      //goto position if camera doesn like the ptz_set_absolute_position
      $answer=emulate_set_pantilt_absolute_position($fp,$command);
  }
  else
  if ($fp) {
    fwrite($fp, $command."\n");
    $answer = fgets($fp, 128);
    if($answer === false) {
        // Timeout: send a mail to the admins if visca timed out more than 1h ago
            mail($mailto_admins, 'Camera timeout', 'Camera from classroom '.$classroom.' stopped responding!');
        //}
    }
    else if (strncmp($answer, "11", 2) == 0) {
      $ret1 = ereg_replace(".*:","",fgets($fp, 128));
    } else if (strncmp($answer, "12", 2) == 0) {
      $ret1 = ereg_replace(".*:","",fgets($fp, 128));
      $ret2 = ereg_replace(".*:","",fgets($fp, 128));
    } else if (strncmp($answer, "13", 2) == 0) {
      $ret1 = ereg_replace(".*:","",fgets($fp, 128));
      $ret2 = ereg_replace(".*:","",fgets($fp, 128));
      $ret3 = ereg_replace(".*:","",fgets($fp, 128));
    }
  }

  if($debug)print "cmd:$command answ:$answer ret1:$ret1 ret2:$ret2 ret3:$ret3<br>";
}

?>
