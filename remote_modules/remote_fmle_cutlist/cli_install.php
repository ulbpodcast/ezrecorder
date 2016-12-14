<?php

require_once 'config_sample.inc';
require_once(__DIR__.'/../../global_config.inc');
require_once(__DIR__."/../../lib_install.php");

echo PHP_EOL . "***************************************" . PHP_EOL;
echo "* Installation of remote_fmle_cutlist remote module    *" . PHP_EOL;
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
$value = read_line("Path to this remote module on this Mac [default: '$remotefmle_basedir']: ");
if ($value != "")
    $remotefmle_basedir = $value;
    
$value = read_line("Path to the local video repository on this Mac [default: '$remotefmle_recorddir']: ");
if ($value != "")
    $remotefmle_recorddir = $value;
    
$config = preg_replace('/\$remotefmle_basedir = (.+);/', '\$remotefmle_basedir = "' . $remotefmle_basedir . '";', $config);
$config = preg_replace('/\$remotefmle_recorddir = (.+);/', '\$remotefmle_recorddir = "' . $remotefmle_recorddir . '";', $config);
$config = preg_replace('/\$ffmpegpath = (.+);/', '\$ffmpegpath = "' . $ffmpegpath . '";', $config);
file_put_contents("$remotefmle_basedir/config.inc", $config);

echo PHP_EOL . "Changing values in bash/localdefs" . PHP_EOL;

$bash_file = file_get_contents("$remotefmle_basedir/bash/localdefs_sample");
$bash_file = str_replace("!PATH", $remotefmle_basedir, $bash_file);
$bash_file = str_replace("!RECORD_PATH", $remotefmle_recorddir, $bash_file);
$bash_file = str_replace("!CLASSROOM", $classroom, $bash_file);
$bash_file = str_replace("!MAIL_TO", $mailto_admins, $bash_file);
$bash_file = str_replace("!PHP_PATH", $php_cli_cmd, $bash_file);
file_put_contents("$remotefmle_basedir/bash/localdefs", $bash_file);

system("chmod -R 755 $remotefmle_basedir/bash");
chmod("$remotefmle_basedir/bin/CoreImageTool", 0755);

