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

/**
 * These functions relate to template parsing
 */
// WARNING: you need to call template_load_dictionnary() to load the dictionnary  *before* trying to parse anything
// WARNING: you need to call template_repository_path() *before* trying to access the templates (template_display())

//require_once 'config.inc';
$dictionnary_xml;
$error_visible = false;
$warning_visible = false;

// Various parameters
$accepted_languages = array('fr', 'en'); // Accepted parameters for the language to use
$accepted_file_extensions = array('.html', '.xhtml', '.xml', '.htm', '.php'); // File with these extensions will be considered as template files

//////////////////////
//                  //
// Template display //
//                  //
//////////////////////

/**
 * Sets up or returns the "repository", i.e. the folder in which the (parsed) template files are stored
 * Warning: please use this function *before* any call to template_display()
 * @staticvar boolean $repository_path
 * @param string $path Path to the folder containing parsed templates
 * @return string|false Either the path to the templates repository, or an error status 
 */
function template_repository_path($path = "") {
    static $tmpl_repository_path = false;

    if($path == "") {
        if($tmpl_repository_path === false) {
          echo ("1 Error: repository path not defined");
          return false;
        } else {
          return $tmpl_repository_path;
        }
     }

    //if path exists then store it
    $res=is_dir($path);
    if($res)
        $tmpl_repository_path=$path;
     else
        echo ("2 Error: repository path not found: $path");
     
    return $res;
}

/**
 * Displays the template whose name is $tmpl_name
 * @param string $tmpl_name Template name, including extension
 * @param bool $include_once(true) if set to false, the function will use include instead of include_once
 */
function template_display($tmpl_name, $include_once = false) {
    require template_getpath($tmpl_name);
}

/**
 * Returns the path to a specified template
 * @param type $tmpl_name 
 */
function template_getpath($tmpl_name) {
    $path = template_repository_path();
    if($path === false) {
        template_last_error("Error: template repository not found");
        trigger_error("Error: template repository not found");
    }
    
    if(!file_exists($path.'/'.$tmpl_name)) {
        template_last_error("Error: template $tmpl_name not found");
    }
 
    return $path . '/' . $tmpl_name;
}


//////////////////////
//                  //
// Template parsing //
//                  //
//////////////////////

/**
 * Returns all the HTML files within the $source_folder folder
 * @param string $source_folder 
 * @return array List of filenames to template files
 */
function template_list_files($source_folder) {
    if(!is_dir($source_folder)) {
        template_last_error("$source_folder is not a directory");
        return false;
    }
    //get all file&dir from the source template dir
    $folder = glob($source_folder.'/*');
    $res = array();
    
    foreach($folder as $entry) {
        if(is_dir($entry))
            $res = array_merge($res, template_list_files($entry)); //dig the template source dir recursively
        else
            $res[] = $entry;
    }
    
    return $res;
}

/**
 * Parses the file given in input, and places the result in output_folder/lang
 * Caution: this function expects the output folder to already have the correct structure (subfolders)
 * @param type $source_folder Source folder
 * @param type $file Template name
 * @param type $lang Output language
 * @param type $output_folder Output folder
 * @return bool error status
 */
function template_parse($file, $lang, $output_folder) {
    global $dictionnary_xml;
    //
    // 1) Sanity checks
    //
    if(!is_dir($output_folder)) {
        template_last_error("Output folder $output_folder does not exist!");
        return false;
    }
    
    if(!file_exists($file)) {
        template_last_error("Input file $file does not exist");
        return false;
    }
    
    // TODO: Gérer les sous-dossiers
    
    //
    // 2) Parsing the file
    //
    $data = file_get_contents($file);
    
    template_load_dictionnary('translations.xml');
    
    // Version 1 (not optimized at all)
    /*$labels = template_get_labels('fr');
    foreach($labels as $label) {
        $keyword = '®'.$label['id'].'®';
        $label = (string) $label;
        
        $data = preg_replace('!'.$keyword.'!iU', $label, $data);
    }*/
    
    // version 2, "probably" more efficient
    //look for ®string® where string in any (smallest, ungreedy) suite of nonspace chars.
    //Begin and end of @string@ must be on the same line.
    //calls template_get_label for each match and replace keywork with translated value in the text
    $data = preg_replace_callback('!®(\S+)®!iU', create_function('$matches', 'return template_get_label($matches[1], \''.$lang.'\');'), $data);
    
    //
    // 3) Saving the result
    //
    $subfolder = strstr($file, '/'); // We want the result to be in the same subfolder as the input, but without the source_folder part.
    $output_file = $output_folder.'/'.$lang.$subfolder;
    
    if(!file_exists($output_folder.'/'.$lang))
        mkdir($output_folder.'/'.$lang);//dest dir doesn't exist so create it
    
    if(file_exists($output_file))
        unlink($output_file);
    
    file_put_contents($output_file, $data);
}

