#!/bin/bash

# EZCAST EZrecorder
#
# Copyright (C) 2014 Université libre de Bruxelles
#
# Written by Michel Jansens <mjansens@ulb.ac.be>
# 	     Arnaud Wijns <awijns@ulb.ac.be>
#            Antoine Dewilde
# UI Design by Julien Di Pietrantonio
#
# This software is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 3 of the License, or (at your option) any later version.
#
# This software is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this software; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


#try to find fullpath of command if in path or returns default value
cmd_path_find()
{
  COMMAND=$1
  DEFAULT=$2
  cmdpath=`which $1`
  if [ "$?" -eq "0" ]; then 
    RES=$cmdpath
    return 0
   else
    RES=$DEFAULT  
    return 0
  fi
}

G='\033[32m\033[1m'
R='\033[31m\033[1m'
N='\033[0m'

nb_errors=0;
clear;
echo "Welcome in this installation script !";
echo " ";
echo "This script is aimed to install the following components of EZcast:";
echo " ";
echo "*******************************************************************";
echo "*                                                                 *";
echo "*      - EZrecorder : for automated recording in classrooms       *";
echo "*                                                                 *";
echo "*******************************************************************";
echo " ";
echo "Press [Enter] to continue";
read whatever;
echo "First of all, this script will verify you have all programs,";
echo "commands and libraries required by EZrecorder to run properly";
echo " ";
echo "You can skip the tests if you want.";
echo -e "${R}Warning ! Skipping this verification may have critical repercussions${N}";
echo -e "${R}          on the use of EZrecorder after its installation.${N}";
echo " ";
read -p "Would you like to verify your server ? [y/n]: " choice;
if [ "$choice" != "n" ];
then
    check=1;
    echo "The script will now proceed to some verifications";
    echo " ";
    echo "*************************************************";
    echo "Verification for PHP5 ...";
    echo "*************************************************";
    cmd_path_find php /usr/bin/php
    default_path=$RES
    echo "Enter the path to 'php' bin (with trailing 'php')";
    read -p "[default:$default_path]:" php_path ;
    if [ "$php_path" == "" ];
    then
    	php_path=$default_path;
    fi;
    value=$( $php_path -v);
    # Verify that a version of PHP is installed
    if [[ "$value" != PHP* ]]; then
        check=0;
        echo -e "${R}PHP does not seem to be installed at $php_path${N}" ;
    fi;
    # Retry as long as PHP has not been found
    while [ $check -lt 1 ]; do
        echo "If PHP5 is installed, please enter its path now (with tailing 'php')";
        echo "otherwise, please enter 'exit' to quit this script and install PHP5";
        read php_path;
        if [ "$php_path" == "exit" ]; then exit; fi;
        if [ "$php_path" == "" ];
        then
    	    php_path=$default_path;
        fi;
        value=$( $php_path -v );
        if [[ "$value" == PHP* ]]; then
            check=1;
        fi;
	php_path=$default_path;
    done;
    echo "-------------------------------------------------";
    echo -e "${G}PHP is installed${N}, verification of your version ...";
    echo "-------------------------------------------------";
    # Substring on the result of 'php -v'
    # 'php -v' always starts by 'PHP 5.x.x'
    version=${value:4:3};
    if [ $(expr $version '<' 5.3) -eq 1 ]; then
        echo -e "${R}You are using a deprecated version${N} of PHP [$version]. Please update your version";
        echo "of PHP (at least 5.3) to ensure a good compatibility with EZcast";
        echo "Press [Enter] to quit this script or enter 'continue' to go to the";
        echo "next step anyway.";
        read choice;
        if [ "$choice" != "continue" ]; then exit; fi;
    fi;
    echo -e "${G}Your current version of PHP [$version] matches EZrecorder's needs${N}";
    echo " ";
    echo "*************************************************";
    echo "Verification of extensions for PHP ...";
    echo "*************************************************";
    read -p "Do you want to check PHP extensions ? [y/n]: " choice;
    if [ "$choice" != "n" ]; then
        # Verification for CURL
        check=0;
        while [ $check -lt 1 ]; do
            check=$($php_path -r "echo (function_exists('curl_version'))? 'enabled' : 'disabled';");
            if [[ "$check" == "disabled" ]]; then
                echo -e "${R}CURL seems not to be enabled for PHP.${N}";
                echo "Enable CURL for PHP and press [Enter] to retry.";
                read -p "Enter 'force' to continue without CURL enabled or 'quit' to leave: " choice;
                if [ "$choice" == "quit" ]; then exit; fi;
                check=0;
                if [ "$choice" == "force" ]; then check=1; fi;
            else 
                echo -e "${G}CURL is enabled for PHP${N}";
                check=1;
            fi;
        done;
        # Verification for SIMPLE_XML
        check=0;
        while [ $check -lt 1 ]; do
            check=$($php_path -r "echo (function_exists('simplexml_load_file'))? 'enabled' : 'disabled';");
            if [[ "$check" == "disabled" ]]; then
                echo -e "${R}SimpleXML seems not to be enabled for PHP.${N}";
                echo "Enable SimpleXML for PHP and press [Enter] to retry.";
                read -p "Enter 'force' to continue without SimpleXML enabled or 'quit' to leave: " choice;
                if [ "$choice" == "quit" ]; then exit; fi;
                check=0;
                if [ "$choice" == "force" ]; then check=1; fi;
            else 
                echo -e "${G}SimpleXML is enabled for PHP${N}";
                check=1;
            fi;
        done;
    fi;
    echo " ";
    echo "*************************************************";
    echo "Verification for AT ...";
    echo "*************************************************";
    check=1;
    timer=45;
    echo "echo test > at.tmp " | at now;
    sleep 1;
    while [ $timer -gt 0 ]; do
    	# Verify that a version of AT is installed
    	if [ ! -f ./at.tmp ]; then
	    let timer--;
	    echo -n ".";
	    sleep 1;
	    if [ $timer == 0 ]; then
		check=0;
            	echo -e "${R}AT does not seem to be installed or its path is not set in PATH var${N}";
    	    fi;
	else
	    timer=0;
            rm -rf ./at.tmp;
	    break;
    	fi;
    done;
    # Retry as long as AT has not been found
    while [ $check -lt 1 ]; do
        echo "If AT is installed, please enter its path now (with tailing 'at').";
        echo "Otherwise, please enter 'exit' to quit this script and install AT";
        read at_path;
        if [ "$at_path" == "exit" ]; then exit; fi;
        echo "echo test > at.tmp | $at_path now";
    	sleep 1;
	timer=45;
   	while [ $timer -gt 0 ]; do
    	    # Verify that a version of AT is installed
    	    if [ ! -f ./at.tmp ]; then
	        timer=$timer - 1;
	        echo -n ".";
	        sleep 1;
	        if [ $timer == 0 ]; then
		    check=0;
            	    echo -e "${R}AT does not seem to be installed at $at_path${N}";
    	        fi;
	    else
	        timer=0;
                rm -rf ./at.tmp;
	        break;
    	    fi;
        done;
    done;
    echo " ";
    echo -e "${G}AT is installed on your machine${N}";
    echo "";
    echo "Press [Enter] to continue";
    read whatever;
