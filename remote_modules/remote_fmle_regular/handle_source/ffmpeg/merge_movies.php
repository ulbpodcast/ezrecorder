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

// Uses ffmpeg to concatenate video files

$path = dirname($argv[0]); //get the program directory to fix a relative path include problem

if (trim($path) != "")
    $path.='/';
include "$path" . "lib_ffmpeg.php";

print "cam merge_fmle_movies\n";
//handles an offlineqtb recording: multifile recording on podcv and podcs
if ($argc != 4) {
    echo "usage: " . $argv[0] . " <directory> <commonpartname> <output_movie_filename>\n";
    echo "        where <directory> is the directory containing the movies\n";
    echo "        <commonpartname> part name that is common to all movies\n";
    echo "        <merge_filename> filename to write output to\n";
    die;
}

$movies_path = $argv[1]; //basedir containing movies (typically /Users/podclient/Movies/local_processing/date_course)
$commonpart = $argv[2]; // common part of video name (typically 'qtbmovie')
$outputfilename = $argv[3]; // // name for output file (typically 'fmle_movie.f4v')
//
//First start with merging parts of each stream (QuickTime Broadcaster is limited to 2GB files
//join all cam parts (if neccessary)
$moviename = $commonpart;

$output = system("ls -la $movies_path/$moviename*.f4v | wc -l");
if ($output > 1) {
    print "Join movies with ffmpeg\n";
    $res = movie_join_parts($movies_path, $commonpart, $outputfilename); //movie span on multiple files
    if ($res)
        myerror("Join movies error:$res");
}else {
    global $remotefmle_mono;
    if ($remotefmle_mono){
        global $ffmpegpath;
        // reencodes video to duplicates mono source in right and left channels
         $cmd="$ffmpegpath -i $movies_path/$commonpart.f4v -vcodec copy -ac 1 $movies_path/$outputfilename";
         print $cmd . PHP_EOL;
         exec($cmd, $cmdoutput, $returncode);
    } else {        
        copy("$movies_path/$commonpart.f4v", "$movies_path/$outputfilename");
    }
}

function myerror($msg) {
    fprintf(STDERR, "%s", $msg);
    exit(1); //return error code
}

?>
