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
 * This is the list of functions that must be implemented for authentication module.
 */
require "config.inc";

/**
 * @implements
 * Checks that a user has the right to log in. 
 * @param type $login user to log in
 * @param type $passwd user's password
 * @return false | <assoc_array> Array containing the user_login, real_login, full_name and email
 *         assoc_array['user_login'] : the user that manages the recording
 *         assoc_array['real_login'] : the user that logged in (admin for instance)
 *         assoc_array['full_name'] : First name and last name of the user_login
 *         assoc_array['email'] : email address of the user_login
 */
function auth_modulename_check($login, $passwd){};


/**
 * @implements
 * checks if user is a recorder administrator
 * @param string $login
 * @return bool
 */
function auth_modulename_user_is_admin($login) {};

/**
 * @implements
 * returns the list of all courses sorted by user
 * @global type $courselist_file
 * @return <assoc_array> the list of all courses sorted by user 
 */
function auth_modulename_courselist_get() {};

/**
 * @implements
 * returns the list of all courses for a given user
 * @global type $courselist_file
 * @param type $user
 * @return <assoc_array> the list of all courses for the given user
 */
function auth_modulename_user_courselist_get($user) {};

/**
 * @implements
 * determines whether or not the user has access to a course
 * @global type $courselist_file
 * @param type $user
 * @param type $course_name
 * @return type
 */
function auth_modulename_user_has_course($user, $course_name) {};

?>
