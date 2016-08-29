<?php

/*
 * This is a CLI script that compiles the templates in $tmpl_source into a specific language
 * Usage: template_generate.php $tmpl_source $language
 * 
 * Note: you can add languages by editing config.inc
 */

require_once 'global_config.inc';
require_once 'lib_template.php';

//
// Inits and sanity checks
//
if ($argc != 5) {
    echo 'Usage: cli_template_generate.php source_folder language output_folder' . PHP_EOL;
    die;
}

$source_folder = $argv[1];
$lang = $argv[2];
$output_folder = $argv[3];

echo 'Translation of *all* templates in ' . $source_folder . ' will now start.' . PHP_EOL;
echo 'Output language: ' . $lang . PHP_EOL . PHP_EOL;


template_set_errors_visible();
template_set_warnings_visible();
    
$res = template_generate($source_folder, $lang, $output_folder, $error);
if(!$res) {
    echo $error . PHP_EOL;
    die;
}

if (template_last_error() != '' || template_last_warning() != '')
    echo PHP_EOL;
echo 'Translation finished, you can find your files in \'' . $output_folder . '/' . $lang . '\'' . PHP_EOL;