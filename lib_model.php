<?php

require_once 'global_config.inc';
require_once 'lib_various.php';
require_once 'lib_template.php';
require_once 'lib_error.php';
require_once $auth_lib;
require_once $session_lib;
if ($cam_enabled)
    require_once $cam_lib; // defined in global_config
if ($slide_enabled)
    require_once $slide_lib; // defined in global_config
if ($cam_management_enabled)
    require_once $cam_management_lib;

/**
 * We are called by a browser with no action, but sessions is alive and login has succeeded, so go to the according screen
 * @global <type> $status
 * @global <type> $already_recording
 */
function reconnect_active_session() {
    global $status;
    global $already_recording;
    global $redraw;
    global $logger;
    
    $logger->log(EventType::RECORDER_LOGIN, LogLevel::INFO, 'User '.$_SESSION['user_login'].' reconnected active session', array('auth'));
    log_append("Reconnect active session");
    $status = status_get();
    //lets check what the 'real' state we're in
    $already_recording = ($status == 'recording' || $status == 'paused');
    if ($already_recording || $status == 'open') {
        //state is one of the recording mode
        $_SESSION['recorder_mode'] = 'view_record_screen';
        $redraw = true;
        view_record_screen();
    } else if ($status == 'stopped') {
        //stopped means we have already clicked on stop
        $_SESSION['recorder_mode'] = 'view_record_submit';
        controller_view_record_submit();
    } else
        controller_view_record_form(); //none of the above cases to this is a first form screen
}

/**
 * Handles recording_form values and open recorder_view
 * @global type $input
 * @global <type> $classroom
 */
function controller_recording_submit_infos() {
    global $input;
    global $classroom;
    global $auth_module;
    global $session_module;
    global $dir_date_format;
    global $recorder_session;
    global $logger;

    // Sanity checks
    if (!isset($input['title']) || empty($input['title'])) {
        error_print_message(template_get_message('title_not_defined', get_lang()), false);
        die;
    }

    if (!isset($input['record_type']) || empty($input['record_type'])) {
        error_print_message(template_get_message('type_not_defined', get_lang()), false);
        die;
    }

    $streaming = (isset($input['streaming']) && $input['streaming'] == 'enabled') ? 'true' : 'false';

    // authorization check
    $fct_user_has_course = "auth_" . $auth_module . "_user_has_course";
    if (!$fct_user_has_course($_SESSION['user_login'], $input['course'])) {
        error_print_message('You do not have permission to access course ' . $input['course'], false);
        $msg = 'submit_record_infos: ' . $_SESSION['user_login'] . ' tried to access course ' . $input['course'] . ' without permission';
        log_append('warning', $msg);
        $logger->log(EventType::RECORDER_USER_SUBMIT_INFO, LogLevel::WARNING, $msg, array('controller'));

        die;
    }
    $_SESSION['recorder_course'] = $input['course'];
    $_SESSION['recorder_type'] = $input['record_type'];

    $datetime = date($dir_date_format);

    // Now we create and store the metadata
    $record_meta_assoc = array(
        'course_name' => $input['course'],
        'origin' => $classroom,
        'title' => trim($input['title']),
        'description' => $input['description'],
        'record_type' => $input['record_type'],
        'moderation' => 'true',
        'author' => $_SESSION['user_full_name'],
        'netid' => $_SESSION['user_login'],
        'record_date' => $datetime,
        'streaming' => $streaming
    );


    $fct_metadata_save = "session_" . $session_module . "_metadata_save";
    $res = $fct_metadata_save($record_meta_assoc);

    if (!$res) {
        error_print_message('submit_record_infos: something went wrong while saving metadata');
        die;
    }

    log_append("submit info from recording form");
    // Don't forget to save the current viewed page into a session var, just in cast the user reloads the page
    $_SESSION['recorder_mode'] = 'view_record_screen';
    $_SESSION['asset'] = $record_meta_assoc['record_date'] . '_' . $record_meta_assoc['course_name'];
    file_put_contents($recorder_session, $_SESSION['asset'] . ";" . $_SESSION['user_login']);

    // And finally we can display the main screen! This will init the recorders
    view_record_screen();
}

/**
 * Starts a new recording
 */
