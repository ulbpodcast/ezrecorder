#!/bin/bash -x

#include localdefs
source $(dirname $0)/localdefs

networksetup -setairportpower en1 off
sleep 5
networksetup -setairportpower en1 on
sleep $WAIT_DELAY
ping -c 1 -t $PING_TIMEOUT $IP
if [ "${PIPESTATUS[0]}" != "0" ]
  then
   echo "Internet sharing not working"
   echo "$MESSAGE" | /usr/bin/mail -s "$CLASSROOM: Internet sharing not working" $MAIL_TO 
fi
