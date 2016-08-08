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
 * This is the list of functions that must be implemented for camera management.
 */

include_once "config.inc";

/**
 * @implements
 * Returns an array of the preset positions
 * @return array
 */
function cam_modulename_posnames_get() {}

/**
 * @implements
 * Saves the current position of the camera
 * @param string $name the name of the position to save
 * @return int -1 if an error occured
 */
function cam_modulename_pos_save($name) {}

/**
 * @implements
 * Deletes the given position
 * @param type $name
 */
function cam_modulename_pos_delete($name) {}

/**
 * @implements
 * moves the camera to a given preset position
 * @param type $name the preset position
 */
function cam_modulename_move($name) {}


?>
