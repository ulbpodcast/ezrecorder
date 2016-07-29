<?php

require_once __DIR__.'/logger_sync_daemon.php';
require_once __DIR__.'/logger_recorder.php';

Logger::$print_logs = true;

$daemon = new LoggerSyncDaemon(); 
$res = $daemon->run();
exit($res);