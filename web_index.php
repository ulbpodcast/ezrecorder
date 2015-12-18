<?php

/*
 * EZCAST EZrecorder
 *
 * Copyright (C) 2014 Université libre de Bruxelles
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

session_start();

error_reporting(E_PARSE | E_ERROR);

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
    if (isset($input['action']) && $input['action'] == 'login') {
        if (!isset($input['login']) || !isset($input['passwd'])) {
            //echo 'Login error: no login/password provided';
            echo template_get_message('Empty_username_password', get_lang());
            die;
        }

        //login and password were given, try to login
        user_login($input['login'], $input['passwd']);
    } else {
        // No login infos were submitted, display login form
        view_login_form();
    }
    die;
}

// Check if the asset is known
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
        recording_submit_infos();
        break;

    // Displays the screenshot iframe for visual feedback
    case 'view_screenshot_iframe':
        view_screenshot_iframe();
        break;

    case 'view_screenshot_image':
        view_screenshot_image();
        break;

    case 'view_login_form':
        view_login_form();
        break;

    case 'view_record_form':
        view_record_form();
        break;

    case 'view_record_submit':
        view_record_submit();
        break;

    // Starts recording
    case 'recording_start':
        recording_start();
        break;

    // Stops recording
    case 'recording_stop':
        recording_stop();
        break;

    // Discards a record
    case 'recording_cancel':
        recording_cancel();
        break;

    case 'recording_pause':
        recording_pause();
        break;

    case 'recording_resume':
        recording_resume();
        break;

    // Case when someone asks to log in while someone else was recording
    case 'recording_force_quit':
        recording_force_quit();
        break;

    case 'camera_move':
        camera_move();
        break;

    case 'logout':
        user_logout();
        break;
    // At this point of the code, we know the user is logged in, but for some reason they didn't provide an action.
    // That means they manually reloaded the page. In this case, we bring them back from where they came.
    default:
        reconnect_active_session();
}

?>
