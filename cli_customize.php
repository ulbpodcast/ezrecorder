<?php

	require_once 'global_config.inc';
    
	$file="translations.xml";

    if(!file_exists($file)) {
        template_last_error("Input file $file does not exist");
        return false;
    }
    
    $data = file_get_contents($file);

    $output_file = $file.".bck";
    
    
    file_put_contents($output_file, $data);
    
    
    //customize fot organization
    if (strcmp($organization,"Generale") !== 0){
		$data = str_replace('ULB',$organization,$data);
		$data = str_replace('(02/650) 29 26',$organisation_help,$data);
    }
    
    
    file_put_contents($file, $data);

?>
