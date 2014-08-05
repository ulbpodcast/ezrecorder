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


/*
 * This is a CLI script that finalizes the recording process 
 * for the enabled modules.
 * 
 */

require_once 'global_config.inc';

require_once $cam_lib;
require_once $slide_lib;

$asset = $argv[1];

system("rm -rf " . "$basedir/var/$asset");

if ($slide_enabled) {
    $fct = 'capture_' . $slide_module . '_finalize';
    $res_slide = $fct($asset);
}

if ($cam_enabled) {
    $fct = 'capture_' . $cam_module . '_finalize';
    $res_cam = $fct($asset);
}
?>
