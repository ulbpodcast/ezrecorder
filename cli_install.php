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

/*
 * This file is part of the installation process
 */

if ($argc < 2) {
    echo "usage: " . $argv[0] . " <php_path> " .
    "\n <php_path> the path to the php binary";
    die;
}

if (file_exists("global_config.inc")) {
    require_once 'global_config.inc';
    echo PHP_EOL . "Would you like to change EZrecorder's configuration file ?" . PHP_EOL;
    $choice = read_line("change configuration [Y/n]: ");
} else {
    require_once 'global_config_sample.inc';
    $php_cli_cmd = $argv[1];
    $basedir = dirname(__FILE__);
    $choice = 'Y';
}

/*
 * First, we add user's configuration in global-config.inc
 */
if (strtoupper($choice) != 'N' && strtoupper($choice) != 'NO') {
    echo "Please enter now the requested values: " . PHP_EOL;
    $value = read_line("Classroom where the recorder is installed [default: '$classroom']: ");
    if ($value != "")
        $classroom = $value; unset($value);
    $value = read_line("Static IP address of the recorder (used by EZcast remote server) [default '$ezrecorder_ip']: ");
    if ($value != "")
        $ezrecorder_ip = $value; unset($value);
    $value = read_line("Recorder username (used to launch bash scripts) [default: '$ezrecorder_username']: ");
    if ($value != "")
        $ezrecorder_username = $value; unset($value);
    $value = read_line("Path to the local video storage [default: '$ezrecorder_recorddir']: ");
    if ($value != "")
        $ezrecorder_recorddir = $value; unset($value);
    $value = read_line("Path to EZrecorder's basedir [default: '$basedir']: ");
    if ($value != "")
        $basedir = $value; unset($value);
    $value = read_line("Path to the webspace [default: '$web_basedir']: ");
    if ($value != "")
        $web_basedir = $value; unset($value);
    $value = read_line("URL to EZmanager server: [default: '$ezcast_submit_url']: ");
    if ($value != "")
        $ezcast_submit_url = $value; unset($value);
    $value = read_line("URL to this recorder [default: '$ezrecorder_url']: ");
    if ($value != "")
        $ezrecorder_url = $value; unset($value);
    $value = read_line("Email address aimed to receive EZrecorder's alerts [default: '$mailto_admins']: ");
    if ($value != "")
        $mailto_admins = $value; unset($value);
    $value = read_line("PHP binary [default: '$php_cli_cmd']: ");
    if ($value != "")
        $php_cli_cmd = $value; unset($value);
    $value = read_line("Apache's username [typically _www or www-data]: ");
    if ($value != "")
        $ezrecorder_web_user = $value; unset($value);

    $config = file_get_contents($basedir . "/global_config_sample.inc");

    $config = preg_replace('/\$classroom = (.+);/', '\$classroom = "' . $classroom . '";', $config);
    $config = preg_replace('/\$ezrecorder_ip = (.+);/', '\$ezrecorder_ip = "' . $ezrecorder_ip . '";', $config);
    $config = preg_replace('/\$ezrecorder_username = (.+);/', '\$ezrecorder_username = "' . $ezrecorder_username . '";', $config);
    $config = preg_replace('/\$ezrecorder_recorddir = (.+);/', '\$ezrecorder_recorddir = "' . $ezrecorder_recorddir . '";', $config);
    $config = preg_replace('/\$basedir = (.+);/', '\$basedir = "' . $basedir . '/";', $config);
    $config = preg_replace('/\$web_basedir = (.+);/', '\$web_basedir = "' . $web_basedir . '";', $config);
    $config = preg_replace('/\$ezrecorder_web_user = (.+);/', '\$ezrecorder_web_user = "' . $ezrecorder_web_user . '";', $config);
    $config = preg_replace('/\$ezcast_submit_url = (.+);/', '\$ezcast_submit_url = "' . $ezcast_submit_url . '";', $config);
    $config = preg_replace('/\$ezrecorder_url = (.+);/', '\$ezrecorder_url = "' . $ezrecorder_url . '";', $config);
    $config = preg_replace('/\$mailto_admins = (.+);/', '\$mailto_admins = "' . $mailto_admins . '";', $config);
    $config = preg_replace('/\$php_cli_cmd = (.+);/', '\$php_cli_cmd = "' . $php_cli_cmd . '";', $config);
    file_put_contents($basedir . "/global_config.inc", $config);
}

