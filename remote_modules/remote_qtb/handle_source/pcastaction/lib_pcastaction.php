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
 *interfaces pcastaction commandline tool on macos x 10.6
 * All path should be absolute
 * you need to call movie_set_tempdir first
 */
$pcapath="/usr/bin/pcastaction";
/**
 *
 * @global string $tempdir
 * @param string $path
 * @return bool
 * @desc set the temporary directory that is needed to compute the movie operations
 * @desc ?the directory should be in same partition (to avoid copy)?
 */
function movie_set_tempdir($path){
 global $tempdir;
 if(!is_dir($path)){
  echo "could not find temporary directory $path";
  die;
 }

 $tempdir=$path;
 return false;
}

/**
 *
 * @global string $pcapath
 * @global string $tempdir
 * @param absolute_path $moviein
 * @param decimal $starttime
 * @param decimal $endtime
 * @param absolute_path $movieout
 * @return false or error_string
 * @desc take the part between start and end, save it to $movieout (as a reference movie)
 */
function movie_trim($moviein,$starttime,$endtime,$movieout){
    global $pcapath,$tempdir;
 if(!is_file($moviein))return "input movie not found $moviein";
 $moviein=escape_path($moviein);
 $movieout=escape_path($movieout);
 $cmd="$pcapath trim --prb=$tempdir --input=$moviein --output=$movieout --start_time=$starttime --end_time=$endtime";
 exec($cmd, $cmdoutput, $returncode);
 //check returncode
 if($returncode)return join ("\n", $cmdoutput);
 return false;
}

/**
 *
 * @global string $pcapath
 * @global string $tempdir
 * @param absolute_path $movie1
 * @param absolute_path $movie2
 * @param absolute_path $movieout
 * @param int|(false) $gap
 * @return false or error_string
 *  @desc join two movies (as a reference movie)
 */
function movie_join($movie1,$movie2,$movieout,$gap=false){
    global $pcapath,$tempdir;
 if(!is_file($movie1))return "input movie1 not found $movie1";
 if(!is_file($movie2))return "input movie2 not found $movie2";
 $movie1=escape_path($movie1);
 $movie2=escape_path($movie2);
 $movieout=escape_path($movieout);
 $options="";
 if($gap)$options.=" --gap=$gap";
 $cmd="$pcapath join --prb=$tempdir --input1=$movie1 --input2=$movie2 --output=$movieout $options";
 exec($cmd, $cmdoutput, $returncode);
 //check returncode
 if($returncode){
     return join ("\n", $cmdoutput);
 }
 return false;
}

/**
 *
 * @global string $pcapath
 * @global string $tempdir
 * @param absolute_path $moviein
 * @param absolute_path $audiomovieout
 * @return false|error_string
 * @desc extract an audio track from a movie
 */
function movie_extract_audiotrack($moviein,$audiomovieout){
    global $pcapath,$tempdir;
 if(!is_file($moviein))return "input movie not found $moviein";
 $moviein=escape_path($moviein);
 $audiomovieout=escape_path($audiomovieout);
 $cmd="$pcapath extracttracks --prb=$tempdir --input=$moviein --output=$audiomovieout --type=audio";
 exec($cmd, $cmdoutput, $returncode);
 //check returncode
 if($returncode)return join ("\n", $cmdoutput);
 return false;
}

/**
 *
 * @global string $pcapath
 * @global string $tempdir
 * @param absolute_path $moviein
 * @param absolute_path $videomovieout
 * @return false|error_string
 * @desc extract a video track from a movie
 */
function movie_extract_videotrack($moviein,$videomovieout){
    global $pcapath,$tempdir;
 if(!is_file($moviein))return "input movie not found $moviein";
 $moviein=escape_path($moviein);
 $videomovieout=escape_path($videomovieout);
 $cmd="$pcapath extracttracks --prb=$tempdir --input=$moviein --output=$videomovieout --type=video";
 exec($cmd, $cmdoutput, $returncode);
 //check returncode
 if($returncode)return join ("\n", $cmdoutput);
 return false;
}

/**
 *
 * @global string $pcapath
 * @global string $tempdir
 * @param absolute_path $movie video movie
 * @param absolute_path $movie2 audio track .mov
 * @param absolute_path $movieout
 * @param decimal|(false) $offset introduce a delay between the 2 tracks
 * @return false or error_string
 *  @desc add a audio track (.mov) to a  movie  (as a reference movie)
 */
function movie_add_audiotrack($moviein,$audiomovie,$movieout,$offset=false){
    global $pcapath,$tempdir;
 if(!is_file($moviein))return "input movie1 not found $moviein";
 if(!is_file($audiomovie))return "input audiotrack not found $audiomovie";
 $movie=escape_path($movie);
 $audiomovie=escape_path($audiomovie);
 $movieout=escape_path($movieout);
 $options="";
 if($offset)$options.=" --offset=$offset ";
 $cmd="$pcapath addtracks --prb=$tempdir --input=$moviein --tracks=$audiomovie --output=$movieout $options";
 exec($cmd, $cmdoutput, $returncode);
 //check returncode
 if($returncode){
     return join ("\n", $cmdoutput);
 }
 return false;
}

/**
 *
 * @global string $pcapath
 * @global string $tempdir
 * @param absolute_path $primarymovie
 * @param absolute_path $secondarymovie
 * @param absolute_path $qtzpath
 * @param absolute_path $movieout
 * @param int|false $threshold
 * @param bool $auto_transitions (false)
 * @param bool $auto_chapter (false)
 * @return false|error_string
 * @desc does picture in picture merge of 2 sources
 */
