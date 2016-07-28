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
if ($argc < 4) {
    echo 'Usage: cli_template_generate.php source_folder language output_folder' . PHP_EOL;
    die;
}

$source_folder = $argv[1];
$lang = $argv[2];
$output_folder = $argv[3];

if (!is_dir($source_folder)) {
    echo 'Error: source folder ' . $source_folder . ' does not exist' . PHP_EOL;
    die;
}


if (!is_dir($output_folder)) {
    mkdir($output_folder);
    chmod($output_folder, 0755);
    if (!is_dir($output_folder)) {
        echo 'Error: Unable to create output folder "' . $source_folder . '"' . PHP_EOL;
        die;
    }
}

if (!in_array($lang, $accepted_languages)) {
    echo 'Error: language ' . $lang . ' not supported' . PHP_EOL;
    die;
}

template_set_errors_visible();
template_set_warnings_visible();

echo 'Translation of *all* templates in ' . $source_folder . ' will now start.' . PHP_EOL;
echo 'Output language: ' . $lang . PHP_EOL . PHP_EOL;

$files = template_list_files($source_folder);

//
// Parsing each template file
//
foreach ($files as $file) {
    //echo 'Translating ' . $file . '...' . PHP_EOL;
    template_parse($file, $lang, $output_folder);
    //echo 'Translation complete' . PHP_EOL . PHP_EOL;
}

if (template_last_error() != '' || template_last_warning() != '')
    echo PHP_EOL;
echo 'Translation finished, you can find your files in \'' . $output_folder . '/' . $lang . '\'' . PHP_EOL;
?>
