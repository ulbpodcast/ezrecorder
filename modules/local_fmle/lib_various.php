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

function image_resize($input, $output, $maxwidth, $maxheight) {
    global $localfmle_status_file;
    
    $img_path = array();
    $img_path['broadcasting'] = dirname(__FILE__) . '/img/broadcasting.png';
    $img_path['connection'] = dirname(__FILE__) . '/img/connection.png';
    $img_path['error'] = dirname(__FILE__) . '/img/error.png';
    $img_path['pending'] = dirname(__FILE__) . '/img/pending.png';
    
    $img = imagecreatefromjpeg($input);
//or imagecreatefrompng,imagecreatefromgif,etc. depending on user's uploaded file extension

    $width = imagesx($img); //get width and height of original image
    $height = imagesy($img);

//determine which side is the longest to use in calculating length of the shorter side, since the longest will be the max size for whichever side is longest.    
    if ($height > $width) {
        $ratio = $maxheight / $height;
        $newheight = $maxheight;
        $newwidth = $width * $ratio;
        $writex = round(($maxwidth - $newwidth) / 2);
        $writey = 0;
    } else {
        $ratio = $maxwidth / $width;
        $newwidth = $maxwidth;
        $newheight = $height * $ratio;
        $writex = 0;
        $writey = round(($maxheight - $newheight) / 2);
    }

    $newimg = imagecreatetruecolor($maxwidth, $maxheight);

//Since you probably will want to set a color for the letter box do this
//Assign a color for the letterbox to the new image, 
//since this is the first call, for imagecolorallocate, it will set the background color
//in this case, black rgb(0,0,0)
    imagecolorallocate($newimg, 0, 0, 0);

    $palsize = ImageColorsTotal($img);  //Get palette size for original image
    for ($i = 0; $i < $palsize; $i++) { //Assign color palette to new image
        $colors = ImageColorsForIndex($img, $i);
        ImageColorAllocate($newimg, $colors['red'], $colors['green'], $colors['blue']);
    }

    imagecopyresized($newimg, $img, $writex, $writey, 0, 0, $newwidth, $newheight, $width, $height);
    
    if (file_exists($localfmle_status_file)){
        $cam_status = file_get_contents($localfmle_status_file);
        switch ($cam_status){
            case "recording":
                $img_status = imagecreatefrompng($img_path['broadcasting']);
                break;
            case "connection problem":
                $img_status = imagecreatefrompng($img_path['connection']);
                break;
            case "stopped":
                $img_status = imagecreatefrompng($img_path['error']);
                break;
            case "pending":
                $img_status = imagecreatefrompng($img_path['pending']);
                break;
        }
        imagecopymerge($newimg, $img_status, 5, 130, 0, 0, 225, 25, 75);
    }

    imagejpeg($newimg, $output); //$output file is the path/filename where you wish to save the file.  
//Have to figure that one out yourself using whatever rules you want.  Can use imagegif() or imagepng() or whatever.
}

?>
