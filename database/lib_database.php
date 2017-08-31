<?php

require_once(__DIR__."/../global_config.inc");
require_once("$basedir/lib_various.php");

/**
To Improve:
 * Make this class a singleton, then move logs and session reladed functions to their own classes
 */
class SQLiteDatabase 
{
  
    /**
    * Database infos
    */
    //PDO object
    protected $db;
    //Database file path
    protected $database_file;
    //Increment this every time you change the structure. If version change, a new database file will be created and the old one moved to *.old
    const DB_VERSION = "1";
    //Structure used to create database. If changing this structure, don't forget to update log(...) function
    const LOG_TABLE_NAME = "logs";
    const SESSION_TABLE_NAME = "session";
    const RECORDS_START_INFOS_TABLE_NAME = "records_form_data";
    const USER_INFO_TABLE_NAME = "user_info";
    
    private $db_structure = [
        self::LOG_TABLE_NAME => [
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
        ],
        self::SESSION_TABLE_NAME => [
            'id'               => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'asset'            => 'VARCHAR(50)',
            'course_id'        => 'VARCHAR(50)',
            'lang'             => 'VARCHAR(10) DEFAULT "en"',
            'record_type'      => 'VARCHAR(25)',
            'rec_start_time_ts'=> 'DATETIME',
            'rec_init_time_ts' => 'DATETIME',
            'lock_user'        => 'VARCHAR(50) NOT NULL',
            'lock_user_admin'  => 'VARCHAR(50)',
            'closed'           => 'TINYINT(1) DEFAULT 0 NOT NULL',
            'last_request'     => 'DATETIME',
        ],
        self::RECORDS_START_INFOS_TABLE_NAME => [
            'id'               => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'author'           => 'VARCHAR(50) NOT NULL',
            'course_id'        => 'VARCHAR(50) NOT NULL',
            'title'            => 'VARCHAR(100) NOT NULL',
            'description'      => 'TEXT NOT NULL',
            'record_type'      => 'VARCHAR(25) NOT NULL',
            'submit_time'      => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ],
        self::USER_INFO_TABLE_NAME => [
            'user_id'          => 'VARCHAR(50) PRIMARY KEY',
            'full_name'        => 'VARCHAR(100) NOT NULL',
            'email'            => 'VARCHAR(100) NOT NULL',
        ],
    ];
     
    private static $statements = array(); //definition is in constructor. From PHP5.6, it could be moved here.
    
