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

echo PHP_EOL . "**********************************************" . PHP_EOL;
echo "* Installation of onvif_cam_management module *" . PHP_EOL;
echo "**********************************************" . PHP_EOL;

echo PHP_EOL . "Creating config.inc" . PHP_EOL;

echo "Please, enter now the requested values :" . PHP_EOL;
$value = read_line("Static IP address for onvif Camera [default: '$onvifcam_ip']: ");
if ($value != "")
    $onvifcam_ip = $value; 
unset($value);
$value = read_line("Username for onvif camera [default: '$onvifcam_username']: ");
if ($value != "")
    $onvifcam_username = $value; 
unset($value);
$value = read_line("Password for onvif Camera [default: '$onvifcam_password']: ");
if ($value != "")
    $onvifcam_password = $value; 


$config = file_get_contents("$modules_basedir/onvif_cam_management/config_sample.inc");

$config = preg_replace('/\$onvifcam_ip = (.+);/', '\$onvifcam_ip = "' . $onvifcam_ip . '";', $config);
$config = preg_replace('/\$onvifcam_username = (.+);/', '\$onvifcam_username = "' . $onvifcam_username . '";', $config);
$config = preg_replace('/\$onvifcam_password = (.+);/', '\$onvifcam_password = "' . $onvifcam_password . '";', $config);
file_put_contents("$modules_basedir/onvif_cam_management/config.inc", $config);

system("mv $web_basedir/ptzposdir $web_basedir/ptzposdir_old");
system("cp -rp $modules_basedir/onvif_cam_management/ptzposdir $web_basedir/ptzposdir");

function read_line($prompt = '') {
    echo $prompt . PHP_EOL;
    return rtrim(fgets(STDIN), "\n");
}
?>

