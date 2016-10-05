<?php

/*
 * interfaces ffmpeg commandline tool
 * All path should be absolute
 */

require_once("global_config.inc");
require_once("lib_various.php");

/**
 * concatenates multiple video files without re-encoding (as a reference movie)
 * @global string $ffmpeg_cli_cmd 
 * @param type $movies_path path to the video files
 * @param type $commonpart name contained in each video file (= 'qtbmovie')
 * @param type $output name for the output video
 * @return boolean
 */
function movie_join_parts($movies_path, $commonpart, $output) {
    global $ffmpeg_cli_cmd;
    global $logger;

    $tmpdir = "$movies_path/joinparts_tmpdir";

    chdir($movies_path);
    $movie_count = trim(system("ls -la $movies_path | grep $commonpart | wc -l"));
    print "ls -la $movies_path | grep $commonpart | wc -l --> output: $movie_count" . PHP_EOL;

    if ($movie_count < 1)
        return 'No file(s) found';

    if(!file_exists($tmpdir))
        mkdir($tmpdir);
    
    $concat_file = "$tmpdir/concat2.txt";

    $cmd = "";
    for ($i = 0; $i < $movie_count; $i++) {
        // high resolution exists
        if (is_file("${commonpart}_$i/high/$commonpart.m3u8")) {
            $cmd = "$ffmpeg_cli_cmd -i $movies_path/${commonpart}_$i/high/$commonpart.m3u8 -c copy -bsf:a aac_adtstoasc -y $tmpdir/part$i.mov";
        } else if (is_file("${commonpart}_$i/low/$commonpart.m3u8")) {
            $cmd = "$ffmpeg_cli_cmd -i $movies_path/${commonpart}_$i/low/$commonpart.m3u8 -c copy -bsf:a aac_adtstoasc -y $tmpdir/part$i.mov";
        } else {
            return "no .m3u8 file found at i = $i";
        }
        $return_val = 0;
        $cmd_output = "";
        exec($cmd, $cmd_output, $return_val);
        if($return_val != 0) {
            return "Join command failed, last command: $cmd " .PHP_EOL."Result: " . print_r($cmd_output, true);
        }
    }

    if (count(glob("$tmpdir/*")) === 1) {
        //only one part, just rename it
        rename("$tmpdir/part0.mov", "$movies_path/$output");
        exec("rm -rf $tmpdir", $cmdoutput, $errno);
        $tmpdir = '';
    } else {
        //several parts, need to concat
        // creates a temporary text file containing all video files to join
        $cmd = "for f in $tmpdir/part*.mov; do echo \"file '\$f'\" >> $concat_file; done";
        exec($cmd, $cmdoutput, $returncode);
        // uses the temporary text file to concatenate the video files
        // -f concat : option for concatenation
        // -i file : input is the list of files
        // -c copy : copy the existing codecs (no reencoding)
        $output_file = "$movies_path/$output";
        $cmd = "$ffmpeg_cli_cmd -f -y concat -i $concat_file $output_file";
        print $cmd . PHP_EOL;
        exec($cmd, $cmdoutput, $returncode);
        //check returncode
        if ($returncode != 0) {
            return join("\n", $cmdoutput);
        }
        
        // deletes the temporary text file
        unlink($concat_file);
        exec("rm -rf $tmpdir", $cmdoutput, $errno);
    }
    return 0;
}

function movie_cutlist_afterfixes(&$ffmpeg_params) {
    global $logger;
    
    //check empty
    if(empty($ffmpeg_params)) {
        //then use whole file, no parts
        $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::ERROR, "Could not get parts from custlist, using the whole video file instead", array("movie_extract_cutlist"));
        $ffmpeg_params[] = array(0, 9999999999);
    }
    // what else could we check ?
    // Check if there was one whole hour between pause and stop ?
}

/*
 * Fill $ffmpeg_params with movie segments (start time + duration), from given $cutlist_array
 * Example of resulting array:
 * [
 *   //segment 1
 *   [
 *       436,  //start time
 *       2404, //duration
 *   ],
 *   //segment 2
 *   [
 *       3739, //start time
 *       2428, //duration
 *   ],
 * ]
 */
function movie_prepare_cutlist_segments(&$ffmpeg_params, &$cutlist_array) {
    $init = 0;
    $startime = 0;
    $duration = 0;
    // prepares parameters for ffmpeg 
    foreach ($cutlist_array as $value) {

        $action = $value[0];
        $action_time = $value[1];
        
        switch ($action) {
            case 'init' :
                $init = $action_time;
                break;
            case 'play' :
            case 'resume':
                if ($startime == 0 && $action_time >= $init) {
                    $startime = $action_time;
                }
                break;
            case 'pause' :
            case 'stop':
                if ($startime != 0 && $action_time > $startime) {
                    $duration = $action_time - $startime;
                    //insert: array(part start (time file beginning), part duration)
                    $ffmpeg_params[] = array($startime - $init, $duration);
                    $startime = 0;
                }
                break;
        }
        if ($action == 'stop') 
            break;
    }
}

