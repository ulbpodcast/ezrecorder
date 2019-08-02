<?php

require_once 'global_config.inc';
require_once 'lib_various.php';
require_once 'lib_template.php';
require_once 'lib_error.php';
require_once $auth_lib;
require_once __DIR__.'/lib_recording_session.php';

if ($cam_enabled)
    require_once $cam_lib;
if ($slide_enabled)
    require_once $slide_lib;
if ($sound_backup_enabled)
    require_once $sound_backup_lib;
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

    log_append("Reconnect active session");
    $status = status_get();
    //lets check what the 'real' state we're in
    $already_recording = ($status == 'recording' || $status == 'paused');
    try {
        $user_id = RecordingSession::instance()->get_current_user();
    } catch (Exception $e) {
        $user_id = "<no_connected_user>";
    }
    $logger->log(EventType::RECORDER_LOGIN, LogLevel::DEBUG, 'User '.$user_id." reconnected active session. Current recorder status is $status", array(__FUNCTION__));
    if ($already_recording || $status == 'open') {
        //state is one of the recording mode
        $redraw = true;
        view_record_screen();
    } else if ($status == 'stopped') {
        //stopped means we have already clicked on stop
        $ok = controller_view_record_submit();
        if(!$ok) //something was wrong with the submit form
            recording_force_quit();
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
    global $dir_date_format;
    global $logger;
    global $already_recording;

    $user_id = RecordingSession::instance()->get_current_user();

    if($already_recording == false)
    {
        // Sanity checks
        if (!isset($input['title']) || empty($input['title'])) {
            error_print_message(template_get_message('title_not_defined', get_lang()), false);
            return false;
        }

        if (!isset($input['record_type']) || empty($input['record_type'])) {
            error_print_message(template_get_message('type_not_defined', get_lang()), false);
            return false;
        }

        $valid_record_type = validate_allowed_record_type($input['record_type'], get_allowed_record_type());
        if($valid_record_type == false) {
            $logger->log(EventType::RECORDER_USER_SUBMIT_INFO, LogLevel::CRITICAL, "Invalid record type given (".$input['record_type']."), cannot continue", array(__FUNCTION__));
            error_print_message(template_get_message('type_not_valid', get_lang()), false);
            return false;
        }

        $streaming = (isset($input['streaming']) && $input['streaming'] == 'enabled') ? 'true' : 'false';

        $user_id = RecordingSession::instance()->get_current_user();

        // authorization check
        $fct_user_has_course = "auth_" . $auth_module . "_user_has_course";
        if (!$fct_user_has_course($user_id, $input['course'])) {
            error_print_message('You do not have permission to access course ' . $input['course'], false);
            $msg = 'submit_record_infos: ' .$user_id . ' tried to access course ' . $input['course'] . ' without permission';
            log_append('warning', $msg);
            $logger->log(EventType::RECORDER_USER_SUBMIT_INFO, LogLevel::WARNING, $msg, array(__FUNCTION__));
            return false;
        }

        $datetime = date($dir_date_format);
        $course_id = $input['course'];
        $title = trim($input['title']);
        $description = $input['description'];

        $user_extended_info = UserExtendedInfo::get($user_id);
        $full_name = $user_extended_info !== null ? $user_extended_info->full_name : '';

        // Now we create and store the metadata
        $record_meta_assoc = array(
            'course_name' => $course_id,
            'origin' => $classroom,
            'title' => $title,
            'description' => $description,
            'record_type' => $valid_record_type,
            'moderation' => 'true',
            'author' => $full_name,
            'netid' => RecordingSession::instance()->get_current_user(),
            'record_date' => $datetime,
            'streaming' => $streaming,
            'super_highres' => false
        );


        $res = RecordingSession::instance()->metadata_save($record_meta_assoc);

        if ($res == false) {
            error_print_message('submit_record_infos: something went wrong while saving metadata');
            return false;
        }

        log_append("submit info from recording form");

        $asset_name = get_asset_name($record_meta_assoc['course_name'], $record_meta_assoc['record_date']);
        RecordingSession::instance()->init_record($asset_name, $course_id, $valid_record_type);
        insert_form_data($user_id, $course_id, $title, $description, $valid_record_type);
    } else {
        $logger->log(EventType::RECORDER_USER_SUBMIT_INFO, LogLevel::WARNING, "User $user_id tried to re submit infos while we were already recording, ignoring submitted infos...", array(__FUNCTION__));
    }

    // And finally we can display the main screen! This will init the recorders (blocking call)
    view_init_record_screen();
    return true;
}

function insert_form_data($author, $course, $title, $decription, $record_type)
{
    global $database;
    $database->form_data_insert($author, $course, $title, $decription, $record_type);
}

/** Update record_type in metadata file according to allowed cam/slide
 * You can for example use this to disable camera in metadata if camera failed
 * Return false if type was changed
*/
function update_metadata_with_allowed_types($meta_assoc, $allow_cam, $allow_slide, &$new_record_type = null) {
    $record_type = $meta_assoc['record_type'];

    $allowed = RecordType::to_int_for_options($allow_cam, $allow_slide);
    $new_record_type = validate_allowed_record_type($record_type, $allowed);

    if($record_type != $new_record_type) {
        //write new type
        $meta_assoc['record_type'] = $new_record_type;
        return false;
    }
    //else nothing to do, type is ok

    return true;
}

/**
 * Same as update_metadata_with_allowed_types but will open file and write to it if needed
 * Return true on success
 * @param $new_record_type new record type after filtering
 * @param $meta_assoc metadata array that was read from file
 */
