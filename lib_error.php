<?php

/*
 * Error managing and logging library
 */

include_once 'global_config.inc';

/**
 * Prints the error message on screen
 * @param string $msg The message to print
 * @param bool $log If set to false, the error won't be logged.
 */
function error_print_message($msg, $log = true) {
    //echo '<b>Error: </b>'.$msg;
    echo '<script type="text/javascript">window.alert("'.$msg.'");</script>';
    
    if($log) {
        log_append('error', $msg);
    }
}

/**
 * Adds a line in log
 * @global string $podman_logs Path to the log file
 * @param string $operation The operation done
 * @param string $message Additionnal information (parameters)
 */
function log_append($operation, $message = '') {
    global $recorder_logs;
    
    // 1) Date/time at which the event occurred
    $data = date('Y-m-d-H:i:s');
    $data .= ' ';
    
    // 2) IP address of the user that provoked the event
    $data .= (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : 'noip';
    $data .= ' ';
    
    $has_session =  RecordingSession::instance() != null;
    // 3) Username and realname of the user that provoked the event
    // There can be no login if the operation was performed by a CLI tool for instance.
    // In that case, we display "nologin" instead.
    $current_user = "";
    $admin_user = "";
            
    if($has_session) {
        $current_user = RecordingSession::instance()->get_current_user();
        $admin_user = RecordingSession::instance()->get_current_admin();
    }
    
    if($current_user === null) {
        $data .= 'nologin';
    }
    // General case, where there is a login and (possibly) a real login
    else if($admin_user != null) {
        $data .= $admin_user.'/'.$current_user;
    }
    else {
        $data .= $current_user;
    }
    $data .= ' ';
    
    // 4) Operation performed
    $data .= $operation;
    
    // 5) Optionnal parameters
    if(!empty($message))
        $data .= ': '.$message;
    
    // 6) And we add a carriage return for readability
    $data .= PHP_EOL;
    
    // Then we save the new entry
    file_put_contents($recorder_logs, $data, FILE_APPEND | LOCK_EX);
}

/**
 * Gets or sets an error message
 * @staticvar string $last_error
 * @param type $msg
 * @return string 
 */
function error_last_message($msg = '') {
  static  $last_error="";

  if($msg=="")
      return $last_error;
   else{
       $last_error=$msg;
       return true;
   }
}