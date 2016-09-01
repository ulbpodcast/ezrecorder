<?php

require_once 'config_sample.inc';
require_once(__DIR__."/../../../lib_various.php");

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
$value = read_line("Classroom where the remote recorder is installed [default: '$classroom']: ");
if ($value != "")
    $classroom = $value; unset($value);
$value = read_line("Path to this remote module on this Mac [default: '$remoteffmpeg_basedir']: ");
if ($value != "")
    $remoteffmpeg_basedir = $value; unset($value);
$value = read_line("Path to the local video repository on this Mac [default: '$remoteffmpeg_recorddir']: ");
if ($value != "")
    $remoteffmpeg_recorddir = $value; unset($value);
$value = read_line("URL to the main recorder [default '$ezrecorder_url']: ");
if ($value != "")
    $ezrecorder_url = $value; unset($value);
$value = read_line("Email address aimed to receive EZrecorder's alerts [default: '$mailto_admins']: ");
if ($value != "")
    $mailto_admins = $value; unset($value);
$value = read_line("Path to your PHP binary [default: '$php_path']: ");
if ($value != "")
    $php_path = $value; unset($value);
    
if (!is_dir($remoteffmpeg_recorddir . '/ffmpeg_hls')){
    mkdir($remoteffmpeg_recorddir . '/ffmpeg_hls', 0755, true);
}
$config = preg_replace('/\$classroom = (.+);/', '\$classroom = "' . $classroom . '";', $config);
$config = preg_replace('/\$remoteffmpeg_basedir = (.+);/', '\$remoteffmpeg_basedir = "' . $remoteffmpeg_basedir . '";', $config);
$config = preg_replace('/\$remoteffmpeg_recorddir = (.+);/', '\$remoteffmpeg_recorddir = "' . $remoteffmpeg_recorddir . '";', $config);
$config = preg_replace('/\$ezrecorder_url = (.+);/', '\$ezrecorder_url = "' . $ezrecorder_url . '";', $config);
$config = preg_replace('/\$mailto_admins = (.+);/', '\$mailto_admins = "' . $mailto_admins . '";', $config);
$config = preg_replace('/\$ffmpegpath = (.+);/', '\$ffmpegpath = "' . $ffmpegpath . '";', $config);
$config = preg_replace('/\$php_path = (.+);/', '\$php_path = "' . $php_path . '";', $config);
file_put_contents("$remoteffmpeg_basedir/config.inc", $config);

echo PHP_EOL . "Changing values in bash/localdefs" . PHP_EOL;

$bash_file = file_get_contents("$remoteffmpeg_basedir/bash/localdefs_sample");
$bash_file = str_replace("!PATH", $remoteffmpeg_basedir, $bash_file);
$bash_file = str_replace("!RECORD_PATH", $remoteffmpeg_recorddir, $bash_file);
$bash_file = str_replace("!CLASSROOM", $classroom, $bash_file);
$bash_file = str_replace("!MAIL_TO", $mailto_admins, $bash_file);
$bash_file = str_replace("!INPUT_SOURCE", $remoteffmpeg_input_source, $bash_file);
$bash_file = str_replace("!PHP_PATH", $php_path, $bash_file);
$bash_file = str_replace("!FFMPEG_PATH", $ffmpegpath, $bash_file);
file_put_contents("$remoteffmpeg_basedir/bash/localdefs", $bash_file);


$perms_file = file_get_contents("$remoteffmpeg_basedir/setperms_sample.sh");
file_put_contents("$remoteffmpeg_basedir/setperms.sh", $perms_file);

system("chmod -R 755 $remoteffmpeg_local_basedir/bash");
