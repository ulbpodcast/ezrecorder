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
    protected $db_version = "0.3";
    //Structure used to create database. If changing this structure, don't forget to update log(...) function
    const LOG_TABLE_NAME = "logs";
    
    private $db_structure = [
      'event_time'  => 'DATETIME',
      'course'  => 'VARCHAR(50)',
      'author'  => 'VARCHAR(50)',
      'cam_slide'  => 'VARCHAR(50)',
      'context'   => 'VARCHAR(30)',
      'type_id'      => 'INT(10)',
      'loglevel'  => 'TINYINT(1)',
      'message'   => 'TEXT',
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
      $this->db->exec('DROP TABLE IF EXISTS '. RecorderLogger::LOG_TABLE_NAME);
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
      $this->db->exec($createTableStr);

      $this->db->exec('DROP TABLE IF EXISTS db_version');
      $this->db->exec('CREATE TABLE db_version(`version` VARCHAR(30))');
      $this->db->exec('INSERT INTO db_version VALUES ("'.$this->db_version.'")');

      $this->log(EventType::RECORDER_DB, LogLevel::INFO, "Created database " . $this->database_file);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context Context can have several levels, such as array('module', 'capture_ffmpeg'). Cannot contain pipes (will be replaced with slashes if any).
     * @return null
     */
    public function log($type, $level, $message, array $context = array(), $asset = "dummy", $assetInfo = null) {
        $tempLogData = parent::log($type, $level, $message, $context, $asset, $assetInfo);
        
        // db insert
        $statement = $this->db->prepare(
          'INSERT INTO '. RecorderLogger::LOG_TABLE_NAME.' (`event_time`, `course`, `author`, `cam_slide`, `context`, `type_id`, `loglevel`, `message`) VALUES ('.
          '(SELECT datetime()), :course, :author, :camslide, :context, :type_id, :loglevel, :message)');
    
        if($statement == false) {
            echo __CLASS__ .": Prepared statement failed";
            print_r($this->db->errorInfo());
            return;
        }   
        
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