function update_metadata_file_with_allowed_types($metadata_file, $allow_cam, $allow_slide, &$new_record_type = null, &$meta_assoc = null) {
    global $logger;

    $meta_assoc = xml_file2assoc_array($metadata_file);
    if($meta_assoc == false) {
        $logger->log(EventType::TEST, LogLevel::ERROR, "Could not get session metadata file: $metadata_file", array(__FUNCTION__));
        return false;
    }

    update_metadata_with_allowed_types($meta_assoc, $allow_cam, $allow_slide, $new_record_type);

    //write back to file if needed
    if($new_record_type != $meta_assoc['record_type']) {
        $ok = xml_assoc_array2file($meta_assoc, $metadata_file);
        if(!$ok) {
            $logger->log(EventType::TEST, LogLevel::ERROR, "Could not write new record type to file ($metadata_file).", array(__FUNCTION__));
            return false;
        }
    }
    return true;
}

/**
 * Recording is ready, start the actual recording
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

    RecordingSession::instance()->start_record();
    $asset = RecordingSession::instance()->get_current_asset();
    $user = RecordingSession::instance()->get_current_user();

    //get current status and check if its compatible with current action
    $status = status_get();
    if ($status != 'open') {
        error_print_message("capture_start: error status ($status): status not 'open'");
        $logger->log(EventType::RECORDER_START, LogLevel::ERROR, "(action recording_start) Could not start recording because of status '$status'", array(__FUNCTION__), $asset);
        return false;
    }

    $asset_dir = get_asset_dir($asset, "local_processing");
    if (!file_exists($asset_dir)) {
        $logger->log(EventType::RECORDER_START, LogLevel::ERROR, "Asset dir $asset_dir not found. Invalid asset? Trying to restore asset from session module.", array(__FUNCTION__), $asset);
        $asset = RecordingSession::instance()->get_current_asset();
        $asset_dir = get_asset_dir($asset, "local_processing");
        if (!file_exists($asset_dir)) {
            $logger->log(EventType::RECORDER_START, LogLevel::ERROR, "Could not start recording because asset dir does not exists: $asset_dir", array(__FUNCTION__), $asset);
            return false;
        }
    }


    $metadata_file = $asset_dir . '/_metadata.xml';
    $meta_assoc = xml_file2assoc_array($metadata_file);
    if($meta_assoc == false) {
        $logger->log(EventType::TEST, LogLevel::ERROR, "Could not get session metadata file: $metadata_file", array(__FUNCTION__));
        return false;
    }

    //we always start every module enabled, so that we have some backup files in case of problems (doesn't depend on the
    // recording format chose by user - cam, slide, camslide)
    if ($cam_enabled) {
        $fct_capture_start = 'capture_' . $cam_module . '_start';
        // ideally, capture_start should return the pid
        // $res_cam = $fct_capture_start($cam_pid);
        $res_cam = $fct_capture_start($asset);
    }

    if ($slide_enabled) {
        $fct_capture_start = 'capture_' . $slide_module . '_start';
        // ideally, capture_start should return the pid
        //     $res_slide = $fct_capture_start($slide_pid);
        $res_slide = $fct_capture_start($asset);
    }

    $requested_record_type = $meta_assoc['record_type'];

    // something went wrong while starting the recording
    if (($cam_enabled && !$res_cam) || ($slide_enabled && !$res_slide)) {
        $logger->log(EventType::RECORDER_START, LogLevel::ERROR, "Error in record start in capture module (cam_enabled:$cam_enabled,res_cam:$res_cam/slide_enabled:$slide_enabled,res_slide:$res_slide)", array(__FUNCTION__), $asset);

        //At this point, if user requested both cam & slide, we continue anyway if at least one started

        $new_type = '';
        update_metadata_file_with_allowed_types($metadata_file, $cam_enabled && $res_cam, $slide_enabled && $res_slide, $new_type, $meta_assoc);
        if($new_type == '') {
            $logger->log(EventType::RECORDER_START, LogLevel::CRITICAL, "All requested modules failed to start (Requested type: $requested_record_type). Cannot continue.", array(__FUNCTION__), $asset);
            return false; // nothing we can do
        } else if($new_type != $meta_assoc['record_type']) {
            $logger->log(EventType::RECORDER_START, LogLevel::CRITICAL, "One requested module failed to start. Continuing with what's left. (Requested type: $requested_record_type, effective type: $new_type)", array(__FUNCTION__), $asset);
        }
    }

    log_append("recording_start", "started recording by user request");
    $logger->log(EventType::ASSET_CREATED, LogLevel::NOTICE,
            "Started recording at user $user request. Requested type: $requested_record_type", array(__FUNCTION__), $asset,
            $user, $requested_record_type, RecordingSession::instance()->get_course_id(), $classroom);

    echo "OK";
    return true;
}

function destroy_session() {
    // releases the recording session

    //re open session to destroy it if needed
    if(session_status() == PHP_SESSION_NONE)
        session_start();

    // And finally, closing the user's session
    session_destroy();
}

function try_stopping()
{
    global $logger;
    global $input;
    global $recorder_monitoring_pid;

    $asset = RecordingSession::instance()->get_current_asset();
    if(!RecordingSession::instance()->get_current_asset() || $asset == "") {
        $logger->log(EventType::RECORDER_PUBLISH, LogLevel::ERROR, "controller_stop_and_publish called without asset in session", array(__FUNCTION__));
        return false;
    }

    // stops the timeout monitoring
    if (file_exists($recorder_monitoring_pid))
        unlink($recorder_monitoring_pid);

    $moderation = 'false';
    if (isset($input['moderation']) && $input['moderation'] == 'true')
        $moderation = 'true';

    // Logging the operation
    $rec_start_time = RecordingSession::instance()->get_rec_start_time();
    $course = RecordingSession::instance()->get_course_id();

    log_append('recording_stop', 'Stopped recording by user request (course ' . $course . ', started on ' . $rec_start_time . ', moderation: ' . $moderation . ')');
    $logger->log(EventType::RECORDER_PUBLISH, LogLevel::NOTICE, 'Publishing recording at user request (course ' . $course . ', started on ' . $rec_start_time . ', moderation: ' . $moderation . ').', array(__FUNCTION__), $asset);

    //get the start time and course from metadata
    $meta_assoc = RecordingSession::instance()->metadata_get();
    if($meta_assoc == false) {
        $logger->log(EventType::RECORDER_PUBLISH, LogLevel::CRITICAL, "Could not get metadata from session for publishing, stopping now", array(__FUNCTION__), $asset);
        destroy_session();
        return false;
    }

    $asset_dir = get_asset_dir($asset, 'local_processing');
    if(!file_exists($asset_dir)) {
        $logger->log(EventType::RECORDER_PUBLISH, LogLevel::CRITICAL, "Trying to publish unknown asset $asset from dir $asset_dir", array(__FUNCTION__), $asset);
        destroy_session();
        return false;
    }

    if($moderation != 'true' && $moderation != 'false') {
        $logger->log(EventType::RECORDER_PUBLISH, LogLevel::ERROR, "Invalid moderation type given: '$moderation'. Defaulting to true.", array(__FUNCTION__), $asset);
        $moderation = true;
    }

    //update session metadata with moderation
    $meta_assoc['moderation'] = $moderation;
    $meta_xml_string = RecordingSession::instance()->metadata_save($meta_assoc);
    if($meta_xml_string == false) {
        $logger->log(EventType::RECORDER_PUBLISH, LogLevel::CRITICAL, "Could not write metadata to session.", array(__FUNCTION__), $asset);
        destroy_session();
        return false;
    }

    //also update metadata file in asset dir
    file_put_contents("$asset_dir/metadata.xml", $meta_xml_string);

    // launches the video processing in background
    start_post_process($asset);

    destroy_session();
    return true;
}

/**
 * Stops the recording and processes it
 */
