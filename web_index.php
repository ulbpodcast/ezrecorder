<?php

//htdocs version warning, will warn if the htdocs were updated in the code but not applied to the web server.
if(isset($htdocs_version)) { //if this is set, this file was included from the web space
    $web_version = $htdocs_version;
    require_once(__DIR__ . "/htdocs/htdocs_version.php");
    $source_version = $htdocs_version;
    if($web_version != $source_version)
        trigger_error("Documents in web space are not up to date, please run cli_install_htdocs.php", E_USER_ERROR);
}

/* ezcast recorder main program (MVC controller)
 *
 */
// Inits
//
include_once 'global_config.inc';
require_once 'common.inc';

session_register_shutdown(); // By default, calls to die() do not write session on shutdown. This makes it so that it does.
session_start();

require_once $auth_lib;
require_once 'lib_error.php';
require_once 'lib_template.php';
require_once 'lib_model.php';

require_once __DIR__.'/lib_recording_session.php';

$input = array_merge($_GET, $_POST);
$template_folder = 'tmpl/';

template_repository_path($template_folder . get_lang());
template_load_dictionnary('translations.xml');

RecordingSession::restore_session_if_any();

// At this point of the code, we know the user is logged in.
// So now, we must see what action they wanted to perform, and do it.
$action = '';
if(isset($input['action']))
    $action = $input['action'];


if($action == 'recording_force_quit')
{
    controller_recording_force_quit();
    close_session();
    die();
}

//
// Controller
//
// Login/logout
// If we're not logged in, we try to log in or display the login form

if (!user_logged_in()) {
    
    // If an "action" was given, it means we've already submitted the login form
    // So all we want to do is check whether there is still a "forgotten" recording
    // and if not, log the user in
    if (isset($input['action']) && $input['action'] == 'login' && isset($input['login']) && isset($input['passwd'])) {
        //login and password were given, try to login
        $ok = user_login($input['login'], $input['passwd']);
    } else {
        // No login infos were submitted, display login form
        controller_view_login_form();
    }
    session_write_close();
    die;
}

session_write_close(); //we only use session for login processing
   
global $service; //true if we're currently running a service. 
$service = false;
    
switch ($action) {
    // Someone submitted record information.
    // We save these metadata and display the record_screen
    // This action is blocking until capture is initialized
    case 'submit_record_infos':
        controller_recording_submit_infos();
        break;

    // Displays the screenshot iframe for visual feedback
    case 'view_screenshot_iframe':
        global $service;
        $service = true;
        controller_view_screenshot_iframe();
        break;

    case 'view_screenshot_image':
        global $service;
        $service = true;
        controller_view_screenshot_image();
        break;

    case 'view_sound_status':
        global $service;
        $service = true;
        controller_view_sound_status();
        break;
    
    case 'view_login_form':
        controller_view_login_form();
        break;

    case 'view_record_form':
        controller_view_record_form();
        break;

    // Starts recording
    case 'recording_start':
        controller_recording_start();
        break;

    // view submit menu, called when the user press the stop button
    case 'view_press_stop':
        controller_stop_and_view_record_submit();
        break;

    // Stops recording
    case 'stop_and_publish': //new name
        controller_stop_and_publish();
        break;

    // Discards a record
    case 'recording_cancel':
        controller_recording_cancel();
        break;

    case 'recording_pause':
        controller_recording_pause();
        break;

    case 'recording_resume':
        controller_recording_resume();
        break;

    // Case when someone asks to log in while someone else was recording
    /*
    case 'recording_force_quit':
        controller_recording_force_quit();
        break;
*/
    case 'camera_move':
        controller_camera_move();
        break;

    case 'logout':
        user_logout();
        break;
    // At this point of the code, we know the user is logged in, but for some reason they didn't provide an action.
    // That means they manually reloaded the page. In this case, we bring them back from where they came.
    default:
        $user_id = RecordingSession::instance()->get_current_user();
        $logger->log(EventType::TEST, LogLevel::DEBUG, 'Index controller: User is logged in but did not provided an action, try to reconnect active session. User: ' . $user_id, array('controller'));
        reconnect_active_session();
}