function controller_recording_start() {
    global $logger;
    global $dir_date_format;
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;
    global $session_module;
    global $classroom;

    // another user is connected
    $fct_current_user_get = "session_" . $session_module . "_current_user_get";
    $user = $fct_current_user_get();

    $asset = $_SESSION['asset'];

    if ($user != $_SESSION['user_login']) {
        error_print_message('User conflict - session user [' . $_SESSION['user_login'] . '] different from current user [' . $user . '] : check permission on current_user file in session module');
        $logger->log(EventType::RECORDER_START, LogLevel::ERROR, '(action recording_start) User conflict - session user [' . $_SESSION['user_login'] . '] different from current user [' . $user . '] : check permission on current_user file in session module', array('controller'), $asset);
        return false;
    }

    //get current status and check if its compatible with current action
    $status = status_get();
    if ($status != 'open') {
        error_print_message("capture_start: error status ($status): status not 'open'");
        $logger->log(EventType::RECORDER_START, LogLevel::INFO, "(action recording_start) Could not start recording because of status '$status'", array('controller'), $asset);
        return false;
    }

    // saves the start time
    $datetime = date($dir_date_format);
    $startrec_info = "$datetime\n";
    $startrec_info.=$_SESSION['recorder_course'] . "\n";
    $fct_recstarttime_set = "session_" . $session_module . "_recstarttime_set";
    $fct_recstarttime_set($startrec_info);

    $asset_dir = get_asset_dir($asset, "local_processing");
    if (!file_exists($asset_dir)) {
        $res = mkdir($asset_dir);
        if ($res == false) {
            $logger->log(EventType::RECORDER_START, LogLevel::INFO, "Could not start recording because asset dir couln't be created: $asset_dir. Error: $res", array('controller'), $asset);
            return false;
        }
    }
    chmod($asset_dir, 0777);

    // determines if the slide module is enabled
    if ($slide_enabled) {
        $fct_capture_start = 'capture_' . $slide_module . '_start';
        // ideally, capture_start should return the pid
        //     $res_slide = $fct_capture_start($slide_pid);
        $res_slide = $fct_capture_start($asset);
    }

    // determines if the cam module is enabled (doesn't depend on the 
    // recording format chose by user - cam, slide, camslide)
    if ($cam_enabled) {
        $fct_capture_start = 'capture_' . $cam_module . '_start';
        // ideally, capture_start should return the pid
        // $res_cam = $fct_capture_start($cam_pid);
        $res_cam = $fct_capture_start($asset);
    }

    //      while(is_process_running($cam_pid) || is_process_running($slide_pid))
    //          sleep(0.5);
    // something went wrong while starting the recording
    if (($cam_enabled && !$res_cam) || ($slide_enabled && !$res_slide)) {
        error_print_message(capture_last_error());
        $logger->log(EventType::RECORDER_START, LogLevel::INFO, "Record start failed in capture module", array('controller'), $asset);
        return false;
    }

    log_append("recording_start", "started recording by user request");
    $logger->log(EventType::ASSET_CREATED, LogLevel::NOTICE, 
            "Started recording at user $user request. cam_enabled: $cam_enabled / slide_enabled: $slide_enabled", array('controller'), $asset, 
            $user, "todo", $_SESSION['recorder_course'], $classroom);

    return true;
}

function close_session() {
    global $session_module;

    // releases the recording session
    $fct_session_unlock = "session_" . $session_module . "_unlock";
    $fct_session_unlock();

    // And finally, closing the user's session
    session_destroy();
}

/**
 * Stops the recording and processes it
 */
function controller_publish() {
    global $logger;
    global $input;
    global $php_cli_cmd;
    global $cli_post_process;
    global $session_module;
    global $basedir;
    global $recorder_monitoring_pid;

    $asset = $_SESSION['asset'];

    // stops the timeout monitoring
    if (file_exists($recorder_monitoring_pid))
        unlink($recorder_monitoring_pid);

    $moderation = 'false';
    if (isset($input['moderation']) && $input['moderation'] == 'true')
        $moderation = 'true';

    // Logging the operation
    $fct_recstarttime_get = "session_" . $session_module . "_recstarttime_get";
    $recstarttime = explode(PHP_EOL, $fct_recstarttime_get());
    $starttime = $recstarttime[0];
    $album = $recstarttime[1];

    log_append('recording_stop', 'Stopped recording by user request (course ' . $album . ', started on ' . $starttime . ', moderation: ' . $moderation . ')');
    $logger->log(EventType::RECORDER_PUBLISH, LogLevel::NOTICE, 'Recording published at user request (course ' . $album . ', started on ' . $starttime . ', moderation: ' . $moderation . ').', array('controller'), $asset);

    //get the start time and course from metadata
    $fct_metadata_get = "session_" . $session_module . "_metadata_get";
    $meta_assoc = $fct_metadata_get();
    if($meta_assoc == false) {
        $logger->log(EventType::RECORDER_PUBLISH, LogLevel::CRITICAL, "Could not get metadata from session for publishing, stopping now", array('controller'), $asset);
        close_session();
        return false;
    }

    $asset_dir = get_local_processing_dir($asset);
    if(!file_exists($asset_dir)) {
        $logger->log(EventType::RECORDER_PUBLISH, LogLevel::CRITICAL, "Trying to publish unknown asset $asset from dir $asset_dir", array('controller'), $asset);
        close_session();
        return false;
    }
    
    //update metadata with moderation
    if ($moderation == 'true' || $moderation == 'false') {
        $meta_assoc['moderation'] = $moderation;
        $fct_metadata_save = "session_" . $session_module . "_metadata_save";
        $fct_metadata_save($meta_assoc);
    }

    $fct_metadata_xml_get = "session_" . $session_module . "_metadata_xml_get";
    $meta_xml_string = $fct_metadata_xml_get();
    if($meta_xml_string == false) {
        $logger->log(EventType::RECORDER_PUBLISH, LogLevel::CRITICAL, "Could not get XML metadata from session for publishing, stopping now", array('controller'), $asset);
        close_session();
        return false;
    }
    
    // saves the recording metadata in a tmp xml file (used later in cli_upload_to_server.php)
    file_put_contents("$asset_dir/metadata.xml", $meta_xml_string);

    // launches the video processing in background
    $return_val = 0;
    system("$php_cli_cmd $cli_post_process $asset > $asset_dir/post_process.log &", $return_val);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_PUBLISH, LogLevel::CRITICAL, "$cli_post_process returned error $return_val", array('controller'), $asset);
    }
    
    close_session();

    // Displaying a confirmation message
    require_once template_getpath('div_record_submitted.php');
}