/**
 * Loads the dictionnary in $file and puts the result in $dictionnary_xml
 * Call this function *before* trying to access the dictionnary
 * @param type $file 
 */
function template_load_dictionnary($file) {
    global $dictionnary_xml;
    
    if(!file_exists($file)) {
        template_last_error("File $file does not exist");
        return false;
    }
        
    $dictionnary_xml = new SimpleXMLElement(file_get_contents($file));
}

/**
 * Returns a specific label in a specific language
 * @global mixed $dictionnary_xml
 * @param type $id The label ID (used in the template and the dictionary)
 * @param type $lang The language to translate the label into
 * @return string the new label
 */
function template_get_label($id, $lang) {
    global $dictionnary_xml;
    
    if($dictionnary_xml == null) {
        template_last_error("Please load dictionnary before anything else");
        return false;
    }
    //fetch the element matching given id and language in xml stucture via xpath
    $res = $dictionnary_xml->xpath("/data/labels/label[@id='$id' and @lang='$lang']");
    
    if(!$res) {
        template_last_warning('Label '.$id.' not found for lang '.$lang);
        return '';
    }
    
    $res = $res[0];
    return (string) $res;
}

/**
 * Returns the *message* given as a parameter, in the language given as another parameter
 * @param type $id
 * @param type $lang 
 */
function template_get_message($id, $lang) {
    global $dictionnary_xml;
    
    if($dictionnary_xml == null) {
        template_last_error("Please load dictionnary before anything else");
        return false;
    }
    //fetch the element matching given id and language in xml stucture via xpath
    $res = $dictionnary_xml->xpath("/data/messages/message[@id='$id' and @lang='$lang']");
    
    if(!$res) {
        template_last_warning('Message '.$id.' not found for lang '.$lang);
        return '';
    }
    
    $res = $res[0];
    return (string) $res;
}

function template_generate($source_folder, $lang, $output_folder, &$error = '') {
    global $accepted_languages;
    
    if (!is_dir($source_folder)) {
        $error = 'Error: source folder ' . $source_folder . ' does not exist';
        return false;
    }
    
    if (!is_dir($source_folder)) {
        $error = 'Error: source folder ' . $source_folder . ' does not exist';
        return false;
    }

    if (!is_dir($output_folder)) {
        mkdir($output_folder);
        chmod($output_folder, 0755);
        if (!is_dir($output_folder)) {
            $error = 'Error: Unable to create output folder "' . $source_folder . '"';
            return false;
        }
    }

    if (!in_array($lang, $accepted_languages)) {
        $error = 'Error: language ' . $lang . ' not supported';
        return false;
    }

    $files = template_list_files($source_folder);

    //
    // Parsing each template file
    //
    foreach ($files as $file) {
        //echo 'Translating ' . $file . '...' . PHP_EOL;
        template_parse($file, $lang, $output_folder);
        //echo 'Translation complete' . PHP_EOL . PHP_EOL;
    }
    return true;
}


/**
 *
 * @param [string $msg error meesage (optional)]
 * @return string error message
 * @desc Store and return last error message in ezmam library
 */
function template_last_error($msg=""){
    static  $last_error="";
    global $error_visible;

  if($msg=="") {
      return $last_error;
  }
   else{
       $last_error=$msg;
       if($error_visible)
          echo 'Error: '.$msg . PHP_EOL;
       return true;
       }
}

function template_last_warning($msg="") {
    static $last_warning = "";
    global $warning_visible;
    
    
    if($msg=="")
        return $last_warning;
    else {
        $last_warning = $msg;
        if($warning_visible) {
            echo 'Warning: ' . $msg . PHP_EOL;
        }
        return true;
    }
}

/**
 * If this function is called, errors are printed on screen instead of just
 * put in a log
 */
function template_set_errors_visible() {
    global $error_visible;
    $error_visible = true;
}

function template_set_warnings_visible() {
    global $warning_visible;
    $warning_visible = true;
}