function controller_stop_and_publish() {
    global $logger;
    global $input;

    $moderation = 'false';
    if (isset($input['moderation']) && $input['moderation'] == 'true')
        $moderation = 'true';


    $ok = try_stopping();
    // releases the recording session. Someone else can now record
    RecordingSession::unlock();
    destroy_session();
    if(!$ok) {
        $logger->log(EventType::RECORDER_PUBLISH, LogLevel::ERROR, "Something went wrong while publishing record, reseting recorder state to avoid locking users in publish menu", array(__FUNCTION__));
        status_set('');
        error_print_message("Failure");
        return;
    }

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
    global $recorder_monitoring_pid;

    $asset = null;
    $metadata = RecordingSession::instance()->metadata_get();
    if($metadata) {
        $asset = get_asset_name($metadata['course_name'], $metadata['record_date']);
        $logger->log(EventType::ASSET_CANCELED, LogLevel::NOTICE, "Record cancelled at user request", array(__FUNCTION__), $asset);
    } else {
        $logger->log(EventType::ASSET_CANCELED, LogLevel::NOTICE, "Cancelling current record, but we could not get asset name. It may be that there is no current recording.", array(__FUNCTION__));
    }

    // stops the timeout monitoring
    if (file_exists($recorder_monitoring_pid))
        unlink($recorder_monitoring_pid);

    // Logging the operation
    $rec_start_time = RecordingSession::instance()->get_rec_start_time();
    $course = RecordingSession::instance()->get_course_id();
    log_append('recording_cancel', 'Deleted recording by user request (course ' . $course . ', started on ' . $rec_start_time . ')');

    // Stopping and releasing the recorder

    $result = cancel_current_record($asset, true);

    // something wrong happened while cancelling the recording
    if (!$result) {
        $logger->log(EventType::ASSET_CANCELED, LogLevel::ERROR, "Something wrong happened while cancelling record. Destroying session and status anway." . error_last_message(), array(__FUNCTION__));
        error_print_message(error_last_message());
    }

    // releases the recording session. Someone else can now record
    RecordingSession::unlock();

    status_set('');

    // Displaying a confirmation message
    require_once template_getpath('div_record_cancelled.php');
}

function cancel_current_record($asset, $reset_cam_position = true) {
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;
    global $session_module;

    $res_cam = true;
    $res_slide = true;

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

    if($reset_cam_position) {
        reset_cam_position();
    }
    //moving asset to trash. Don't forget to clean-up trash folder regularly!
    move_asset($asset, 'trash',true);
    return $res_cam && $res_slide;
}

/* Start post process for asset.
 * Asset must be in local processing directory
 *  */
function start_post_process($asset) {
    global $php_cli_cmd;
    global $cli_post_process;
    global $logger;

    if(!$asset) {
        $logger->log(EventType::RECORDER_STOP, LogLevel::ERROR, "No asset given " . PHP_EOL . print_r(debug_backtrace(), true), array(__FUNCTION__), $asset);
        return false;
    }

    $asset_dir = get_local_processing_dir($asset);
    if(!file_exists($asset_dir)) {
        $logger->log(EventType::RECORDER_STOP, LogLevel::ERROR, "Asset directory does not exists: $asset_dir", array(__FUNCTION__), $asset);
        return false;
    }

    $return_val = 0;
    system("$php_cli_cmd $cli_post_process $asset > $asset_dir/post_process.log 2>&1 &", $return_val);
    if($return_val != 0) {
        $logger->log(EventType::RECORDER_STOP, LogLevel::CRITICAL, "$cli_post_process returned error $return_val", array(__FUNCTION__), $asset);
        return false;
    }

    return true;
}