function movie_pip($primarymovie,$secondarymovie,$qtzpath,$movieout,$threshold=false,$auto_transitions=false,$auto_chapter=false){
    global $pcapath,$tempdir;
 if(!is_file($primarymovie))return "input primarymovie not found '$primarymovie'";
 if(!is_file($secondarymovie))return "input secondarymovie not found '$secondarymovie'";
 if(!is_file($qtzpath))return "qtz file not found '$qtzpath'";
 $primarymovie=escape_path($primarymovie);
 $secondarymovie=escape_path($secondarymovie);
 $qtzpath=escape_path($qtzpath);
 $movieout=escape_path($movieout);
 $options="";
 if($threshold)$options.=" --threshold=$threshold ";
 if($auto_transitions)$options.=" --enable_automatic_transitions ";
 if($auto_chapter)$options.=" --enable_auto_chaptering ";
 $cmd="$pcapath pip --prb=$tempdir --primary_input=$primarymovie --secondary_input=$secondarymovie --composition=$qtzpath --output=$movieout $options";
 exec($cmd, $cmdoutput, $returncode);
 //check returncode
 if($returncode)return join ("\n", $cmdoutput);
 return false;
}

/**
 *
 * @global string $pcapath
 * @global string $tempdir
 * @param absolute_path $moviein
 * @param absolute_path $movieout
 * @return false|error_string
 * @desc transform a ref movie to a self-contained movie
 */
function movie_flatten($moviein,$movieout){
    global $pcapath,$tempdir;
 if(!is_file($moviein))return "input movie not found $moviein";
 $moviein=escape_path($moviein);
 $movieout=escape_path($movieout);
 $cmd="$pcapath flatten --prb=$tempdir --input=$moviein --output=$movieout";
 exec($cmd, $cmdoutput, $returncode);
 //check returncode
 if($returncode)return join ("\n", $cmdoutput);
 return false;
}

function movie_qtinfo($moviein,&$qtinfo,$key=""){
    global $pcapath,$tempdir;
 if(!is_file($moviein))return "input movie not found $moviein";
 $moviein=escape_path($moviein);
 $options="";
 if($key!="")$options.="--key=$key";
 $cmd="$pcapath qtinfo --prb=$tempdir --input=$moviein $options";
 exec($cmd, $cmdoutput, $returncode);
 //check returncode
 if($returncode)return join ("\n", $cmdoutput);
 $qtinfo=join ("\n", $cmdoutput);
 return false;
}

/**
 *
 * @global string $pcapath
 * @global string $tempdir
 * @param absolute_path $moviein
 * @param absolute_path $movieout
 * @param string encoder
 * @return false|error_string
 * @desc encodes a (ref) movie to a self-contained movie using a specified codec
 */
function movie_encode($moviein,$movieout,$encoder){
    global $pcapath,$tempdir;
 if(!is_file($moviein))return "input movie not found $moviein";
 $moviein=escape_path($moviein);
 $movieout=escape_path($movieout);
 $cmd="$pcapath encode --prb=$tempdir --input=$moviein --output=$movieout --encoder=$encoder >/tmp/encode_log";
 exec($cmd, $cmdoutput, $returncode);
 //check returncode
 if($returncode)return join ("\n", $cmdoutput);
 return false;
}

/**
 *
 * @global string $pcapath
 * @global string $tempdir
 * @param array $encoders return an array of known encoders
 * @return false|error_string
 * @desc returns list of available codecs in an array
 */
function movie_getencoderlist(&$encoders){
    global $pcapath,$tempdir;
 $cmd="$pcapath help encode 2>&1";
 exec($cmd, $cmdoutput, $returncode);
 //check returncode
 if($returncode)return join ("\n", $cmdoutput);
    $encoders=array();
    $found=false;
    foreach ($cmdoutput as $value) {
      if (strstr($value,"the available encoders are:"))$found=true;
      if($found && trim($value)!="")array_push ($encoders, trim($value));
    }
 return false;
}

/**
 *
 * @global string $pcapath
 * @global string $tempdir
 * @param <array_of_strings> $movies_arr all to movies to join
 * @param <string> $outputmovie reference movie of all movies joined
 * @return <false|error_string>
 * @desc joins all movie (absolute path) given in the array outputs a ref movie
 */
function movie_join_array($movies_arr,$outputmovie){
 global $pcapath,$tempdir;
$curjoinfile=$movies_arr[0];
//creates a temporary directory
$uniqid=uniqid();
$uniqdir="$tempdir/$uniqid";
mkdir($uniqdir);

$nbmovies=count($movies_arr);
for($idx=1;$idx<$nbmovies;$idx++){
    $res=movie_join($curjoinfile, $movies_arr[$idx], "$uniqdir/movie$idx.mov");
    $curjoinfile="$uniqdir/movie$idx.mov";
    if($res){
       // system("rm -rf $uniqdir");
        return "join error: $res";
    }
}

//all went well so we need to flatten last ref movie to the destination
 if(!file_exists($outputmovie))copy($curjoinfile, $outputmovie);
 //system("rm -rf $uniqdir");
    
 //all went well
 return false;
}

function escape_path($path){
  $newpath=str_replace("//", "/", $path); //removes multiple / 
  return escapeshellarg($newpath);

}
?>
