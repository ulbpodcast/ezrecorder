<?php
require_once("global_config.inc");

system("mkdir -p $web_basedir");

system("cp -r $basedir/htdocs/* $web_basedir/");
//system("chown -R $ezrecorder_username:$ezrecorder_web_user $web_basedir");
//system("chown -R $ezrecorder_username:$ezrecorder_web_user $basedir");

$web_file = file_get_contents($web_basedir . "/index.php");
$web_file = str_replace("!PATH", $basedir, $web_file);
file_put_contents($web_basedir . "/index.php", $web_file);
