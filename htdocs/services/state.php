<?php

class RecorderState {
    
    public $recording; //actually recording, 0 or 1
    public $status_general;
    public $status_cam;
    public $status_slides;
    public $author;
    public $asset;
    public $course;
    
    function init() {
        $this->recording = "1";
        $this->status_general = "recording";
        $this->status_cam = "paused";
        $this->status_slides = "error";
        $this->author = "jcvd";
        $this->asset = "A7";
        $this->course = "Be aware";
    }
    
    function encode() {
        $json = array(
            "recording"       => $this->recording,
            "status_general"  => $this->status_general,
            "status_cam"      => $this->status_cam,
            "status_slides"   => $this->status_slides,
            "author"          => $this->author,
            "asset"           => $this->asset,
            "course"          => $this->course,
        );
        return json_encode($json);
    }
}

$state = new RecorderState();
$state->init();

echo $state->encode();