<?php

/* ezcast recorder main program (MVC controller)
 *
 */
// Inits
//
include_once 'global_config.inc';
include_once 'common.inc';

session_register_shutdown(); // By default, calls to die() do not write session on shutdown. This makes it so that it does.
session_start();

require_once $auth_lib;
require_once 'lib_error.php';
require_once 'lib_template.php';
require_once 'lib_model.php';

require_once $session_lib;

$input = array_merge($_GET, $_POST);
$template_folder = 'tmpl/';

template_repository_path($template_folder . get_lang());
template_load_dictionnary('translations.xml');


// At this point of the code, we know the user is logged in.
// So now, we must see what action they wanted to perform, and do it.
$action = '';
if(isset($input['action']))
    $action = $input['action'];


if($action == 'recording_force_quit')
{
    controller_recording_force_quit();
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
        user_login($input['login'], $input['passwd']);
    } else {
        // No login infos were submitted, display login form
        controller_view_login_form();
    }
    die;
}

// Check if the asset is known, restore it if needed
// The asset is not known if the session has been force quit,
// if the session has expired or if there is a remote
// control of the session    
$fct_session_is_locked = "session_" . $session_module . "_is_locked";
if (!isset($_SESSION['asset']) && $fct_session_is_locked()) {
    $session = explode(';', file_get_contents($recorder_session));
    if ($_SESSION['user_login'] == $session[1]) {
        $_SESSION['asset'] = $session[0];
        $logger->log(EventType::TEST, LogLevel::INFO, 'Restored asset into session from session file. User: ' . $_SESSION['user_login'], array('controller'));
    } else {
        $logger->log(EventType::TEST, LogLevel::ERROR, 'Could not restore asset for user ' . $_SESSION['user_login'] . '. Current user did not match with the one in session file.', array('controller'));
    }
}


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
        $logger->log(EventType::TEST, LogLevel::DEBUG, 'Index controller: User is logged in but did not provided an action, try to reconnect active session. User: ' . $_SESSION['user_login'], array('controller'));
        reconnect_active_session();
}

