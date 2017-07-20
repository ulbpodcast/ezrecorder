<?php

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
