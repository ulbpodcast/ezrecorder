<?php

/*
 * EZCAST EZrecorder
 *
 * Copyright (C) 2016 Université libre de Bruxelles
 *
 * Written by Michel Jansens <mjansens@ulb.ac.be>
 * 	      Arnaud Wijns <awijns@ulb.ac.be>
 *            Antoine Dewilde
 * UI Design by Julien Di Pietrantonio
 *
 * This software is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this software; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

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

if ($cam_enabled)
    require_once $cam_lib; // defined in global_config
if ($slide_enabled)
    require_once $slide_lib; // defined in global_config
require_once $session_lib;
if ($cam_management_enabled)
    require_once $cam_management_lib;

$input = array_merge($_GET, $_POST);
$template_folder = 'tmpl/';

template_repository_path($template_folder . get_lang());
template_load_dictionnary('translations.xml');

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
    }
}

// At this point of the code, we know the user is logged in.
// So now, we must see what action they wanted to perform, and do it.
$action = '';
if(isset($input['action']))
    $action = $input['action'];

switch ($action) {
    // Someone submitted record information.
    // We save these metadata and display the record_screen
    case 'submit_record_infos':
        controller_recording_submit_infos();
        break;

    // Displays the screenshot iframe for visual feedback
    case 'view_screenshot_iframe':
        controller_view_screenshot_iframe();
        break;

    case 'view_screenshot_image':
        controller_view_screenshot_image();
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
    case 'view_record_submit': //old name for compatibility until all recorders are updated
        controller_view_record_submit();
        break;

    // Stops recording
    case 'publish': //new name
    case 'recording_stop': //old name for compatibility until all recorders are updated
        controller_publish();
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
    case 'recording_force_quit':
        controller_recording_force_quit();
        break;

    case 'camera_move':
        controller_camera_move();
        break;

    case 'logout':
        user_logout();
        break;
    // At this point of the code, we know the user is logged in, but for some reason they didn't provide an action.
    // That means they manually reloaded the page. In this case, we bring them back from where they came.
    default:
        $logger->log(EventType::TEST, LogLevel::DEBUG, 'Index controller: User is logged in but did not provided an action, try to reconnect active session', array('controller'));
        reconnect_active_session();
}

