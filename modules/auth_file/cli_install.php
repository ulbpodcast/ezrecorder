<?php

echo PHP_EOL . "***************************************" . PHP_EOL;
echo           "* Installation of Auth_file module    *" . PHP_EOL;
echo           "***************************************" . PHP_EOL;
echo PHP_EOL . "creating config.inc" . PHP_EOL;

copy(dirname(__FILE__)."/config_sample.inc", dirname(__FILE__)."/config.inc");
