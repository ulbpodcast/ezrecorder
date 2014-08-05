#!/bin/sh

#include localdefs
source $(dirname $0)/localdefs

# checks whether internet sharing is enabled or not
var=$(ps ax | egrep '[ /](PID|boo|nat)' | wc -l);
if [ $var -lt 3 ]; then
	# internet sharing is not enabled. Applescript is used
	# to simulate manual click on "Internet Sharing" in "System preferences"
	/usr/bin/osascript $(dirname $0)/websharingfix.scpt;
	# logs for ezcast_recorder
	echo "Web Sharing restarted on $(date)" >> $EZCASTDIR/var/_websharing_log;
fi;


