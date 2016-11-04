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

/**
 * Library containing various functions related to user authentification.
 */
require "config.inc";

/**
 * @implements
 * Checks that a user has the right to log in. This function uses a SHA1-encrypted htpasswd
 * @param type $login
 * @param type $passwd 
 * @return false|assoc_array Array containing the user_login, real_login, full_name and email
 */
function auth_file_check($login, $passwd) {
    global $htpasswd_file;
    global $admin_file;
    global $courselist_file;
    // Sanity check
    if (!file_exists($htpasswd_file) || !is_readable($htpasswd_file)) {
        auth_file_last_error('htpasswd not found or not readable');
        return false;
    }

    // We could get a login of type "real_login/user_login"
    // In that case, we split it into two different variables
    if (strpos($login,'/') !== false) {
        list($real_login, $user_login) = explode('/', $login);
    } else {
        $real_login = $login;
        $user_login = $login;
    }
    
    // If there was a real_login and a user_login, that means somone is trying to take another's identity.
    // The only persons allowed to do that are admins so we check if $real_login is in admin list
    require_once $admin_file;
    if (($real_login != $user_login) && (!isset($admin[$real_login]) || !$admin[$real_login])) {
        auth_file_last_error($real_login . ' is trying to take ' . $user_login . '\'s rights without being in admin list.');
        return false;
    }


    // 1) First we check that the user is in the .htpasswd file and has the right login/password combination
    // For that, we read the entire .htpasswd
    $htpasswd = file($htpasswd_file);
    foreach ($htpasswd as $line) {
        // The information is stored as login:password in .htaccess, so it's easy
        // to retrieve using an explode
        list($flogin, $fpasswd) = explode(':', $line);
        if ($flogin == $real_login) {
            // We crypt the user-provided password to check if it's equal to the one
            // stored in the htpasswd
            $salt = substr($fpasswd, 0, 2);
            $cpasswd = crypt($passwd, $salt);
            $fpasswd = rtrim($fpasswd);

            // If not, that means the user has entered a wrong password. We don't log them in.
            if ($cpasswd != $fpasswd) {
                auth_file_last_error('Wrong password');
                return false;
            }
            // In this case, we retrieve their information from pwfile, and return them.
            else {
                require_once $courselist_file;
                $ret = $users[$user_login]; // We return all the information from courselist.php ...
                $ret['user_login'] = $user_login;
                $ret['real_login'] = $real_login; // ... To which we add the real_login and user_login
                $ret['full_name'] = $users[$user_login]['full_name'];
                $ret['email'] = $users[$user_login]['email'];
                return $ret;
            }
        }
    }

    // If we arrive here, that means there is no user with the login provided
    auth_file_last_error('No user with login ' . $login . ' found');
    return false;
}

/**
 * set/get last error for checkauth
 * @staticvar string $last_error
 * @param string $msg
 * @return string
 */
function auth_file_last_error($msg = "") {
    static $last_error = "";

    if ($msg == "")
        return $last_error;
    else {
        $last_error = $msg;
        return true;
    }
}

/**
 * @implements
 * check if user is a recorder administrator
 * @param string $login
 * @return bool
 */
function auth_file_user_is_admin($login) {
    global $admin_file;
    include $admin_file; //file containing an assoc array of admin users
    print $admin[$login];
    if (isset($admin[$login]))
        return true;
    else
        return false;
}

/**
 * @implements
 * returns the list of all courses sorted by user
 * @global type $courselist_file
 * @return <assoc_array> the list of all courses sorted by user 
 */
function auth_file_courselist_get() {
    global $courselist_file;
    global $logger;
    
    include $courselist_file;
    
    if(isset($course))
        return $course;
    else {
        $logger->log(EventType::RECORDER_LOGIN, LogLevel::WARNING, "Could not get any course from file $courselist_file. Did the server pushed the course list?", array('auth_file_courselist_get'));
        return false;
    }
}

/**
 * @implements
 * returns the list of all courses for a given user
 * @global type $courselist_file
 * @param type $user
 * @return <assoc_array> the list of all courses for the given user
 */
function auth_file_user_courselist_get($user) {
    global $courselist_file;
    global $logger;
    
    include $courselist_file;
    
    if(!isset($course)) {
        $logger->log(EventType::RECORDER_LOGIN, LogLevel::WARNING, "Could not get any course from file $courselist_file. Did the server pushed the course list?", array('auth_file_user_courselist_get'));
        return array();
    }
            
    if(isset($course[$user]))
        return $course[$user];
    
    return array();    
}

/**
 * @implements
 * determines whether or not the user has access to a course
 * @global type $courselist_file
 * @param type $user
 * @param type $course_name
 * @return type
 */
function auth_file_user_has_course($user, $course_name) {
    global $logger;
    global $courselist_file;
    
    include $courselist_file;
    
    if(!isset($course)) {
        $logger->log(EventType::RECORDER_LOGIN, LogLevel::WARNING, "Could not get any course from file $courselist_file. Did the server pushed the course list?", array('auth_file_user_courselist_get'));
        return array();
    }
    
    if(isset($course[$user]))
        return (array_key_exists($course_name, $course[$user]));
    
    return false;
}
