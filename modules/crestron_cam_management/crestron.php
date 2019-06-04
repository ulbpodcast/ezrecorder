<?php

    class Crestron_management{
        private $url;
        private $socket;
        function __construct($cam_ip,$cam_port){
            $this->url = "tcp://$cam_ip:$cam_port";
            $this->socket = @stream_socket_client($this->url, $errorno, $errorstr,5);
        }
        function move($preset){
            if($this->socket){
                fwrite($this->socket, $preset);
            }
            else{
                return false;
            }
            fclose($this->socket);
        }
    }

?>