function stop_current_record($start_post_process = true) {
    global $logger;
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;

    $logger->log(EventType::RECORDER_STOP, LogLevel::DEBUG, "stop_current_record called with post process: $start_post_process.", array(__FUNCTION__));

    if(!RecordingSession::is_locked()) {
        $logger->log(EventType::RECORDER_STOP, LogLevel::ERROR, "stop_current_record was called recorder was not currently locked", array(__FUNCTION__));
        return false;
    }

    $asset = RecordingSession::instance()->get_current_asset();
    if(!$asset || $asset == "") {
        $logger->log(EventType::RECORDER_STOP, LogLevel::ERROR, "No asset found in session", array(__FUNCTION__));
        return false;
    }


    $asset_dir = get_asset_dir($asset, 'local_processing');
    if(!$asset_dir) {
        $logger->log(EventType::RECORDER_STOP, LogLevel::ERROR, "Could not find asset dir for asset $asset", array(__FUNCTION__), $asset);
        return false;
    }

    //write almost final metadata here a first time in asset folder. Note that it may still be overwritten when choosing a moderation type later
    {
        $ok = copy($asset_dir . "/_metadata.xml", $asset_dir . "/metadata.xml");
        if(!$ok) {
            $logger->log(EventType::RECORDER_STOP, LogLevel::ERROR, "Could not create pre-final metadata file. Processing will probably fail later on", array(__FUNCTION__), $asset);
        }
    }

    // Stopping the recording
    $slide_pid = 0;
    $cam_pid = 0;
    // if slide module is enabled
    if ($slide_enabled) {
        $fct_capture_stop = 'capture_' . $slide_module . '_stop';
        $success = $fct_capture_stop($slide_pid, $asset);
        if (!$success) {
            $logger->log(EventType::RECORDER_STOP, LogLevel::ERROR, 'Cam module stopping failed. Trying to continue anyway.', array(__FUNCTION__), $asset);
        }

    }
    // if cam module is enabled
    if ($cam_enabled) {
        $fct_capture_stop = 'capture_' . $cam_module . '_stop';
        $success = $fct_capture_stop($cam_pid, $asset);
        if (!$success) {
            $logger->log(EventType::RECORDER_STOP, LogLevel::ERROR, 'Cam module stopping failed. Trying to continue anyway.', array(__FUNCTION__), $asset);
        }
    }

    // waits until both processes are finished to continue.
    while (is_process_running($cam_pid) || is_process_running($slide_pid))
        sleep(0.5);

    //video stopping is done
    if($start_post_process) {
        $ok = start_post_process($asset);
        if(!$ok) {
            $logger->log(EventType::RECORDER_STOP, LogLevel::ERROR, 'Start post processing failed', array(__FUNCTION__), $asset);
            return false;
        }
    }

    return true;
}

function recording_force_quit()
{
    global $logger;
    global $recorder_monitoring_pid;

    // stops the timeout monitoring
    if (file_exists($recorder_monitoring_pid))
        unlink($recorder_monitoring_pid);

    $asset = RecordingSession::instance()->get_current_asset();

    $logger->log(EventType::ASSET_CANCELED, LogLevel::NOTICE, "Record was forcefully cancelled", array('controller_recording_force_quit'), $asset);
    $logger->log(EventType::RECORDER_FORCE_QUIT, LogLevel::NOTICE, "Record was forcefully cancelled", array('controller_recording_force_quit'), $asset);

    $status = status_get();
    if ($status == '' || $status == 'open') {
        $result = cancel_current_record($asset, false);
        if(!$result) {
            $logger->log(EventType::RECORDER_FORCE_QUIT, LogLevel::ERROR, "Previous record cancelling returned an error. Trying to continue anyway.", array('controller_recording_force_quit'), $asset);
        }
    } else { // a recording is pending (or stopped)
        $result = stop_current_record(true);
        if(!$result) {
            $logger->log(EventType::RECORDER_FORCE_QUIT, LogLevel::ERROR, "Previous record stopping returned an error. Trying to continue anyway.", array('controller_recording_force_quit'), $asset);
        }
    }

    // reinits the recording status
    status_set('');

    // releases the recording session. Someone else can now record
    RecordingSession::unlock();
}

/**
 * Interrupts current recording
 * (example: this is called when someone tries to log in, but someone else was already recording)
 */
function controller_recording_force_quit()
{
    global $notice;
    global $logger;

    if(RecordingSession::is_locked() === false)
        return false; //nothing to do

    $old_asset = RecordingSession::instance()->get_current_asset();
    //$old_user_id = RecordingSession::instance()->get_current_user();

    recording_force_quit();

    template_load_dictionnary('translations.xml');
    $notice = template_get_message('ongoing_record_interrupted_message', get_lang()); // Message to display on top of the page, warning the user that they just stopped someone else's record*/

    if(!isset($_SESSION['user_login']) || $_SESSION['user_login'] == "") {
        echo "No user given";
        return false;
    }

    $new_user = $_SESSION['user_login'];

    try {
        RecordingSession::lock($new_user);
    } catch (Exception $e) {
        error_print_message('lib_model: recording_force_quit: Could not lock recorder: ' . $e->getMessage());
        $logger->log(EventType::RECORDER_FORCE_QUIT, LogLevel::ERROR, "Could not lock recorder: " . $e->getMessage(), array('controller_recording_force_quit'), $old_asset);
        return false;
    }

    //make sure the user is considered as logged
    $_SESSION['recorder_logged'] = true;
    // 4) And finally, we can display the record form
    controller_view_record_form();
    return true;
}

function recording_pause() {
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;

    $asset = RecordingSession::instance()->get_current_asset();

    $res_cam = true;
    $res_slide = true;

    // if cam module is enabled
    if ($cam_enabled) {
        $fct_capture_pause = 'capture_' . $cam_module . '_pause';
        $res_cam = $fct_capture_pause($asset);
    }

    // if slide module is enabled
    if ($slide_enabled) {
        $fct_capture_pause = 'capture_' . $slide_module . '_pause';
        $res_slide = $fct_capture_pause($asset);
    }

    return $res_cam && $res_slide;
}

/*
 * Pauses the current recording
 */
