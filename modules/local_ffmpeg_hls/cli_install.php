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

require_once 'etc/config_sample.inc';

echo PHP_EOL . 
     "*******************************************" . PHP_EOL;
echo "* Installation of local_ffmpeg_hls module *" . PHP_EOL;
echo "*******************************************" . PHP_EOL;

echo "Creating etc/config.inc" . PHP_EOL;

$config = file_get_contents("$ffmpeg_basedir/etc/config_sample.inc");

preg_replace('/\$ffmpegpath = (.+);/', '\$ffmpegpath = "' . $ffmpegpath . '";', $config);

file_put_contents("$ffmpeg_basedir/etc/config.inc", $config);

if (!is_dir($ffmpeg_moviesdir)) {
    mkdir($ffmpeg_moviesdir, 0755, true);
    chown($ffmpeg_moviesdir, $ezrecorder_username);
}

$perms_file = file_get_contents("$ffmpeg_basedir/setperms_sample.sh");
$perms_file = str_replace("!USER", $ezrecorder_username, $perms_file);
$perms_file = str_replace("!WEB_USER", $ezrecorder_web_user, $perms_file);
file_put_contents("$ffmpeg_basedir/setperms.sh", $perms_file);

system("chmod -R 755 $ffmpeg_basedir/bash");
chmod("$ffmpeg_basedir/setperms.sh", 0755);
echo "Enter sudo password for executing setperms.sh .";
system("sudo $ffmpeg_basedir/setperms.sh");

function read_line($prompt = '') {
    echo $prompt . PHP_EOL;
    return rtrim(fgets(STDIN), "\n");
}

//todo check ffmpeg compilation flags
// --with-fdk-aac 
?>

