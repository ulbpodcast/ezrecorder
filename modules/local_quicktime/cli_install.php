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

require_once 'config_sample.inc';

echo PHP_EOL . "***************************************" . PHP_EOL;
echo           "* Installation of local_qt module     *" . PHP_EOL;
echo           "***************************************" . PHP_EOL;

echo "Creating config.inc" . PHP_EOL; 
    
copy("$modules_basedir/local_quicktime/config_sample.inc", "$modules_basedir/local_quicktime/config.inc");

echo PHP_EOL . "Changing values in bash/localdefs" . PHP_EOL;

$bash_file = file_get_contents("$modules_basedir/local_quicktime/bash/localdefs_sample");
$bash_file = str_replace("!PATH", $basedir, $bash_file);
$bash_file = str_replace("!RECORD_PATH", $ezrecorder_recorddir, $bash_file);
$bash_file = str_replace("!CLASSROOM", $classroom, $bash_file);
$bash_file = str_replace("!MAIL_TO", $mailto_admins, $bash_file);
$bash_file = str_replace("!PHP_PATH", $php_cli_cmd, $bash_file);
file_put_contents("$modules_basedir/local_quicktime/bash/localdefs", $bash_file);

$perms_file = file_get_contents("$modules_basedir/local_quicktime/setperms_sample.sh");
$perms_file = str_replace("!USER", $ezrecorder_username, $perms_file);
$perms_file = str_replace("!WEB_USER", $ezrecorder_web_user, $perms_file);
file_put_contents("$modules_basedir/local_quicktime/setperms.sh", $perms_file);

system("chmod -R 755 $modules_basedir/local_quicktime/bash");
chmod("$modules_basedir/local_quicktime/setperms.sh", 0755);
echo "Enter sudo password for executing setperms.sh .";
system("sudo $modules_basedir/local_quicktime/setperms.sh");
?>

