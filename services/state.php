<?php

require_once("./lib_model.php");

class RecorderState {
    
    public $recording        = '0'; //actually recording, 0 or 1
    public $status_general   = '';
    public $status_cam       = '';
    public $status_slides    = '';
    public $author           = '';
    public $author_full_name = '';
    public $asset            = '';
    public $course           = '';
    public $streaming        = '';
    public $record_type      = '';
    
    function init() {
        global $session_module;
        
        $status = status_get();
        $this->recording = $status == "recording" ? '1' : '0';
        $this->status_general = $status;
        $this->status_cam = status_get_cam();
        if($this->status_cam == null) //send empty instead of null in response
            $this->status_cam = '';
        $this->status_slides = status_get_slide();
        if($this->status_slides == null) //send empty instead of null in response
            $this->status_slides = '';
        
        $fct_metadata_get = "session_" . $session_module . "_metadata_get";
        $metadata = $fct_metadata_get();
        if(!$metadata)
            return;
        
        $this->author = $metadata['netid'];
        $this->author_full_name = $metadata['author'];
        $this->asset = $metadata['record_date'].'_'.$metadata['course_name'];
        $this->course = $metadata['course_name'];
        $this->streaming = $metadata['streaming'];
        $this->record_type = $metadata['record_type'];
        
    }
    
    function encode() {
        $json = array(
            "recording"        => $this->recording,
            "status_general"   => $this->status_general,
            "status_cam"       => $this->status_cam,
            "status_slides"    => $this->status_slides,
            "author"           => $this->author,
            "author_full_name" => $this->author_full_name,
            "asset"            => $this->asset,
            "course"           => $this->course,
            "streaming"        => $this->streaming,
            "record_type"      => $this->record_type,
        );
        return json_encode($json);
    }
}

$state = new RecorderState();
$state->init();

echo $state->encode();