<?php

require_once 'config_sample.inc';
require_once(__DIR__."/../../lib_install.php");

echo PHP_EOL . "***************************************" . PHP_EOL;
echo "* Installation of local_qtb module    *" . PHP_EOL;
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

$config = file_get_contents("$modules_basedir/local_qtb/config_sample.inc");

$config = preg_replace('/\$ffmpegpath = (.+);/', '\$ffmpegpath = "' . $ffmpegpath . '";', $config);
file_put_contents("$modules_basedir/local_qtb/config.inc", $config);

echo PHP_EOL . "Changing values in bash/localdefs" . PHP_EOL;

$bash_file = file_get_contents("$modules_basedir/local_qtb/bash/localdefs_sample");
$bash_file = str_replace("!PATH", $basedir, $bash_file);
$bash_file = str_replace("!RECORD_PATH", $ezrecorder_recorddir, $bash_file);
$bash_file = str_replace("!CLASSROOM", $classroom, $bash_file);
$bash_file = str_replace("!MAIL_TO", $mailto_admins, $bash_file);
$bash_file = str_replace("!PHP_PATH", $php_cli_cmd, $bash_file);
file_put_contents("$modules_basedir/local_qtb/bash/localdefs", $bash_file);

if (strtoupper(php_uname('s'))==='LINUX'){
	$perms_file = file_get_contents("$modules_basedir/local_qtb/setperms_samplinux.sh");
}else{
	$perms_file = file_get_contents("$modules_basedir/local_qtb/setperms_sample.sh");
}
$perms_file = str_replace("!USER", $ezrecorder_username, $perms_file);
$perms_file = str_replace("!WEB_USER", $ezrecorder_web_user, $perms_file);
file_put_contents("$modules_basedir/local_qtb/setperms.sh", $perms_file);

system("chmod -R 755 $modules_basedir/local_qtb/bash");
chmod("$modules_basedir/local_qtb/setperms.sh", 0755);
chmod("$modules_basedir/local_qtb/bin/CoreImageTool", 0755);
echo "Enter sudo password for executing setperms.sh .";
system("sudo $modules_basedir/local_qtb/setperms.sh");
