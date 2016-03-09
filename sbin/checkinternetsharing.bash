#!/bin/bash -x

#include localdefs
source $(dirname $0)/localdefs
attempts=3
while [ "$attempts" -gt "0" ]
do
 networksetup -setairportpower en1 off
 sleep 5
 networksetup -setairportpower en1 on
 sleep $WAIT_DELAY
 ping -c 1 -t $PING_TIMEOUT $IP
 if [ "${PIPESTATUS[0]}" != "0" ]
   then
    echo "Internet sharing not working"
    attempts=$(($attempts - 1))         # decrement timeout counter.
    if [ $attempts -eq 0 ]; then
     echo "$MESSAGE" | /usr/bin/mail -s "$CLASSROOM: Internet sharing not working" $MAIL_TO 
     exit 1
    fi
   sleep 60
 else
   exit 0
fi

done