else 
    cmd_path_find php /usr/bin/php
    default_php_path=$RES
    echo "Please, enter the path to PHP5 (with trailing 'php'):";
    read -p "[default: $default_php_path]" php_path;
    if [ "$php_path" == "" ]; then 
        php_path=$default_php_path;
    fi;
fi;


value=$( networksetup -version);
# Verify that networksetup is installed
if [[ "$value" == networksetup* ]]; then
    echo " ";
    echo "*******************************************************************";
    echo "*          N E T W O R K   C O N F I G U R A T I O N              *";
    echo "*******************************************************************";
    echo "";
    read -p "Would you like to configure your network (IP / DNS / domain) now ? [Y/n]: " choice;
    if [ "$choice" != "n" ]; 
    then 
        echo "Enter now the requested values:";
        read -p "Computer's name: " COMPUTER_NAME;
        read -p "Static IP address (used by EZcast to communicate with): " COMPUTER_IP;
        read -p "Subnet mask: " COMPUTER_SUBNET;
        read -p "Router: " COMPUTER_ROUTER;
        read -p "Primary DNS Server: " COMPUTER_DNS;
        read -p "Search Domains: " COMPUTER_DOMAINS;
        #change computer's name
        networksetup -setcomputername "$COMPUTER_NAME";
        #change network settings
        networksetup -setmanual "Ethernet" $COMPUTER_IP $COMPUTER_SUBNET $COMPUTER_ROUTER
        #change DNS
        networksetup -setdnsservers "Ethernet" $COMPUTER_DNS
        #change search domains
        networksetup -setsearchdomains "Ethernet" "$COMPUTER_DOMAINS"
        echo "Your network has been set up";
    fi;
fi;

echo " ";
echo "*******************************************************************";
echo "*                         C  O  N  F  I  G                        *";
echo "*******************************************************************";
echo "";

echo -n "Creating the config file...";
$php_path cli_install.php "$php_path";

$php_path cli_modules_selection.php;

echo " ";
echo "*******************************************************************";
echo "*          T E M P L A T E S   G E N E R A T I O N                *";
echo "*******************************************************************";
echo "";
#regenerate template files
$php_path cli_template_generate.php tmpl_sources/ fr tmpl
$php_path cli_template_generate.php tmpl_sources/ en tmpl

$(dirname $0)/setperms.sh

echo -e "${G} Done${N}"
echo " ";
echo -e "${G}-- The end.${N}";

