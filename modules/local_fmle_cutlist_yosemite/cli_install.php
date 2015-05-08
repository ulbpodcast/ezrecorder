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

require_once 'config_sample.inc';

echo PHP_EOL . "***************************************" . PHP_EOL;
echo "* Installation of local_fmle_cutlist module    *" . PHP_EOL;
echo "***************************************" . PHP_EOL;
echo PHP_EOL . "Verification of FFMPEG" . PHP_EOL;
$success = false;
do {
    $value = read_line("Enter the path to ffmpeg binary [default: $ffmpegpath]: ");
    if ($value == "")
        $value = $ffmpegpath;
    $ret = system("$value -version | grep 'ffmpeg'");
    if (strpos($ret, "ffmpeg") === 0) {
        echo "FFMPEG has been found and seems ready to work";
        $ffmpegpath = $value;
        $success = true;
    } else {
        $value = read_line("Press [enter] to retry | enter [continue] to continue anyway or [quit] to leave: ");
        if ($value == 'continue')
            break;
        else if ($value == 'quit')
            die;
    }
} while (!$success);

echo "Creating config.inc" . PHP_EOL;

$config = file_get_contents("$localfmle_basedir/config_sample.inc");

$config = preg_replace('/\$ffmpegpath = (.+);/', '\$ffmpegpath = "' . $ffmpegpath . '";', $config);
file_put_contents("$localfmle_basedir/config.inc", $config);

echo PHP_EOL . "Changing values in bash/localdefs" . PHP_EOL;

$bash_file = file_get_contents("$localfmle_basedir/bash/localdefs_sample");
$bash_file = str_replace("!PATH", $localfmle_basedir, $bash_file);
$bash_file = str_replace("!RECORD_PATH", $ezrecorder_recorddir, $bash_file);
$bash_file = str_replace("!CLASSROOM", $classroom, $bash_file);
$bash_file = str_replace("!MAIL_TO", $mailto_admins, $bash_file);
$bash_file = str_replace("!PHP_PATH", $php_cli_cmd, $bash_file);
file_put_contents("$localfmle_basedir/bash/localdefs", $bash_file);

$perms_file = file_get_contents("$localfmle_basedir/setperms_sample.sh");
$perms_file = str_replace("!USER", $ezrecorder_username, $perms_file);
$perms_file = str_replace("!WEB_USER", $ezrecorder_web_user, $perms_file);
file_put_contents("$localfmle_basedir/setperms.sh", $perms_file);

system("chmod -R 755 $localfmle_basedir/bash");
chmod("$localfmle_basedir/setperms.sh", 0755);
chmod("$localfmle_basedir/bin/CoreImageTool", 0755);
echo "Enter sudo password for executing setperms.sh .";
system("sudo $localfmle_basedir/setperms.sh");

function read_line($prompt = '') {
    echo $prompt . PHP_EOL;
    return rtrim(fgets(STDIN), "\n");
}
?>

