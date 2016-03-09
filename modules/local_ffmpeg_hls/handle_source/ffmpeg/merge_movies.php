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

print "cam merge_ffmpeg_movies\n";
//handles an offlineqtb recording: multifile recording on podcv and podcs
if ($argc != 5) {
    echo "Usage: " . $argv[0] . " <root_movies_directory> <commonpartname> <output_movie_filename> <cutlist_file>\n";
    echo "        where <root_movies_directory> is the directory containing the movies\n";
    echo "        <commonpartname> part name that is common to all movies\n";
    echo "        <merge_filename> filename to write output to\n";
    echo "        <cutlist_file> the file containing the segments to extract from the recording\n";
    echo "";
    echo "Example: php merge_movies.php /Users/podclient/Movies/upload_ok/2016_02_20_10h06_PHYS-S201/ ffmpegmovie cam. /Users/podclient/Movies/upload_ok/2016_02_20_10h06_PHYS-S201/_cut_list ";
    die;
}

$movies_path = $argv[1]; //basedir containing movies (typically /Users/podclient/Movies/local_processing/date_course)
$commonpart = $argv[2]; // common part of video name (typically 'ffmpegmovie')
$outputfilename = $argv[3]; // // name for output file (typically 'cam.mov')
$cutlist_file = $argv[4]; // file containing the video segments to extract from the full recording
//
//First start with merging parts of each stream (QuickTime Broadcaster is limited to 2GB files
//join all cam parts (if neccessary)
$moviename = $commonpart;

$output = system("ls -la $movies_path/$moviename* | wc -l");
if ($output >= 1) {
    print "Join movies with ffmpeg\n";
    $res = movie_join_parts($movies_path, $commonpart, $outputfilename); //movie span on multiple files
    if ($res)
        myerror("Join movies error:$res");
} else if ($output == 0) {
    myerror("No video file found ");
} else {
    myerror("Command: 'ls $movies_path/$moviename* | wc -l' failed");
}

//We will now extract the parts user wants to keep according to the cutlist
movie_extract_cutlist($movies_path, $outputfilename, $cutlist_file);

function myerror($msg) {
    fprintf(STDERR, "%s", $msg);
    exit(1); //return error code
}

?>