function controller_recording_pause() {
    global $logger;

    $result = recording_pause();
    if(!$result) {
        $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::ERROR, "Pause failed: " . error_last_message(), array('controller_recording_pause'));
        error_print_message(error_last_message());
        die;
    }

    $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::INFO, "Pause recording at user request", array('controller_recording_pause'));
    log_append("paused recording by request");
    echo '';
}

function recording_resume() {
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;

    $asset = RecordingSession::instance()->get_current_asset();

    $res_cam = true;
    $res_slide = true;

    // if cam module is enabled
    if ($cam_enabled) {
        $fct_capture_resume = 'capture_' . $cam_module . '_resume';
        $res_cam = $fct_capture_resume($asset);
    }
    // if slide module is enabled
    if ($slide_enabled) {
        $fct_capture_resume = 'capture_' . $slide_module . '_resume';
        $res_slide = $fct_capture_resume($asset);
    }

    return $res_cam && $res_slide;
}

/*
 * Resumes the current recording
 */

function controller_recording_resume() {
    global $logger;

    $result = recording_resume();
    if(!$result) {
        $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::ERROR, "Resuming failed. " . error_last_message(), array('controller_view_record_form'), $asset);
        error_print_message(error_last_message());
        die;
    }

    log_append("resumed recording by request");
    $logger->log(EventType::RECORDER_PAUSE_RESUME, LogLevel::INFO, "Resumed recording at user request", array('controller_recording_pause'));

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

function get_last_session_data($author)
{
    global $database;
    return $database->form_data_get_data_for_day_of_week($author, time());
}

/**
 * Displays the form people get when they log in (i.e. asking for a title, description, ...)
 */
function controller_view_record_form()
{
    global $input;
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;
    global $auth_module;
    global $streaming_available;
    global $recorder_monitoring_pid;
    global $logger;

    //$logger->log(EventType::TEST, LogLevel::DEBUG, "controller_view_record_form called. Backtrace:\n" . print_r(debug_backtrace(), true), array('controller_view_record_form'));

    // stops the timeout monitoring
    if (file_exists($recorder_monitoring_pid))
        unlink($recorder_monitoring_pid);

    $asset = RecordingSession::instance()->get_current_asset();
    if ($asset !== false && isset($input['reset_player']) && $input['reset_player'] == 'true') {
        $logger->log(EventType::RECORDER_CANCEL, LogLevel::NOTICE, "Input has 'reset_player' argument, cancelling record", array(__FUNCTION__), $asset);
        $result = cancel_current_record($asset, true);
        if(!$result) {
            $logger->log(EventType::RECORDER_CANCEL, LogLevel::ERROR, "Something wrong happened while cancelling current record. Trying to continue anyway.", array(__FUNCTION__), $asset);
        }

        status_set('');
    }
    file_put_contents('/home/arwillame/test/txt2.txt','YEAH'.PHP_EOL,FILE_APPEND);

    // if cam module is enabled
    if ($cam_enabled) {
      file_put_contents('/home/arwillame/test/txt2.txt','1'.PHP_EOL,FILE_APPEND);

        $fct_capture_features_get = 'capture_' . $cam_module . '_features_get';
        $cam_features = $fct_capture_features_get();
    }
    // if slide module is enabled
    if ($slide_enabled) {
      file_put_contents('/home/arwillame/test/txt2.txt','2'.PHP_EOL,FILE_APPEND);

        $fct_capture_features_get = 'capture_' . $slide_module . '_features_get';
        $slide_features = $fct_capture_features_get();
    }
    file_put_contents('/home/arwillame/test/txt2.txt','3'.PHP_EOL,FILE_APPEND);

    $cam_stream_ok = !$cam_enabled || in_array('streaming', $cam_features);
    $slide_stream_ok = !$slide_enabled || in_array('streaming', $slide_features);
    if ($cam_stream_ok && $slide_stream_ok) {
        $streaming_available = true;
    }

    $current_user = RecordingSession::instance()->get_current_user();
    $last_session_data = get_last_session_data($current_user);

    //form data
    $prefill_course = "";
    $prefill_title = "";
    $prefill_description = "";
    $prefill_type = "slide";

    if($last_session_data) {
        //Pre fill with previous data
        $prefill_course = $last_session_data->course;
        $prefill_title = $last_session_data->title;
        $prefill_description = $last_session_data->description;
        $prefill_type = $last_session_data->record_type; //Improvement: what if the type is not supported anymore on this recorder?
    } else {
        //set a default record type
        if ($cam_enabled && $slide_enabled)
            $prefill_type = 'camslide';
        elseif ($cam_enabled)
            $prefill_type = 'cam';
        else if($slide_enabled)
            $prefill_type = 'slide';
    }

    // Retrieving the course list (to display in the web interface)
    $fct_user_courselist_get = "auth_" . $auth_module . "_user_courselist_get";
    $courselist = $fct_user_courselist_get($current_user);

    global $notice; // Possible errors that occurred at previous steps.
    require_once template_getpath('record_form.php');
}

//
// Helper functions
//

/**
 * Helper function
 * @return bool true if the user is already logged in; false otherwise
 */
