<?php

// generate audio files
exec("$ffmpeg_cli_cmd -i $movies_path/$outputfilename -map 0:1 -acodec pcm_s16le -ac 2 -ar 44100 -t 11 ".$movies_path."/../".$outputfilename.".wav");

if(file_exists($movies_path.'/../slide.mov.wav') && file_exists($movies_path.'/../cam.mov.wav') ){
	
	if(file_exists($movies_path.'/../slide.mov')) $slidepath=$movies_path.'/../slide.mov'; 
	else $slidepath=$movies_path.'/../'.$slide_module.'/slide.mov'; 
	if(file_exists($movies_path.'/../cam.mov')) $campath=$movies_path.'/../cam.mov'; 
	else $campath=$movies_path.'/../'.$cam_module.'/cam.mov'; 
		
	if(file_exists($slidepath) && file_exists($campath)){	
		
		 // find decallage
		 $cmd=$praat_cli_cmd." --run $basedir/services/crosscorrelate.praat $movies_path.'/../slide.mov.wav $movies_path.'/../cam.mov.wav";
		 $diff_time=abs(floatval(shell_exec($cmd)));
		 // file_put_contents ( '/Users/ezrecorder/praat00.txt' , $cmd."   difftime:  ".$diff_time);
	 
	 
		 // duration of Cam video
		 $cmd=$ffprobe_cli_cmd.' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '.$campath;	 
		 $duration=abs(floatval(shell_exec($cmd)));
		 
		 if(($duration-$diff_time)>0 ){
			 
			 // create new files
			 $cmd=$ffmpeg_cli_cmd.' -ss '.$diff_time.' -i '.$slidepath.' '.$movies_path.'/../slidetemp.mov';
			 exec($cmd,$output1,$output2);
			 $cmd=$ffmpeg_cli_cmd.' -ss 0 -i '.$campath.' -t '.($duration-$diff_time).' '.$movies_path.'/../camtemp.mov';
			 exec($cmd,$output1,$output2);
			 // file_put_contents ( '/Users/ezrecorder/createtemp.txt' , $cmd);
			 
			 // replace cam.mov & slide.mov with the synchronised files
			 exec('mv '.$movies_path.'/../slidetemp.mov '.$slidepath);
			 exec('mv '.$movies_path.'/../camtemp.mov '.$campath);
		 }
	}
}

?>