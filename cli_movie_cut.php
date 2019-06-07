<?php
/*
 * Move asset to another processing folder 
 */

require_once __DIR__.'/global_config.inc';
require_once __DIR__.'/lib_various.php';
require_once __DIR__.'/lib_ffmpeg.php';
global $service;
$service = true;

Logger::$print_logs = true;

if ($argc != 4 ) {
    echo 'Usage: cli_movie_cut.php <source_movie_path> <cutlist_path> <destination_movie_path>' . PHP_EOL .
         ' Cut the source movie according to the cutlist file and saves result in destination_movie'. PHP_EOL."example: php cli_movie_cut.php ~/Movie/local_processing/slide_uncut.mov cutlist.txt ~/Movie/local_processing/slide.mov".PHP_EOL;
    exit(1);
}

$source_movie_path = $argv[1];
$cutlist_path = $argv[2];
$dest_movie_path = $argv[3];


//cut movie
movie_extract_cutlist(dirname($source_movie_path), basename($source_movie_path), $cutlist_path, $dest_movie_path ,'cli_movie_cut');
