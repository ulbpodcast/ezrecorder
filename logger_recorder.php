<?php

require_once("logger.php");
require_once("logger_sync_daemon.php");
require_once("global_config.inc");
require_once("lib_various.php");
require_once(__DIR__.'/lib_recording_session.php');

/**
 * This is the ezcast recorder logger,
 * This logger uses the PSR-3 Logger Interface as described here: http://www.php-fig.org/psr/psr-3/
 * Before using this object you must the default timezone by usig date_default_timezone_set() or the date.timezone option.
 * Usage:
 * $logger = Logger(LogLevel::INFO);
 * $logger->log(...)
 *
 */
class RecorderLogger extends Logger 
{  
    protected $classroom;
    
    /**
     * Class constructor
     *
     * @param string $classroom      
     */
    public function __construct($classroom) 
    {
        parent::__construct();
        ini_set("allow_url_fopen", 1); //needed to use file_get_contents on web, used by get_last_log_sent
        
        $this->classroom = $classroom;
    }
                
    // returns events array (with column names as keys)
    // this ignores debug entries, unless debug_mode (global config) is enabled
    public function get_all_events_newer_than($id, $limit) 
    {
        global $database;
        return $database->logs_get_all_events_newer_than($id, $limit);
    }
    
    //return last event id in local database. return 0 on error.
    public function get_last_local_event_id() 
    {
        global $database;
        return $database->logs_get_last_local_event_id();
    }
    
    public function set_autoincrement($id) 
    {
        global $database;
        return $database->logs_set_autoincrement($id);
    }

    public function get_default_asset_for_log() 
    {
        if(RecordingSession::instance() === null)
            return "";
        
        $current_asset = RecordingSession::instance()->get_current_asset();
        return $current_asset ? $current_asset : "";
    }
    
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $type type in the form of EventType::*
     * @param mixed $level in the form of LogLevel::*
     * @param string $message
     * @param string $asset asset identifier
     * @param array $context Context can have several levels, such as array('module', 'capture_ffmpeg'). Cannot contain pipes (will be replaced with slashes if any).
     * @param string $asset asset name
     * @param AssetLogInfo $asset_info Additional information about asset if any, in the form of a AssetLogInfo structure
     * @return LogData temporary data, used by children functions
     */
    public function log($type, $level, $message, array $context = array(), $asset = "", 
            $author = null, $cam_slide = null, $course = null, $classroom = null) 
    {
        global $database;
        
        if($asset == "") {
            $asset = $this->get_default_asset_for_log();
        }
        
        $tempLogData = parent::_log($type, $level, $message, $context, $asset, $author, $cam_slide, $course, $classroom);
        
        //default classroom if none specified
        if($classroom == null)
            $classroom = $this->classroom;
        
        LoggerSyncDaemon::ensure_is_running();
        
        $database->logs_insert($asset, $course, $author, $cam_slide, $tempLogData->context, $tempLogData->log_level_integer, $tempLogData->type_id, $message);
      
        return $tempLogData;
    }
}