/**
 * Cancel the recording after the record form has been submitted or at the 
 * end of the recording
 * @global type $recstarttime_file
 * @global type $cam_enabled
 * @global type $cam_module
 * @global type $slide_enabled
 * @global type $slide_module
 * @global type $visca_enabled
 * @return boolean
 */
function controller_recording_cancel() {
    global $logger;
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;
    global $cam_management_enabled;
    global $cam_management_module;
    global $session_module;
    global $recorder_monitoring_pid;

    
    $fct_metadata_get = "session_" . $session_module . "_metadata_get";
    $metadata = $fct_metadata_get();
    if($metadata) {
        $asset = get_asset_name($metadata['course_name'], $metadata['record_date']);
        $logger->log(EventType::ASSET_RECORD_END, LogLevel::NOTICE, "Record cancelled at user request", array('controller'), $asset);
    } else {
        $logger->log(EventType::ASSET_RECORD_END, LogLevel::ERROR, "A record was cancelled at user request, but we could not get asset name. Not a problem in the processing but shouldn't happen.", array('controller'));
    }
        
    // stops the timeout monitoring
    if (file_exists($recorder_monitoring_pid))
        unlink($recorder_monitoring_pid);

    // Logging the operation
    $fct_recstarttime_get = "session_" . $session_module . "_recstarttime_get";
    $recstarttime = explode(PHP_EOL, $fct_recstarttime_get());
    $starttime = $recstarttime[0];
    $album = $recstarttime[1];
    log_append('recording_cancel', 'Deleted recording by user request (course ' . $album . ', started on ' . $starttime . ')');

    // Stopping and releasing the recorder
    // if cam module is enabled
    if ($cam_enabled) {
        $fct_capture_cancel = 'capture_' . $cam_module . '_cancel';
        $res_cam = $fct_capture_cancel($_SESSION['asset']);
    }

    // if slide module is enabled 
    if ($slide_enabled) {
        $fct_capture_cancel = 'capture_' . $slide_module . '_cancel';
        $res_slide = $fct_capture_cancel($_SESSION['asset']);
    }

    if ($cam_management_enabled) {
        //cam management enabled so try to put camera back in place
        $fct_cam_move = "cam_" . $cam_management_module . "_move";
        $fct_cam_move($GLOBALS['cam_default_scene']); // set cam to the initial position
    }

    // something wrong happened while cancelling the recording
    if (($cam_enabled && !$res_cam) || ($slide_enabled && !$res_slide)) {
        error_print_message(error_last_message());
        return false;
    }

    // releases the recording session. Someone else can now record
    $fct_session_unlock = "session_" . $session_module . "_unlock";
    $fct_session_unlock();

    $fct_metadata_delete = "session_" . $session_module . "_metadata_delete";
    $fct_metadata_delete();

    //closing the user's session
    session_destroy();
    status_set('');

    // Displaying a confirmation message
    require_once template_getpath('div_record_cancelled.php');
}

/**
 * Interrupts current recording
 * (example: this is called when someone tries to log in, but someone else was already recording)
 */
