<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class FileSoundInfo {
    public $max_volume = -999.0; //dummy value
    public $mean_volume = -999.0;
}

interface sound_detect
{
    public function available();
    public function mean_volume_get($asset);
}