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

/*
 * interfaces ffmpeg commandline tool
 * All path should be absolute
 */

require_once dirname(__FILE__) . '/../../config.inc';

/**
 * concatenates multiple video files without re-encoding (as a reference movie)
 * @global string $ffmpegpath 
 * @param type $movies_path path to the video files
 * @param type $commonpart name contained in each video file (= 'qtbmovie')
 * @param type $output name for the output video
 * @return boolean
 */
function movie_join_parts($movies_path, $commonpart, $output) {
    global $ffmpegpath;
    global $remotefmle_mono;
    $tmpdir = 'tmpdir';

    chdir($movies_path);
    $movie_count = trim(system("ls -la $commonpart*.f4v | wc -l"));
    print "ls -la $commonpart*.f4v | wc -l --> output: $movie_count" . PHP_EOL;
    // makes sure there are - at least - two movies to join
    if ($movie_count <= 1)
        return "Missing movies for concatenation";

    mkdir("./$tmpdir");

    // reorders files by creation date
    $files = array();
    if ($handle = opendir('.')) {
        // loops on files and put them in an array
        while (false !== ($file = readdir($handle))) {
            if (strpos($file, $commonpart) !== false) {
                $files[filectime($file)] = $file;
            }
        }
        closedir($handle);
        // sorts the array on files creation date
        ksort($files);
        $index = 1;
        foreach ($files as $file) {
            // renames files 
            rename($file, $commonpart . sprintf("%03s", $index) . ".f4v"); 
            $index++;
        }
    }

    // reorders audio and video streams to make sure there are no compatibility errors while concatenating
    $cmd = "for f in $commonpart*.f4v; do $ffmpegpath -i \$f -map 0:v -map 0:a -c copy ./$tmpdir/\$f; done";
    exec($cmd, $cmdoutput, $returncode);
    // if tmpdir is empty, it means there was missing streams in the movies (i.e, image has not been recorded
    // because the canopus was unplugged, ...). We then use original movies to be concatenated 
    if (count(glob("$tmpdir/*")) === 0) { // no video in tmpdir
        exec("rm -rf ./$tmpdir", $cmdoutput, $errno);
        $tmpdir = '';
    }
    // creates a temporary text file containing all video files to join
    $cmd = "for f in $movies_path/$tmpdir/$commonpart*.f4v; do echo \"file '\$f'\" >> $movies_path/tmp.txt; done";
    exec($cmd, $cmdoutput, $returncode);
    $avcodecs = ($remotefmle_mono) ? '-vcodec copy -ac 1' : '-c copy';
    // uses the temporary text file to concatenate the video files
    // -f concat : option for concatenation
    // -i file : input is the list of files
    // -c copy : copy the existing codecs (no reencoding)
    $cmd = "$ffmpegpath -safe 0 -f concat -i $movies_path/tmp.txt $avcodecs $movies_path/$output";
    print $cmd . PHP_EOL;
    exec($cmd, $cmdoutput, $returncode);
    // deletes the temporary text file
    unlink("$movies_path/tmp.txt");
    if ($tmpdir != '')
        exec("rm -rf ./$tmpdir", $cmdoutput, $errno);
    //check returncode
    if ($returncode) {
        return join("\n", $cmdoutput);
    }
    return false;
}

