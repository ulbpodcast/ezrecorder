<?php

require_once 'config_sample.inc';

echo PHP_EOL . "***********************************************" . PHP_EOL;
echo           "* Installation of visca_cam_management module *" . PHP_EOL;
echo           "***********************************************" . PHP_EOL;
echo PHP_EOL . "creating config.inc" . PHP_EOL;

copy(__DIR__."/config_sample.inc", __DIR__."/config.inc");

system("mv $web_basedir/ptzposdir $web_basedir/ptzposdir_old");
system("cp -rp $modules_basedir/visca_cam_management/ptzposdir $web_basedir/ptzposdir");
