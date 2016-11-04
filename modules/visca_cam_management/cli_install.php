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

require_once 'config_sample.inc';

echo PHP_EOL . "***********************************************" . PHP_EOL;
echo           "* Installation of visca_cam_management module *" . PHP_EOL;
echo           "***********************************************" . PHP_EOL;
echo PHP_EOL . "creating config.inc" . PHP_EOL;

copy(dirname(__FILE__)."/config_sample.inc", dirname(__FILE__)."/config.inc");

system("mv $web_basedir/ptzposdir $web_basedir/ptzposdir_old");
system("cp -rp $modules_basedir/visca_cam_management/ptzposdir $web_basedir/ptzposdir");

?>
