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
echo "* Installation of remote_fmle_regular remote module    *" . PHP_EOL;
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

$config = file_get_contents(dirname(__FILE__) . "/config_sample.inc");

echo "Please enter now the requested values: " . PHP_EOL;
$value = read_line("Classroom where the remote recorder is installed [default: '$classroom']: ");
if ($value != "")
    $classroom = $value; unset($value);
$value = read_line("Path to this remote module on this Mac [default: '$remotefmle_basedir']: ");
if ($value != "")
    $remotefmle_basedir = $value; unset($value);
$value = read_line("Path to the local video repository on this Mac [default: '$remotefmle_recorddir']: ");
if ($value != "")
    $remotefmle_recorddir = $value; unset($value);
$value = read_line("URL to the main recorder [default '$ezrecorder_url']: ");
if ($value != "")
    $ezrecorder_url = $value; unset($value);
$value = read_line("Email address aimed to receive EZrecorder's alerts [default: '$mailto_admins']: ");
if ($value != "")
    $mailto_admins = $value; unset($value);
$value = read_line("Path to your PHP binary [default: '$php_path']: ");
if ($value != "")
    $php_path = $value; unset($value);

$config = preg_replace('/\$classroom = (.+);/', '\$classroom = "' . $classroom . '";', $config);
$config = preg_replace('/\$remotefmle_basedir = (.+);/', '\$remotefmle_basedir = "' . $remotefmle_basedir . '";', $config);
$config = preg_replace('/\$remotefmle_recorddir = (.+);/', '\$remotefmle_recorddir = "' . $remotefmle_recorddir . '";', $config);
$config = preg_replace('/\$ezrecorder_url = (.+);/', '\$ezrecorder_url = "' . $ezrecorder_url . '";', $config);
$config = preg_replace('/\$mailto_admins = (.+);/', '\$mailto_admins = "' . $mailto_admins . '";', $config);
$config = preg_replace('/\$ffmpegpath = (.+);/', '\$ffmpegpath = "' . $ffmpegpath . '";', $config);
$config = preg_replace('/\$php_path = (.+);/', '\$php_path = "' . $php_path . '";', $config);
file_put_contents("$remotefmle_basedir/config.inc", $config);

echo PHP_EOL . "Changing values in bash/localdefs" . PHP_EOL;

$bash_file = file_get_contents("$remotefmle_basedir/bash/localdefs_sample");
$bash_file = str_replace("!PATH", $remotefmle_basedir, $bash_file);
$bash_file = str_replace("!RECORD_PATH", $remotefmle_recorddir, $bash_file);
$bash_file = str_replace("!CLASSROOM", $classroom, $bash_file);
$bash_file = str_replace("!MAIL_TO", $mailto_admins, $bash_file);
$bash_file = str_replace("!PHP_PATH", $php_path, $bash_file);
file_put_contents("$remotefmle_basedir/bash/localdefs", $bash_file);

system("chmod -R 755 $remotefmle_basedir/bash");
chmod("$remotefmle_basedir/bin/CoreImageTool", 0755);

function read_line($prompt = '') {
    echo $prompt . PHP_EOL;
    return rtrim(fgets(STDIN), "\n");
}
?>