function controller_recording_force_quit() {
    global $notice;
    global $cam_enabled;
    global $slide_enabled;
    global $cam_module;
    global $slide_module;
    global $session_module;
    global $php_cli_cmd;
    global $cli_post_process;
    global $recorder_session;
    global $basedir;
    global $logger;
    global $recorder_monitoring_pid;

    // stops the timeout monitoring
    if (file_exists($recorder_monitoring_pid))
        unlink($recorder_monitoring_pid);

    $session = explode(';', file_get_contents($recorder_session));
    $asset = $session[0];

    $logger->log(EventType::ASSET_RECORD_END, LogLevel::NOTICE, "Record was forcefully cancelled", array('controller'), $asset);
    $logger->log(EventType::RECORDER_FORCE_QUIT, LogLevel::NOTICE, "Record was forcefully cancelled", array('controller'), $asset);

    $fct_current_user_get = "session_" . $session_module . "_current_user_get";
    log_append('warning', $_SESSION['user_login'] . ' trying to log in but recorder is already in use by ' . $fct_current_user_get() . '. Stopping current record.');
    $status = status_get();
    if ($status == '' || $status == 'open') {
        // if cam module is enabled
        if ($cam_enabled) {
            $fct_capture_cancel = 'capture_' . $cam_module . '_cancel';
            $res_cam = $fct_capture_cancel($asset);
        }
        // if slide module is enabled
        if ($slide_enabled) {
            $fct_capture_cancel = 'capture_' . $slide_module . '_cancel';
            $res_slide = $fct_capture_cancel($asset);
        }
        // deletes the previous metadata file 
        $fct_metadata_delete = "session_" . $session_module . "_metadata_delete";
        $fct_metadata_delete();
    } else { // a recording is pending (or stopped)
        // Logging the operation
        $fct_recstarttime_get = "session_" . $session_module . "_recstarttime_get";
        $recstarttime = explode(PHP_EOL, $fct_recstarttime_get());
        $starttime = $recstarttime[0];
        $album = $recstarttime[1];
        log_append('recording_force_quit', 'Force quit recording by another user [' . $_SESSION['user_login'] . '] (course ' . $album . ', started on ' . $starttime . ')');

        $asset_dir = get_local_processing_dir($asset);

        $fct_metadata_xml_get = "session_" . $session_module . "_metadata_xml_get";
        $meta_xml_string = $fct_metadata_xml_get();
        // saves the recording metadata in a tmp xml file (used later in cli_upload_to_server.php)
        file_put_contents($asset_dir . "/metadata.xml", $meta_xml_string);

        // Stopping (pausing) the recording
        // if slide module is enabled
        if ($slide_enabled) {
            $fct_capture_stop = 'capture_' . $slide_module . '_stop';
            $fct_capture_stop($slide_pid, $asset);
        }
        // if cam module is enabled
        if ($cam_enabled) {
            $fct_capture_stop = 'capture_' . $cam_module . '_stop';
            $fct_capture_stop($cam_pid, $asset);
        }

        // waits until both processes are finished to continue.
        while (is_process_running($cam_pid) || is_process_running($slide_pid))
            sleep(0.5);

        // launches the video processing in background
        $return_val = 0;
        system("$php_cli_cmd $cli_post_process $asset > $asset_dir/post_process.log &", $return_val);
        if($return_val != 0) {
            $logger->log(EventType::RECORDER_FORCE_QUIT, LogLevel::CRITICAL, "$cli_post_process returned error $return_val", array('controller'), $asset);
        }
    }

    // reinits the recording status
    status_set('');

    // releases the recording session. Someone else can now record
    $fct_session_unlock = "session_" . $session_module . "_unlock";
    $fct_session_unlock();

    template_load_dictionnary('translations.xml');
    $notice = template_get_message('ongoing_record_interrupted_message', get_lang()); // Message to display on top of the page, warning the user that they just stopped someone else's record*/

    $fct_session_lock = "session_" . $session_module . "_lock";
    $res = $fct_session_lock($_SESSION['user_login']);

    if (!$res) {
        error_print_message('lib_model: recording_force_quit: Could not lock recorder: ' . error_last_message());
        return false;
    }

    log_append('login');

    // 4) And finally, we can display the record form
    controller_view_record_form();
    return true;
}

/*
 * Pauses the current recording
 */

function controller_recording_pause() {
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;
    global $logger;

    $res_cam = true;
    $res_slide = true;

    // if cam module is enabled
    if ($cam_enabled) {
        $fct_capture_pause = 'capture_' . $cam_module . '_pause';
        $res_cam = $fct_capture_pause($_SESSION['asset']);
    }

    // if slide module is enabled
    if ($slide_enabled) {
        $fct_capture_pause = 'capture_' . $slide_module . '_pause';
        $res_slide = $fct_capture_pause($_SESSION['asset']);
    }

    // if something wrong happened while pausing the recording
    if (!$res_cam || !$res_slide) {
        error_print_message(error_last_message());
        die;
    }

    log_append("paused recording by request");
    echo '';
}

/*
 * Resumes the current recording
 */

function controller_recording_resume() {
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;
    global $session_module;

    $res_cam = true;
    $res_slide = true;

    // if cam module is enabled 
    if ($cam_enabled) {
        $fct_capture_resume = 'capture_' . $cam_module . '_resume';
        $res_cam = $fct_capture_resume($_SESSION['asset']);
    }
    // if slide module is enabled
    if ($slide_enabled) {
        $fct_capture_resume = 'capture_' . $slide_module . '_resume';
        $res_slide = $fct_capture_resume($_SESSION['asset']);
    }

    // if something wrong happened while resuming the recording
    if (!$res_cam || !$res_slide) {
        error_print_message(error_last_message());
        die;
    }

    log_append("resumed recording by request");

    echo '';
}

