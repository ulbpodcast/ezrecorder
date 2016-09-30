<?php

require_once __DIR__.'/logger_sync_daemon.php';
require_once __DIR__.'/logger_recorder.php';

Logger::$print_logs = true;

echo "Starting sync loop..." . PHP_EOL;
$daemon = new LoggerSyncDaemon(); 
$res = $daemon->run(false, true);
exit($res);