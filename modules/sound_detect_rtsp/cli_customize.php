<?php

    
	$file="../../tmpl_sources/record_screen.php";

    if(!file_exists($file)) {
        echo("Input file $file does not exist");
        return false;
    }
    
    $data = file_get_contents($file);

    $output_file = $file.".bck";
    
    
    file_put_contents($output_file, $data);
    
    
    //customize fot organization

		$data = str_replace('}, 1500);','}, 3000);',$data);
		$data = str_replace('}, 2000);','}, 3000);',$data);
    
    
    
    file_put_contents($file, $data);

?>