/**
 * Moves the camera to the position given as a POST parameter (position name)
 * @global type $input 
 */
function controller_camera_move() {
    global $input;
    global $cam_management_module;

    if (!isset($input['position'])) {
        error_print_message('Asked to move camera but no position given');
        die;
    }

    $scene = $input["position"];
    $fct_cam_move = "cam_" . $cam_management_module . "_move";
    $fct_cam_move($scene);
    log_append("camera moved to position : $scene");
}

//
// Functions calling the view
//

/**
 * Displays the login form
 */
function controller_view_login_form() {
    global $url;
    session_destroy();
    require_once template_getpath('login.php');
    die;
}

/**
 * Displays the form people get when they log in (i.e. asking for a title, description, ...)
 */
function controller_view_record_form() {
    global $input;
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;
    global $cam_management_enabled;
    global $cam_management_module;
    global $session_module;
    global $auth_module;
    global $notice; // Possible errors that occurred at previous steps.
    global $streaming_available;
    global $recorder_monitoring_pid;
    global $logger;

    //$logger->log(EventType::TEST, LogLevel::DEBUG, "controller_view_record_form called. Backtrace:\n" . print_r(debug_backtrace(), true), array('controller_view_record_form'));

    // stops the timeout monitoring
    if (file_exists($recorder_monitoring_pid))
        unlink($recorder_monitoring_pid);

    // Retrieving the course list (to display in the web interface)
    $fct_user_courselist_get = "auth_" . $auth_module . "_user_courselist_get";
    $courselist = $fct_user_courselist_get($_SESSION['user_login']);

    if (isset($input['reset_player']) && $input['reset_player'] == 'true') {
        // if cam module is enabled
        if ($cam_enabled) {
            $fct_capture_cancel = 'capture_' . $cam_module . '_cancel';
            $fct_capture_cancel($_SESSION['asset']);
        }
        // if slide module is enabled
        if ($slide_enabled) {
            $fct_capture_cancel = 'capture_' . $slide_module . '_cancel';
            $fct_capture_cancel($_SESSION['asset']);
        }

        if (status_get() != 'open') {
            $fct_metadata_delete = "session_" . $session_module . "_metadata_delete";
            $fct_metadata_delete();
        }

        status_set('');

        if ($cam_management_enabled) {
            //cam management enabled so try to put camera back in place
            $fct_cam_move = 'cam_' . $cam_management_module . '_move';
            $fct_cam_move($GLOBALS['cam_default_scene']); //set ptz to the initial position
        }
    }

    // if cam module is enabled
    if ($cam_enabled) {
        $fct_capture_features_get = 'capture_' . $cam_module . '_features_get';
        $cam_features = $fct_capture_features_get();
    }
    // if slide module is enabled
    if ($slide_enabled) {
        $fct_capture_features_get = 'capture_' . $slide_module . '_features_get';
        $slide_features = $fct_capture_features_get();
    }

    if ($cam_enabled && in_array('streaming', $cam_features) || $slide_enabled && in_array('streaming', $slide_features)) {
        $streaming_available = true;
    }

    $fct_metadata_get = "session_" . $session_module . "_metadata_get";
    $metadata = $fct_metadata_get();

    $_SESSION['recorder_course'] = $metadata['course_name'];
    $_SESSION['title'] = $metadata['title'];
    $_SESSION['description'] = $metadata['description'];
    $_SESSION['recorder_type'] = $metadata['record_type'];

    require_once template_getpath('record_form.php');
}

//
// Helper functions
//

/**
 * Helper function
 * @return bool true if the user is already logged in; false otherwise
 */
//TODO: The function doesn't check anything for now
function user_logged_in() {
    return isset($_SESSION['recorder_logged']);
}

/**
 * Logs a user in and send him a new form depending on the result
 * On success, record form is showed.
 * On failure, send login form again.
 * 
 * Return wheter the user successfully logged
 */
