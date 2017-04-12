<?php

echo PHP_EOL . "***************************************" . PHP_EOL;
echo           "* Installation of sound_backup module    *" . PHP_EOL;
echo           "***************************************" . PHP_EOL;
echo PHP_EOL . "Creating 'config.inc'...";

copy(__DIR__."/config_sample.inc", __DIR__."/config.inc");

echo " OK" . PHP_EOL;