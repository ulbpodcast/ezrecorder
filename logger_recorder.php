<?php

require_once("logger.php");
require_once("logger_sync_daemon.php");
require_once("global_config.inc");
require_once("lib_various.php");

/**
 * This is the ezcast recorder logger,
 * This logger uses the PSR-3 Logger Interface as described here: http://www.php-fig.org/psr/psr-3/
 * Before using this object you must the default timezone by usig date_default_timezone_set() or the date.timezone option.
 * Usage:
 * $logger = Logger(LogLevel::INFO);
 * $logger->log(...)
 *
 */
class RecorderLogger extends Logger {
  
    /**
    * Database infos
    */
    //PDO object
    protected $db;
    //Database file path
    protected $database_file;
    //Increment this every time you change the structure. If version change, a new database file will be created and the old one moved to *.old
    protected $db_version = "0.6";
    //Structure used to create database. If changing this structure, don't forget to update log(...) function
    const LOG_TABLE_NAME = "logs";
    
    private $db_structure = [
      'id'          => 'INTEGER PRIMARY KEY AUTOINCREMENT',
      'event_time'  => 'DATETIME',
      'asset'       => 'VARCHAR(50)',
      'course'      => 'VARCHAR(50)',
      'author'      => 'VARCHAR(50)',
      'cam_slide'   => 'VARCHAR(50)',
      'context'     => 'VARCHAR(30)',
      'type_id'     => 'INTEGER',
      'loglevel'    => 'TINYINT(1)',
      'message'     => 'TEXT',
    ];

