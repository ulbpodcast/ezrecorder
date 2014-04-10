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
 * This library contains usefull tools 
 */

/**
 * 
 * @param path $file_path the path to the xml file
 * @return assoc_array|false
 * @desc opens a metadatafile (xml 1 level) and returns all properties and values in an associative array
 */
function xml_file2assoc_array($file_path){
   $xml= simplexml_load_file($file_path);
   if($xml===false)return false;
   $assoc_array=array();
    foreach ($xml as $key => $value) {
        $assoc_array[$key]=(string)$value;
    }
 return $assoc_array;
}

/**
 *
 * @param <type> $assoc_array the array to convert as xml string
 * @return <xml_string>
 * @desc takes an associative array and transforms it in a xml metadata string
 */
function assoc_array2xml_file($assoc_array){
 $xmlstr="<?xml version='1.0' standalone='yes'?>\n<metadata>\n</metadata>\n";
 $xml = new SimpleXMLElement($xmlstr);
 foreach ($assoc_array as $key => $value) {
  $xml->addChild($key,$value);
 }
 $xml_txt=$xml->asXML();
 return $xml_txt;
}

?>
