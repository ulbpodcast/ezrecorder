<?php

require_once __DIR__.'/global_config.inc';
require_once __DIR__.'/lib_various.php';

class LoggerSyncDaemon {
    // Log sync with server interval, in seconds
    const UPDATE_INTERVAL = 60;
    const PID_FILE = './var/sync_logs_daemon.pid';
    const CLI_SYNC = 'cli_sync_logs.php';
    const CLI_SYNC_DAEMON = 'cli_sync_logs_daemon.php';
    const SYNC_BATCH_SIZE = 1000;
    
    public $last_log_sent_get_url = '';
    public $log_push_url = '';
    
    function __construct() {
        global $ezcast_logs_url;
        global $classroom;
        
        ini_set("allow_url_fopen", 1); //needed to use file_get_contents on web
        $this->last_log_sent_get_url = "$ezcast_logs_url?action=last_log_sent&source=$classroom";
        $this->log_push_url = "$ezcast_logs_url?action=push_logs"; //followed by json array
        
    }
            
    public static function ensure_is_running() {
        if(!self::is_running()) {
            system("php -f ". self::CLI_SYNC_DAEMON . " 2<&1 &");
        }
    }
    
    public static function write_PID() {
        file_put_contents(self::PID_FILE, getmypid());
    }
    
    public static function is_running() {
        return is_process_running(get_pid_from_file(self::PID_FILE));
    }
    
    public function sync_logs() {
        global $logger;
        
        $last_id_sent = file_get_contents($this->last_log_sent_get_url);
        if($last_id_sent == false) {
            $logger->log(EventType::LOG_SYNC, LogLevel::ERROR, "Failed to get last log sent from $this->last_log_sent_get_url", array("LoggerSyncDaemon"));
            return 1;
        }

        $last_id_sent = trim($last_id_sent); //server service does send line returns for some reason

        if(!is_numeric($last_id_sent)) {
            $logger->log(EventType::LOG_SYNC, LogLevel::ERROR, "Failed to get last log sent from $this->last_log_sent_get_url, invalid response: $last_id_sent", array("LoggerSyncDaemon"));
            return 1;
        }

        $logger->log(EventType::LOG_SYNC, LogLevel::DEBUG, "Sending logs newer than $last_id_sent at address $this->last_log_sent_get_url", array("LoggerSyncDaemon"));

        $events_to_send = $logger->get_all_events_newer_than($last_id_sent, self::SYNC_BATCH_SIZE);

        if(count($events_to_send) == 0) {
            $logger->log(EventType::LOG_SYNC, LogLevel::DEBUG, "All okay, nothing to send", array("LoggerSyncDaemon"));
            return 0;
        }

        $events_count = sizeof($events_to_send);
        $handle = curl_init($this->log_push_url);
        if(!$handle) {
            $logger->log(EventType::LOG_SYNC, LogLevel::ERROR, "Failed to init curl for $this->log_push_url", array("LoggerSyncDaemon"));
            return 2;
        }

        $post_array = array(
            'log_data' => json_encode($events_to_send),
        );

        curl_setopt($handle, CURLOPT_POST, 1); //activate POST parameters
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_array);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1); //don't send answer to stdout but in returned string
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10); 
        curl_setopt($handle, CURLOPT_TIMEOUT, 30); //timeout in seconds

        $result = curl_exec($handle);

        if(!$result !== false) {
            $logger->log(EventType::LOG_SYNC, LogLevel::ERROR, "Failed to exec curl for $this->log_push_url. Result $result", array("LoggerSyncDaemon"));
            return 3;
        }

        //service returns SUCCESS or ERRORX
        if(strpos($result, "SUCCESS") === false) {
            $logger->log(EventType::LOG_SYNC, LogLevel::ERROR, "Post service returned an error: $result. What we sent: ".json_encode($post_array), array("LoggerSyncDaemon"));
            return 4;
        }

        $logger->log(EventType::LOG_SYNC, LogLevel::DEBUG, "Log sync was succesful, $events_count entries were synced. Server response: $result", array("LoggerSyncDaemon"));
    }
    
    public function run() {
        global $logger;

        self::write_PID();

        while (true) {
            $start_time = time();
            
            $error = $this->sync_logs();
            if($error) {
                $logger->log(EventType::LOG_SYNC, LogLevel::ERROR, "Command '".self::CLI_SYNC."' failed", array("LoggerSyncDaemon"));
            }
            
            $end_time = time();
            
            $time_spent = $end_time - $start_time;
            // Try to keep UPDATE_INTERVAL between each sync start. 
            // For example, for UPDATE_INTERVAL = 60:
            //   if we spent 5 seconds syncing, sleep only 55 seconds.
            $time_to_sleep = $time_spent >= self::UPDATE_INTERVAL ? 0 : self::UPDATE_INTERVAL - $time_spent;

            sleep($time_to_sleep);
        }
    }
 
}