    protected $last_log_sent_get_url;
    /**
     * Class constructor
     *
     * @param string $database_file      File path to the sqlite database
     * @param string $last_log_sent_get_url Web address to the last log sent service on ezcast
     */
    public function __construct($database_file, $last_log_sent_get_url) {
        global $debug_mode;
        
        parent::__construct();
        ini_set("allow_url_fopen", 1); //needed to use file_get_contents on web, used by get_last_log_sent
        
        $this->last_log_sent_get_url = $last_log_sent_get_url;
        $this->database_file = $database_file;

        $this->db = new PDO('sqlite:'.$this->database_file);
        if(!$this->database_is_valid()) {
            $this->backup_database();
            $this->db = null; //close it. Needed?
            $this->db = new PDO('sqlite:'.$this->database_file);
            $this->create_database();
        }
        
        if($debug_mode)
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Class destructor
     */
    public function __destruct() {
       $this->db = null;
    }

    /**
    * Rename database file to *.backup.<date>
    * Return true if success (or file does not exists)
    */
    private function backup_database() {
        if(file_exists($this->database_file))
          return rename($this->database_file, $this->database_file . '.backup.' . date("Y-m-d.H-i-s"));

        return true;
    }

    /**
    * Return true if database seems to be usable
    */
    private function database_is_valid() {
        try {
            $result = $this->db->query('SELECT version FROM db_version');
            if(!$result)
              return false;

            //check if our current version matches
            $versionArray = $result->fetch();
            if(sizeof($versionArray) < 1)
              return false;

            if($versionArray[0] != $this->db_version)
              return false;

            // dummy query to test the log table
            $testQuery = "SELECT ";
            $first = true;
            foreach($this->db_structure as $key => $type)
            {
              //do not add a comma before the first column
              if(!$first)
                $testQuery .= ',';
              else
                $first = false;

              $testQuery .= '`'.$key.'`';
            }
            $testQuery .= " FROM ".RecorderLogger::LOG_TABLE_NAME." LIMIT 1";
            $result = $this->db->query($testQuery);
            if(!$result)
              return false;
        } catch (Exception $e) {
            //something went wrong
            return false;
        }

        //all ok
        return true;
    }

    /**
    *  Create database if need be
    *  Return true if database was created
    */
    private function create_database() {
      $this->db->query('DROP TABLE IF EXISTS '. self::LOG_TABLE_NAME);
      $createTableStr = "CREATE TABLE ".self::LOG_TABLE_NAME."(";
      $first = true;
      foreach($this->db_structure as $key => $type)
      {
        //do not add a comma before the first column
        if(!$first)
          $createTableStr .= ',';
        else
          $first = false;

        $createTableStr .= '`'.$key.'` ' . $type;
      }
      $createTableStr .= ')';
      $result = $this->db->query($createTableStr);
      if($result == false) {
          trigger_error("CRITICAL: Failed to create database for logger. PDO error: " . json_encode($this->db->errorInfo()) , E_USER_ERROR);
      }

      $this->db->query('DROP TABLE IF EXISTS db_version');
      $this->db->query('CREATE TABLE db_version(`version` VARCHAR(30))');
      $this->db->query('INSERT INTO db_version VALUES ("'.$this->db_version.'")');
      
      //set AUTO INCREMENT value at first given by the server
      $starting_id = 0;
      $ok = $this->get_last_log_sent($starting_id);
      if(!$ok) {
          $this->log(EventType::RECORDER_DB, LogLevel::ERROR, "Couldn't get last log sent to ezcast for db init. Using 0 as starting id");
      }
      
      if($starting_id != 0 && $starting_id != -1) {
          
          $this->db->query("REPLACE INTO SQLITE_SEQUENCE (name, seq) VALUES ('".self::LOG_TABLE_NAME."',$starting_id)");
      }

      $this->log(EventType::RECORDER_DB, LogLevel::INFO, "Created database with starting id $starting_id" . $this->database_file);
    }
    
    // @param $recorder_event an entry as given by pdo when fetched from recorder db
    // return ServersideLogEntry object
    public function convert_event_to_server_event($recorder_event) {
        global $classroom;
        
        $server_event = new ServersideLogEntry();
        $server_event->id = $recorder_event["id"];
        $server_event->asset = $recorder_event["asset"];
        $server_event->origin = "ezrecorder";
        $server_event->asset_classroom_id = $classroom;
        $server_event->asset_course = $recorder_event["course"];
        $server_event->asset_author = $recorder_event["author"];
        $server_event->asset_cam_slide = $recorder_event["cam_slide"];
        $server_event->event_time = $recorder_event["event_time"];
        $server_event->type_id = $recorder_event["type_id"];
        $server_event->context = $recorder_event["context"];
        $server_event->loglevel = $recorder_event["loglevel"];
        $server_event->message = $recorder_event["message"];
        
        return $server_event;
    }
            
    //Return last log the server knows from us. Return success of query
    public function get_last_log_sent(&$last_id_sent) {        
        $last_id_sent = file_get_contents($this->last_log_sent_get_url);
        if($last_id_sent == false) {
            $this->log(EventType::LOGGER, LogLevel::ERROR, "Failed to get last log sent from $this->last_log_sent_get_url", array("RecorderLogger"));
            return false;
        } 
        
        $last_id_sent = trim($last_id_sent); //server service does send line returns for some reason

        if(!is_numeric($last_id_sent)) {
            $this->log(EventType::LOGGER, LogLevel::ERROR, "Failed to get last log sent from $this->last_log_sent_get_url, invalid response: $last_id_sent", array("RecorderLogger"));
            return false;
        }
        
        return true;
    }
    
    // returns events array (with column names as keys)
    // this ignores debug entries, unless debug_mode (global config) is enabled
    public function get_all_events_newer_than($id, $limit) {
        global $send_debug_logs_to_server;

        $to_send = array();
        

        $where = "WHERE id > $id";
        if($send_debug_logs_to_server == false)
            $where .= " AND loglevel < " . LogLevel::$log_levels[LogLevel::DEBUG];
        
        $statement = $this->db->prepare('SELECT `id`, `event_time`, `asset`, `course`, `author`, `cam_slide`, `context`, `type_id`, `loglevel`, `message` FROM '.
                RecorderLogger::LOG_TABLE_NAME." $where ORDER BY id LIMIT 0,$limit");
        
        $success = $statement->execute();
        if(!$success) {
            $this->log(EventType::LOGGER, LogLevel::CRITICAL, "get_all_events_newer_than failed", array("RecorderLogger"));
            return $to_send;
        }
        
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach($results as $key => $value) {
            $server_log = $this->convert_event_to_server_event($value);
            array_push($to_send, $server_log);
            
        }
        return $to_send;
    }
    
    //return last event id in local database. return 0 on error.
    public function get_last_local_event_id() {
        $statement = $this->db->prepare('SELECT MAX(id) FROM '.RecorderLogger::LOG_TABLE_NAME);
        $success = $statement->execute();
        if(!$success) {
            $this->log(EventType::LOGGER, LogLevel::CRITICAL, __FUNCTION__ . " failed", array("RecorderLogger"));
            return 0;
        }
        $results = $statement->fetch(PDO::FETCH_NUM);
        $maxId = $results["0"];
        return $maxId;
    }
    
    public function set_autoincrement($id) {
        $PDOstatement = $this->db->query("UPDATE SQLITE_SEQUENCE SET seq = $id WHERE name = '" . RecorderLogger::LOG_TABLE_NAME . "'");
        return $PDOstatement != false;        
    }

    public function get_default_asset_for_log() {
        /* removed for now, remote recorder may not have session module and we don't have a way to know if we're remote or not at this point
        require_once($session_lib); //problem right now: remote module may not have this module configured
        global $session_module;
        
        //try getting it from session
        $fct = "session_" . $session_module . "_metadata_get";
        $meta_assoc = $fct();
        if($meta_assoc != false) {
            return get_asset_name($meta_assoc['course_name'], $meta_assoc['record_date']);
        } else {
            return "dummy";
        }
         * 
         */
        return "dummy";
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
            $author = null, $cam_slide = null, $course = null, $classroom = null) {
        
        if($asset == "") {
            $asset = $this->get_default_asset_for_log();
        }
        
        $tempLogData = parent::_log($type, $level, $message, $context, $asset, $author, $cam_slide, $course, $classroom);
        
        LoggerSyncDaemon::ensure_is_running();
        
        $statement = $this->db->prepare(
          'INSERT INTO '.RecorderLogger::LOG_TABLE_NAME.' (`event_time`, `asset`, `course`, `author`, `cam_slide`, `context`, `type_id`, `loglevel`, `message`) VALUES ('.
          "(SELECT datetime('now','localtime')), :asset, :course, :author, :camslide, :context, :type_id, :loglevel, :message)");
    
        if($statement == false) {
            echo __CLASS__ .": Prepared statement failed";
            print_r($this->db->errorInfo());
            return;
        }
        
        $statement->bindParam(':asset', $asset);
        $statement->bindParam(':course', $course);
        $statement->bindParam(':author', $author);
        $statement->bindParam(':camslide', $cam_slide);
        $statement->bindParam(':context', $tempLogData->context);
        $statement->bindParam(':loglevel', $tempLogData->log_level_integer);
        $statement->bindParam(':type_id', $tempLogData->type_id);
        $statement->bindParam(':message', $message);

        try {
            $statement->execute();
        } catch (Exception $ex) {
            //something went wrong. How to report this ?
            return false;
        }
        
        return $tempLogData;
    }
}
