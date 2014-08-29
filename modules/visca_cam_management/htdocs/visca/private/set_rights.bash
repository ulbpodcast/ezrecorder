#!/bin/bash
cd /Library/WebServer/Documents/visca
#set permission for directory remote
chown _www:_www remote
cd remote
#remove old tempfiles
rm direction zoomout speed.inc focusfar focusnear reset preset direction movingup movingdown movingleft movingright zoomin zoomout  
cd ..
chown -R _www:_www remote/ptzposdir