function user_login($login, $passwd) {
    global $logger;
    global $input;
    global $template_folder;
    global $notice;
    global $redraw;
    global $already_recording;
    global $status;
    global $session_module;
    global $auth_module;

    // 0) Sanity checks
    if (empty($login) || empty($passwd)) {
        $error = template_get_message('Empty_username_password', get_lang());
        //show login form again
        require_once template_getpath('login.php');
        $logger->log(EventType::RECORDER_LOGIN, LogLevel::INFO, 'Login failed, no login/password provided', array('auth'));
        return false;
    }

    // 1) We check the user's identity and retrieve their personal information
    $fct_auth_check = "auth_" . $auth_module . "_check";
    $res = $fct_auth_check($login, $passwd);
    if (!$res) {
        $fct_auth_last_error = "auth_" . $auth_module . "_last_error";
        $error = $fct_auth_last_error();
        require_once template_getpath('login.php');
        $logger->log(EventType::RECORDER_LOGIN, LogLevel::INFO, "Login failed, wrong credentials for login: $login", array('auth'));
        return false;
    }

    // 2) Now we can set the session variables
    $_SESSION['recorder_logged'] = 'LEtimin'; // "Boolean" telling that we're logged in
    $_SESSION['user_login'] = $res['user_login'];
    $_SESSION['user_real_login'] = $res['real_login'];
    $_SESSION['user_full_name'] = $res['full_name'];
    $_SESSION['user_email'] = $res['email'];
    set_lang($input['lang']);
    template_repository_path($template_folder . get_lang());

    // 3) Now we have to check whether or not there is still a recording ongoing.
    // For that, we check the _current_user file. If it exists, it means there is already
    // a recording ongoing. If the user who started the recording is the one trying to log in again,
    // then we display the recording screen again. If not, then we stop the current recording
    // and display the record_form.
    $fct_session_is_locked = "session_" . $session_module . "_is_locked";
    $session_locked = $fct_session_is_locked();
    if ($session_locked) {
        $fct_current_user_get = "session_" . $session_module . "_current_user_get";
        $current_user = $fct_current_user_get();
        if ($_SESSION['user_login'] == $current_user) {
            // We retrieve the recorder page
            log_append('reconnecting', $_SESSION['user_login'] . ' trying to log in but was already using recorder. Retrieving lost session.');

            $redraw = true;
            $status = status_get();
            $already_recording = ($status == 'recording' || $status == 'paused');
            if ($status == 'recording' || $status == 'paused' || $status == 'open')
                view_record_screen(); //go directly to record screen
            else if ($status == 'stopped')
                controller_view_record_submit();
            else
                controller_view_record_form(); //ask metadata again
            die;
        }
        // Case where someone else is trying to connect while someone is already using the recorder
        else {
            // We ask the user if they want to stop the current recording and save it.
            // Various information we want to display
            $fct_current_user_get = "session_" . $session_module . "_current_user_get";
            $current_user = $fct_current_user_get();

            $fct_recstarttime_get = "session_" . $session_module . "_recstarttime_get";
            $recstarttime = explode(PHP_EOL, $fct_recstarttime_get());
            $start_time = $recstarttime[0];
            $course = $recstarttime[1];

            $start_time = trim($start_time);
            $course = trim($course);
            require_once template_getpath('div_error_recorder_in_use.php');
            die;
        }
    }
    $fct_session_lock = "session_" . $session_module . "_lock";
    $res = $fct_session_lock($res['user_login']);

    if (!$res) {
        error_print_message('Could not lock recorder: ' . error_last_message());
        die;
    }

    $logger->log(EventType::RECORDER_LOGIN, LogLevel::INFO, "User $login logged in", array('auth'));
    log_append('login');

    // 4) And finally, we can display the record form
    controller_view_record_form();
}

function reset_cam_position() {
    global $cam_management_enabled;
    global $cam_management_module;
    
    if ($cam_management_enabled) {
       //cam management enabled so try to put camera back in place
       if ($_SESSION['recorder_type'] == 'slide') {
           $fct_cam_move = "cam_" . $cam_management_module . "_move";
           $fct_cam_move($GLOBALS['cam_screen_scene']); //if slide only, record screen as a backup
       } else {
           $fct_cam_move = "cam_" . $cam_management_module . "_move";
           $fct_cam_move($GLOBALS['cam_default_scene']); //set ptz to the initial position
       }
    }
}

function init_capture(&$cam_ok, &$slide_ok) {
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;
    global $logger;
    
    reset_cam_position();

    $cam_pid = 0;
    $slide_pid = 0;

    // if cam module is enabled
    if ($cam_enabled) {
        $fct_capture_init = 'capture_' . $cam_module . '_init';
        $cam_ok = $fct_capture_init($cam_pid, $metadata);
        if ($cam_ok == false) {
            $logger->log(EventType::RECORDER_CAPTURE_INIT, LogLevel::ERROR, "Camera capture module reported init failure", array("view_record_screen"));
            log_append('error', "view_record_screen: Cam capture init failed.");
        }
    }
    // if slide module is enabled
    if ($slide_enabled) {
        $fct_capture_init = 'capture_' . $slide_module . '_init';
        $slide_ok = $fct_capture_init($slide_pid, $metadata);
        if ($slide_ok == false) {
            $logger->log(EventType::RECORDER_CAPTURE_INIT, LogLevel::ERROR, "Slides capture module reported init failure", array("view_record_screen"));
            log_append('error', "view_record_screen: Slides capture init failed.");
        }
    }

    // capture_init is launched in background in order to save time.
    // waits until both processes are finished to continue.
    while (($cam_enabled && is_process_running($cam_pid) ) || ($slide_enabled && is_process_running($slide_pid)))
        sleep(0.5);
    
    $status = status_get();
    if ((!$cam_ok && !$slide_ok) || $status == 'error' || $status == 'launch_failure') {
        status_set('launch_failure');
        $logger->log(EventType::RECORDER_CAPTURE_INIT, LogLevel::CRITICAL, "Capture init scripts finished and recording status is now: \"$status\". (check module log for more info, until we get rid of the bash scripts)", array("init_capture"));
    }
}

