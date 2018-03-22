<?php

if(isset($config_version) && $config_version != "2017041201")
    trigger_error("sound_backup config.inc file is not up to date", E_USER_WARNING);

$sound_backup_basedir = dirname(__DIR__).'/';
$backup_filename = "backup.wav";

$sound_backup_pid_file = $sound_backup_basedir.'var/sound_backup_pid';
$sound_backup_script_init = $sound_backup_basedir.'bash/init';
$sound_backup_script_stop = $sound_backup_basedir.'bash/stop';
