<?php
/*
 * EZCAST EZrecorder
 *
 * Copyright (C) 2014 Université libre de Bruxelles
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
