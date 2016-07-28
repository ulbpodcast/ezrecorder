<?php

require_once("logger.php");

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

    /**
     * Class constructor
     *
     * @param string $database_file      File path to the sqlite database
     */
    public function __construct($database_file = "db.sqlite") {
        parent::__construct();
        
        global $debug_mode;
        
        $this->database_file = $database_file;

        $this->db = new PDO('sqlite:'.$this->database_file);
        if(!$this->database_is_valid()) {
            $this->backup_database();
            $this->db = null; //close it
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
      $this->db->query('DROP TABLE IF EXISTS '. RecorderLogger::LOG_TABLE_NAME);
      $createTableStr = "CREATE TABLE ".RecorderLogger::LOG_TABLE_NAME."(";
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

      $this->log(EventType::RECORDER_DB, LogLevel::INFO, "Created database " . $this->database_file);
    }
    
    // @param $recorder_event an entry as given by pdo when fetched from recorder db
    // return ServersideLogEntry object
    public function convert_event_to_server_event($recorder_event) {
        global $classroom;
        
        $server_event = new ServersideLogEntry();
        $server_event->id = $recorder_event["id"];
        $server_event->asset = $recorder_event["asset"];
        $server_event->origin = "recorder";
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
            
    // returns events array (with column names as keys)
    public function get_all_events_newer_than($id, $limit) {
        $to_send = array();
        
        $statement = $this->db->prepare('SELECT `id`, `event_time`, `asset`, `course`, `author`, `cam_slide`, `context`, `type_id`, `loglevel`, `message` FROM '.
                RecorderLogger::LOG_TABLE_NAME.' WHERE id > "'.$id.'" ORDER BY id LIMIT 0,'.$limit);
        
        $success = $statement->execute();
        if(!$success) {
            $this->log(EventType::LOGGER, LogLevel::CRITICAL, "get_all_events_newer_than failed");
            return $to_send;
        }
        
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach($results as $key => $value) {
            $server_log = $this->convert_event_to_server_event($value);
            array_push($to_send, $server_log);
            
        }
        return $to_send;
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
    public function log($type, $level, $message, array $context = array(), $asset = "dummy", $asset_info = null) {
        $tempLogData = parent::log($type, $level, $message, $context, $asset, $asset_info);
        
        // db insert
        $statement = $this->db->prepare(
          'INSERT INTO '.RecorderLogger::LOG_TABLE_NAME.' (`event_time`, `asset`, `course`, `author`, `cam_slide`, `context`, `type_id`, `loglevel`, `message`) VALUES ('.
          '(SELECT datetime()), :asset, :course, :author, :camslide, :context, :type_id, :loglevel, :message)');
    
        if($statement == false) {
            echo __CLASS__ .": Prepared statement failed";
            print_r($this->db->errorInfo());
            return;
        }   
        
        $statement->bindParam(':asset', $asset);
        $statement->bindParam(':course', $tempLogData->asset_info->course);
        $statement->bindParam(':author', $tempLogData->asset_info->author);
        $statement->bindParam(':camslide', $tempLogData->asset_info->cam_slide);
        $statement->bindParam(':context', $tempLogData->context);
        $statement->bindParam(':loglevel', $tempLogData->log_level_integer);
        $statement->bindParam(':type_id', $tempLogData->type_id);
        $statement->bindParam(':message', $tempLogData->message);

        $statement->execute();
        
        return $tempLogData;
    }
}
