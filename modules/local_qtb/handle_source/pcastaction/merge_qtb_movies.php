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

include "localdefs.php";
include "lib_pcastaction.php";

movie_set_tempdir($tempdir);
print "slide merge_qtb_movies: tempdir=$tempdir\n";

//handles an offlineqtb recording: multifile recording on podcv and podcs
if ($argc < 4 || $argc > 5) {
    echo "usage: " . $argv[0] . " <directory> <commonpartname> <output_movie_filename> [<filename of soundtrack to use>]\n";
    echo "        where <directory> is the directory containing the movies\n";
    echo "        <commonpartname> part name that is common to all movies\n";
    echo "        <merge_filename> filename to write output to\n";
    echo "        <filename of soundtrack to use> replace soundtrack by a new soundtrack";
    die;
}

$movies_path = $argv[1]; //basedir containing movies
$commonpart = $argv[2];
$outputfilename = $argv[3];
//First start with merging parts of each stream (QuickTime Broadcaster is limited to 2GB files
//join all cam parts (if neccessary)
$moviename = $commonpart;

if (file_exists($movies_path . "/" . $moviename . "2.mov")) {
    $curmovie = $moviename . "_joined.mov";
    $res = join_parts($movies_path, $moviename, $curmovie); //movie span on multiple files
}
else
    $curmovie = $moviename . ".mov"; //only one file

    
//check if we need to add a sound track
if ($argc == 5) {
    $soundtrack = $argv[4];
    //remove current audio track
    $res = movie_extract_videotrack($movies_path . "/" . $curmovie, $movies_path . "/" . $curmovie . "_videotack.mov");
    if ($res)
        myerror("extract videotrack error:$res");
    $with_sound_added = $moviename . "_added_soundtrack.mov"; //only one file
    print "adding audio track\n";
    $res = movie_add_audiotrack($movies_path . "/" . $curmovie . "_videotack.mov", $soundtrack, $movies_path . "/" . $with_sound_added);
    if ($res)
        myerror("Add soundtrack error:$res");
    $curmovie = $with_sound_added;
}

//Go to flatten step
print "flattening movie\n";
$res = movie_flatten($movies_path . "/" . $curmovie, $movies_path . "/" . $outputfilename);
if ($res)
    myerror("Flatten error:$res");

/**
 *
 * @param <string> $path
 * @param <string> $moviepartname
 * @param <string> $output 
 * @desc takes all files in $path matching moviename[|2|3|4|...|44|...].mov and join them together in that order
 */
function join_parts($path, $moviepartname, $output) {
    $lastjoinname = $moviepartname; //first movie without _<a-z> existance is taken for granted
    $idx = 2; //start with "_b"
    $encore = true;
    $movies2join = array("$path/$moviepartname.mov");

    while ($encore) {
        if (file_exists($path . "/" . $moviepartname . "$idx.mov")) {
            //we have another movie part so join it with previous parts
            array_push($movies2join, "$path/$moviepartname$idx.mov");
            $idx+=1; //next letter part
        } else {
            //no more movie part found
            $encore = false;
        }
    }//end while
    //array is built. Now join the different files
    $res = movie_join_array($movies2join, "$path/$output");
    if ($res)
        myerror("join_array error $res");
    return false;
}

function myerror($msg) {
    fprintf(STDERR, "%s", $msg);
    exit(1); //return error code
}

?>
