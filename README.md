# [EZrecorder](http://ezcast.ulb.ac.be)

## Overview

**EZrecorder** is part of the EZcast product. It is a web interface that allows a user to record videos and publish them to a specific audience using **EZmanager**, another web interface for the management of the videos.

## How does it work ? 

EZrecorder is built in multiple distinct parts:
* The main core :
It contains the web interface and the controller. It must be installed and configured according to your own configuration (see below).
* The modules:
Each module must be installed to work and can be enabled or disabled. Different modules might do the same thing.
It is up to you to create new modules to match your own needs.
There are 4 types of modules:
    1. recording : for the recording of the video.
    2. authentication: for user's authentication
    3. cam_management: for camera management (i.e preset positions of a PTZ camera)
    4. session: for saving information about the current session

**NOTE** : For module creation, you need to do the following things:
* choose a prefix for your module
* create the file 'info.php' containing information about your module (see ./docs/info.php)
* create the file 'cli_install.php' containing installer for your module (see ./docs/install.php)
* implement the functions of your module according to the library provided in ./docs/
* change 'modulename' by the prefix of your module in the library

## Compatibility

EZrecorder requires a UNIX/LINUX architecture to run. It will NOT work on Windows.
Some modules of the recorder are only available for Mac OS X.

## Prerequisites

Before installing EZrecorder, make sure the following commands / programs / libraries are correctly installed on your recorder:

* EZcast (see our EZcast package on Git)
* Apache 
* PHP5 
* php5_simplexml library
* php5_curl library
* SSH
* AT 

## Installation

Here is a quick installation guide. Refer to our website [EZcast](http://ezcast.ulb.ac.be) for detailed information.

1. Download the latest available version of EZrecorder from our Git repository
2. Put the 'ezrecorder' directory wherever you want on your server (we recommend to place it under "/Library/ezrecorder")
3. Launch the "install.sh" script as root from the 'ezrecorder' directory and follow the instructions on the screen
4. Configure the SSH link between EZmanager (EZcast server) and EZrecorder
5. Edit the "commons/config.inc" file on the remote EZcast server to match your own configuration
6. Configure the recorder depending on your needs (see detailed installation for further information)
7. Add the recorder to EZadmin