/**
 * Displays the screen with "pause/resume", video feedback, etc.
 */
function view_record_screen() {
    global $logger;
    global $session_module;
    global $cam_enabled;
    global $slide_enabled;
    global $cam_management_enabled;
    global $cam_management_module;
    global $php_cli_cmd;
    global $cli_timeout_monitoring;

    $fct_metadata_get = "session_" . $session_module . "_metadata_get";
    $metadata = $fct_metadata_get();
    //get status of recording (from file)
    $status = status_get();
    
    $cam_ok = false;
    $slide_ok = false;
        
    // First of all we init the recorder
    if ($status == '') {
        init_capture($cam_ok, $slide_ok);
    }

    // did something went wrong while initializing the recorders ?
    // if capture module launch failed, reset status and display an error box
    $status = status_get();
    if ((!$cam_ok && !$slide_ok) || $status == 'error' || $status == 'launch_failure') {
        header( "refresh:1;url=index.php" );
        require_once template_getpath('div_error_launch_failure.php');
        return;
    }
        
    // Then we set up some variables
    if ($cam_management_enabled) {
        $fct_cam_posnames_get = "cam_" . $cam_management_module . "_posnames_get";
        $positions = $fct_cam_posnames_get(); // List of camera positions available (used in record_screen.php)
    }
    
    // launches the timeout monitoring process in background
    exec("$php_cli_cmd $cli_timeout_monitoring > /dev/null &", $output, $errno);

    log_append("recording_init", "initiated recording by request (record_type: " .
            $metadata['record_type'] . " - cam module enabled : $cam_enabled - slide module enabled : $slide_enabled");

    
    // view only variables
    //
    // DIsplaying a "disabled" image if one of the two video sources has been disabled
    $has_camera = (strpos($metadata['record_type'], 'cam') !== false);
    $has_slides = (strpos($metadata['record_type'], 'slide') !== false);
    global $cam_management_views_dir;
    global $redraw;
    global $already_recording;
    
    // And finally we display the page
    require_once template_getpath('record_screen.php');
}

/*
 * After stopping the recording
 */

function controller_view_record_submit() {
    global $cam_management_enabled;
    global $cam_management_module;
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;
    global $logger;

    $asset = $_SESSION['asset'];
    if (!$asset) {
        $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::WARNING, 'controller_view_record_submit called without asset', array('controller'));
        die();
    }
    
    $logger->log(EventType::ASSET_RECORD_END, LogLevel::NOTICE, "Record submitted", array('controller'), $asset);

    $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::INFO, 'Stop button pressed', array('controller'), $asset);

    // Stopping (pausing) the recording
    $cam_pid = 0;
    $slide_pid = 0;

    // if cam module is enabled
    if ($cam_enabled) {
        $fct_capture_stop = 'capture_' . $cam_module . '_stop';
        $success = $fct_capture_stop($cam_pid, $_SESSION['asset']);
        if (!$success) {
            $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::ERROR, 'Cam module stopping (start) failed. Trying to continue anyway.', array('controller'), $asset);
        }
    }

    // if slide module is enabled
    if ($slide_enabled) {
        $fct_capture_stop = 'capture_' . $slide_module . '_stop';
        $success = $fct_capture_stop($slide_pid, $_SESSION['asset']);
        if (!$success) {
            $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::ERROR, 'Slides module stopping (start) failed. Trying to continue anyway.', array('controller'), $asset);
        }
    }

    // waits until both processes are finished to continue.
    while ($cam_pid && is_process_running($cam_pid) || $slide_pid && is_process_running($slide_pid))
        sleep(0.5);

    //If any failure happened here, try to continue anyway. We may loose the "stop" point but this is a salvagable situation.

    $_SESSION['recorder_mode'] = 'view_record_submit';

    if ($cam_management_enabled) {
        //cam management enabled so try to put camera back in initial pos
        $fct_cam_move = "cam_" . $cam_management_module . "_move";
        $fct_cam_move($GLOBALS['cam_default_scene']); //set ptz to the initial position
    }
    // And displaying the submit form
    require_once template_getpath('record_submit.php');
}

