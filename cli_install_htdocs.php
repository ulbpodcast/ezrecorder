<?php
require_once("global_config.inc");

system("mkdir -p $web_basedir");

system("cp -r $basedir/htdocs/* $web_basedir/");
//system("chown -R $ezrecorder_username:$ezrecorder_web_user $web_basedir");
//system("chown -R $ezrecorder_username:$ezrecorder_web_user $basedir");

$index_file = $web_basedir . "/index.php";
$web_file = file_get_contents($index_file);
$web_file = str_replace("!PATH", $basedir, $web_file);
file_put_contents($index_file, $web_file);

$root_file = $web_basedir . "/services/root.inc";
$web_file = file_get_contents($root_file);
$web_file = str_replace("!ROOT", $basedir, $web_file);
file_put_contents($root_file, $web_file);