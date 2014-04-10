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
 *interfaces ffmpeg commandline tool
 * All path should be absolute
 */

require_once dirname(__FILE__).'/../../config.inc';

/**
 * concatenates multiple video files without re-encoding (as a reference movie)
 * @global string $ffmpegpath 
 * @param type $movies_path path to the video files
 * @param type $commonpart name contained in each video file (= 'qtbmovie')
 * @param type $output name for the output video
 * @return boolean
 */
function movie_join_parts($movies_path,$commonpart,$output){
    global $ffmpegpath;
    $tmpdir = 'tmpdir';
    
 chdir($movies_path);
 $movie_count = trim(system("ls -la $commonpart*.mov | wc -l"));
 print "ls -la $commonpart*.mov | wc -l --> output: $movie_count" . PHP_EOL;
 // makes sure there are - at least - two movies to join
 if($movie_count <= 1)return "Missing movies for concatenation";
 // reorders audio and video streams to make sure there are no compatibility errors while concatenating
 mkdir("./$tmpdir");
 
 $cmd= "for f in $commonpart*.mov; do $ffmpegpath -i \$f -map 0:v -map 0:a -c copy ./$tmpdir/\$f; done";
 exec($cmd, $cmdoutput, $returncode);
 // if tmpdir is empty, it means there was missing streams in the movies (i.e, image has not been recorded
 // because the canopus was unplugged, ...). We then use original movies to be concatenated 
 if (count(glob("$tmpdir/*")) === 0 ) { // no video in tmpdir
     exec("rm -rf ./$tmpdir", $cmdoutput, $errno);
     $tmpdir = '';
 } 
 // creates a temporary text file containing all video files to join
 $cmd= "for f in $movies_path/$tmpdir/$commonpart*.mov; do echo \"file '\$f'\" >> $movies_path/tmp.txt; done";
 exec($cmd, $cmdoutput, $returncode);
 // uses the temporary text file to concatenate the video files
 // -f concat : option for concatenation
 // -i file : input is the list of files
 // -c copy : copy the existing codecs (no reencoding)
 $cmd="$ffmpegpath -f concat -i $movies_path/tmp.txt -c copy $movies_path/$output";
 print $cmd . PHP_EOL;
 exec($cmd, $cmdoutput, $returncode);
 // deletes the temporary text file
 unlink("$movies_path/tmp.txt");
 if ($tmpdir != '')
    exec("rm -rf ./$tmpdir", $cmdoutput, $errno);
 //check returncode
 if($returncode){
     return join ("\n", $cmdoutput);
 }
 return false;
}

?>
