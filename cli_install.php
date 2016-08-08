<?php

/*
 * This file is part of the installation process. The first part is the install.sh file.
 */

if ($argc < 3) {
    echo "usage: " . $argv[0] . " <php_path> <ffmpeg_path>" .
    "\n <php_path> the path to the php binary";
    "\n <ffmpeg_path> the path to the ffmpeg binary";
    die;
}

if (file_exists("global_config.inc")) {
    require_once 'global_config.inc';
    echo PHP_EOL . "Would you like to setup EZrecorder's global configuration now? (global_config.inc)" . PHP_EOL;
    $choice = read_line("[Y/n]: ");
} else {
    require_once 'global_config_sample.inc';
    $php_cli_cmd = $argv[1];
    $ffmpeg_cli_cmd = $argv[2];
    $choice = 'Y';
}
$basedir = __DIR__;

/*
 * First, we add user's configuration in global-config.inc
 */
if (strtoupper($choice) != 'N') {
    echo "Please enter now the requested values: " . PHP_EOL;
    $value = read_line("Name of the classroom where the recorder is installed ['$classroom']: ");
    if ($value != "")
        $classroom = $value;

    $value = read_line("Static IP address of this recorder ['$ezrecorder_ip']: ");
    if ($value != "")
        $ezrecorder_ip = $value;

    if(!ping($ezrecorder_ip, 2000))
      echo "This IP does not seems to be valid. You should double-check it and fix it in the final config file if needed." . PHP_EOL;

    $value = read_line("Recorder username (used to launch bash scripts) ['$ezrecorder_username']: ");
    if ($value != "")
        $ezrecorder_username = $value;

    $value = read_line("Path to the local video storage ['$ezrecorder_recorddir']: ");
    if ($value != "")
        $ezrecorder_recorddir = $value;

    $value = read_line("Path to the webspace (where the static web files will be placed) ['$web_basedir']: ");
    if ($value != "")
        $web_basedir = $value;

    $value = read_line("URL to EZmanager server submit service ['$ezcast_submit_url']: ");
    if ($value != "")
        $ezcast_submit_url = $value;

    $value = read_line("EZrecorder's alerts destination mail address ['$mailto_admins']: ");
    if ($value != "")
        $mailto_admins = $value;

    $value = read_line("Apache's username [" .$ezrecorder_web_user. "]: ");
    if ($value != "")
        $ezrecorder_web_user = $value;

    $config = file_get_contents($basedir . "/global_config_sample.inc");

    $config = preg_replace('/\$classroom = (.+);/', '\$classroom = "' . $classroom . '";', $config);
    $config = preg_replace('/\$ezrecorder_ip = (.+);/', '\$ezrecorder_ip = "' . $ezrecorder_ip . '";', $config);
    $config = preg_replace('/\$ezrecorder_username = (.+);/', '\$ezrecorder_username = "' . $ezrecorder_username . '";', $config);
    $config = preg_replace('/\$ezrecorder_recorddir = (.+);/', '\$ezrecorder_recorddir = "' . $ezrecorder_recorddir . '";', $config);
    $config = preg_replace('/\$basedir = (.+);/', '\$basedir = "' . $basedir . '/";', $config);
    $config = preg_replace('/\$web_basedir = (.+);/', '\$web_basedir = "' . $web_basedir . '";', $config);
    $config = preg_replace('/\$ezrecorder_web_user = (.+);/', '\$ezrecorder_web_user = "' . $ezrecorder_web_user . '";', $config);
    $config = preg_replace('/\$ezcast_submit_url = (.+);/', '\$ezcast_submit_url = "' . $ezcast_submit_url . '";', $config);
    $config = preg_replace('/\$mailto_admins = (.+);/', '\$mailto_admins = "' . $mailto_admins . '";', $config);
    $config = preg_replace('/\$php_cli_cmd = (.+);/', '\$php_cli_cmd = "' . $php_cli_cmd . '";', $config);
    $config = preg_replace('/\$ffmpeg_cli_cmd = (.+);/', '\$ffmpeg_cli_cmd = "' . $ffmpeg_cli_cmd . '";', $config);
    file_put_contents($basedir . "/global_config.inc", $config);
}

echo PHP_EOL . $basedir . "/global_config.inc" . " was created with given values." . PHP_EOL;
/*
 * Then, we adapt some paths in configuration files
 */

echo "Modification of global values in ./sbin/localdefs" . PHP_EOL;

$sbin_file = file_get_contents($basedir . "/sbin/localdefs_sample");
$sbin_file = str_replace("!PATH", $basedir, $sbin_file);
$sbin_file = str_replace("!CLASSROOM", $classroom, $sbin_file);
$sbin_file = str_replace("!MAIL_TO", $mailto_admins, $sbin_file);
file_put_contents($basedir . "/sbin/localdefs", $sbin_file);

echo "Modification of global values in ./setperms.sh" . PHP_EOL;

$perms_file = file_get_contents("$basedir/setperms_sample.sh");
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
system("chown -R $ezrecorder_username:$ezrecorder_web_user $web_basedir");
system("chown -R $ezrecorder_username:$ezrecorder_web_user $basedir");
system("chmod 755 $basedir/setperms.sh");

echo "Modification of global values in $web_basedir/index.php" . PHP_EOL;

$web_file = file_get_contents($web_basedir . "/index.php");
$web_file = str_replace("!PATH", $basedir, $web_file);
file_put_contents($web_basedir . "/index.php", $web_file);

function read_line($prompt = '') {
    echo $prompt;
    return rtrim(fgets(STDIN), "\n");
}

//Try to connect on port 80. return 0 on failure, 1 on success
function ping($host, $timeout) {
  if(fSockOpen($host, 80, $errno, $errstr, $timeout))
    return 1;

  return 0;
}

?>
