<?php

echo PHP_EOL . "***************************************" . PHP_EOL;
echo           "* Installation of Auth_file module    *" . PHP_EOL;
echo           "***************************************" . PHP_EOL;
echo PHP_EOL . "Creating 'config.inc'...";

copy(dirname(__FILE__)."/config_sample.inc", dirname(__FILE__)."/config.inc");

echo " OK" . PHP_EOL;