function controller_view_screenshot_iframe() {
    global $input;

    $source = "cam";
    if ($input['source'] == 'slides')
        $source = 'slides';

    require_once template_getpath('iframe_screenshot.php');
    //require_once 'screenshot.php';
}

function controller_view_screenshot_image() {
    global $input;
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;
    global $session_module;
    global $nopic_file;

    // updates the last_request time
    $fct_last_request_set = 'session_' . $session_module . '_last_request_set';
    $fct_last_request_set();


    if (isset($input['source']) && in_array($input['source'], array('cam', 'slides'))) {
        if ($input['source'] == 'cam' && $cam_enabled) {
            $fct_capture_thumbnail = 'capture_' . $cam_module . '_thumbnail';
            $pic = $fct_capture_thumbnail();
        } else if ($input['source'] == "slides" && $slide_enabled) {
            $fct_capture_thumbnail = 'capture_' . $slide_module . '_thumbnail';
            $pic = $fct_capture_thumbnail();
        }
    }
    if (!isset($pic) || $pic == '') {
        $pic = file_get_contents($nopic_file);
    }

    header('Content-Type: image/jpeg');
    echo $pic;
}

/**
 * Logs the user out, i.e. destroys all the data stored about them
 */
function user_logout() {
    global $session_module;

    close_session();

    $fct_metadata_delete = "session_" . $session_module . "_metadata_delete";
    $fct_metadata_delete();
    // 3) Displaying the logout message

    include_once template_getpath('logout.php');
}

//
// Helper functions
//

/**
 * Returns current chosen language
 * @return string(fr|en) 
 */
function get_lang() {
    if (isset($_SESSION['lang']) && !empty($_SESSION['lang'])) {
        return $_SESSION['lang'];
    } else
        return 'en';
}

/**
 * Sets the current language to the one chosen in parameter
 * @param type $lang 
 */
function set_lang($lang) {
    $_SESSION['lang'] = $lang;
}

/*
 * return cam module current status
 * return null if cam in not enabled
 */
function status_get_cam() {
    global $cam_enabled;
    global $cam_module;
    
    if ($cam_enabled) {
        $fct_status_get = 'capture_' . $cam_module . '_status_get';
        return $fct_status_get();
    }
    return null;
}

/*
 * return slide module current status
 * return null if slide in not enabled
 */
function status_get_slide() {
    global $slide_enabled;
    global $slide_module;
    
    if ($slide_enabled) {
        $fct_status_get = 'capture_' . $slide_module . '_status_get';
        return $fct_status_get();
    }
    return null;
}

/*
 * returns the status of current recording.
 * Status is set in each module. If status is not the same in every modules,
 * returns an "error" status.
 */

function status_get() {
    global $cam_enabled;
    global $slide_enabled;

    $cam_status = status_get_cam();
    $slide_status = status_get_slide();

    if ($slide_enabled && $cam_enabled) {
        if ($cam_status == $slide_status)
            return $cam_status;
        else
            return "error";
    } else if ($slide_enabled) {
        return $slide_status;
    } else if ($cam_enabled) {
        return $cam_status;
    }
}

/*
 * Sets current status for all enabled modules.
 */

function status_set($status) {
    global $cam_enabled;
    global $slide_enabled;
    global $cam_module;
    global $slide_module;

    if ($cam_enabled) {
        $fct_status_set = 'capture_' . $cam_module . '_status_set';
        $fct_status_set($status);
    }

    if ($slide_enabled) {
        $fct_status_set = 'capture_' . $slide_module . '_status_set';
        $fct_status_set($status);
    }
}

function get_asset_name($course_name, $record_date) {
    return $record_date . '_' . $course_name;
}

/* step == "upload" or "local_processing" or "" 
    Empty step will return first found
 *  */
function get_asset_dir($asset, $step = '') {
    if ($step != 'upload' && $step != 'local_processing' && $step != '')
        return false;

    switch ($step) {
        case "upload":
            return get_upload_to_server_dir($asset);
        case "local_processing":
            return get_local_processing_dir($asset);
        default:
            $dir = get_upload_to_server_dir($asset);
            if(!file_exists($dir))
                $dir = get_local_processing_dir($asset);
            
            if(!file_exists($dir))
                return false;
            
            return $dir;
    }
}

function get_local_processing_dir($asset) {
    global $ezrecorder_recorddir;

    return $ezrecorder_recorddir . '/local_processing/' . $asset . '/';
}

function get_upload_to_server_dir($asset) {
    global $ezrecorder_recorddir;

    return $ezrecorder_recorddir . '/upload_to_server/' . $asset . '/';
}

// @returns <slide|cam|camslide>
function get_record_type($cam, $slide) {
    if ($cam && $slide)
        return "camslide";
    elseif ($cam)
        return "cam";
    elseif ($slide)
        return "slide";
    else
        return false;
}
