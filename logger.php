<?php

include_once('external_products/Psr/Log/AbstractLogger.php');

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * This is the ezcast recorder logger,
 * This logger uses the PSR-3 Logger Interface as described here: http://www.php-fig.org/psr/psr-3/
 * Before using this object you must the default timezone by usig date_default_timezone_set() or the date.timezone option.
 * Usage:
 * $log = Logger(Psr\Log\LogLevel::INFO);
 * $log->info('Returned a million search results'); //Insert error in database
 * $log->error('Oh dear.', array("SUCHCONTEXT")); //Insert error in database
 * $log->debug('x = 5'); //Prints nothing due to current severity threshhold
 *
 */
class Logger extends AbstractLogger
{
    /**
     * Current minimum logging threshold
     * @var integer
     */
    protected $logLevelThreshold;

    /**
    * Classroom name to use in log messages
    * @var string
    */
    protected $classroomName;

    /**
     * Log Levels
     * @var array
     */
    protected $logLevels = array(
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7
    );

    /**
    * Database infos
    */
    //PDO object
    protected $db;
    //Database file path
    protected $databaseFile;
    //Increment this every time you change the structure. If version change, a new database file will be created and the old one moved to *.old
    protected $dbVersion = "0.1";
    //Structure used to create database. If changing this structure, don't forget to update log(...) function
    protected $logTableName = "logs";
    protected $dbStructure = [
      'classroom' => 'VARCHAR(30)',
      'datetime'  => 'DATETIME',
      'context'   => 'VARCHAR(30)',
      'loglevel'  => 'TINYINT(1)',
      'message'   => 'TEXT',
    ];

    /**
     * Class constructor
     *
     * @param string $databaseFile      File path to the sqlite database
     * @param string $logLevelThreshold The LogLevel Threshold
     */
    public function __construct($databaseFile = "db.sqlite", $classroomName = "classroom_name", $logLevelThreshold = LogLevel::INFO)
    {
        $this->databaseFile = $databaseFile;
        $this->$classroomName = $classroomName;
        $this->logLevelThreshold = $logLevelThreshold;

        $this->db = new PDO('sqlite:'.$this->databaseFile);
        if(!$this->databaseIsValid()) {
            $this->backupDatabase();
            $this->db = null; //close it
            $this->db = new PDO('sqlite:'.$this->databaseFile);
            $this->createDatabase();
        }
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
       $this->db = null;
    }

    /**
    * Rename database file to *.backup.<date>
    * Return true if success (or file does not exists)
    */
    private function backupDatabase()
    {
        if(file_exists($this->databaseFile))
          return rename($this->databaseFile, $this->databaseFile . '.backup.' . date("Y-m-d.H-i-s"));

        return true;
    }

    /**
    * Return true if database seems to be usable
    */
    private function databaseIsValid()
    {
        try {
            $result = $this->db->query('SELECT version FROM db_version');
            if(!$result)
              return false;

            //check if our current version matches
            $versionArray = $result->fetch();
            if(sizeof($versionArray) < 1)
              return false;

            if($versionArray[0] != $this->dbVersion)
              return false;

            // dummy query to test the log table
            $testQuery = "SELECT ";
            $first = true;
            foreach($this->dbStructure as $key => $type)
            {
              //do not add a comma before the first column
              if(!$first)
                $testQuery .= ',';
              else
                $first = false;

              $testQuery .= '`'.$key.'`';
            }
            $testQuery .= " FROM $this->logTableName LIMIT 1";
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
    private function createDatabase()
    {
      $this->db->exec('DROP TABLE IF EXISTS '.$this->logTableName);
      $createTableStr = "CREATE TABLE $this->logTableName(";
      $first = true;
      foreach($this->dbStructure as $key => $type)
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
      $this->db->exec('INSERT INTO db_version VALUES ("'.$this->dbVersion.'")');

      $this->info("Created database " . $this->databaseFile);
    }

    /**
     * Sets the Log Level Threshold
     *
     * @param string $logLevelThreshold The log level threshold
     */
    public function setLogLevelThreshold($logLevelThreshold)
    {
        $this->logLevelThreshold = $logLevelThreshold;
    }

    /**
    * Return log level given in the form of LogLevel::* into an integer
    */
    public function getLogLevelInteger($level)
    {
        foreach($this->logLevels as $key => $value)
        {
          if($key == $level)
            return $value;
        }

         throw new RuntimeException('getLogLevelInteger: Invalid level given');
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context Context can have several levels, such as array('module', 'capture_ffmpeg'). Cannot contain pipes (will be replaced with slashes if any).
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        //do not log if log above log level
        if ($this->logLevels[$this->logLevelThreshold] < $this->logLevels[$level]) {
            return;
        }

        try {
          $logLevelInteger = $this->getLogLevelInteger($level);
        } catch (Exception $e) {
          //invalid level given, default to "error" and prepend this problem to the message
          $message = "(Invalid log level) " . $message;
          $logLevelInteger = $this->logLevels[LogLevel::ERROR];
        }

        //remove pipes
        $contextStr = str_replace($context, '/', '|');
        //concat contexts for db insert
        $contextStr = implode('|', $context);
        
        $insertQuery = 'INSERT INTO '.$this->logTableName.' (`classroom`, `datetime`, `context`, `loglevel`, `message`) VALUES ('.
          '"'.$this->classroomName.'",'.
          '(SELECT datetime()),'.
          '"'.$contextStr.'",'. //just concat everything in context with slashes between
          $logLevelInteger.','.
          '"'.$message.'"'.
          ')';

        $this->db->exec($insertQuery);
    }
}