function user_logged_in() {
    global $logger;

    if(!isset($_SESSION['recorder_logged'])) {
        return false;
    }

    //Session should always be locked when a user is logged in
    if(!RecordingSession::is_locked()) {
        $logger->log(EventType::RECORDER_LOGIN, LogLevel::DEBUG, "User is logged in but session is not locked", array(__FUNCTION__));
        return false;
    }

    if (!RecordingSession::can_access_lock($_SESSION['user_login'])) {
        $logger->log(EventType::RECORDER_LOGIN, LogLevel::WARNING, "User is logged in but session is not locked by this user", array(__FUNCTION__));
        return false;
    }

    return true;
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
    global $session;
    global $session_class;

    // 0) Sanity checks
    if (empty($login) || empty($passwd)) {
        $error = template_get_message('Empty_username_password', get_lang());
        //show login form again
        require_once template_getpath('login.php');
        $logger->log(EventType::RECORDER_LOGIN, LogLevel::INFO, 'Login failed, no login/password provided', array(__FUNCTION__));
        return false;
    }

    // 1) We check the user's identity and retrieve their personal information
    $fct_auth_check = "auth_" . $auth_module . "_check";
    $res = $fct_auth_check($login, $passwd);
    if (!$res) {
        $fct_auth_last_error = "auth_" . $auth_module . "_last_error";
        $error = $fct_auth_last_error();
        require_once template_getpath('login.php');
        $logger->log(EventType::RECORDER_LOGIN, LogLevel::INFO, "Login failed, wrong credentials for login: $login", array(__FUNCTION__));
        return false;
    }

    $user = $res['user_login'];
    $_SESSION['user_login'] = $user;

    // 3) Now we have to check whether or not there is still a recording ongoing.
    //  If the user who started the recording is the one trying to log in again,
    // then we display the recording screen again. If not, then we stop the current recording
    // and display the record_form.
    if (RecordingSession::is_locked()) {

        //change language if specified an init rempository path
        if(isset($input['lang']))
            set_lang($input['lang']);

        template_repository_path($template_folder . get_lang());

        if (RecordingSession::instance()->can_access_lock($user)) {
            // We retrieve the recorder page
            log_append('reconnecting', $user . ' trying to log in but was already using recorder. Retrieving lost session.');
            $logger->log(EventType::RECORDER_LOGIN, LogLevel::INFO, $user . ' trying to log in but was already using recorder. Retrieving lost session.', array(__FUNCTION__));

            $_SESSION['recorder_logged'] = true; // "Boolean" telling that we're logged in

            $redraw = true;
            $status = status_get();
            $already_recording = ($status == 'recording' || $status == 'paused');
            if ($status == 'recording' || $status == 'paused' || $status == 'open')
                view_record_screen(); //go directly to record screen
            else if ($status == 'stopped')
                controller_stop_and_view_record_submit();
            else
                controller_view_record_form(); //ask metadata again
            return true;

        } else {
            $logger->log(EventType::RECORDER_LOGIN, LogLevel::WARNING, "User " . $user . " tried to login but session was locked, asking him if he wants to interrupt the current record", array(__FUNCTION__));

            // We ask the user if they want to stop the current recording and save it.
            //
            // Various information we want to display
            $current_user =  RecordingSession::instance()->get_current_user();
            $start_time_ts = RecordingSession::instance()->get_init_time();
            $start_time = $start_time_ts != 0 ? date('Y-m-d H:i:s', $start_time_ts) : "NA";
            $course_id = RecordingSession::instance()->get_course_id();
            $course = $course_id != "" ? $course_id : "NA";

            require_once template_getpath('div_error_recorder_in_use.php');
            return false;
        }
    }

    try {
        $admin_id = $res['real_login'];
        RecordingSession::lock($user, $admin_id);

        $_SESSION['recorder_logged'] = true; // "Boolean" telling that we're logged in
        $_SESSION['user_login'] = $user;
        UserExtendedInfo::write($res['user_login'], $res['full_name'], $res['email']);

    } catch (Exception $e) {
        $logger->log(EventType::RECORDER_LOGIN, LogLevel::ERROR, "Could not lock recorder for user $user. Exception message: " . $e->getMessage(), array(__FUNCTION__));
        error_print_message('Could not lock recorder: ' . $e->getMessage());
        return false;
    }

    if(isset($input['lang']))
        set_lang($input['lang']);
    template_repository_path($template_folder . get_lang());

    $logger->log(EventType::RECORDER_LOGIN, LogLevel::INFO, "User $login logged in", array(__FUNCTION__));
    log_append('login');

    // 4) And finally, we can display the record form
    controller_view_record_form();
}

function reset_cam_position() {
    global $cam_management_enabled;
    global $cam_management_module;

    if ($cam_management_enabled) {
       //cam management enabled so try to put camera back in place
       if (RecordingSession::instance()->get_record_type() == 'slide') {
           $fct_cam_move = "cam_" . $cam_management_module . "_move";
           $fct_cam_move($GLOBALS['cam_screen_scene']); //if slide only, record screen as a backup
       } else {
           $fct_cam_move = "cam_" . $cam_management_module . "_move";
           $fct_cam_move($GLOBALS['cam_default_scene']); //set ptz to the initial position
       }
    }
}

/* Various environment checks
 * Returns true if all ok, else returns false and a description of the problem in $problem_str
*/
function validate_environment(&$problem_str) {
    $steps = array('get_upload_ok_dir', 'get_upload_to_server_dir', 'get_local_processing_dir');
    foreach($steps as $step) {
        $dir = call_user_func($step);
        if(!is_writable($dir)) {
            $problem_str = "Directory for step $step is not writable. ($dir)";
            return false;
        }
    }

    //what else ?

    return true;
}

