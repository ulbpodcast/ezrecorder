<?php

require_once __DIR__.'/global_config.inc';
require_once __DIR__.'/lib_various.php';
require_once __DIR__.'/lib_recording_session.php';

class LoggerSyncDaemon {
    // Log sync with server interval, in seconds
    const UPDATE_INTERVAL = 60;
    const PID_FILE = './var/sync_logs_daemon.pid';
    const CLI_SYNC = 'cli_sync_logs.php';
    const CLI_SYNC_DAEMON = 'cli_sync_logs_daemon.php';
    const SYNC_BATCH_SIZE = 1000;
    const MAX_RUN_TIME = 86400; //run max 24 hours. This is to help when global_config has been changed, or if this file has been updated
    const MAX_FAILURES_BEFORE_WARNING = 15;
            
    public static function ensure_is_running() {
        global $basedir;
        if(!self::is_running()) {
            system("php -f ". self::CLI_SYNC_DAEMON . " > $basedir/var/log_sync_daemon 2>&1 &");
        }
    }
    
    public static function write_PID() {
        file_put_contents(self::PID_FILE, getmypid());
    }
    
    public static function is_running() {
        return is_process_running(get_pid_from_file(__DIR__.'/'.self::PID_FILE));
    }
    
    public function sync_logs() {
        global $logger;
        global $log_push_url;
        global $database;
        
        $last_id_sent = 0;
        $ok = $database->get_last_log_sent($last_id_sent);
        if(!$ok) {
            $logger->log(EventType::RECORDER_LOG_SYNC, LogLevel::ERROR, "Failed to get last log sent, cannot continue", array(basename(__FILE__)));
            return 1;
        }
        
        $last_local_id = $logger->get_last_local_event_id();
        if($last_local_id < $last_id_sent) {
            $logger->set_autoincrement($last_id_sent + 1);
            $logger->log(EventType::RECORDER_LOG_SYNC, LogLevel::DEBUG, "Dummy log, just to insert one row after resetting auto increment", array(basename(__FILE__)));

            $logger->log(EventType::RECORDER_LOG_SYNC, LogLevel::ERROR, "Server knows of a more recent event ($last_id_sent) than we actually have ($last_local_id) on this recorder... this should not happen. Reseting our auto increment to this id.", array(basename(__FILE__)));
            return 2;
        }
        
        //$logger->log(EventType::RECORDER_LOG_SYNC, LogLevel::DEBUG, "Sending logs newer than $last_id_sent at address $log_push_url. (Last local log is $last_local_id)", array(basename(__FILE__)));

        $events_to_send = $logger->get_all_events_newer_than($last_id_sent, self::SYNC_BATCH_SIZE);

        if(count($events_to_send) == 0) {
           // $logger->log(EventType::RECORDER_LOG_SYNC, LogLevel::DEBUG, "All okay, nothing to send", array(basename(__FILE__)));
            return 0;
        }

        $events_count = sizeof($events_to_send);
        $handle = curl_init($log_push_url);
        if(!$handle) {
            $logger->log(EventType::RECORDER_LOG_SYNC, LogLevel::ERROR, "Failed to init curl for $log_push_url", array(basename(__FILE__)));
            return 3;
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
            $logger->log(EventType::RECORDER_LOG_SYNC, LogLevel::ERROR, "Failed to exec curl for $log_push_url. Result $result", array(basename(__FILE__)));
            return 4;
        }

        //service returns SUCCESS if ok
        if(strpos($result, "SUCCESS") === false) {
            $logger->log(EventType::RECORDER_LOG_SYNC, LogLevel::ERROR, "Post service returned an error: $result. What we sent: ".json_encode($post_array), array(basename(__FILE__)));
            return 5;
        }

        $logger->log(EventType::RECORDER_LOG_SYNC, LogLevel::DEBUG, "Log sync was succesful, $events_count entries were synced. Server response: $result", array(basename(__FILE__)));
        return 0;
    }
    
    public function run($check_if_running = true) {
        /* it seems we sometimes have several sync_daemon using ensure_is_running. This is because cli_sync_logs is started as a background process
         * and the PID may not be written yet when ensure_is_running is called. The next check is there to fix this.
         */
        if($check_if_running) {
            if($this->is_running())
                return;
        }
        
        self::write_PID();

        global $logger;
        global $disable_logs_sync;

        $process_start_time = time();
        $failure_in_a_row = 0;
        
        while (true) {
            if($disable_logs_sync)
                break;
            
            $current_sync_start_time = time();
            
            //$logger->log(EventType::RECORDER_LOG_SYNC, LogLevel::DEBUG, "Syncing...", array(basename(__FILE__)));
            
            $error = $this->sync_logs();
            if($error) {
                $failure_in_a_row++;
                if($failure_in_a_row >= self::MAX_FAILURES_BEFORE_WARNING)
                    $logger->log(EventType::RECORDER_LOG_SYNC, LogLevel::ERROR, "Command '".self::CLI_SYNC."' failed", array(basename(__FILE__)));
            } else {
                $failure_in_a_row = 0;
            }
            
            $current_sync_end_time = time();
            
            $time_spent = $current_sync_end_time - $current_sync_start_time;
            // Try to keep UPDATE_INTERVAL between each sync start. 
            // For example, for UPDATE_INTERVAL = 60:
            //   if we spent 5 seconds syncing, sleep only 55 seconds.
            $time_to_sleep = $time_spent >= self::UPDATE_INTERVAL ? 0 : self::UPDATE_INTERVAL - $time_spent;

            //$logger->log(EventType::RECORDER_LOG_SYNC, LogLevel::DEBUG, "Logs synced with return val $error. Sleep for $time_to_sleep", array(basename(__FILE__)));
            
            sleep($time_to_sleep);
            
            if(($process_start_time + self::MAX_RUN_TIME) < time())
                exit(0); //max run time reached, stop here
        }
    }
}