    /**
     * Class constructor
     *
     * @param string $database_file      File path to the sqlite database
     * @param string $last_log_sent_get_url Web address to the last log sent service on ezcast
     */
    public function __construct($database_file) 
    {
        global $debug_mode;
        
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
        
        self::$statements = [
            //LOGS
            
            // ... todo: move them here
            
            //SESSION
            
            'session_get_open_session'     =>  'SELECT id FROM '.self::SESSION_TABLE_NAME.' WHERE id = (SELECT MAX(id) FROM '.self::SESSION_TABLE_NAME.') AND closed = 0', //get last session, only if it's not closed
            'session_get_rec_time'         =>  'SELECT rec_start_time_ts FROM '.self::SESSION_TABLE_NAME.' WHERE id = :session_id',
            'session_get_init_time'        =>  'SELECT rec_init_time_ts FROM '.self::SESSION_TABLE_NAME.' WHERE id = :session_id',
            'session_get_user'             =>  'SELECT lock_user FROM '.self::SESSION_TABLE_NAME.' WHERE id = :session_id',
            'session_get_admin'            =>  'SELECT lock_user_admin FROM '.self::SESSION_TABLE_NAME.' WHERE id = :session_id',
            'session_get_asset'            =>  'SELECT asset FROM '.self::SESSION_TABLE_NAME.' WHERE id = :session_id',
            'session_get_record_type'      =>  'SELECT record_type FROM '.self::SESSION_TABLE_NAME.' WHERE id = :session_id',
            'session_get_course_id'        =>  'SELECT course_id FROM '.self::SESSION_TABLE_NAME.' WHERE id = :session_id',
            'session_get_lang'             =>  'SELECT lang FROM '.self::SESSION_TABLE_NAME.' WHERE id = :session_id',
            'session_set_lang'             =>  'UPDATE '.self::SESSION_TABLE_NAME.' SET lang = :lang WHERE id = :session_id',
            'session_close'                =>  'UPDATE '.self::SESSION_TABLE_NAME.' SET closed = 1 WHERE id = :session_id',
            'session_set_rec_start_time'   =>  'UPDATE '.self::SESSION_TABLE_NAME.' SET rec_start_time_ts = DATETIME(\'now\') WHERE id = :session_id',
            'session_init_record'          =>  'UPDATE '.self::SESSION_TABLE_NAME.' SET 
                                                    asset = :asset ,
                                                    course_id = :course ,
                                                    record_type = :record_type ,
                                                    rec_init_time_ts = DATETIME(\'now\')
                                                WHERE id = :session_id',
            'session_get_first_free_id'    => 'SELECT COALESCE(MAX(id) + 1,0) FROM '.self::SESSION_TABLE_NAME,
            'session_new'                  => 'INSERT INTO '.self::SESSION_TABLE_NAME. '(id, lock_user, lock_user_admin) VALUES(:id, :user, :admin)',
            'session_get_lock_info'        => 'SELECT lock_user, lock_user_admin, course_id FROM '.self::SESSION_TABLE_NAME.' WHERE id = :session_id',
            'session_set_last_request'     => 'UPDATE '.self::SESSION_TABLE_NAME.' SET last_request = DATETIME(\'now\') WHERE id = :session_id',
            'session_get_last_request'     => 'SELECT TIMESTAMP(last_request) FROM '.self::SESSION_TABLE_NAME.' WHERE id = :session_id',
            
            /*
             *   'id'               => 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'course_id'        => 'VARCHAR(50)',
            'title'            => 'VARCHAR(100)',
            'description'      => 'TEXT',
            'record_type'      => 'VARCHAR(25)',
             */
            
            // RECORDS START INFOS
            'form_data_get_all'                   => 'SELECT course_id, title, description, record_type, submit_time FROM '.self::RECORDS_START_INFOS_TABLE_NAME.' WHERE author = :author',
            'form_data_get_data_for_last_month'   => 'SELECT course_id, title, description, record_type, submit_time FROM '.self::RECORDS_START_INFOS_TABLE_NAME
                                                     . ' WHERE author = :author AND submit_time > datetime(\'now\', \'-1 month\')'
                                                     . ' ORDER BY submit_time',
            'form_data_insert'                    => 'INSERT INTO '.self::RECORDS_START_INFOS_TABLE_NAME.'(author, course_id, title, description, record_type)'
                                                     . ' VALUES (:author, :course, :title, :description, :record_type)',
            
            // USER INFO
            
            'user_info_get'                       => 'SELECT full_name, email FROM '.self::USER_INFO_TABLE_NAME.' WHERE user_id = :user_id',
            'user_info_write'                     => 'REPLACE INTO '.self::USER_INFO_TABLE_NAME.'(user_id, full_name, email) VALUES (:user_id, :full_name, :email)',
        ];
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
    private function backup_database() 
    {
        if(file_exists($this->database_file))
            return rename($this->database_file, $this->database_file . '.backup.' . date("Y-m-d.H-i-s"));

        return true;
    }

    /**
    * Return true if database seems to be usable
    */
    private function database_is_valid() 
    {
        try {
            $result = $this->db->query('SELECT version FROM db_version');
            if(!$result)
              return false;

            //check if our current version matches
            $versionArray = $result->fetch();
            if(sizeof($versionArray) < 1)
              return false;

            if($versionArray[0] != self::DB_VERSION)
              return false;

            // dummy query to test each table
            foreach($this->db_structure as $table_name => &$tables_def) {
                $testQuery = "SELECT ";
                $first = true;
                foreach($tables_def as $column => $type) {
                    //do not add a comma before the first column
                    if(!$first)
                        $testQuery .= ',';
                    else
                        $first = false;

                    $testQuery .= '`'.$column.'`';
                }
                $testQuery .= " FROM ".$table_name." LIMIT 1";
                $result = $this->db->query($testQuery);
                if(!$result)
                  return false;
            }
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
    private function create_database() 
    {
        foreach($this->db_structure as $table_name => &$tables_def) {
            $this->db->query('DROP TABLE IF EXISTS '. $table_name);
            $createTableStr = "CREATE TABLE ".$table_name."(";
            $first = true;
            foreach($tables_def as $column => $type) {
                //do not add a comma before the first column
                if(!$first)
                  $createTableStr .= ',';
                else
                  $first = false;

                $createTableStr .= '`'.$column.'` ' . $type;
            }
            $createTableStr .= ')';
            $result = $this->db->query($createTableStr);
            if($result == false) {
                trigger_error("CRITICAL: Failed to create sqlite database. PDO error: " . json_encode($this->db->errorInfo()) , E_USER_ERROR);
            }
        }

        $this->db->query('DROP TABLE IF EXISTS db_version');
        $this->db->query('CREATE TABLE db_version(`version` VARCHAR(30))');
        $this->db->query('INSERT INTO db_version VALUES ("'.self::DB_VERSION.'")');

        //set AUTO INCREMENT value at first given by the server
        $starting_id = 0;
        $ok = self::get_last_log_sent($starting_id);
        if(!$ok) {
            trigger_error("Couldn't get last log sent to ezcast for db init. Using 0 as starting id");
        }

        if($starting_id != 0 && $starting_id != -1) {
            $this->db->query("REPLACE INTO SQLITE_SEQUENCE (name, seq) VALUES ('".self::LOG_TABLE_NAME."',$starting_id)");
        }

        trigger_error("Created database with starting id $starting_id - " . $this->database_file);
    }
    
    public function exec_query_first_value()
    {
        $results = call_user_func_array('self::exec_query', func_get_args());
        if($results === false)
            return false;
        if(empty($results))
            return false;
        
        //print_r($results);
        return $results[0][0];
    }
    
    public function exec_query_first_row()
    {
        $results = call_user_func_array('self::exec_query', func_get_args());
        if($results === false)
            return false;
        if(empty($results))
            return false;
        
        //print_r($results);
        return $results[0];
    }
    
    //return all results, or false
    public function exec_query($statement_id /*, ...*/) 
    {
        global $logger;
        
        if(!isset(self::$statements[$statement_id])) {
            //print_r(self::$statements);
            trigger_error("No statement with id: $statement_id"); //probably a typo somewhere in this class
            return false;
        }
        
        $statement_str = self::$statements[$statement_id];
        //echo "$statement_str </br>";
        $statement = $this->db->prepare($statement_str);
        
        if($statement == false) {
            print_r($this->db->errorInfo());
            $logger->log(EventType::TEST, LogLevel::ERROR, "DB error while executing query $statement_str", array(__FUNCTION__));
           
            return false;
        }
        $sql_args = array_slice(func_get_args(), 1);
        /*echo $statement_id . '</br>';
        echo "<pre>";
        print_r($sql_args);
        echo "</pre>";
        */
        foreach($sql_args as $args) {
            $statement->bindParam($args[0], $args[1]);
        }

        try {
           $success = $statement->execute();
            if(!$success) {
               trigger_error( __FUNCTION__ . " failed for query with id: $statement_id");
               return false;
            }
         
            //normal exit point
            return $statement->fetchAll();
        } catch (Exception $ex) {
            trigger_error("Database exception while executing statement with id $statement_id: ". $ex->getMessage());
            //something went wrong. How to report this ?
            return false;
        }
        return false;
    }
    
    // LOGS //
    // 
     //Return last log the server knows from us. Return success of query
    public static function get_last_log_sent(&$last_id_sent) 
    {       
        global $last_log_sent_get_url;
        
        $last_id_sent = file_get_contents($last_log_sent_get_url);
        if($last_id_sent == false) {
            //logger may not yet be init // $this->log(EventType::LOGGER, LogLevel::ERROR, "Failed to get last log sent from $this->last_log_sent_get_url", array("RecorderLogger"));
            return false;
        } 
        
        $last_id_sent = trim($last_id_sent); //server service does send line returns for some reason

        if(!is_numeric($last_id_sent)) {
            //logger may not yet be init // $this->log(EventType::LOGGER, LogLevel::ERROR, "Failed to get last log sent from $this->last_log_sent_get_url, invalid response: $last_id_sent", array("RecorderLogger"));
            return false;
        }
        
        return true;
    }
    
    // @param $recorder_event an entry as given by pdo when fetched from recorder db
    // return ServersideLogEntry object
    public function convert_event_to_server_event($recorder_event) 
    {
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
    
    // returns events array (with column names as keys)
    // this ignores debug entries, unless debug_mode (global config) is enabled
    public function logs_get_all_events_newer_than($id, $limit) 
    {
        global $send_debug_logs_to_server;
        global $logger;

        $to_send = array();
        

        $where = "WHERE id > $id";
        if($send_debug_logs_to_server == false)
            $where .= " AND loglevel < " . LogLevel::$log_levels[LogLevel::DEBUG];
        
        $statement = $this->db->prepare('SELECT `id`, `event_time`, `asset`, `course`, `author`, `cam_slide`, `context`, `type_id`, `loglevel`, `message` FROM '.
                RecorderLogger::LOG_TABLE_NAME." $where ORDER BY id LIMIT 0,$limit");
        
        $success = $statement->execute();
        if(!$success) {
            $logger->log(EventType::LOGGER, LogLevel::CRITICAL, "logs_get_all_events_newer_than failed", array("RecorderLogger"));
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
    public function logs_get_last_local_event_id() 
    {
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
    
    public function logs_set_autoincrement($id) 
    {
        $PDOstatement = $this->db->query("UPDATE SQLITE_SEQUENCE SET seq = $id WHERE name = '" . RecorderLogger::LOG_TABLE_NAME . "'");
        return $PDOstatement != false;        
    }
    
    public function logs_insert($asset, $course, $author, $cam_slide, $context, $log_level_integer, $type_id, $message)
    {
        $statement = $this->db->prepare(
        'INSERT INTO '.self::LOG_TABLE_NAME.' (`event_time`, `asset`, `course`, `author`, `cam_slide`, `context`, `type_id`, `loglevel`, `message`) VALUES ('.
        "(SELECT datetime('now','localtime')), :asset, :course, :author, :camslide, :context, :type_id, :loglevel, :message)");

        if($statement == false) {
            print_r($this->db->errorInfo());
            return false;
        }
        
        $statement->bindParam(':asset', $asset);
        $statement->bindParam(':course', $course);
        $statement->bindParam(':author', $author);
        $statement->bindParam(':camslide', $cam_slide);
        $statement->bindParam(':context', $context);
        $statement->bindParam(':loglevel', $log_level_integer);
        $statement->bindParam(':type_id', $type_id);
        $statement->bindParam(':message', $message);

        try {
            $statement->execute();
        } catch (Exception $ex) {
            trigger_error("Database exception: ". $ex->getMessage());
            //something went wrong. How to report this ?
            return false;
        }
        return true;
    }
    
    // SESSION //
    
    public function session_rec_time_get($session_id)
    {
        return self::exec_query_first_value('session_get_rec_time', array(':session_id', $session_id));
    }
    
    public function session_init_time_get($session_id)
    {
         return self::exec_query_first_value('session_get_init_time', array(':session_id', $session_id));
    }
    
    public function session_admin_get($session_id)
    {
        return self::exec_query_first_value('session_get_admin', array(':session_id', $session_id));
    }
    
    public function session_user_get($session_id)
    {
        return self::exec_query_first_value('session_get_user', array(':session_id', $session_id));
    }
    
    
    public function session_asset_get($session_id)
    {
        return self::exec_query_first_value('session_get_asset', array(':session_id', $session_id));
    }
    
    public function session_record_type_get($session_id)
    {
        return self::exec_query_first_value('session_get_record_type', array(':session_id', $session_id));
    }
    
    public function session_course_id_get($session_id)
    {
        return self::exec_query_first_value('session_get_course_id', array(':session_id', $session_id));
    }
    
    public function session_lang_get($session_id)
    {
        return self::exec_query_first_value('session_get_lang', array(':session_id', $session_id));
    }
    
    public function session_lang_set($session_id, $lang) 
    {
        return self::exec_query('session_set_lang', array(':session_id', $session_id), array(':lang', $lang));
    }
    
    public function session_init_record($session_id, $asset_name, $course_name, $record_type)
    {
        return self::exec_query('session_init_record', 
                            array(':session_id', $session_id), 
                            array(':asset', $asset_name), 
                            array(':course', $course_name), 
                            array(':record_type', $record_type)
                        );
    }
    
    //set record start time on current time
    public function session_start_record($session_id) 
    {
        return self::exec_query('session_set_rec_start_time', array(':session_id', $session_id));
    }
    
    public function session_new($user_id, $admin_id = null)
    {
        $session_id_res = self::exec_query('session_get_first_free_id');
        if($session_id_res === false)
            return false;
        
        $session_id = $session_id_res[0][0];
        
        $ok = self::exec_query('session_new', array(':id', $session_id), array(':user', $user_id), array(':admin', $admin_id));
        if($ok === false)
            return false;
        return $session_id;
    }
    
    public function session_close($session_id)
    {
        return self::exec_query('session_close', array(':session_id', $session_id));
    }
    
    public function session_get_lock_info($session_id, &$lock_user, &$lock_course, &$lock_admin)
    {
        $results = self::exec_query_first_row('session_get_lock_info', array(':session_id', $session_id));
        if($results === false)
            return false;
        
        $lock_user = $results['lock_user'];
        $lock_admin = $results['lock_user_admin'];
        $lock_course = $results['course_id'];
        return true;
    }
    
    public function session_last_request_set($session_id)
    {
        return self::exec_query('session_set_last_request', array(':session_id', $session_id));
    }
    
    //return timestamp
    public function session_last_request_get($session_id)
    {
        //$timestamp == False not handled, implement if it if you need it
        return self::exec_query_first_value('session_get_last_request', array(':session_id', $session_id));
    }
    
    public function session_get_open_session()
    {
        return self::exec_query_first_value('session_get_open_session');
    }
    
    // RECORD FORM DATA
//            'form_data_get_all'            => 'SELECT course_id, title, description, record_type FROM '.self::RECORDS_START_INFOS_TABLE_NAME.' WHERE id = :session_id',
//            'form_data_insert'             => 'INSERT INTO '.self::RECORDS_START_INFOS_TABLE_NAME.'(course_id, title, description, record_type) VALUES (:course, :title, :description, :record_type)',
    
    //get first found form data for a record done on given day of the week (max 1 month backward). If not found return null
    public function form_data_get_data_for_day_of_week($author, $timestamp) 
    {
        $results = self::exec_query('form_data_get_data_for_last_month', array(':author', $author));
        if($results === false)
            return false;
        
        $now = new DateTime();
        $day_of_week = $now->format('N');
        $best_date = null;
        $data = null;
        foreach($results as $result) {     
            // We'll try to get the best form based on the following rules in that order:
            //    1. Same day of the week
            //    2. Most recent
            //    3. Anything else found
            // Note that results are fetched from older to newer  
            
            $formDate = new DateTime($result['submit_time']);
            $same_day_as_today = $formDate->format('N') == $day_of_week;
            $best_date_has_same_day_as_today = $best_date !== null ? ($best_date->format('N') == $day_of_week) : false;
            
            //we already found a same day form data, only replace it if we found a more recent form with same day as well
            if($best_date_has_same_day_as_today && !$same_day_as_today)
                continue;
            
            $data = new FormData($result['course_id'], $result['title'], $result['description'], $result['record_type']);
            $best_date = $formDate;
        }
        
        //return best data found
        return $data;
    }
    
    public function form_data_insert($author, $course, $title, $decription, $record_type) 
    {
        // (:course, :title, :description, :record_type)',
        $ok = self::exec_query('form_data_insert', array(':author', $author), array(':course', $course), array(':title', $title), array(':description', $decription), array(':record_type', $record_type));
        if($ok === false)
            return false;
        
        return true;
    }
    
    // USER INFO
    
    public function user_info_get($user_id, &$full_name, &$email)
    {
        $results = self::exec_query_first_row('user_info_get', array(':user_id', $user_id));
        if($results === false)
            return false;
        
        $full_name = $results['full_name'];
        $email = $results['email'];
        
        return true;
    }
    
    public function user_info_write($user_id, $full_name, $email)
    {
        $ok = self::exec_query('user_info_write', array(':user_id', $user_id), array(':full_name', $full_name), array(':email', $email));
        if($ok === false)
            return false;
        
        return true;
    }
}

class FormData
{
    function __construct($course, $title, $description, $record_type) 
    {
        $this->course = $course;
        $this->title = $title;
        $this->description = $description;
        $this->record_type = $record_type;
    }
    
    public $course;
    public $title;
    public $description;
    public $record_type;
}
    