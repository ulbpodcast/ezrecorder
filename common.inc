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

/* This file contains global objects, functions or defines */


include_once('global_config.inc');
include_once('logger.php');
include_once('external_products/Psr/Log/LogLevel.php');
use Psr\Log\LogLevel;

// Logger
if($debugMode) {
  $logLevelThreshold = LogLevel::DEBUG;
} else {
  $logLevelThreshold = LogLevel::INFO;
}
//this is required by the logger
date_default_timezone_set($recorder_timezone);
$logger = new Logger($databaseFile, $classroom, $logLevelThreshold);

if($debugMode) {
  ini_set("display_errors", 1);
  error_reporting(E_ALL); 
}