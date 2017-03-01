<?php
require_once __DIR__.'/../../global_config.inc';
// Logger::$print_logs = true;
// generate audio files
function get_wav_from_video($input, $output,$asset_name){
	global $logger;
	global $ffmpeg_cli_cmd;
	
	//Try to create audio file. log errors
	exec($ffmpeg_cli_cmd." -i ".$input." -map 0:1 -acodec pcm_s16le -ac 2 -ar 44100 ".$output,$err,$return);	
	if($return!= 0) $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::WARNING, "Audio creation of $output from $input failed with error: $err. ",array("merge_movies"), $asset_name);
	else $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::DEBUG, "Audio creation of $output from $input Succed",array("merge_movies"), $asset_name);

	return $return;
}

// Synchronize vidéos
function sync_video($movies_path,$asset_name){
	global $logger;
	global $ffmpeg_cli_cmd;
	global $ffprobe_cli_cmd;
	global $praat_cli_cmd;
	global $cam_module;
	global $slide_module;
	global $basedir;
	$campath='';
	$slidepath='';
	
	// determine the slide and cam path
	if(file_exists($movies_path.'/../slide.mov')) $slidepath=$movies_path.'/../slide.mov'; 
	elseif(file_exists($movies_path.'/../'.$slide_module.'/slide.mov'))$slidepath=$movies_path.'/../'.$slide_module.'/slide.mov'; 

	if(file_exists($movies_path.'/../cam.mov')) $campath=$movies_path.'/../cam.mov'; 
	elseif(file_exists($movies_path.'/../'.$cam_module.'/cam.mov')) $campath=$movies_path.'/../'.$cam_module.'/cam.mov'; 
	
	
	// create audio files and log if error
	if($slidepath!='' && !file_exists($slidepath.".wav")){
		$return=get_wav_from_video($slidepath, $slidepath.".wav",$asset_name);
		if($return!= 0){
			$logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::WARNING, "Audio creation of ".$slidepath.".wav from $slidepath failed. ",array("merge_movies"), $asset_name);
			return;
		}
	}
	
	if($campath!='' && !file_exists($campath.".wav")){
		$return=get_wav_from_video($campath, $campath.".wav",$asset_name);
		if($return!= 0){
			$logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::WARNING, "Audio creation of ".$campath.".wav from $campath failed. ",array("merge_movies"), $asset_name);
			return;
		}
	}
	
	// synchronize the two video
	if(file_exists($campath.".wav") && file_exists($slidepath.".wav") && file_exists($slidepath) && file_exists($campath)){
			
		
		// find decallage
		$cmd=$praat_cli_cmd." --run $basedir/services/crosscorrelate.praat ".$slidepath.".wav ".$campath.".wav";
		$diff_time_string=shell_exec($cmd);
		if(!is_null($diff_time_string))$diff_time=abs(floatval($diff_time_string));
		else $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::WARNING, "Get diff_time between the two audio files failed.  cmd: ".$cmd." ",array("merge_movies"), $asset_name);
		
	 
		// duration of Cam video
		$cmd=$ffprobe_cli_cmd.' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '.$campath;	 
		$duration_string=shell_exec($cmd);
		if(!is_null($duration_string))$duration=abs(floatval($duration_string));
		else $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::WARNING, "Get duration of ".$campath." failed.  cmd: ".$cmd." ",array("merge_movies"), $asset_name);

		// Cut correctly the two video to synchronize
		if( !is_null($duration) && !is_null($diff_time) && ($duration-$diff_time)>0 ){
				
			 
			// create new temp files
			$cmd=$ffmpeg_cli_cmd.' -ss '.$diff_time.' -i '.$slidepath.' -vcodec copy -acodec copy '.$movies_path.'/../slidetemp.mov';
			exec($cmd,$err,$return);
			if($return!= 0) $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::WARNING, "creation of ".$movies_path."/../slidetemp.mov failed. ",array("merge_movies"), $asset_name);

			$cmd=$ffmpeg_cli_cmd.' -ss 0 -i '.$campath.' -vcodec copy -acodec copy -t '.($duration-$diff_time).' '.$movies_path.'/../camtemp.mov';
			exec($cmd,$err,$return);
			if($return!= 0) $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::WARNING, "creation of ".$movies_path."/../camtemp.mov failed. ",array("merge_movies"), $asset_name);
			 
			 // replace cam.mov & slide.mov with the synchronised temp files
			exec('mv '.$movies_path.'/../slidetemp.mov '.$slidepath,$err,$return);
		 	if($return!= 0) $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::WARNING, "Move ".$slidepath." from ".$movies_path."/../slidetemp.mov failed. ",array("merge_movies"), $asset_name);
			exec('mv '.$movies_path.'/../camtemp.mov '.$campath,$err,$return);
		 	if($return!= 0) $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::WARNING, "Move ".$campath." from ".$movies_path."/../camtemp.mov failed. ",array("merge_movies"), $asset_name);
			
			$logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::DEBUG, "AUDIO_SYNCHRONISATION SUCCEED ",array("merge_movies"), $asset_name);
		}
		
		// Delete audio files
		exec('rm '.$campath.'.wav ',$err,$return);
		if($return!= 0) $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::WARNING, "DELETE ".$campath.".wav failed. ",array("merge_movies"), $asset_name);
		exec('rm '.$slidepath.'.wav ',$err,$return);
		if($return!= 0) $logger->log(EventType::RECORDER_MERGE_MOVIES, LogLevel::WARNING, "DELETE ".$slidepath.".wav failed. ",array("merge_movies"), $asset_name);
		
	}
}