function init_capture(&$metadata, &$cam_ok, &$slide_ok) {
    global $cam_enabled;
    global $cam_module;
    global $slide_enabled;
    global $slide_module;
    global $sound_backup_enabled;
    global $sound_backup_module;
    global $logger;
    file_put_contents('/home/arwillame/test/txt2.txt','4'.PHP_EOL,FILE_APPEND);

    $error = "";
    $env_ok = validate_environment($error);
    if(!$env_ok) {
        $logger->log(EventType::RECORDER_CAPTURE_INIT, LogLevel::ALERT, "Environment validation failed with error: $error", array(__FUNCTION__));
        return false;
    }
    file_put_contents('/home/arwillame/test/txt2.txt','5'.PHP_EOL,FILE_APPEND);

    //create asset name
    $asset = get_asset_name($metadata['course_name'], $metadata['record_date']);
    if($asset == '') {
        $logger->log(EventType::RECORDER_CAPTURE_INIT, LogLevel::CRITICAL, "Couldn't get asset name from metadata, metadata probably invalid.", array(__FUNCTION__));
        return false;
    }

    $asset_dir = get_asset_dir($asset, "local_processing");
    file_put_contents('/home/arwillame/test/txt2.txt','6'.PHP_EOL,FILE_APPEND);

    //create asset dir
    if(!file_exists($asset_dir)) {
        $ok = mkdir($asset_dir, 0777, true); //mode is not set ??
        if($ok) {
            chmod($asset_dir, 0777);
            //set default permissions for this dir
            $return_val = 0;
			if (strtoupper(php_uname('s'))==='LINUX'){
				system("chmod a=rwx  $asset_dir", $return_val);
			}else{
				system("chmod +a \"group:everyone allow list,add_file,search,add_subdirectory,delete_child,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit\" $asset_dir", $return_val);
			}
            if($return_val != 0) {
                $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::ERROR, "Failed to set folder permissions for $asset_dir", array(__FUNCTION__), $asset);
            }
        }  else {
            $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::WARNING, "Failed to create dir $asset_dir", array(__FUNCTION__), $asset);
            return false;
        }
    }
    file_put_contents('/home/arwillame/test/txt2.txt','7'.PHP_EOL,FILE_APPEND);

    // saves recording metadata as xml file
    $success = xml_assoc_array2file($metadata, "$asset_dir/_metadata.xml");
    if(!$success) {
        $logger->log(EventType::RECORDER_FFMPEG_INIT, LogLevel::CRITICAL, "Can't init because _metadata writing failed", array(__FUNCTION__), $asset);
        return false;
    }

    $cam_pid = 0;
    $slide_pid = 0;
    file_put_contents('/home/arwillame/test/txt2.txt','8'.PHP_EOL,FILE_APPEND);

    // inits are is started in background to allow both to prepare at the same time
    // init cam module if enabled
    if ($cam_enabled) {
      file_put_contents('/home/arwillame/test/txt2.txt','9 : '.$slide_enabled.PHP_EOL,FILE_APPEND);

        reset_cam_position();
        $fct_capture_init = 'capture_' . $cam_module . '_init';
        $cam_ok = $fct_capture_init($cam_pid, $metadata, $asset);
        if ($cam_ok == false) {
            $logger->log(EventType::RECORDER_CAPTURE_INIT, LogLevel::ERROR, "Camera capture module reported init failure", array(__FUNCTION__), $asset);
            log_append('error', "view_record_screen: Cam capture init failed.");
        }
    }
    // init slide module if enabled
    if ($slide_enabled) {
      file_put_contents('/home/arwillame/test/txt2.txt','10'.PHP_EOL,FILE_APPEND);

        $fct_capture_init = 'capture_' . $slide_module . '_init';
        $slide_ok = $fct_capture_init($slide_pid, $metadata, $asset);
        if ($slide_ok == false) {
            $logger->log(EventType::RECORDER_CAPTURE_INIT, LogLevel::ERROR, "Slides capture module reported init failure", array(__FUNCTION__), $asset);
            log_append('error', "view_record_screen: Slides capture init failed.");
        }
    }

    if($sound_backup_enabled) {
        $fct = 'capture_' . $sound_backup_module . '_init';
        $pid = 0;
        $success = $fct($pid, $metadata, $asset);
        if(!$success) {
            $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::ERROR, "Failed to start sound_backup", array(basename(__FILE__)), $asset);
        } else if ($slide_pid == 0) {
            $logger->log(EventType::RECORDER_CAPTURE_POST_PROCESSING, LogLevel::WARNING, "sound backup module was successfully started", array(basename(__FILE__)), $asset);
        }
    }

    // waits until both processes are finished to continue.
    while (($cam_enabled && is_process_running($cam_pid) ) || ($slide_enabled && is_process_running($slide_pid)))
        sleep(0.5);

    //inits scripts will set at status when they are done, check the result here
    $cam_status = '';
    $slide_status = '';
    $status = status_get($cam_status, $slide_status);
    if ((!$cam_ok && !$slide_ok) || $status == 'error' || $status == 'launch_failure') {
        status_set('launch_failure');
        $logger->log(EventType::RECORDER_CAPTURE_INIT, LogLevel::CRITICAL, "Capture init scripts finished and recording status is now: \"$status\". Cam status: $cam_status. Slide status: $slide_status. (check logs in asset directory for more info, until we get rid of the bash scripts)", array(__FUNCTION__), $asset);
        return false;
    } else {
        $logger->log(EventType::RECORDER_CAPTURE_INIT, LogLevel::DEBUG, "Capture init scripts finished and recording status is now: \"$status\".", array(__FUNCTION__), $asset);
    }
    return true;
}

