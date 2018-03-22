<?php

/*
 * This class handles the recording locking and all recording session data.
 * The recorder is locked when a user is logged in, in this case other users can't connect without canceling the current record first.
 * The RecordingSession class is a singleton that stores info about the current session as well as about the current record.
 * The session is created when the user login, and is destroyed when he logs out.
 * First initialization must be done with the "lock" function. As of 
 * 
 * A user login is assimilated into a recording session. The session is created when the user login, and is destroyed when he logs out.
 * This class is used as a singleton trough the instance() function. First initialization must be done with the "lock" function
 */
class RecordingSession
{
    private static $instance = null;
    private static $session_id = null;
    private static $meta_file = null; //defined in constructor
    
    //can throw if called . $user_id can be left out if 
    private function __construct($user_id = null, $admin_id = null) 
    {
        self::$meta_file = __DIR__ . '/var/metadata.xml';
        
        global $database;
        
        //if self::$session_id has already been defined, we're in a restore case, no need to create a new session
        if(self::$session_id === null && $user_id !== null) {
            $id = $database->session_new($user_id, $admin_id);
            if($id === false)
                throw new Exception("Failed to init new session for user $user_id");
            
            self::$session_id = $id;
        }
        //if we still don't have a session id at this point, something is wrong
        if(self::$session_id === null)
            throw new Exception("Could not initialized session, could not get a session id");
        
        //update template folder
        global $template_folder;
        $lang = $this->get_lang();
        if($lang == "" || $lang == false) {
            trigger_error("Failed to get lang from session, defaulting to en");
            $lang = "en";
        }
        
        template_repository_path($template_folder . $this->get_lang());
    }
    
    static public function restore_session_if_any()
    {
        global $database;
        $session_id = $database->session_get_open_session();
        if($session_id === false) {
            return;
        }
        self::$session_id = $session_id;
        self::$instance = new RecordingSession();
    }
    
    static public function &lock($user_id, $admin_id = null) 
    {
        if(self::$instance === null) { //no current lock
            if($user_id === null)
                throw new Exception("No userid given");
            if(!self::can_access_lock($user_id))
               throw new Exception("User $user_id cannot access lock, cannot lock session");
           
            self::$instance = new RecordingSession($user_id, $admin_id);
        } else {
            //lock already exists, can I access it ?
            if(!self::can_access_lock($user_id))
                throw new Exception("User $user_id cannot access already existing lock, cannot lock session. Unlock it fist.");
        }
        return self::$instance;
    }
    
    //check if recorder is locked
    static public function is_locked()
    {
        return self::$instance !== null;
    }
    
    static public function can_access_lock($user_id)
    {
        global $logger;
        
        if(!self::is_locked())
            return true;
        
        global $database;
        $session_id = self::instance()->get_session_id();
        $lock_user = false;
        $lock_course = false;
        $lock_admin = false;
        $ok = $database->session_get_lock_info($session_id, $lock_user, $lock_course, $lock_admin);
        if($ok === false) {
            $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::ERROR, "Could not get info for current lock. Allowing access by default.", array(__FUNCTION__));
            return true; //DEFAULT TO TRUE
        }
        
        if($lock_user == $user_id)
            return true;
        
        //possible improvement... if user ID is moderator of course, allow him 
        
        return false;
    }
    
    public function get_session_id()
    {
        return self::$session_id;
    }
    
    static public function unlock()
    {
        if(self::is_locked() === false)
            return;
        
        global $database;
        $database->session_close(self::instance()->get_session_id());
        self::$instance = null;
        self::$session_id = null;
    }
    
    //return the recording session if it already exists, else return null
    static public function &instance()
    {
        return self::$instance;
    }
    
    public static function metadata_save($metadata_assoc_array)
    {
        global $logger;

        $processUser = posix_getpwuid(posix_geteuid());
        $name = $processUser['name'];

        //create and store recording properties
        $xml = xml_assoc_array2metadata($metadata_assoc_array);
        $res = file_put_contents(self::$meta_file, $xml);
        if(!$res) {
            $logger->log(EventType::RECORDER_METADATA, LogLevel::ERROR, __FUNCTION__.": Failed to save metadata to self::$meta_file. Current user: $name. Probably a permission problem.", array("lib_recording_session"));
            return false;
        }
        $res = chmod(self::$meta_file, 0644);
        if(!$res) {
            //file is owned by podclient. Any solution ?
            $logger->log(EventType::TEST, LogLevel::WARNING, "Could not chmod file self::$meta_file. Current user: $name", array("lib_recording_session"));
        }
        return $xml;
    }
    
    public static function metadata_delete() 
    {
        if (file_exists(self::$meta_file))
            unlink(self::$meta_file);
    }
    
    //persistent through session
    public static function metadata_get()
    {
        if (file_exists(self::$meta_file))
            return xml_file2assoc_array(self::$meta_file);
        else 
            return false;
    }
    
    public static function metadata_get_xml() 
    {
        return file_get_contents(self::$meta_file);
    }
    
    //old initstarttime_set
    public function start_record() 
    {
        global $database;
        $database->session_start_record(self::$session_id);
        
        // also updates the last_request time
        $this->set_last_request();
    }
    
    public function init_record($asset_name, $course_name, $record_type)
    {
        global $database;
        $database->session_init_record(self::$session_id, $asset_name, $course_name, $record_type);
        
        // also updates the last_request time
        $this->set_last_request();
    }
    
    //return timestamp
    public function get_rec_start_time()
    {
        global $database;
        return $database->session_rec_time_get(self::$session_id);
    }
    
    //return timestamp
    public function get_init_time()
    {
        global $database;
        return $database->session_init_time_get(self::$session_id);
    }
    
    public function get_current_user()
    {
        global $database;
        return $database->session_user_get(self::$session_id);
    }
    
    //if any
    public function get_current_admin()
    {
        global $database;
        return $database->session_admin_get(self::$session_id);
    }
    
    public function get_current_asset()
    {
        global $database;
        return $database->session_asset_get(self::$session_id);
    }
    
    public function get_record_type()
    {
        global $database;
        return $database->session_record_type_get(self::$session_id);
    }
    
    public function get_course_id()
    {
        global $database;
        return $database->session_course_id_get(self::$session_id);
    }
    
    public function get_lang()
    {
        global $database;
        return $database->session_lang_get(self::$session_id);
        //default to 'en';
    }
    
    public function set_lang($lang)
    {
        global $database;
        return $database->session_lang_set(self::$session_id, $lang);
    }
    
    public function set_last_request()
    {
        global $database;
        return $database->session_last_request_set(self::$session_id);
    }
    
    //get last request from user
    public function get_last_request()
    {
        global $database;
        return $database->session_last_request_get(self::$session_id);
    }
}