function movie_extract_cutlist($movie_path, $movie_in, $cutlist_file, $movie_out = '') {
    global $ffmpegpath;

    if (!isset($movie_out) || $movie_out == '')
        $movie_out = $movie_in;

    // saves cutlist in an indexed array
    if (($handle = fopen($cutlist_file, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ":")) !== FALSE) {
            $cutlist_array[] = $data;
        }
        fclose($handle);
    } else {
        return "Error while opening cutlist file";
    }

    $ffmpeg_params = array();
    $init = 0;
    $startime = 0;
    $duration = 0;
    // prepares parameters for ffmpeg 
    foreach ($cutlist_array as $value) {

        switch ($value[0]) {
            case 'init' :
                $init = $value[1];
                break;
            case 'play' :
            case 'resume':
                if ($startime == 0 && $value[1] >= $init) {
                    $startime = $value[1];
                }
                break;
            case 'pause' :
            case 'stop':
                if ($startime != 0 && $value[1] > $startime) {
                    $duration = $value[1] - $startime;
                    $ffmpeg_params[] = array($startime - $init, $duration);
                    $startime = 0;
                }
                break;
        }
        if ($value[0] == 'stop')
            break;
    }

    chdir($movie_path);

    $tmp_dir = 'tmpdir';
    mkdir("./$tmp_dir");

    // creates each recording segments to be concatenated
    foreach ($ffmpeg_params as $index => $params) {
        $try = 0;
        $part_duration = 0;
        // sometimes, ffmpeg doesn't extract the recording segment properly
        // This results in a shortened segment which may cause problems in the final rendering 
        // We then loop on segment extraction to make sure it has the expected duration
        while ($try < 3 && $part_duration < $params[1]) {
            // extracts the recording segment from the full recording
            // -ss : the segment starts at $param[0] seconds of the full video
            // -t  : the segment lasts $param[1] seconds long
            // -c  : audio and video codecs are copied
            // -y  : the segment is replaced if already existing
            $more_params = ($try >= 1) ? ' -probesize 1000000 -analyzeduration 1000000 ' : ''; // increase analyze duration
            $more_params .= ($try >= 2) ? ' -pix_fmt yuv420p ' : ''; // defines pixel format, which is often lacking
            $cmd = "$ffmpegpath -i $movie_path/$movie_in -ss " . $params[0] . " -t " . $params[1] . $more_params  . " -c copy -y $tmp_dir/part-$index.f4v; wait";
            print "*************************************************************************". PHP_EOL .
                    $cmd . PHP_EOL .
                  "*************************************************************************". PHP_EOL;
            exec($cmd, $cmdoutput, $returncode);
            // the segment has been extracted, we verify here its duration
            $cmd = "$ffmpegpath -i $tmp_dir/part-$index.f4v 2>&1 | grep Duration | cut -d ' ' -f 4 | sed s/,// | sed 's@\..*@@g'";
            $part_duration = system($cmd); // duration in HH:MM:SS
            sscanf($part_duration, "%d:%d:%d", $hours, $minutes, $seconds);
            $part_duration = $hours * 3600 + $minutes * 60 + $seconds; // duration in seconds
            $try++;
            print "--------------------------------------------------------------------------" . PHP_EOL .
                    "Try [$try]: duration found : $part_duration - expected : " . $params[1]   . PHP_EOL .
                  "--------------------------------------------------------------------------" . PHP_EOL;
            
        }
    }

    file_put_contents($cutlist_file, PHP_EOL . print_r($ffmpeg_params, true), FILE_APPEND);

    // creates a temporary text file containing all video files to join
    $cmd = "for f in $movie_path/$tmp_dir/part*.f4v; do echo \"file '\$f'\" >> $movie_path/tmp.txt; done";
    exec($cmd, $cmdoutput, $returncode);
    // uses the temporary text file to concatenate the video files
    // -f concat : option for concatenation
    // -i file : input is the list of files
    // -c copy : copy the existing codecs (no reencoding)
    $cmd = "$ffmpegpath -safe 0 -f concat -i $movie_path/tmp.txt -c copy -y $movie_path/$movie_out";
    print $cmd . PHP_EOL;
    exec($cmd, $cmdoutput, $returncode);
    // deletes the temporary text file
    unlink("$movie_path/tmp.txt");

    if ($tmp_dir != '')
      exec("rm -rf ./$tmp_dir", $cmdoutput, $errno);
    //check returncode
    if ($returncode) {
        return join("\n", $cmdoutput);
    }
    return false;
}

?>