echo PHP_EOL . "global-config.inc created with custom values." . PHP_EOL;
/*
 * Then, we adapt the paths in webspace according to the actual
 * position of EZrecorder
 */
echo "Modification of global values in ./htdocs/index.php" . PHP_EOL;

$web_file = file_get_contents($basedir . "/htdocs/index.php");
$web_file = str_replace("!PATH", $basedir, $web_file);
file_put_contents($basedir . "/htdocs/index.php", $web_file);

echo "Modification of global values in ./sbin/localdefs" . PHP_EOL;

$sbin_file = file_get_contents($basedir . "/sbin/localdefs");
$sbin_file = str_replace("!PATH", $basedir, $sbin_file);
$sbin_file = str_replace("!CLASSROOM", $classroom, $sbin_file);
$sbin_file = str_replace("!MAIL_TO", $mailto_admins, $sbin_file);
file_put_contents($basedir . "/sbin/localdefs", $sbin_file);

echo "Modification of global values in ./setperms.sh" . PHP_EOL;

$perms_file = file_get_contents("$basedir/setperms.sh");
$perms_file = str_replace("!USER", $ezrecorder_username, $perms_file);
$perms_file = str_replace("!WEB_USER", $ezrecorder_web_user, $perms_file);
file_put_contents("$basedir/setperms.sh", $perms_file);

/*
 * Then, we create working directories in RECORD_PATH
 */
echo "Creation of working directories in $ezrecorder_recorddir" . PHP_EOL;

system("mkdir -p $ezrecorder_recorddir/upload_ok");
system("mkdir -p $ezrecorder_recorddir/upload_to_server");
system("mkdir -p $ezrecorder_recorddir/trash");
system("mkdir -p $ezrecorder_recorddir/local_processing");
system("chown -R $ezrecorder_username $ezrecorder_recorddir");
system("chmod -R 755 $ezrecorder_recorddir");

/*
 * Finaly, we copy htdocs from BASEDIR to WEB_BASEDIR
 */
echo "Creation of web content in $web_basedir" . PHP_EOL;

system("mkdir -p $web_basedir");
system("cp -rp $basedir/htdocs/* $web_basedir/.");
system("chown -R $ezrecorder_username:$apache_user $web_basedir");
system("chown -R $ezrecorder_username:$apache_user $basedir");
system("chmod 755 $basedir/setperms.sh");

echo PHP_EOL."*******************************************************************".PHP_EOL;
echo "*         I N S T A L L A T I O N    O F    M O D U L E S         *".PHP_EOL;
echo "*******************************************************************".PHP_EOL;
echo PHP_EOL."You will be requested to mention the modules you want to enable at the end of this installation script." . PHP_EOL;
$modules = glob('modules/*/info.php');
foreach ($modules as $module) {
    require $module;
    echo "------------------------------------------------------------" . PHP_EOL;
    echo "Name: $module_title" . PHP_EOL;
    echo "Description: $module_description" . PHP_EOL;
    echo "------------------------------------------------------------" . PHP_EOL;

    $value = read_line("Would you like to install this module ? [Y/n] : ");
    if (strtoupper($value) != 'N' && strtoupper($value) != 'NO') {
        system("$php_cli_cmd $module_path/cli_install.php");
    }
}
echo PHP_EOL;

function read_line($prompt = '') {
    echo $prompt;
    return rtrim(fgets(STDIN), "\n");
}

?>