function view_init_record_screen() {
    global $session_module;
    global $logger;
    global $php_cli_cmd;
    global $cli_timeout_monitoring;
    global $cam_enabled;
    global $slide_enabled;

    $metadata = RecordingSession::instance()->metadata_get();
    if($metadata == false) {
        $logger->log(EventType::RECORDER_METADATA, LogLevel::WARNING, 'view_record_screen called but couldnt get metadata. Return to submit form', array(__FUNCTION__));
        controller_view_record_form();
        return;
    }

    //get status of recording (from file)
    $status = status_get();

    if($status == 'init') {
        $logger->log(EventType::TEST, LogLevel::INFO, 'view_record_screen, capture is currently initializing. Was form sent a second time?', array(__FUNCTION__));

    }

    $cam_ok = true;
    $slide_ok = true;

    // First of all we init the recorder
    if ($status != 'open' && $status != 'recording') {
        $cam_ok = false;
        $slide_ok = false;
        $ok = init_capture($metadata, $cam_ok, $slide_ok);
        if(!$ok) {
            $logger->log(EventType::TEST, LogLevel::CRITICAL, 'init_capture(...) failed', array(__FUNCTION__));
            header( "refresh:1;url=index.php" );
            require_once template_getpath('div_error_launch_failure.php');
            return;
        }
    } else {
        $logger->log(EventType::TEST, LogLevel::INFO, 'view_record_screen, capture was already initiliazed', array(__FUNCTION__));
    }

    // Update status, did something went wrong while initializing the recorders ?
    // if capture module launch failed, reset status and display an error box
    $status = status_get();
    if ((!$cam_ok && !$slide_ok) || $status == 'error' || $status == 'launch_failure') {
        header( "refresh:1;url=index.php" );
        require_once template_getpath('div_error_launch_failure.php');
        return;
    }

    // launches the timeout monitoring process in background
    $errno = 0;
    $cmd = "$php_cli_cmd $cli_timeout_monitoring > /dev/null 2>&1 &";
    system($cmd, $errno);
    if($errno != 0) {
        $logger->log(EventType::RECORDER_TIMEOUT_MONITORING, LogLevel::CRITICAL, "Failed to start timeout monitoring. Return val: $errno. Cmd was $cmd", array(__FUNCTION__));
    }

    log_append("recording_init", "initiated recording by request (record_type: " .
            $metadata['record_type'] . " - cam module enabled : $cam_enabled - slide module enabled : $slide_enabled");

    view_record_screen();
}

/**
 * Displays the screen with "pause/resume", video feedback, etc.
 */
function view_record_screen() {
    global $logger;
    global $session_module;
    global $cam_management_enabled;
    global $cam_management_module;

    $metadata = RecordingSession::instance()->metadata_get();
    if($metadata == false) {
        $logger->log(EventType::RECORDER_METADATA, LogLevel::WARNING, 'view_record_screen called but couldnt get metadata. Return to submit form', array(__FUNCTION__));
        controller_view_record_form();
        return;
    }

    //get status of recording (from file)
    $status = status_get();

    // Then we set up some variables
    if ($cam_management_enabled) {
        $fct_cam_posnames_get = "cam_" . $cam_management_module . "_posnames_get";
        $positions = $fct_cam_posnames_get(); // List of camera positions available (used in record_screen.php)
    }

    // view only variables
    //
    // DIsplaying a "disabled" image if one of the two video sources has been disabled
    $has_camera = (strpos($metadata['record_type'], 'cam') !== false);
    $has_slides = (strpos($metadata['record_type'], 'slide') !== false);
    global $cam_management_views_dir;
    global $redraw;
    global $already_recording;
    global $enable_vu_meter;

    $record_type = RecordingSession::instance()->get_record_type();

    // And finally we display the page
    require_once template_getpath('record_screen.php');
}

/*
 * After stopping the recording
 */

function controller_stop_and_view_record_submit() {
    global $logger;

    $asset = RecordingSession::instance()->get_current_asset();
    if (!$asset) {
        $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::WARNING, 'controller_stop_and_view_record_submit called without asset', array(__FUNCTION__));
        die();
    }

    $logger->log(EventType::ASSET_RECORD_END, LogLevel::NOTICE, "Record submitted", array(__FUNCTION__), $asset);
    $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::INFO, 'Stop button pressed', array(__FUNCTION__), $asset);

    //If any failure happened here, try to continue anyway. We may loose the "stop" point but this is a salvagable situation.
    $success = stop_current_record(false);
    if(!$success) {
        $logger->log(EventType::RECORDER_PUSH_STOP, LogLevel::ERROR, 'Stopping failed. Trying to continue anyway.', array(__FUNCTION__), $asset);
    }

    controller_view_record_submit();
}

function controller_view_record_submit() {
    $asset = RecordingSession::instance()->get_current_asset();
    if($asset === false)
        return false;

    // And displaying the submit form
    require_once template_getpath('record_submit.php');
    return true;
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
    RecordingSession::instance()->set_last_request();

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
    RecordingSession::metadata_delete();
    RecordingSession::unlock();
    destroy_session();

    // Displaying the logout message
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
    if(RecordingSession::instance() !== null)
        return RecordingSession::instance()->get_lang();
    else
        return 'en';
}

/**
 * Sets the current language to the one chosen in parameter
 * @param type $lang
 */
function set_lang($lang) {
    if(RecordingSession::instance() !== null)
        RecordingSession::instance()->set_lang($lang);
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
function status_get(&$cam_status = '', &$slide_status = '') {
    global $slide_enabled;
    global $cam_enabled;

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

function controller_view_sound_status() {
    global $enable_vu_meter;
    global $sound_detect;

    if(!$enable_vu_meter)
        return false;

    $asset = RecordingSession::instance()->get_current_asset();
    $sound_info = $sound_detect->mean_volume_get($asset);
    if($sound_info === false) {
        echo "Couldn't get sound info";
        http_response_code(500);
        return;
    }

    echo $sound_info;
}

function sound_info_available() {
    global $enable_vu_meter;
    global $sound_detect;

    if(!$enable_vu_meter)
        return false;

    return $sound_detect->available();
}

function debug_checks() {

}

class UserExtendedInfo
{
    public $full_name = null;
    public $email = null;

    function __construct($name, $email)
    {
        $this->full_name = $name;
        $this->email = $email;
    }

    //write given info to DB
    static public function write($user_id, $full_name, $email)
    {
         global $database;
         $database->user_info_write($user_id, $full_name, $email);
    }

    //get a UserExtendedInfo object for user_id, or null if not found
    static public function get($user_id)
    {
        global $database;
        $full_name = null;
        $email = null;
        $ok = $database->user_info_get($user_id, $full_name, $email);
        if(!$ok)
            return null;

        return new UserExtendedInfo($full_name, $email);
    }
}