function movie_extract_cutlist($movie_path, $movie_in, $cutlist_file, $movie_out = '', $asset_name = '') {
    global $ffmpeg_cli_cmd;
    global $logger;

    if (!isset($movie_out) || $movie_out == '')
        $movie_out = $movie_in;

    // saves cutlist in an indexed array
    if (($handle = fopen($cutlist_file, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ":")) !== FALSE) {
            $cutlist_array[] = $data;
        }
        fclose($handle);
    } else {
        $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::ERROR, "Can't open cutlist file: $cutlist_file", array("movie_extract_cutlist"), $asset_name);
        return "1/ Error while opening cutlist file";
    }

    $ffmpeg_params = array();
    
    movie_prepare_cutlist_segments($ffmpeg_params, $cutlist_array);
    
    //post extraction fixes
    movie_cutlist_afterfixes($ffmpeg_params);
    
    chdir($movie_path);

    $tmp_dir = "$movie_path/cutlist_tmpdir";
    $ok = file_exists($tmp_dir) || mkdir($tmp_dir);
    if(!$ok) {
        $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::ERROR, "Could not create temporary cut folder: $tmp_dir", array("movie_extract_cutlist"), $asset_name);
        return "/7 Could not create temporary cut folder: $tmp_dir";
    }

    file_put_contents($cutlist_file.'_debug', print_r($ffmpeg_params, true), FILE_APPEND);

    // creates each recording segments to be concatenated
    foreach ($ffmpeg_params as $index => $params) {
        $try = 0;
        $part_duration = -999;
        $part_start_second = $params[0];
        $desired_part_duration = $params[1];
        // sometimes, ffmpeg doesn't extract the recording segment properly
        // This results in a shortened segment which may cause problems in the final rendering 
        // We then loop on segment extraction to make sure it has the expected duration
        $try_count = 3;
        while ($try < $try_count && abs($part_duration - $desired_part_duration) > 1) { //allow one second difference max
            // extracts the recording segment from the full recording
            // -ss : the segment starts at $param[0] seconds of the full video
            // -t  : the segment lasts $param[1] seconds long
            // -c  : audio and video codecs are copied
            // -y  : the segment is replaced if already existing
            $more_params = ($try >= 1) ? ' -probesize 1000000 -analyzeduration 1000000 ' : ''; // increase analyze duration
            $more_params .= ($try >= 2) ? ' -pix_fmt yuv420p ' : ''; // defines pixel format, which is often lacking
            $cmd = "$ffmpeg_cli_cmd -i $movie_path/$movie_in -ss " . $part_start_second . " -t " . $desired_part_duration . $more_params . " -c copy -y $tmp_dir/part-$index.mov; wait";
            print "*************************************************************************" . PHP_EOL .
                    $cmd . PHP_EOL .
                    "*************************************************************************" . PHP_EOL;
            $return_code = 0;
            $cmdoutput = system($cmd, $return_code);
            if($return_code != 0) {
                return '2/' . $cmdoutput;
            }
            
            // the segment has been extracted, we verify here its duration
            $cmd = "$ffmpeg_cli_cmd -i $tmp_dir/part-$index.mov 2>&1 | grep Duration | cut -d ' ' -f 4 | sed s/,// | sed 's@\..*@@g'";
            $cmdoutput = system($cmd, $return_code); // duration in HH:MM:SS
            if($return_code != 0) {
                return '3/' . $cmdoutput;
            }
            
            list($hours, $minutes, $seconds) = sscanf($cmdoutput, "%d:%d:%d");
            $part_duration = $hours * 3600 + $minutes * 60 + $seconds; // duration in seconds
            $try++;
            print   "--------------------------------------------------------------------------"     . PHP_EOL .
                    "Try [$try]: duration found : $part_duration - expected : " . $desired_part_duration . PHP_EOL .
                    "--------------------------------------------------------------------------"     . PHP_EOL;
        }
        if($try == $try_count) {
            $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::ERROR, "Part creation failed after $try_count tries. Let's try to continue anyway with our current result.", array("movie_extract_cutlist"), $asset_name);
        } else if ($try == 0) {
            $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::CRITICAL, "WHAT? We didn't even loop once, there is a logic error here.", array("movie_extract_cutlist"), $asset_name);
        }
    }

    $join_file = "$tmp_dir/concat1.txt";
    if(file_exists($join_file))
        unlink($join_file); //cleanup before starting
    // creates a temporary text file containing all video files to join
    $cmd = "for f in $tmp_dir/part*.mov; do echo \"file '\$f'\" >> $join_file; done";
    $return_code = 0;
    $cmdoutput = system($cmd, $return_code);
    if($return_code != 0) {
        return '5/' . $cmdoutput;
    }
    // uses the temporary text file to concatenate the video files
    // -f concat : option for concatenation
    // -i file : input is the list of files
    // -c copy : copy the existing codecs (no reencoding)
    $cmd = "$ffmpeg_cli_cmd -f concat -safe 0 -i $join_file -c copy -y $movie_path/$movie_out";
    print $cmd . PHP_EOL;
    $return_code = 0;
    $cmdoutput = system($cmd, $return_code);
    if($return_code != 0) {
        return "6/ Command $cmd failed with output:" . $cmdoutput;
    }
    
    // deletes the temporary text file
    unlink($join_file);

    if ($tmp_dir != '')
        exec("rm -rf $tmp_dir", $cmdoutput, $errno);
    
    return 0;
}

//get ffmpeg cutlist file for asset
function ffmpeg_get_cutlist_file($module_name, $asset) {
    $folder = get_asset_module_folder($module_name, $asset);
    if(!$folder)
        return false;
    
    return "$folder/_cut_list.txt";
}